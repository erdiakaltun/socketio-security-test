# Socket.IO Security Test Script (PHP)

Bu script, Socket.IO tabanlı sunucuları çeşitli komut ve payloadlar ile test ederek güvenlik açıklarını tespit etmeye çalışır.

## Kullanım

- PHP 7+ ile çalışır.
- Composer ile `textalk/websocket` kütüphanesi kurulmalıdır:
  



- `test_socketio.php` dosyasındaki `$baseUrl` değişkenini kendi sunucu URL’n ile güncelle.
- Komut satırından veya web sunucusundan çalıştırılabilir.
- Loglar `socket_attack.log` dosyasına yazılır.

## Uyarılar

- SSL doğrulaması devre dışı bırakılmıştır, sadece local veya güvenli test ortamları için uygundur.

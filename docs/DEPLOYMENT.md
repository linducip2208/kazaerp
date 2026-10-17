# Deployment (Laravel + MySQL)

## Dev lokal (Laragon, Windows)
- Serve via Laragon Apache (`http://kazaerp.test`, DocumentRoot `public/`, mod_php) atau `php artisan serve --host=127.0.0.1 --port=8099`.
- **Penting**: Apache hanya membaca vhost saat start. Laragon membuat `etc/apache2/sites-enabled/auto.<nama>.test.conf` otomatis saat project ditambahkan — setelah itu **Apache wajib di-restart** (Laragon → Stop → Start). Tanpa restart, domain baru jatuh ke *default vhost* (`alias/wanode.conf` → proxy Node `127.0.0.1:8891`) dan menjawab **503 Service Unavailable** bila service 8891 mati (insiden 17 Okt 2026: Apache jalan sejak 8 Okt, vhost kazaerp dibuat 15 Okt).
- Cek cepat: `curl.exe -s -o NUL -w '%{http_code}' http://kazaerp.test/` harus `200`; bila `503`, lihat ekor `bin/apache/httpd-*/logs/error_log` (cari `AH00957 ... 127.0.0.1:8891 failed` = jatuh ke proxy default).

# Produksi

- Document root: `public/`. HTTPS wajib. `APP_ENV=production APP_DEBUG=false`.
- `.env` produksi: APP_KEY baru, DB kredensial kuat, MAIL, `KAZA_COGS_METHOD=none`.
- `composer install --no-dev --optimize-autoloader; php artisan config:cache; route:cache; view:cache; npm run build`.
- Migrasi: `php artisan migrate --force` (jangan `fresh` di produksi!). Seeder demo JANGAN dijalankan (`DemoSeeder` menolak di production).
- Akun awal: `php artisan kaza:admin-create` (interaktif di produksi; idempoten per email) — atau `db:seed --class=AdminSeeder` via env `KAZA_ADMIN_*`; lalu paksa ganti password setelah login pertama.
- Permission file: `storage bootstrap/cache` writable; cron scheduler tiap menit; queue worker bila dipakai (`queue:work --daemon` + supervisor).
- Backup: dump MySQL harian + `storage/app/private`; uji restore berkala (lihat OPERATIONS_RUNBOOK).
- Rollback: `php artisan migrate:rollback --step=N` + restore backup; catat di CHANGELOG.
- Aplikasi POS mobile (Flutter, `mobile/pos_kaza`): `flutter build apk --release --dart-define=API_BASE=https://erp.example.com/api/v1` (sekali per mesin build: `flutter doctor --android-licenses` — sudah diterima & APK terverifikasi terbangun 16 Okt 2026, 47,7 MB). Alamat server juga bisa diisi di layar login. Wajib HTTPS (token bearer).

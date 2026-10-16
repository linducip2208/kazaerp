# Deployment (Laravel + MySQL)

- Document root: `public/`. HTTPS wajib. `APP_ENV=production APP_DEBUG=false`.
- `.env` produksi: APP_KEY baru, DB kredensial kuat, MAIL, `KAZA_COGS_METHOD=none`.
- `composer install --no-dev --optimize-autoloader; php artisan config:cache; route:cache; view:cache; npm run build`.
- Migrasi: `php artisan migrate --force` (jangan `fresh` di produksi!). Seeder demo JANGAN dijalankan (`DemoSeeder` menolak di production).
- Akun awal: `php artisan kaza:admin-create` (interaktif di produksi; idempoten per email) — atau `db:seed --class=AdminSeeder` via env `KAZA_ADMIN_*`; lalu paksa ganti password setelah login pertama.
- Permission file: `storage bootstrap/cache` writable; cron scheduler tiap menit; queue worker bila dipakai (`queue:work --daemon` + supervisor).
- Backup: dump MySQL harian + `storage/app/private`; uji restore berkala (lihat OPERATIONS_RUNBOOK).
- Rollback: `php artisan migrate:rollback --step=N` + restore backup; catat di CHANGELOG.
- Aplikasi POS mobile (Flutter, `mobile/pos_kaza`): `flutter build apk --release --dart-define=API_BASE=https://erp.example.com/api/v1` (sekali per mesin build: `flutter doctor --android-licenses`). Alamat server juga bisa diisi di layar login. Wajib HTTPS (token bearer).

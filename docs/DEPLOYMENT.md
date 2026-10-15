# Deployment (Laravel + MySQL)

- Document root: `public/`. HTTPS wajib. `APP_ENV=production APP_DEBUG=false`.
- `.env` produksi: APP_KEY baru, DB kredensial kuat, MAIL, `KAZA_COGS_METHOD=none`.
- `composer install --no-dev --optimize-autoloader; php artisan config:cache; route:cache; view:cache; npm run build`.
- Migrasi: `php artisan migrate --force` (jangan `fresh` di produksi!). Seeder demo JANGAN dijalankan (`DemoSeeder` menolak di production).
- Akun awal: buat via `db:seed --class=AdminSeeder` (pending: sediakan seeder admin interaktif) lalu hapus kredensial default; paksa ganti password.
- Permission file: `storage bootstrap/cache` writable; cron scheduler tiap menit; queue worker bila dipakai (`queue:work --daemon` + supervisor).
- Backup: dump MySQL harian + `storage/app/private`; uji restore berkala (lihat OPERATIONS_RUNBOOK).
- Rollback: `php artisan migrate:rollback --step=N` + restore backup; catat di CHANGELOG.

# Installation (local)

1. PHP 8.2+ + ext pdo_mysql mbstring openssl tokenizer xml ctype json bcmath fileinfo; Composer; Node 18+; MySQL 8.
2. `composer install`
3. `cp .env.example .env`, isi `DB_DATABASE=kazaerp`, `APP_URL`, `APP_TIMEZONE=Asia/Jakarta`.
4. `php artisan key:generate`
5. `mysql -u root -e "CREATE DATABASE kazaerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE kazaerp_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
6. `php artisan migrate --force && php artisan db:seed --force`
7. `npm install && npm run build`
8. `php artisan serve --port=8000` → http://localhost:8000 (dev: admin@kazaka.local / Kazaka#2026)
9. Test: `php artisan test` (memakai `kazaerp_testing`).

Troubleshooting: DB access denied → cek DB_USERNAME/PASSWORD; `vite manifest missing` → `npm run build`; 500 → `storage/logs/laravel.log`; permission storage → `chmod -R 775 storage bootstrap/cache` (Linux).

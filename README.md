# KazaERP — Kazaka Group / MZA Holdings Integrated ERP

ERP terpadu multi-entitas: holding, badan usaha, brand, cabang, gudang. Laravel 11 + MySQL + Blade + Tabler lokal (tanpa CDN).

## Stack
- PHP 8.3, Laravel 11.57, MySQL 8.4, Blade, Eloquent
- Tabler 1.6 + Bootstrap 5.3 via npm (dibundel Vite ke `public/build`, tanpa CDN)
- PDF: barryvdh/laravel-dompdf. Export/import: CSV native. PHPUnit 11 (MySQL testing).

## Persyaratan
PHP >= 8.2 (ext: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo), Composer, Node 18+, MySQL 8.

## Instalasi dari awal
```bash
composer install
cp .env.example .env   # lalu isi DB_* (lihat docs/INSTALLATION.md)
php artisan key:generate
mysql -u root -e "CREATE DATABASE kazaerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE kazaerp_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --force
php artisan db:seed --force
npm install && npm run build
php artisan serve
```
Login dev: `admin@kazaka.local` / `Kazaka#2026` (seeder demo; nonaktifkan di production — lihat docs/DEPLOYMENT.md).

## Menjalankan test (MySQL, bukan SQLite)
```bash
php artisan test
```

## Fitur per fase
Fase A Foundation (auth, RBAC 16 role, multi-entitas + konteks, audit, master data) — DONE.
Fase B Core ERP (CRM+konversi, sales order→invoice→bayar, POS+shift, inventory ledger+reservasi, purchasing+three-way matching, approval) — DONE.
Fase C Finance (COA, jurnal balance+idempotent+reversal, AR/AP aging, kas, expense, closing) — DONE.
Fase D HR (karyawan, absensi, payroll komponen configurable + approval + slip PDF) — DONE.
Fase E Bisnis (pet/vet/grooming/boarding/transport, apparel desain versi, BOM+WO terkunci, thrift unik+live, studio, rental anti-bentrok) — DONE (inti fungsional).
Fase F Manajemen (dashboard holding per-entitas, laporan penjualan/inventory/pembelian, register intercompany) — DONE (inti).
Fase G Hardening (throttle login, CSRF, permission middleware, policy server-side, PWA aman, docs) — DONE (inti).

Detail status jujur: `docs/IMPLEMENTATION_STATUS.md`. Keputusan bisnis terbuka: `docs/DECISIONS_AND_OPEN_QUESTIONS.md`.

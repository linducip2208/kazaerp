# Implementation Status (per 16 Okt 2026 — v0.4.2)

## Selesai (DONE, bukti run nyata)
- Foundation: Laravel 11.57 + PHP 8.3.30 + MySQL 8.4.9; 179 tabel; migrate:fresh hijau; seed hijau.
- 70+ controller, 165+ Blade, ±200 routes, Tabler + Chart.js lokal.
- API v1 Sanctum: login/me/logout, invoices, stock, products (+harga tier per pelanggan), customers, POS (shift open/close/checkout), GPS trips + entity guard + revoke.
- Bayar utang + jurnal; retur beli → stok; jurnal gaji otomatis; delegasi approval; notifikasi + bell.
- Dashboard per-peran (holding/finance/ops/HR) + grafik tren 6 bulan.
- Hardening: security headers, cabut sesi nonaktif, throttle, `kaza:backup` + scheduler, AdminSeeder env; 2FA; file privat terotorisasi.
- v0.4.0 baru: harga tier otomatis (web+API+POS), QC/karantina batch + guard stok keluar, MFG issue→produce + variance biaya, komisi penjualan (akrual + approve), GPS transport (push posisi + tracking web), queue worker nyata (`kaza:overdue-alerts`).
- Smoke HTTP: 37 URL → semua 200. E2E: GRN→SO→invoice→jurnal→paid (DB diverifikasi).
- Test: suite penuh MySQL **39 passed / 319 assertions / 0 failed** (lihat TEST_RESULTS.md); rincian Phase4 6/85 (`phase4_final.log`), Phase5 4/29 (`v041_full.log`), Phase6 3/86 (`phase6_full.log`).
- Hardening v0.4.1: read-only auditor/viewer ditegakkan server-side (middleware `ReadOnlyRoles`), reset password mandiri diuji penuh, backup+restore terverifikasi (dump → DB scratch → identik), job queue diverifikasi diproses `queue:work` — bukti `tests/Feature/Phase5Test.php` + TEST_RESULTS.md.
- v0.4.2 baru: checkout POS idempoten via `client_ref` (retry sync offline aman — fondasi POS mobile), command `kaza:audit-integrity` (saldo stok vs ledger, ledger yatim, balance jurnal, paid_total invoice vs pembayaran; exit 1 saat ada temuan), rate limit API 60 req/menit per user; migrasi dev DB disinkronkan (179 tabel / 21 migrasi, backup+restore ulang terverifikasi).

## Parsial (berfungsi inti, perlu pendalaman)
Intercompany (register + worksheet eliminasi manual — otomatis menunggu kebijakan), HPP sederhana (`none` — FIFO/average menunggu keputusan), integrasi vendor eksternal (marketplace/WA/payment gateway — kredensial belum ada), GPS pihak ketiga (arsitektur push siap).

## Tertunda (PENDING)
Eliminasi intercompany otomatis, payment gateway/marketplace/WA adapter nyata, aplikasi POS mobile offline penuh (fondasi server idempoten `client_ref` sudah ada), kebijakan pajak/payroll resmi, provider GPS produksi.

## Blocker
Tidak ada blocker teknis. Blocker bisnis: lihat DECISIONS_AND_OPEN_QUESTIONS.md.

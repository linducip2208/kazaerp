# Implementation Status (per 15 Okt 2026 — v0.2.0)

## Selesai (DONE, bukti run nyata)
- Foundation: Laravel 11.57 + PHP 8.3.30 + MySQL 8.4.9; 178 tabel; migrate:fresh hijau; seed hijau.
- 65+ controller, 160+ Blade, ±170 routes, Tabler + Chart.js lokal (build CSS 835KB / JS 477KB).
- API v1 Sanctum (login/me/logout, invoices, stock, products + entity guard + revoke).
- Bayar utang + jurnal; retur beli → stok; jurnal gaji otomatis; delegasi approval; notifikasi + bell.
- Dashboard per-peran (holding/finance/ops/HR) + grafik tren 6 bulan.
- Hardening: security headers, cabut sesi nonaktif, throttle, `kaza:backup` + scheduler, AdminSeeder env.
- Smoke HTTP: 37 URL → semua 200. E2E: GRN→SO→invoice→jurnal→paid (DB diverifikasi).
- Test: suite penuh MySQL (lihat TEST_RESULTS.md).

## Parsial (berfungsi inti, perlu pendalaman)
Intercompany (register saja), harga tier/karantina, output/variance MFG, file private studio, komisi, queue worker, 2FA.

## Tertunda (PENDING)
Eliminasi otomatis, payment gateway/marketplace/WA/GPS, transaksi offline, HPP FIFO/average, kebijakan pajak/payroll resmi.

## Blocker
Tidak ada blocker teknis. Blocker bisnis: lihat DECISIONS_AND_OPEN_QUESTIONS.md.

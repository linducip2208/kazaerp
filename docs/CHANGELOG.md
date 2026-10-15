# Changelog

## v0.3.0 — 2026-10-15
- 2FA TOTP (setup QR SVG lokal, challenge login, disable berpassword).
- File privat (`storage/app/private`) + download terotorisasi per tipe pemilik + audit download.
- Rekam medis veteriner hanya `pet.treat`; worksheet eliminasi intercompany (manual).
- Alokasi biaya lot thrift (rata / proporsional harga).
- Voucher di invoice (validasi periode/kuota/minimum/entitas) + loyalty accrual saat lunas.
- Laporan stok menua, budget vs aktual, rekonsiliasi bank (matched/draft + konfirmasi).
- Test Phase3Test (6 test) — hijau.

## v0.2.0 — 2026-10-15
- API v1 Sanctum (login/me/logout, invoices, stock, products + entity guard + revoke).
- Pembayaran utang vendor (partial/paid + jurnal 2000→1000 + guard overpay).
- Retur pembelian (mengurangi stok via ledger + guard oversell).
- Posting jurnal gaji otomatis saat payroll disetujui (6100→2300 + `payroll_journals`).
- Delegasi approval berbatas tanggal (delegate tanpa permission bisa memutuskan bila delegator berhak).
- Notifikasi DB (request/decide approval) + bell dropdown + halaman.
- Dashboard per-peran (holding/finance/ops/HR) + grafik tren 6 bulan (Chart.js lokal).
- Middleware SecurityHeaders + CheckActiveUser (sesi dicabut saat akun dinonaktifkan).
- `kaza:backup` + scheduler (backup harian, pengingat closing bulanan).
- AdminSeeder berbasis env (tanpa kredensial default); auditor read-only.
- branch_id di vendor_invoices + purchase_returns.
- Test Phase2Test (4 test) — hijau; total suite 20/20.

## v0.1.0 — 2026-10-15
- Bootstrap Laravel 11 + MySQL kazaerp/kazaerp_testing + Tabler lokal + dompdf.
- 10 file migrasi (173 tabel), 100+ model, 5 service inti, 57 controller, 150 view, ~150 routes.
- Seeder role/permission/org/demo; auth + konteks + audit.
- Alur SO→invoice→bayar, POS+shift, GRN partial, PO+3way match, jurnal balance/idempotent/reversal, payroll, pet, mfg, thrift, studio, rental, marketing, approval+SoD, laporan+CSV+import, PDF.
- 8 file test (17 test); smoke 22/22; docs 26 file + AGENTS.md.

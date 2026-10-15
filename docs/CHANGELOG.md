# Changelog

## v0.4.1 — 2026-10-15
- Read-only enforcement server-side: middleware `ReadOnlyRoles` menolak operasi tulis akun yang hanya berperan auditor/viewer (defense-in-depth; self-service logout/profil/2FA/konteks/notifikasi dikecualikan).
- Reset password mandiri diuji penuh (forgot → notifikasi token → reset → hash baru; token salah ditolak).
- Queue worker diverifikasi end-to-end: job di-drain `queue:work --once` (antrean database → notifikasi terbuat, antrean bersih).
- Backup & restore terverifikasi: dump dev DB → restore ke DB scratch → 178 tabel & baris kunci identik.
- Test Phase5Test (4 test / 29 assertions) — hijau; suite penuh **36 passed / 233 assertions / 0 failed** (TEST_RESULTS.md).

## v0.4.0 — 2026-10-15
- Harga tier otomatis per pelanggan (retail/wholesale/reseller) di SO/invoice/quotation/POS + API (`PriceService`).
- QC/karantina batch: lot GRN → batch otomatis, halaman Batch & QC; stok keluar diblokir untuk batch pending/quarantine/rejected/kedaluwarsa.
- MFG: WO snapshot biaya standar → issue (biaya aktual) → produce (cap qty) → variance biaya + FG receipt.
- Komisi penjualan: akrual saat invoice lunas (rate `company_configs`), halaman Finance + approve/cancel (idempoten per invoice).
- API v1 baru: products (+harga tier), customers, POS (shift open/close/checkout), GPS positions — semua dengan guard entitas.
- Tracking GPS transport (web) + tabel `transport_positions`.
- Queue worker nyata: job `SendOverdueInvoiceAlert` + command `kaza:overdue-alerts` (scheduler 08:00, idempoten 24 jam).
- Test Phase4Test (6 test / 85 assertions) — hijau; suite penuh **32 passed / 204 assertions / 0 failed** (lihat TEST_RESULTS.md).

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

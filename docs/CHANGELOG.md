# Changelog

## v0.5.3 — 2026-10-17
- Layar auth dirombak ke **split-screen landscape** ala house-style (panel brand gradient + poin fitur + form): layout baru `layouts/auth.blade.php` dipakai login/lupa/reset/2FA challenge; tombol gradient, tanpa dependensi/CDN baru (Tabler + CSS sendiri via `npm run build` → `public/build`, CSS 837 KB).
- Fix: halaman error 403/404/500 kini memakai layout mandiri — sebelumnya extends `layouts.tabler` yang butuh login, sehingga tamu yang kena 404 malah mendapat 500.
- Verifikasi: `test_password_reset_mandiri_penuh` hijau (10 assertions); curl `/login` + `/forgot-password` 200 ber-layout baru; `/password/reset` tanpa token kini 404 yang benar.

## v0.5.2 — 2026-10-16
- **Fix bug rilis**: permission `INTERNET` ditambahkan ke `AndroidManifest.xml` utama — APK rilis v0.5.1 tidak dapat memanggil API sama sekali (template Flutter hanya menaruh permission di manifest debug); `android:usesCleartextTraffic="true"` menyalakan HTTP untuk server dev (produksi tetap wajib HTTPS).
- **Verifikasi end-to-end dari APK rilis di emulator Android** (bukan sekadar build): install → login (`POST /api/v1/login`, token Sanctum id=2) → buka shift kas 100.000 (`POST /api/v1/pos/shift/open`) → katalog dimuat (`GET /api/v1/products`) → keranjang → `POST /api/v1/pos/checkout` → penjualan **MZA-HOLD/PST/POS/2026/10/00002** (paid, total 83.250) + ledger stok `pos_sale #2` = −1; `kaza:audit-integrity` OK 0 temuan (rincian & screenshot: TEST_RESULTS.md).
- **Siklus penuh + offline-first dari APK**: tutup shift #2 dari app (selisih 0); server dimatikan → checkout tersimpan ke antrean lokal ("1 menunggu"); server dinyalakan → restart app → auto-sync → penjualan **…/00003** + ledger `pos_sale #3`; shift #3 ditutup (selisih 0); audit OK 0 temuan.
- Sertifikasi ulang pasca-fix: `flutter analyze` 0 issue; `flutter test` **21 passed** (log `storage/logs/flutter_v052_verify.log`).
- APK rilis dengan fix: 47,7 MB, SHA256 `AC4797212E45AABC9050F151C5252170819930ACC211E2E00BACE4D730DDB267` (menggantikan hash v0.5.1).

## v0.5.1 — 2026-10-16
- POS mobile diperkuat: tombol **Bayar & Simpan** anti double-tap (tidak bisa mem-pop dua kali) dan alur keranjang memakai guard `_cartFlowBusy` dengan `try/finally` — tidak mungkin terkunci bila terjadi error tak terduga.
- **APK rilis Android terbangun pertama kali** di mesin ini: `flutter build apk --release` → `build/app/outputs/flutter-apk/app-release.apk` (47,7 MB, SHA256 `2FC0CA8DCC952F1F79924DD391887A5B47A6A2260E4BEAFA1D001FAE5FB483D3`); Android SDK licenses diterima; rantai bukti source final 15:01 → AOT 15:03 → APK 15:06, rebuild bersih 19,6s dengan hash identik.
- `kotlin.incremental=false` di `android/gradle.properties`: workaround cache Kotlin Windows lintas-drive (pub cache `C:` vs project `D:`) yang memicu stack trace non-fatal saat build.
- Verifikasi ulang pasca-patch: `flutter analyze` 0 issue; `flutter test` **21 passed** (log `storage/logs/flutter_v051_verify.log`).

## v0.5.0 — 2026-10-16
- Aplikasi POS mobile offline-first (Flutter 3.41, `mobile/pos_kaza`): login Sanctum, katalog produk + harga tier pelanggan dengan cache lokal (tetap terlihat saat offline), shift kasir buka/tutup + ringkasan selisih, keranjang → antrean lokal SharedPreferences → sinkron ke API `/api/v1` dengan `client_ref` unik (replay 200 `replayed: true` dianggap sukses), halaman Antrean Sinkron (pending/tersinkron/ditolak + kirim ulang), auto-sync saat aplikasi dibuka, alamat server dapat diisi & tersimpan.
- Uji klien: `flutter analyze` 0 issue; `flutter test` **21 passed** (klien API: login/401/checkout replay/parsing/error jaringan; model & keranjang; repository antrean offline; widget login); `flutter build web --release` sukses.
- Smoke live end-to-end memakai kode klien asli ke server dev: login → buka shift (kas 100.000) → checkout `MZA-HOLD/PST/POS/2026/10/00001` total 83.250 → kirim ulang `client_ref` sama → **200 `replayed: true`, nomor & id sama** → tutup shift (sistem 83.250, selisih 0) — log `storage/logs/flutter_live_smoke.log`.
- Dokumentasi: ARCHITECTURE (bagian mobile), FEATURES (F-MOB-01/02), DEPLOYMENT (build APK + `--dart-define=API_BASE`), TEST_PLAN, ACCEPTANCE_CHECKLIST + README `mobile/pos_kaza`.

## v0.4.3 — 2026-10-16
- Command `php artisan kaza:admin-create`: membuat/memperbarui admin produksi — idempoten per email, validasi password min 12 karakter, role `super_admin` + konteks default; menutup catatan seeder produksi di DEPLOYMENT.
- Alert kedaluwarsa batch: `php artisan kaza:expiry-alerts {--days=30} {--sync}` + job `SendBatchExpiryAlert` per batch (hanya batch dengan sisa stok > 0; dedupe 24 jam; link `/inventory/batches`; penerima permission `inventory.view`); scheduler harian 08:30 setelah `kaza:overdue-alerts` 08:00. Ambang default 30 hari dicatat sebagai PENDING_BUSINESS_DECISION.
- Smoke otomatis 70 halaman menu utama (`tests/Feature/SmokePagesTest.php`) — semua HTTP 200 (menggantikan smoke manual).
- Test: Phase7Test 2 passed / 25 assertions; suite penuh **42 passed / 347 assertions / 0 failed** (durasi 1611,24s; log `storage/logs/v043_full4.log`).

## v0.4.2 — 2026-10-16
- Checkout POS idempoten via `client_ref` (unique per entitas): retry mobile/offline mengembalikan penjualan yang sama (200 `replayed: true`) tanpa dobel stok/jurnal — tetap aman walau shift sudah ditutup; balapan dua request ditutup unique index.
- Command baru `php artisan kaza:audit-integrity`: audit saldo stok vs ledger, ledger yatim, balance jurnal (termasuk header vs baris), paid_total invoice vs pembayaran; exit code 1 bila ada temuan (cocok untuk cron/monitoring).
- Rate limit API: seluruh endpoint terautentikasi dibatasi 60 request/menit per pengguna (ke-61 → 429).
- Migrasi `2026_10_16_000000`: `pos_sales.client_ref`. Dev DB disinkronkan (179 tabel / 21 migrasi) + backup/restore diverifikasi ulang (identik).
- Test Phase6Test (3 test / 86 assertions) — hijau; suite penuh **39 passed / 319 assertions / 0 failed** (TEST_RESULTS.md).

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

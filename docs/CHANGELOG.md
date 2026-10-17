# Changelog

## v0.6.2 — 2026-10-17 (dari blueprint resmi: CRM follow-up + tiket komplain)
- Blueprint PDF v1.0 ditranskripsikan ke `docs/BLUEPRINT_V1_REQUIREMENTS.md`; `BLUEPRINT_TRACEABILITY.md` ditulis ulang dengan ID asli CRM/SAL/RET/CAT/…/GOV/UAT (ganti UNVERIFIED) + status VERIFIED/PARTIAL/MISSING/BLOCKED/OUT_OF_SCOPE.
- Baru: **CRM-03 follow-up** (jadwal scoped-entitas, complete+lock+histori, escalate+notifikasi, `kaza:followup-reminders` dedupe 24 jam, scheduler 08:15); **RET-01/02/05 tiket komplain** (tracking `CMP-xxxxxx`, SoD pelapor≠penyelesai, lock, histori, reject beralasan).
- Fix: `abort(404)` dalam transaction+catch menelan 404 jadi 302 — rethrow `HttpException`; migrasi aditif `2026_10_17_100000`.
- Verifikasi: `Phase11Test` baru **4 passed / 44 assertions** (independen dijalankan ulang hijau); regresi SalesFlow+Phase8+SmokePages hijau. MISSING antrean: CAT/INP/PRC/HRM-rekrutmen/KPI/KOL/afiliate/SOP/aset/insiden.

## v0.6.1 — 2026-10-17 (gelombang finalisasi 2: demo 5 modul + scorecard, tanpa migrasi)
- `DemoSeeder` +5 metode idempoten: WO `DEMO-WO-001` completed (variance −10.000, FG +10, histori 3 tahap); rental `DEMO-RNT-001` (deposit 100.000); thrift `DEMO-LOT-001`+2 item; studio `Katalog Demo`+deliverable v1; grooming `DEMO-GRM-ORD-001` + boarding `DEMO-BRD-001`. WO demo SENGAJA tanpa jurnal (variance menunggu HPP); payroll approve tidak didemo-kan (aturan resmi menunggu).
- `tests/Feature/Phase10Test.php` baru **5 passed / 36 assertions**; regresi `DemoPerfTest` **4/23** hijau; `php -l` bersih; controller tak diubah sesi ini.
- Dokumen baru `docs/FEATURE_SCORECARD.md` (20 fitur × 10 kategori: 11 LULUS 100, 6 HAMPIR, 3 SEBAGIAN, 0 bug kritis); DEMO_GUIDE §4+§6, TRACEABILITY §6, MATRIX, IMPLEMENTATION, TEST_RESULTS, FINALIZATION_AUDIT, DECISIONS butir 20 diperbarui.

## v0.6.0 — 2026-10-17 (validasi suite penuh final)
- Suite penuh MySQL `kazaerp_testing`: **63 passed / 473 assertions / 0 failed** (1794,48s; log `storage/logs/full_suite_final.log`) — seluruh ronde v0.2.0–v0.5.9 termasuk Phase9Test, tanpa regresi.

## v0.5.9 — 2026-10-17 (ronde finalisasi: idempotensi bayar + anti stale approval)
- Pembayaran anti double-submit: `idempotency_key` unik (nullable, aditif) di `sales_payments` + `ap_payments` (migrasi `2026_10_17_000001`); token per render form + hidden field; kiriman ulang kunci sama = replay ramah (tanpa baris/jurnal baru, aman dari balapan via MySQL 1062); fallback uuid server bila kunci tak dikirim. Menutup risiko R1.
- Anti stale approval: `ApprovalService::invalidatePending()` (pending→`superseded` + histori) + `ExpenseController@update` transaksional (batalkan lama → minta ulang otomatis; edit pasca-approve → `draft` + pengajuan baru); badge status approval diwarnai. PO/PR/payroll terverifikasi tak punya jalur edit.
- Dokumen baru: `BLUEPRINT_TRACEABILITY.md` (UNVERIFIED-xx — PDF resmi tak ditemukan di repo), `FEATURE_AUDIT_MATRIX.md` (sapuan: 386 rute ber-handler/0 Closure, 0 CDN, 0 TODO, 2 `href="#"` dropdown sah).
- Verifikasi: `Phase9Test` baru **4 passed / 29 assertions**; regresi `SalesFlowTest|Phase2Test` **5/39** hijau; `php -l` bersih; `migrate --force` dev OK. Suite penuh tidak dijalankan (aturan paralel).

## v0.5.8 — 2026-10-17 (validasi suite penuh)
- Suite penuh MySQL `kazaerp_testing`: **59 passed / 444 assertions / 0 failed** (1788,61s; log `storage/logs/v057_full.log`) — mencakup seluruh ronde v0.2.0–v0.5.7 tanpa regresi (termasuk DemoPerfTest, Phase8Test, ReportsEnterpriseTest).

## v0.5.7 — 2026-10-17 (performa dashboard + demo end-to-end)
- Performa dashboard: baseline terukur 127 ms / 84 query (50 query roles/permission = N+1 otorisasi; 10 scan invoices dari loop tren) → memoization request-scoped di `User` + 3 KPI jadi 1 SELECT + tren jadi 1 GROUP BY → **28 query**, ~86 ms; tanpa cache angka keuangan, otorisasi/audit utuh (bukti: `PERFORMANCE_AUDIT.md`).
- Demo end-to-end: `DemoSeeder` ditulis ulang (SO→invoice→lunas, PO→GRN→VIN→bayar parsial, approval, stok menipis, HR; idempotent + jurnal balance), command `demo:seed` (menolak production), guard `demo_enabled`, hint login demo disembunyi di production; panduan `DEMO_GUIDE.md`.
- Verifikasi: `DemoPerfTest` baru **4 passed / 23 assertions** (MySQL testing; independen dijalankan ulang hijau); regresi SalesFlow+Smoke & Auth+Isolation+Finance+Purchasing hijau; butir bisnis 17–19 (waktu Jakarta vs UTC, password backup, hitungan approval global).

## v0.5.6 — 2026-10-17 (laporan enterprise + impor)
- Audit semua laporan: tombol Export CSV inventori & pembelian yang rusak diperbaiki; view pembelian salah kolom + filter tanggal mati diperbaiki; ringkasan penjualan kini menghormati filter `q`; ekspor penjualan mencakup SEMUA baris (cursor, ≤5000, anti formula-injection), bukan halaman aktif.
- Ekspor PDF via dompdf (sudah terinstal, tanpa paket baru) + tampilan cetak di 8 laporan (4 operasional + ledger/trial/PL/aging); ekspor CSV di semua laporan keuangan.
- Impor CSV suppliers baru + template CSV customers/products/suppliers + tombol "Impor CSV" di index pelanggan/supplier/produk (sebelumnya impor yatim tanpa tautan); pola validasi/audit sama (header wajib, batas 2000 baris, audit log).
- Verifikasi: `ReportsEnterpriseTest` baru **7 passed / 57 assertions** (MySQL testing; independen dijalankan ulang: 235,66s hijau); `php -l` bersih, `route:list` OK. Docs `REPORTS_ENTERPRISE.md` baru; butir bisnis 16 (impor transaksional menunggu keputusan).

## v0.5.5 — 2026-10-17 (redesign dashboard admin)
- Shell enterprise: sidebar kiri berkelompok collapsible (drawer + backdrop/Escape di mobile, persist localStorage), active state `routeIs()`, topbar ringkas (toggle, pencarian → filter laporan penjualan, pemilih entitas/cabang → `context.switch`, approval, notifikasi, profil); ikon SVG inline, tanpa CDN/paket baru.
- Dashboard diurut prioritas: banner aksi (approval pending, stok menipis) → 4 KPI → grafik tren 280px + panel "Perlu perhatian" → pendapatan per entitas + SO terbaru → kartu Keuangan/Operasional/HR; semua tombol tertaut ke route nyata bergate permission; label "bukan laba".
- Fix ikut: pencarian topbar `?q` kini benar-benar memfilter (`ReportController` + sales view).
- Verifikasi: `npm run build` 20,5s sukses; `SmokePagesTest` 1 passed/3 assertions (70 halaman 200); `AuthTest` 2 passed/7 assertions; docs `ADMIN_DASHBOARD_REDESIGN.md` baru.

## v0.5.4 — 2026-10-17 (audit finalisasi backend)
- 7 perbaikan keamanan/integritas: `injectContext` branch/`created_by` (P0); scoping daftar file privat, guard entitas lead-convert, reservasi hantu SO-approve (P1); netralisasi formula-injection CSV, hardening impor CSV (header/batas/audit), privasi daftar delegasi (P2).
- Tes regresi baru `tests/Feature/Phase8Test.php`: **6 passed / 17 assertions** (MySQL `kazaerp_testing`); regresi Isolation+BusinessRules+SalesFlow 8/28 + SmokePages 70 halaman hijau.
- Docs: `FINALIZATION_AUDIT.md` baru; `TEST_RESULTS.md`, `IMPLEMENTATION_STATUS.md`, `DECISIONS_AND_OPEN_QUESTIONS.md` (butir 14–15: waktu Jakarta vs UTC, password backup) diperbarui.
- Tidak ada push/merge/deploy; commit lokal sebelumnya dipertahankan.

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

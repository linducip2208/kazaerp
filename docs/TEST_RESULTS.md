# Test Results (dari run nyata — bukan klaim)

## Ronde blueprint v1.0 — 2026-10-17 (CRM-03 follow-up + RET tiket + traceability ID asli)
- Perintah: `php artisan test --filter Phase11Test` (MySQL `kazaerp_testing`, migrate:fresh per TestCase)
- Hasil: **4 passed, 0 failed — 44 assertions** (durasi ±133s)
- Cakupan: follow-up jadwal→selesai→histori; double-complete ditolak; eskalasi + notifikasi;
  IDOR lintas entitas 404 (show + complete); reminder `--sync` + dedupe 24 jam;
  tiket open→in_review→resolved + SoD pelapor≠penyelesai + histori; resolve ganda + edit final ditolak;
  reject beralasan; isolasi tiket.
- Bug ditemukan & diperbaiki: `abort(404)` di dalam `DB::transaction` + catch `\Throwable` menelan
  404 menjadi 302 — diperbaiki dengan rethrow `HttpException` di semua aksi complete/escalate/
  review/resolve/reject (temuan berlaku ke pola yang sama; tidak ada pola serupa di controller lain
  yang menelan abort — aksi lain memakai `findOrFail` di luar try/catch).
- Regresi sesi ini: SalesFlowTest 1/14 hijau (38s); Phase8Test 6/17 hijau (215s);
  SmokePagesTest 1/3 hijau (45s, 70 halaman 200 — termasuk menu Follow-up/Komplain baru).
- File berubah: migrasi aditif `2026_10_17_100000`, `Complaint`/`Followup` fillable,
  `Crm/FollowupController.php` + `Crm/ComplaintController.php` (baru),
  `routes/modules/master.php`, `routes/console.php`, `SendFollowupReminders` + `SendFollowupReminder` (baru),
  6 blade followups/complaints, menu `tabler.blade.php`, `tests/Feature/Phase11Test.php` (baru).
- Suite penuh tidak dijalankan (aturan larangan paralel + durasi ±27–30 menit; perubahan
  terisolasi di modul CRM + regresi bertarget hijau).

## Gelombang finalisasi 2 — 2026-10-17 (demo MFG/rental/thrift/studio/pet + scorecard)
- Perintah: `php artisan test --filter Phase10Test` (MySQL `kazaerp_testing`, migrate:fresh per TestCase)
- Hasil: **5 passed, 0 failed — 36 assertions** (durasi 178,49s)
- Cakupan: demo gelombang-2 idempotent 2× (WO/rental/thrift/studio/grooming/boarding tepat 1);
  WO `DEMO-WO-001` completed + variance −10.000 + FG +10 + histori 3 tahap;
  rental deposit 100.000 + clash tumpang tindih ditolak (tetap 1 reservasi);
  thrift kode duplikat ditolak unique + live reserve 1× + reserve ulang ditolak;
  studio+pet tampil di index + isolasi entitas.
- Regresi: `--filter DemoPerfTest` → **4 passed / 23 assertions** (142,21s) — seeder
  gelombang-2 tidak merusak idempotensi demo lama / jurnal balance / isolasi / dashboard <45 query.
- File berubah: `database/seeders/DemoSeeder.php` (+5 metode demo), `tests/Feature/Phase10Test.php` (baru).
- Dokumen: `docs/FEATURE_SCORECARD.md` (baru — 20 fitur × 10 kategori + total + bukti);
  `DEMO_GUIDE.md` (§4 +5 skenario, §6 menyusut ke POS shift + payroll approve),
  + pembaruan TRACEABILITY/AUDIT_MATRIX/IMPLEMENTATION/DECISIONS/FINALIZATION/CHANGELOG sesi ini.
- Catatan: suite penuh tidak dijalankan (aturan larangan paralel + durasi ±27 menit);
  controller tak diubah sesi ini (hanya seeder + tes + docs) sehingga regresi DemoPerfTest mencukupi.

## Suite penuh final v0.5.9 — 2026-10-17 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test` — log `storage/logs/full_suite_final.log`
- Hasil: **63 passed, 0 failed, 0 skipped — 473 assertions** (durasi 1794,48s)
- Mencakup seluruh ronde v0.2.0–v0.5.9 tanpa regresi: Phase2–Phase9 + Auth/BusinessRules/DemoPerf/FinancePosting/InventoryConcurrency/Isolation/PurchasingMatch/ReportsEnterprise/SalesFlow/SmokePages (70 halaman 200) — termasuk Phase9Test (4/29: double-submit 1 kunci, kunci beda 2 bayar, AP double-submit, expense edit batalkan approval).

## Ronde finalisasi 17 Okt 2026 — Phase9 (idempotensi bayar + anti stale approval)
- Perintah: `php artisan test --filter Phase9Test` (MySQL `kazaerp_testing`, migrate:fresh per TestCase)
- Hasil: **4 passed, 0 failed — 29 assertions** (durasi 161,52s)
- Cakupan: bayar invoice double-submit 1 kunci → 1 baris + 1 jurnal (replay, bukan duplikat);
  kunci beda → 2 pembayaran sah + `paid`; bayar utang double-submit 1 kunci → 1 baris;
  edit expense setelah pengajuan → approval lama `superseded` + histori + pengajuan baru,
  `decide()` lama melempar, approve baru OK, edit pasca-approve → `draft` + pengajuan ulang.
- Regresi area tersentuh: `--filter "SalesFlowTest|Phase2Test"` → **5 passed / 39 assertions**
  (durasi 170,98s) — pay tanpa kunci (fallback uuid server) + bayar utang lama tetap hijau.
- Migrasi `2026_10_17_000001_add_payment_idempotency_keys` (aditif: kolom nullable + unique
  di `sales_payments`/`ap_payments`): `migrate --force` di dev OK; rollback path diuji via
  struktur down() (tidak dijalankan di dev — larangan destruktif).
- Pemeriksaan lain: `php -l` 6 file (4 controller/service + migrasi + test) bersih;
  sapuan tombol palsu/CDN/TODO/Closure nihil (lihat FEATURE_AUDIT_MATRIX); `route:list`
  386 rute ber-handler, 0 Closure.
- Catatan: suite penuh tidak dijalankan (aturan larangan paralel + durasi ±27 menit);
  file berubah menyentuh sales/purchasing/finance-expense/gov — regresi di atas mencakupnya.

## Suite penuh v0.5.7 — 2026-10-17 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test` — log `storage/logs/v057_full.log`
- Hasil: **59 passed, 0 failed, 0 skipped — 444 assertions** (durasi 1788,61s)
- Mencakup seluruh ronde v0.2.0–v0.5.7 tanpa regresi: Phase2–Phase7 + Auth/BusinessRules/FinancePosting/InventoryConcurrency/Isolation/PurchasingMatch/SalesFlow/SmokePages (70 halaman 200) + ronde baru: DemoPerfTest (4/23: memoize permission, dashboard <45 query, demo idempotent + jurnal balance, demo login + isolasi entitas), Phase8Test (6/17: injectContext branch/creator, file scoping, lead-convert guard, reservasi hantu, impor tolak header rusak, netralisasi formula CSV), ReportsEnterpriseTest (7/57: filter laporan, ekspor CSV/PDF penuh, impor suppliers + template, tautan impor di index).

## Laporan enterprise + impor menyeluruh (ReportsEnterpriseTest) — 2026-10-17
- Perintah: `php artisan test --filter ReportsEnterpriseTest` (MySQL `kazaerp_testing`, migrate:fresh per TestCase)
- Hasil: **7 passed, 0 failed — 57 assertions** (durasi ±246 dtk; rerun 1 tes yang gagal karena bug pemanggilan `streamedContent()` di file tes itu sendiri, lalu full-file hijau)
- Cakupan: halaman + filter + ringkasan penjualan; CSV penuh + PDF penjualan; kolom + CSV + PDF pembelian; halaman + CSV inventori & stok menua; 4 halaman + 4 CSV keuangan (ledger/trial/pl/aging); impor supplier (valid/duplikat/header buruk) + 3 template CSV + 3 halaman impor; tautan "Impor CSV" di 3 index.
- Pemeriksaan lain: `php -l` 4 file PHP diubah tanpa error; `route:list --name=reports` 11 rute; tidak ada perubahan CSS/JS sehingga `npm run build` tidak diperlukan. Detail di docs/REPORTS_ENTERPRISE.md.
- Catatan: suite penuh tidak dijalankan (durasi ±27 menit, dilarang paralel); tidak ada migrate:fresh di DB dev; tidak commit/push (working tree dibiarkan untuk review).

## Audit finalisasi v0.5.4 (7 perbaikan P0/P1/P2 + Phase8Test) — 2026-10-17
- Perintah: `php artisan test --filter Phase8Test` (MySQL `kazaerp_testing`, migrate:fresh per test)
- Hasil: **6 passed, 0 failed — 17 assertions** (durasi 194,67s)
- Perbaikan yang dibuktikan tes:
  1. `CrudController::injectContext` (P0): `array_key_exists` pada daftar fillable (selalu salah + TypeError untuk branch/created_by) → `in_array(..., true)`; branch & `created_by` kini terisi.
  2. `SecureFileController::index` (P1): daftar lampiran kini difilter per cakupan user (non-privileged hanya file karyawan sendiri + pelanggan/pet/studio dalam entitasnya); download memang sudah terotorisasi.
  3. Lead convert (P1 IDOR): closure `crm.leads.convert` kini abort 404 bila lead di luar entitas/cakupan user.
  4. `SalesOrder::approve` (P1): baris `stock_reservations` aktif hanya dibuat bila `StockService::reserve` sukses — tidak ada lagi reservasi hantu saat stok kurang (ledger `qty_reserved` konsisten).
  5. Impor CSV (P2): validasi header wajib, batas 2000 baris/file, validasi email, cek jumlah kolom, audit log `import`.
  6. Ekspor CSV penjualan (P2): sel diawali `=,+,-,@` dinetralkan dengan prefix `'` (anti formula injection).
  7. `DelegationController::index` (P2 privasi): tanpa `roles.assign` hanya melihat delegasi yang melibatkan diri sendiri; daftar user dibatasi ke diri sendiri.
- Regresi terkait: `--filter "IsolationTest|BusinessRulesTest|SalesFlowTest"` → **8 passed (28 assertions)**, 255,81s; `--filter SmokePagesTest` → **1 passed (3 assertions)**, 37,83s (70 halaman tetap 200).
- Pemeriksaan lain: `composer validate` valid; `php -l` seluruh `app/` + `routes/` tanpa error; `php artisan route:list` ±387 baris tanpa error; Pint `--test` melaporkan gaya kompak pre-existing di semua file (tidak diformat massal agar diff minimal — keputusan sadar).
- Catatan: suite penuh tidak dijalankan ulang dalam sesi ini (durasi ±27 menit, dilarang paralel); file yang diubah hanya 7 + 1 test baru, dan semua suite yang menyentuh area berubah hijau.

## Web v0.5.3 (auth landscape + error views) — 2026-10-17
- Alasan: login kazaerp tampil kartu polos tanpa landscape (beda gaya dengan project lain yang split-screen); investigasi membuktikan Tabler asli & terkompilasi (CSS 837 KB, selector `.card/.btn-primary/.navbar/.table` ada, MIME benar) — masalahnya desain, bukan aset rusak.
- Perubahan: `layouts/auth.blade.php` baru + `app.css` (+panel gradient, responsif: panel hilang di mobile) + 4 view auth + 3 view error mandiri; `npm run build` 20,54s sukses.
- Verifikasi: `test_password_reset_mandiri_penuh` **passed (10 assertions)** — alur lupa→reset lewat view baru; curl `/login`, `/forgot-password` 200 + markup `auth-split`; `/password/reset` tanpa token kini **404** (sebelumnya 500 untuk tamu); tidak ada error baru di `laravel.log`.
- Catatan: `public/build` tetap gitignored (dibangun saat deploy sesuai DEPLOYMENT.md).

## Mobile v0.5.2 (fix izin INTERNET + E2E dari APK rilis di emulator) — 2026-10-16
- **Temuan & fix**: APK rilis v0.5.1 gagal memanggil API — manifest utama tidak memuat permission `INTERNET` (template Flutter hanya menaruhnya di manifest debug); app menampilkan "Tidak dapat terhubung ke server" dan tidak ada request yang tercatat di server. Fix: `INTERNET` + `android:usesCleartextTraffic="true"` (khusus server dev HTTP; produksi wajib HTTPS) → rebuild APK.
- **E2E dari APK release** (`id.kazaka.pos_kaza` di emulator-5554, Android 17/API 37) — semua dijalankan dari UI app, bukan skrip:
  1. `adb install -r` → **Success**; launch bersih (`Displayed …MainActivity +2s900ms`, tanpa FATAL).
  2. Login dari form app → `POST /api/v1/login` (log server 21:09:46) → token Sanctum **id=2** (`personal_access_tokens` created 21:09:52).
  3. Buka shift (kas awal 100.000) → `POST /api/v1/pos/shift/open` (log 21:14:41).
  4. Katalog: `GET /api/v1/pos/shift` + `GET /api/v1/products` (21:09:52–53); produk "Produk Demo" (DEMO-001, Rp 75.000) tampil.
  5. Keranjang 1 item → Tunai → `POST /api/v1/pos/checkout` (log 21:22:55) → snackbar app **"Tersimpan: MZA-HOLD/PST/POS/2026/10/00002"**.
  6. Verifikasi DB: `pos_sales` id=2 (number …00002, status `paid`, grand total 83.250 = 75.000 + PPN 11%, `shift_id=2`, `client_ref` dibuat app); `pos_sale_lines` id=2 (Produk Demo qty 1 @75.000); `stock_ledgers` id=4 (`pos_sale #2`, qty −1, 21:22:55) → saldo `stock_balances` product 1 = **6,0000** (10 − 2 invoice − 2 POS) — konsisten manual.
  7. Tutup shift dari app (menu ⋮ → "Tutup shift", kas fisik 183.250) → `POST /api/v1/pos/shift/close` → dialog **"Shift ditutup"** (sistem 83.250 / seharusnya 183.250 / selisih 0); DB `pos_shifts` id=2 = `closed`, difference 0.
  8. **Jalur offline-first dari APK**: server dimatikan → tambah produk → `Bayar & Simpan` → transaksi tersimpan ke antrean lokal (badge cloud = 1, halaman Antrean: **"1 menunggu"**, item `pos-1792167119343-…` + error "Server tidak merespons (timeout)").
  9. **Auto-sync saat app dibuka**: server dinyalakan → force-stop + launch ulang → antrean tersinkron otomatis → `pos_sales` id=3 (**…/00003**, `paid`, 83.250, `shift_id=3`, `client_ref` = item antrean di atas), `stock_ledgers` id=5 (`pos_sale #3`, −1) → saldo = **5,0000** (10 − 2 − 3 ✓); badge antrean hilang.
  10. `php artisan kaza:audit-integrity` dengan 3 sale + 3 shift (shift #3 ditutup via API, kas 133.250, selisih 0): **AUDIT OK — 0 temuan**.
- Sertifikasi ulang pasca-fix: `flutter analyze` **0 issue**; `flutter test` **21 passed** (log `storage/logs/flutter_v052_verify.log`).
- Bukti lokal (`storage/logs/`, tidak di-commit): `serve8099b.out.log` (baris request login/shift/checkout), `flutter_build_apk3.log` (rebuild 89,0s), screenshot `emulator_3_login.png` … `emulator_46_autosync.png` (snackbar "Tersimpan", dialog "Shift ditutup", halaman "Antrean Sinkron" 1 menunggu, badge cloud).
- Catatan operasional emulator: instance lama (hidup 2 hari) ANR SystemUI → `screencap`/`adb install` menggantung; dilakukan cold boot ulang (`-no-window -gpu swiftshader_indirect`); navigasi sempat diganti **3-button** agar tombol paling bawah app tidak tertutup zona gesture, lalu **dikembalikan ke gestural** + `show_touches` dimatikan setelah verifikasi selesai.
- APK rilis final (dengan fix): 47,7 MB, SHA256 `AC4797212E45AABC9050F151C5252170819930ACC211E2E00BACE4D730DDB267`.

## Mobile v0.5.1 (patch checkout + APK rilis) — 2026-10-16
- Patch POS (anti double-tap + guard `try/finally` alur keranjang): `flutter analyze` **0 issue**; `flutter test` **21 passed** (log `storage/logs/flutter_v051_verify.log`).
- `php artisan kaza:audit-integrity` di DB dev (berisi hasil smoke live POS): **AUDIT OK, 0 temuan** — saldo stok, jurnal, dan pembayaran invoice konsisten.
- Build APK rilis pertama (Android SDK licenses diterima): `flutter build apk --release` → `build/app/outputs/flutter-apk/app-release.apk` **47,7 MB**, SHA256 `2FC0CA8DCC952F1F79924DD391887A5B47A6A2260E4BEAFA1D001FAE5FB483D3` (log `flutter_build_apk2.log`).
  - Rantai bukti artefak: source final ditulis 15:01:48 → AOT `libapp.so` 15:03:18 → APK 15:06:09; rebuild 19,6s menghasilkan hash **identik** (repackage deterministik dari input yang sama).
  - Build pertama (`flutter_build_apk.log`) sukses dengan warning non-fatal Kotlin daemon (cross-drive `C:`/`D:`) → diatasi lewat `kotlin.incremental=false`.

## Suite penuh v0.4.3 + mobile v0.5.0 — 2026-10-16 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test` — log `storage/logs/v043_full4.log`
- Hasil: **42 passed, 0 failed, 0 skipped — 347 assertions** (durasi 1611,24s)
- 39 test v0.1–v0.4.2 tetap hijau (regresi nihil) + 3 test baru:
  - Phase7Test (2 passed / 25 assertions, log `phase7_rerun2.log`): `kaza:admin-create` (buat → hash bcrypt terverifikasi + role super_admin; run ulang → update tanpa duplikat; password < 12 & email invalid → exit 1 tanpa user baru) dan alert kedaluwarsa (seleksi kandidat ≤ 30/60 hari via queue fake; `--sync` membuat notifikasi hanya untuk batch berstok; batch tanpa stok & di luar ambang tidak dinotifikasi; run ulang idempoten 24 jam; guard `StockService` memblokir pengeluaran dari batch kedaluwarsa — diuji eksplisit sebagai aturan bisnis).
  - SmokePagesTest (1 passed): 70 halaman menu utama super_admin → semua HTTP 200.
- Mobile (`mobile/pos_kaza`): `flutter analyze` 0 issue; `flutter test` **21 passed** (log `flutter_test2.log`: klien API + model/keranjang + repository antrean offline + widget login); `flutter build web --release` sukses (`build/web`, log `flutter_build_web.log`).
- Smoke live klien Dart → server dev 8099 (log `flutter_live_smoke.log`): login OK → 1 produk + 1 pelanggan → buka shift id=1 kas 100.000 → checkout `MZA-HOLD/PST/POS/2026/10/00001` total 83.250 (`replayed=false`) → kirim ulang `client_ref` sama → **200 `replayed=true`, nomor sama, tanpa efek dobel** → tutup shift (sistem=83.250, kas seharusnya=183.250, selisih=0).
- Catatan insiden (transparansi): 3 log percobaan sebelumnya tidak dipakai — `v043_full.log` (run duplikat tak sengaja, langsung dihentikan → state DB test kacau), `v043_full2.log` (MySQL di-SHUTDOWN eksternal 2× di tengah run → `Connection refused` massal), `v043_full3.log` (proses mati saat mesin idle). Run final di atas dijalankan setelah lingkungan dipastikan bersih dan MySQL stabil.

## Suite penuh v0.4.2 — 2026-10-16 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test`
- Hasil: **39 passed, 0 failed, 0 skipped — 319 assertions** (durasi 1473,78s)
- 36 test v0.1–v0.4.1 tetap hijau (regresi nihil) + 3 test Phase6Test baru (3 passed / 86 assertions, log `storage/logs/phase6_full.log`, durasi 100,37s):
  - idempotensi checkout POS `client_ref`: 201 baru → 200 replay (`replayed: true`, id & nomor sama), stok tidak dobel (10 → 8 → 7), satu baris `pos_sales` per ref, retry tetap 200 walau shift sudah ditutup, ref berbeda → penjualan baru
  - `kaza:audit-integrity`: kondisi bersih → exit 0 "AUDIT OK"; saldo stok digeser tanpa ledger + jurnal tidak balance (JN-RUSAK-1) + paid_total invoice tanpa pembayaran (INV-RUSAK-1) → exit 1 + detail temuan per kategori
  - rate limit API: 60 request/menit OK, request ke-61 → 429 (throttle 60/menit per pengguna)
- Uji operasional backup/restore ulang (dev DB pasca-migrasi, 16 Okt 2026): dump 26.126 bytes → restore ke DB scratch via `mysql` → **179 tabel / 21 migrasi / users=2 / products=1 identik dengan sumber**; scratch DB di-drop setelah verifikasi.
- DB pengujian: MySQL (locking & constraints nyata). Log suite: `storage/logs/v042_full.log`.

## Riwayat v0.4.1 (arsip) — 2026-10-15 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test`
- Hasil: **36 passed, 0 failed, 0 skipped — 233 assertions** (durasi 1366,34s)
- 32 test v0.1–v0.4.0 tetap hijau (regresi nihil) + 4 test Phase5Test baru:
  - read-only enforcement: auditor baca lintas modul 200 tapi POST tulis 403; viewer tetap 403 walau permission tulis diberikan (defense-in-depth); auditor+branch_manager boleh menulis; self-service profil tetap diizinkan
  - reset password mandiri: email tak dikenal ditolak; token diambil dari notifikasi; token salah ditolak; token benar → password berubah (hash baru, password lama mati)
  - queue worker nyata: `SendOverdueInvoiceAlert` di-dispatch ke antrean `database` → `queue:work --once` memproses → notifikasi terbuat & antrean bersih
  - backup command: `kaza:backup` menghasilkan file .gz > 1KB dengan magic gzip valid (dump nyata DB test)
- Uji operasional backup/restore (dev DB, 15 Okt 2026): `kaza:backup` → 25.739 bytes; restore ke DB scratch `kazaerp_restore_verify` via `mysql` → **178 tabel / 17 migrations / users=2 / products=1 identik dengan sumber**; scratch DB di-drop setelah verifikasi (tidak ada data tersentuh).
- DB pengujian: MySQL (locking & constraints nyata). Log: `storage/logs/v041_full.log`.

## Riwayat v0.4.0 (arsip) — 2026-10-15 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test`
- Hasil: **32 passed, 0 failed, 0 skipped — 204 assertions** (durasi 1449,92s)
- 26 test v0.1–v0.3 tetap hijau (regresi nihil) + 6 test Phase4Test baru:
  - harga tier (invoice harga kosong → tier wholesale; override manual dihormati; halaman create; API `products?customer_id` + `/products/{id}/price`)
  - GRN lot → batch otomatis; karantina/kedaluwarsa memblokir stok keluar; audit `quality_checks`
  - WO snapshot biaya standar (5.000) → issue biaya aktual naik (6.000) → variance −2.000 → produce masuk FG → issue ulang ditolak
  - komisi akrual saat invoice lunas (rate config 5% × 30.000 = 1.500) + approve Finance
  - API POS shift open/close/checkout (harga tier 24.000 + PPN 11% = 26.640; stok 10→8) + GPS positions push/read + 403 lintas entitas
  - queue overdue alerts (dispatch `Queue::fake`, eksekusi job, idempoten 24 jam, invoice lunas dilewati) + smoke 6 halaman baru
- DB pengujian: MySQL (locking & constraints nyata). Log: `storage/logs/v040_full.log` (rincian Phase4: `storage/logs/phase4_final.log`).

## Riwayat v0.3.0 (arsip) — 2026-10-15 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test`
- Hasil: **26 passed, 0 failed, 0 skipped — 119 assertions**
- 20 test v0.1–v0.2 tetap hijau (regresi nihil) + 6 test Phase3Test baru:
  - 2FA penuh (setup QR → confirm → challenge → dashboard non-admin 200; sekaligus menemukan & memperbaiki bug `$perEntity` array untuk non-admin)
  - file privat (403 lintas entitas), vet butuh pet.treat, alokasi equal & price,
  - voucher (salah ditolak, diskon + used + poin), bankrec matched/draft + budget/aging/intercompany OK
- DB pengujian: MySQL (locking & constraints nyata). Log: `storage/logs/finaltest3.log`.

## Riwayat v0.2.0 (arsip)
- Hasil v0.2.0: **20 passed — 82 assertions** (`storage/logs/finaltest2.log`).
- 16 test v0.1.0 tetap hijau (regresi nihil) + 4 test Phase2Test baru:
  - bayar utang partial/paid + overpay ditolak + jurnal balance
  - payroll approve → jurnal gaji Rp4.500.000 balance (6100→2300)
  - delegasi memungkinkan decide tanpa permission langsung
  - API login/scope-403/revoke-401/nonaktif-401
- DB pengujian: MySQL (locking & constraints nyata). Log: `storage/logs/finaltest2.log`.
- Rincian file:
  - `tests/Unit/DocNumberTest.php` — 1 passed (sequence monotonik + format)
  - `tests/Feature/AuthTest.php` — 2 passed (login OK/gagal/nonaktif, guest redirect)
  - `tests/Feature/IsolationTest.php` — 2 passed (lintas entitas 404 + konten tersaring; payroll 403 untuk cashier)
  - `tests/Feature/SalesFlowTest.php` — 1 passed (SO→approve→invoice→jurnal balance→partial→paid→overpayment & oversell ditolak)
  - `tests/Feature/InventoryConcurrencyTest.php` — 2 passed (reservasi paralel 3×2 dari stok 5 ⇒ 2 sukses/1 gagal; partial receipt partial→received)
  - `tests/Feature/PurchasingMatchTest.php` — 1 passed (3-way exception→HOLD; duplikat vendor_ref ditolak)
  - `tests/Feature/FinancePostingTest.php` — 2 passed (balance/idempotent/reversal/tutup-periode; SoD self-approve ditolak, orang lain bisa)
  - `tests/Feature/BusinessRulesTest.php` — 5 passed (rental bentrok, thrift unik, boarding capacity, lead convert, report filter)
- DB pengujian: MySQL (phpunit.xml → `kazaerp_testing`), menguji locking & constraints nyata. Tidak memakai SQLite.
- Log: `storage/logs/finaltest.log`.

## Riwayat perbaikan (run pertama: 13 passed, 4 failed)
1. `SalesFlowTest` overpayment/oversell → 500 karena RuntimeException tak tertangkap. Perbaikan: semua aksi transaksional dibungkus → `back()->withErrors(['msg'])` (sekaligus UX lebih baik).
2. `FinancePostingTest` SoD → 500/403. Perbaikan: withErrors di ApprovalController + test memberi permission `governance.approve` dulu agar mencapai guard SoD; verifikasi dua sisi (sendiri ditolak, orang lain bisa).
3. `InventoryConcurrencyTest` approve PO → SoD meledak (pembuat = penyetuju). Perbaikan: test memakai dua user berbeda (praktik SoD yang benar).
4. `BusinessRulesTest` lead convert → 403. Perbaikan: role `module_operator` diberi permission operasional di seeder.

## Pemeriksaan lain (nyata)
- `composer validate` → valid. `php artisan migrate:fresh --force` → 13 migrasi DONE (173 tabel). `php artisan db:seed` → 3 seeder DONE.
- `php artisan route:list` → ±160 routes, tanpa error. `php artisan view:cache` → sukses. `npm run build` → CSS 835KB + JS 268KB lokal.
- `php -l` seluruh `app/` (100+ file) → 0 error sintaks.
- Smoke HTTP (login sebagai admin dev): 30 URL → semua 200 (guest → redirect login; forgot-password guest 200 setelah perbaikan layout).
- E2E manual terverifikasi di DB: GRN 10 → SO 2 → approve (reservasi) → invoice (stok 8, jurnal balance 166.500) → bayar 66.500 (partial) → 100.000 (paid); overpayment 999.999.999 → ditolak ramah.

## Error tersisa
Tidak ada error yang diketahui pada suite dan smoke di atas. Keterbatasan (bukan error): lihat IMPLEMENTATION_STATUS (parsial/pending).

## Redesign dashboard admin enterprise — 2026-10-17
- Perintah: `npm run build` → sukses 20,53s (manifest 0,27 kB; CSS 845,76 kB / gzip 107,91 kB; JS 477,98 kB / gzip 155,97 kB). Tanpa CDN, tanpa dependensi npm baru (Chart.js lokal via `window.KazaChart`).
- Perintah: `php artisan test --filter SmokePagesTest` (MySQL `kazaerp_testing`, migrate:fresh oleh harness) → **1 passed (3 assertions), 47,04s** — `/` + 60+ halaman menu semua 200 untuk super_admin dengan layout sidebar baru.
- Perintah: `php artisan test --filter AuthTest` → **2 passed (7 assertions), 67,90s** — login OK/gagal + guest redirect (topbar profil/logout tidak regresi).
- Bug saat pengerjaan: `/` sempat 500 — `@if` menempel pada teks (`berjalan@if`) tidak dikompilasi Blade (syarat word-boundary). Perbaikan: tambah spasi; `view:clear`; smoke hijau. Tidak ada migrate:fresh manual, tidak menyentuh DB produksi.
- Detail visual/arsitektur: lihat `docs/ADMIN_DASHBOARD_REDESIGN.md`.

## Audit menyeluruh + finalisasi — 2026-10-17 (sesi lanjutan)
- Baseline performa (harness CLI, data dan skenario identik, MySQL `kazaerp` dev): controller+render dashboard 127 ms / 84 query (roles/perm 50, invoices 10). Sesudah perbaikan: 86 ms / 28 query (roles/perm 1, invoices 3). HTML 31.390 byte, render OK.
- Perintah: `php artisan test --filter DemoPerfTest` (MySQL `kazaerp_testing`) → **4 passed / 23 assertions, 167,85s** (memoize permission: 30 cek max 2 query; dashboard 200 dan di bawah 45 query; demo seeder idempotent + jurnal balance; isolasi entitas).
- Regresi: `--filter SalesFlowTest|SmokePagesTest` → **2 passed / 17 assertions, 76,00s**; `--filter AuthTest|IsolationTest|FinancePostingTest|PurchasingMatchTest` → **7 passed / 22 assertions, 241,76s**.
- Perintah: `npm run build` → sukses 19,96s (JS 477,98 kB/gzip 155,97 kB; CSS 845,76 kB/gzip 107,91 kB; manifest 0,27 kB).
- Perintah: `php artisan demo:seed` 2x di `kazaerp` dev → idempotent (tiap nomor DEMO-* tepat 1); verifikasi: SO approved, INV paid 166.500, PO received, VIN partial/matched 255.000/555.000, 3 jurnal J-DEMO-* balance + posted, 1 approval pending, DEMO-003 avail 2, 5 akun demo.
- Perintah: `composer validate` → valid; `route:list` → 390 baris; `php -l` 6 file → bersih; `pint --test` → gagal juga di file pre-existing yang tak diubah (tidak diformat massal, diff minimal).
- HTTP nyata dev (Apache kazaerp.test): `GET /login` → 200, 0,27s, hint demo tampil, tanpa referensi CDN.
- Suite penuh tidak dijalankan (aturan: satu suite penuh +-27 mnt, dilarang paralel).

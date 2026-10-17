# Implementation Status (per 17 Okt 2026 — ronde finalisasi Phase9)

## Selesai (DONE, bukti run nyata)
- Ronde finalisasi Phase9 (17 Okt 2026): pembayaran invoice & utang anti double-submit
  (`idempotency_key` unik + replay aman, migrasi aditif) + expense anti stale-approval
  (`invalidatePending` + pengajuan ulang otomatis, edit pasca-approve → `draft`).
  Bukti: `Phase9Test` **4 passed / 29 assertions**; regresi SalesFlow+Phase2 **5/39** hijau.
  Dokumen baru: `BLUEPRINT_TRACEABILITY.md` (UNVERIFIED-xx, PDF tak ditemukan),
  `FEATURE_AUDIT_MATRIX.md` (sapuan 386 rute/0 Closure/0 CDN/0 TODO).
- Foundation: Laravel 11.57 + PHP 8.3.30 + MySQL 8.4.9; 179 tabel; migrate:fresh hijau; seed hijau.
- 70+ controller, 165+ Blade, ±200 routes, Tabler + Chart.js lokal.
- API v1 Sanctum: login/me/logout, invoices, stock, products (+harga tier per pelanggan), customers, POS (shift open/close/checkout), GPS trips + entity guard + revoke.
- Bayar utang + jurnal; retur beli → stok; jurnal gaji otomatis; delegasi approval; notifikasi + bell.
- Dashboard per-peran (holding/finance/ops/HR) + grafik tren 6 bulan.
- Hardening: security headers, cabut sesi nonaktif, throttle, `kaza:backup` + scheduler, AdminSeeder env; 2FA; file privat terotorisasi.
- v0.4.0 baru: harga tier otomatis (web+API+POS), QC/karantina batch + guard stok keluar, MFG issue→produce + variance biaya, komisi penjualan (akrual + approve), GPS transport (push posisi + tracking web), queue worker nyata (`kaza:overdue-alerts`).
- Smoke otomatis: 70 halaman menu utama → semua 200 (`tests/Feature/SmokePagesTest.php`); smoke live API POS dari klien Dart (`mobile/pos_kaza/tool/api_smoke.dart`) termasuk replay idempoten. E2E: GRN→SO→invoice→jurnal→paid (DB diverifikasi).
- Test: suite penuh MySQL **42 passed / 347 assertions / 0 failed** (lihat TEST_RESULTS.md, log `v043_full4.log`); rincian Phase4 6/85 (`phase4_final.log`), Phase5 4/29 (`v041_full.log`), Phase6 3/86 (`phase6_full.log`), Phase7 2/25 (`phase7_rerun2.log`), SmokePages 70 halaman.
- Audit finalisasi v0.5.4 (17 Okt 2026): 7 perbaikan (injectContext branch/created_by P0; scoping file privat, lead convert, reservasi hantu P1; CSV injection, impor, privasi delegasi P2) + `tests/Feature/Phase8Test.php` **6 passed / 17 assertions**; regresi Isolation+BusinessRules+SalesFlow 8/28 + SmokePages 1/3 hijau.
- Hardening v0.4.1: read-only auditor/viewer ditegakkan server-side (middleware `ReadOnlyRoles`), reset password mandiri diuji penuh, backup+restore terverifikasi (dump → DB scratch → identik), job queue diverifikasi diproses `queue:work` — bukti `tests/Feature/Phase5Test.php` + TEST_RESULTS.md.
- v0.4.2 baru: checkout POS idempoten via `client_ref` (retry sync offline aman — fondasi POS mobile), command `kaza:audit-integrity` (saldo stok vs ledger, ledger yatim, balance jurnal, paid_total invoice vs pembayaran; exit 1 saat ada temuan), rate limit API 60 req/menit per user; migrasi dev DB disinkronkan (179 tabel / 21 migrasi, backup+restore ulang terverifikasi).
- v0.4.3 baru: `kaza:admin-create` (admin produksi idempoten per email), alert kedaluwarsa batch (`kaza:expiry-alerts` + job `SendBatchExpiryAlert`, scheduler harian 08:30, dedupe 24 jam, hanya batch berstok), smoke otomatis 70 halaman menu (semua 200).
- v0.5.0 baru: aplikasi POS mobile offline-first (Flutter `mobile/pos_kaza`) — antrean lokal SharedPreferences + sinkron idempoten `client_ref`, shift kasir buka/tutup, halaman antrean (pending/tersinkron/ditolak + retry); `flutter analyze` 0 issue, `flutter test` 21/21, `flutter build web` sukses, smoke live replay 200 `replayed: true` dari kode klien asli.
- v0.5.1 baru: guard anti double-tap + `try/finally` alur checkout POS; **APK rilis Android terbangun** (`app-release.apk` 47,7 MB, SHA256 `2FC0CA8D…FB483D3`) — Android licenses diterima; workaround Kotlin Windows lintas-drive (`kotlin.incremental=false`).
- v0.5.2 baru: fix izin `INTERNET` manifest rilis (APK v0.5.1 tidak bisa akses jaringan) + cleartext dev; **verifikasi end-to-end dari APK di emulator** — login → buka shift → katalog → checkout tersimpan `MZA-HOLD/PST/POS/2026/10/00002` (paid, 83.250) + ledger stok `pos_sale #2`; tutup shift (selisih 0); **offline-first**: checkout saat server mati → antrean lokal → auto-sync saat app dibuka → sale `…/00003`; `kaza:audit-integrity` OK; flutter analyze 0 issue + test 21/21.

## Parsial (berfungsi inti, perlu pendalaman)
Intercompany (register + worksheet eliminasi manual — otomatis menunggu kebijakan), HPP sederhana (`none` — FIFO/average menunggu keputusan), integrasi vendor eksternal (marketplace/WA/payment gateway — kredensial belum ada), GPS pihak ketiga (arsitektur push siap).

## Tertunda (PENDING)
Eliminasi intercompany otomatis, payment gateway/marketplace/WA adapter nyata, kebijakan pajak/payroll resmi, provider GPS produksi, kredensial produksi (HTTPS, mail, gateway), UAT pemilik/Finance.

## Blocker
Tidak ada blocker teknis. Blocker bisnis: lihat DECISIONS_AND_OPEN_QUESTIONS.md.

## Sesi audit menyeluruh 17 Okt 2026 (performa + demo + keamanan login)
- DONE: N+1 otorisasi dashboard/sidebar (memoization request-scoped, 84→28 query); agregasi invoice (10→3 scan); DemoSeeder end-to-end idempotent + `demo:seed` + guard production + `demo_enabled`; hint kredensial login disembunyikan di production. Bukti: TEST_RESULTS.md (sesi lanjutan), PERFORMANCE_AUDIT.md, DEMO_GUIDE.md, DemoPerfTest 4 passed.
- Parsial baru: paket data demo belum mencakup pet/grooming/boarding, WO apparel, thrift live, studio, rental, POS shift, payroll approve (modul tersedia, smoke 200; dimainkan manual).
- Diketahui (bukan regresi): hitungan + badge approval pending bersifat global (tabel `approvals` tanpa `legal_entity_id`) — butir 17 DECISIONS.

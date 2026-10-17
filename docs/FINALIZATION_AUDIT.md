# Finalization Audit — KazaERP (17 Okt 2026, branch kerja-erp-foundation)

Audit + perbaikan nyata backend Laravel (mobile Flutter tidak diubah). Semua klaim di bawah dari run nyata.

## 0. Sesi audit menyeluruh + finalisasi (17 Okt 2026, lanjutan)
Konteks: keluhan dashboard kurang profesional, app terasa lambat pasca-redesign v0.5.5, demo tidak tersedia.
| ID | Severity | Temuan | Perbaikan (file) | Bukti |
|----|----------|--------|------------------|-------|
| P1 | P0 | N+1 otorisasi: `User::hasPermission()` query DB setiap panggilan; sidebar+dashboard ±35 panggilan → 50 query/halaman (±60% beban). Akar "makin lambat" v0.5.5. | `app/Models/User.php` → memoization request-scoped (`memoRoleNames`/`memoPerms`, `flushAuthCache()`) | Harness 84→28 query (roles 50→1); `DemoPerfTest::test_permission_memoized` (30 cek ≤2 query) |
| P2 | P1 | Dashboard 10 scan `invoices` (3 KPI + loop 6× tren + per-entitas) | `app/Http/Controllers/DashboardController.php` → 1 SELECT kondisional + 1 GROUP BY + kolom dibatasi | `invoices` 10→3; total 84→28; waktu 127→86 ms; `test_dashboard_200_dan_query_terbatas` |
| D1 | P1 | Demo tidak tersedia (seeder hanya 1 produk + 3 master, tanpa transaksi/scenario) | `database/seeders/DemoSeeder.php` → siklus SO→invoice→lunas, PO→GRN→VIN(matched)→bayar parsial, approval pending, stok menipis, HR; `app/Console/Commands/DemoSeedCommand.php` (`demo:seed`, tolak production); `config/kazaerp.php` (`demo_enabled`) | `demo:seed` 2× idempotent (counts=1); jurnal balance; `DemoPerfTest` 4 passed/23 assertions |
| S1 | P1 | Hint kredensial demo di login tampil tanpa syarat (bocor ke production) | `resources/views/auth/login.blade.php` → tampil hanya non-prod / `demo_enabled` | HTTP dev 200 + hint tampil; tanpa CDN |
| — | — | Pint: repo tidak lolos gaya Pint bahkan file pre-existing → TIDAK diformat massal (diff minimal), konsisten R4 | — | `pint --test` gagal di `PostingService.php`/`InvoiceController.php` (tak diubah) |

Tidak ditemukan (pemeriksaan ulang): tombol palsu/placeholder (grep `TODO|FIXME|Coming Soon|...` hanya input `placeholder=` HTML yang sah), route tanpa handler (`route:list` 390 baris OK), CDN eksternal (login HTML tanpa `cdn./unpkg/cdnjs/jsdelivr`), SQLi (`whereRaw` statis), XSS (`{{ }}` escaped).
Risiko baru: tidak ada (tidak ada cache lintas-entitas; angka keuangan tanpa cache = fresh; otorisasi/audit/ledger tak dikurangi).
Batasan: angka performa = dev-lokal, bukan klaim produksi. Suite penuh tidak diulang (larangan paralel).

## 1. Ringkasan arsitektur
Modular monolith Laravel 11 + PHP 8.3 + MySQL 8.4 + Blade + Tabler lokal (Vite `public/build`, tanpa CDN). RBAC native (16 role / 60+ permission) via middleware `perm` + `ReadOnlyRoles` (auditor/viewer) + `CheckActiveUser` + `SecurityHeaders`. Isolasi entitas via `CrudController::baseQuery` (konteks sesi `erp_context`) + guard manual per controller + API `guardEntity`. Transaksi memakai `DB::transaction` + `lockForUpdate` + histori (`doc_status_histories`, `stock_ledgers`/`stock_balances`, `journal_lines`, `sales_payments`) + idempotency (`journals.idempotency_key`, `pos_sales.client_ref` + unique index, `DocNumberService` dengan lock).

## 2. Modul terverifikasi (implementasi nyata, bukan klaim menu)
Auth + 2FA TOTP + reset password mandiri + throttle + audit login; master (produk/kategori/customer/supplier/lead/opportunity + konversi anti-duplikat); sales (quotation→SO→delivery→invoice→payment, voucher, credit note, retur); POS web + API mobile idempoten + shift; inventory (receipt/GRN, batch QC/karantina/kedaluwarsa guard, transfer, adjustment, stock card, aging); purchasing (requisition→PO→GRN→vendor invoice→AP payment, three-way matching, retur); finance (COA, jurnal balance+idempoten+reversal, AR/AP aging, kas/bank, bank rec, expense, budget, closing, komisi); HR (karyawan, attendance, payroll komponen configurable + approve + jurnal gaji + slip PDF); bisnis (pet/vet/grooming/boarding/transport+GPS, apparel desain/BOM/WO + variance, thrift lot/item/live anti-double-sell, studio, rental anti-bentrok); governance (approval + delegasi berbatas tanggal + SoD); reports + CSV + impor CSV; notifikasi + scheduler (backup 02:00, overdue 08:00, expiry 08:30).

## 3. Temuan + perbaikan sesi ini
| ID | Severity | Temuan | Perbaikan (file) | Bukti |
|----|----------|--------|------------------|-------|
| F1 | P0 | `CrudController::injectContext`: `array_key_exists` pada list fillable selalu salah; branch/`created_by` tidak pernah terisi (dan TypeError saat branchField dipakai) | `app/Http/Controllers/CrudController.php` → `in_array(..., true)` | Phase8Test::test_crud_inject_context_branch_dan_creator |
| F2 | P1 | `SecureFileController::index` menampilkan metadata semua lampiran lintas entitas | filter per cakupan user di `index()` | Phase8Test files index |
| F3 | P1 | Lead convert tanpa cek entitas (IDOR) | guard 404 di `routes/modules/master.php` | Phase8Test lead convert |
| F4 | P1 | `SalesOrder::approve`: `stock_reservations` aktif tetap ditulis walau `reserve()` gagal (reservasi hantu) | insert hanya bila reserve sukses | Phase8Test SO approve |
| F5 | P2 | Ekspor CSV penjualan rentan formula injection | prefix `'` di `ReportController::sales` | Phase8Test CSV |
| F6 | P2 | Impor CSV tanpa validasi header/batas/audit | validasi header, batas 2000 baris, cek kolom, validasi email, audit log | Phase8Test import |
| F7 | P2 | `DelegationController::index` + daftar user terlihat semua akun | batasi ke delegasi sendiri + user sendiri tanpa `roles.assign` | manual (route smoke 200) |

Tidak ditemukan: SQL injection (query builder binding; `whereRaw` hanya ekspresi statis), XSS (`{{ }}` escaped; satu `{!! !!}` adalah SVG QR server-side), CDN eksternal (tidak ada), route tanpa handler, tombol palsu.

## 4. File berubah (7 + 1 baru)
- `app/Http/Controllers/CrudController.php`
- `app/Http/Controllers/SecureFileController.php`
- `app/Http/Controllers/Sales/SalesOrderController.php`
- `routes/modules/master.php`
- `app/Http/Controllers/Gov/DelegationController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/ImportController.php`
- `tests/Feature/Phase8Test.php` (baru, 6 tes)
- Docs: `TEST_RESULTS.md`, `IMPLEMENTATION_STATUS.md`, `DECISIONS_AND_OPEN_QUESTIONS.md` (butir 14–15), file ini.

## 5. Hasil pengujian aktual (MySQL `kazaerp_testing`)
- `php artisan test --filter Phase8Test` → **6 passed / 17 assertions** (194,67s).
- `--filter "IsolationTest|BusinessRulesTest|SalesFlowTest"` → **8 passed / 28 assertions** (255,81s).
- `--filter SmokePagesTest` → **1 passed / 3 assertions** (70 halaman 200; 37,83s).
- `composer validate` valid; `php -l` app+routes bersih; `route:list` ±387 baris OK.
- Suite penuh (±27 mnt) TIDAK dijalankan ulang sesi ini — dilarang paralel dan area berubah sudah dicakup regresi di atas.

## 6. Risiko tersisa
- R1 (P2): double-submit pembayaran invoice mengandalkan lock + validasi sisa; belum ada idempotency key per request. Mitigasi: tombol konfirmasi + lock; pertimbangkan token idempotency bila terjadi klik ganda nyata.
- R2 (P2): password backup via argumen proses (lihat DECISIONS butir 15).
- R3 (P2): penyimpanan waktu = wall-clock Jakarta, bukan UTC (lihat DECISIONS butir 14). Tampilan konsisten; jangan migrasi tanpa rencana.
- R4: Pint melaporkan gaya kompak pre-existing di semua file — tidak diformat massal (diff minimal).

## 7. Keputusan bisnis yang dibutuhkan pemilik
Lihat `docs/DECISIONS_AND_OPEN_QUESTIONS.md` butir 1–15: HPP (none vs FIFO/average), pajak/PPN resmi, aturan payroll, intercompany/eliminasi, payment gateway/marketplace/WA/GPS provider, kebijakan deposit sewa, komisi (sudah diimplementasi per entitas — minta pengesahan), QC default lot, variance produksi, ambang expiry, kredensial produksi, UAT pemilik/Finance.

## 8. Langkah deployment aman
1. `composer install --no-dev`, `npm install && npm run build` (public/build gitignored).
2. Salin `.env.example` → `.env`: `APP_DEBUG=false`, `APP_URL` produksi, HTTPS, `SESSION_SECURE_COOKIE=true`, mail produksi, `KAZA_COGS_METHOD` sesuai keputusan.
3. `php artisan key:generate`, `migrate --force`, `db:seed --force` lalu `kaza:admin-create` untuk admin produksi (jangan pakai akun demo).
4. Scheduler cron (backup, overdue, expiry), queue worker `database`, verifikasi restore backup ke DB scratch terisolasi sebelum go-live.
5. Nonaktifkan demo login screen di produksi (lihat DEPLOYMENT.md).

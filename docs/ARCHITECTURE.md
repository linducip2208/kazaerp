# Architecture

Modular monolith Laravel 11 (bukan microservices).

## Pemisahan domain (nyata, bukan sekadar folder)
| Layer | Lokasi |
|---|---|
| Models per domain | `app/Models/{Org,Identity,Master,Crm,Sales,Inventory,Purchasing,Finance,Hr,Pet,Mfg,Thrift,Studio,Rental,Marketing,Gov}/` |
| Business rules/services | `app/Services/`: DocNumberService (sequence terkunci), StockService (ledger+locking+batch QC/expiry guard), PostingService (balance/idempotent/reversal), ApprovalService (matrix+SoD), AuditService, PriceService (harga tier), PosService (shift/checkout POS), CommissionService (akrual komisi) |
| Controllers | `app/Http/Controllers/{domain}/` + `CrudController` generik (scope entitas, audit, guard posted) |
| Routes | `routes/modules/*.php` (org, master, sales, ops, finance_hr, business) + `routes/web.php` |
| Views | `resources/views/modules/<domain>/` + `components/` generik (index/form/show/doc-lines) |
| Tests | `tests/Feature/*`, `tests/Unit/*` (MySQL) |
| Middleware | `perm` (CheckPermission), `ctx` (ActiveContext), `ReadOnlyRoles` (auditor/viewer murni read-only), `SecurityHeaders`, `CheckActiveUser`, throttle login |

Alasan alternatif vs `app/Modules/...`: mengikuti konvensi Laravel (autoload `App\`, artisan, policy discovery) sambil mempertahankan boundary domain lewat namespace + route files + services. Didokumentasikan di sini sesuai mandat.

## Aturan transaksi
- Semua mutasi stok/keuangan dalam `DB::transaction` + `lockForUpdate` untuk resource diperebutkan (saldo, sequence, invoice saat bayar, thrift item).
- Dokumen posted: tidak bisa edit/hapus; koreksi via reversal/status.
- Idempotency: `journals.idempotency_key` unik; service kembalikan ID lama bila kunci sama.
- Error: RuntimeException bisnis → `back()->withErrors(['msg'])`, bukan 500.

## Events/jobs
Job nyata: `App\Jobs\SendOverdueInvoiceAlert` — dispatch lewat command `kaza:overdue-alerts` (scheduler harian 08:00 di `routes/console.php`; idempoten 24 jam per notifikasi). Produksi: jalankan `php artisan queue:work` (supervisor); dev bisa `kaza:overdue-alerts --sync`. Notifikasi via `NotifyService` (bell + halaman). Job kedua: `App\Jobs\SendBatchExpiryAlert` — `kaza:expiry-alerts` (scheduler 08:30; hanya batch berstok, ambang `--days=30`).
Audit integritas data: `php artisan kaza:audit-integrity` — verifikasi saldo stok vs ledger, ledger yatim, balance jurnal, dan paid_total invoice vs pembayaran; exit code 1 bila ada temuan.

## Aplikasi POS Mobile (`mobile/pos_kaza`)
Flutter 3.41 (Material 3) di folder `mobile/pos_kaza` (repo sama). Offline-first: keranjang → antrean lokal SharedPreferences (status `pending`/`synced`/`rejected`) → sinkron ke `/api/v1` dengan `client_ref` unik per penjualan (retry aman; replay 200 dianggap sukses). Layer: `lib/api_client.dart` (HTTP + bedakan error jaringan vs 4xx), `lib/local_store.dart` (token/cache katalog/antrean), `lib/pos_repository.dart` (orkestrasi sinkron), `lib/screens/*` (login, POS + shift, antrean). Uji: `flutter analyze` + `flutter test` (HTTP & SharedPreferences di-mock); smoke kontrak nyata: `dart run tool/api_smoke.dart <base-url> <email> <password> [--checkout]`.

## Authorization
`perm:<nama>` di route + `hasPermission()` (super_admin bypass) + scope entity di `CrudController::baseQuery()` + `inScope()` untuk context switch. UI hanya menyembunyikan menu; keputusan selalu di server.

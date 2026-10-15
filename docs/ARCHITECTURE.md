# Architecture

Modular monolith Laravel 11 (bukan microservices).

## Pemisahan domain (nyata, bukan sekadar folder)
| Layer | Lokasi |
|---|---|
| Models per domain | `app/Models/{Org,Identity,Master,Crm,Sales,Inventory,Purchasing,Finance,Hr,Pet,Mfg,Thrift,Studio,Rental,Marketing,Gov}/` |
| Business rules/services | `app/Services/`: DocNumberService (sequence terkunci), StockService (ledger+locking), PostingService (balance/idempotent/reversal), ApprovalService (matrix+SoD), AuditService |
| Controllers | `app/Http/Controllers/{domain}/` + `CrudController` generik (scope entitas, audit, guard posted) |
| Routes | `routes/modules/*.php` (org, master, sales, ops, finance_hr, business) + `routes/web.php` |
| Views | `resources/views/modules/<domain>/` + `components/` generik (index/form/show/doc-lines) |
| Tests | `tests/Feature/*`, `tests/Unit/*` (MySQL) |
| Middleware | `perm` (CheckPermission), `ctx` (ActiveContext), throttle login |

Alasan alternatif vs `app/Modules/...`: mengikuti konvensi Laravel (autoload `App\`, artisan, policy discovery) sambil mempertahankan boundary domain lewat namespace + route files + services. Didokumentasikan di sini sesuai mandat.

## Aturan transaksi
- Semua mutasi stok/keuangan dalam `DB::transaction` + `lockForUpdate` untuk resource diperebutkan (saldo, sequence, invoice saat bayar, thrift item).
- Dokumen posted: tidak bisa edit/hapus; koreksi via reversal/status.
- Idempotency: `journals.idempotency_key` unik; service kembalikan ID lama bila kunci sama.
- Error: RuntimeException bisnis → `back()->withErrors(['msg'])`, bukan 500.

## Events/jobs
Antrian DB tersedia (`jobs` table) untuk notifikasi/export berat (pending: worker større). Scheduler: `routes/console.php` (kunci periode bulanan — pending).

## Authorization
`perm:<nama>` di route + `hasPermission()` (super_admin bypass) + scope entity di `CrudController::baseQuery()` + `inScope()` untuk context switch. UI hanya menyembunyikan menu; keputusan selalu di server.

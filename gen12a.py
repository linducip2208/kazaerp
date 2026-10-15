"""Generator 12a: docs part 1 + root files."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

w("README.md", """# KazaERP — Kazaka Group / MZA Holdings Integrated ERP

ERP terpadu multi-entitas: holding, badan usaha, brand, cabang, gudang. Laravel 11 + MySQL + Blade + Tabler lokal (tanpa CDN).

## Stack
- PHP 8.3, Laravel 11.57, MySQL 8.4, Blade, Eloquent
- Tabler 1.6 + Bootstrap 5.3 via npm (dibundel Vite ke `public/build`, tanpa CDN)
- PDF: barryvdh/laravel-dompdf. Export/import: CSV native. PHPUnit 11 (MySQL testing).

## Persyaratan
PHP >= 8.2 (ext: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo), Composer, Node 18+, MySQL 8.

## Instalasi dari awal
```bash
composer install
cp .env.example .env   # lalu isi DB_* (lihat docs/INSTALLATION.md)
php artisan key:generate
mysql -u root -e "CREATE DATABASE kazaerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE kazaerp_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --force
php artisan db:seed --force
npm install && npm run build
php artisan serve
```
Login dev: `admin@kazaka.local` / `Kazaka#2026` (seeder demo; nonaktifkan di production — lihat docs/DEPLOYMENT.md).

## Menjalankan test (MySQL, bukan SQLite)
```bash
php artisan test
```

## Fitur per fase
Fase A Foundation (auth, RBAC 16 role, multi-entitas + konteks, audit, master data) — DONE.
Fase B Core ERP (CRM+konversi, sales order→invoice→bayar, POS+shift, inventory ledger+reservasi, purchasing+three-way matching, approval) — DONE.
Fase C Finance (COA, jurnal balance+idempotent+reversal, AR/AP aging, kas, expense, closing) — DONE.
Fase D HR (karyawan, absensi, payroll komponen configurable + approval + slip PDF) — DONE.
Fase E Bisnis (pet/vet/grooming/boarding/transport, apparel desain versi, BOM+WO terkunci, thrift unik+live, studio, rental anti-bentrok) — DONE (inti fungsional).
Fase F Manajemen (dashboard holding per-entitas, laporan penjualan/inventory/pembelian, register intercompany) — DONE (inti).
Fase G Hardening (throttle login, CSRF, permission middleware, policy server-side, PWA aman, docs) — DONE (inti).

Detail status jujur: `docs/IMPLEMENTATION_STATUS.md`. Keputusan bisnis terbuka: `docs/DECISIONS_AND_OPEN_QUESTIONS.md`.
""")

w(".env.example", """APP_NAME="KazaERP - Kazaka Group"
APP_ENV=local
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://localhost:8000
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kazaerp
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
KAZA_COGS_METHOD=none
""")

w(".editorconfig", """root = true
[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 4
insert_final_newline = true
trim_trailing_whitespace = true
[*.blade.php]
indent_size = 4
[*.{js,css}]
indent_size = 2
""")

w("AGENTS.md", """# AGENT RULES — KAZAKA GROUP / MZA HOLDINGS ERP

- Gunakan Laravel, PHP, MySQL, Blade, dan Tabler sebagai stack utama.
- Jangan mengganti database atau menambahkan framework frontend utama tanpa keputusan eksplisit.
- Jangan memuat aset UI dari CDN (Tabler/Bootstrap via `npm run build` → `public/build`).
- Gunakan timezone Asia/Jakarta dan mata uang IDR. DB menyimpan UTC; tampilkan Asia/Jakarta via `tgl_id()`/`idr()`.
- Jangan menghapus file, data, migration, atau pekerjaan pengguna tanpa pemeriksaan dan perlindungan.
- Semua transaksi stok dan keuangan harus memiliki histori (ledger/jurnal) serta aturan integritas (locking, balance, idempotency).
- Semua query sensitif wajib memeriksa authorization (`perm` middleware + `hasPermission`) dan konteks legal entity (baseQuery scope).
- Jangan membuat menu atau tombol palsu: setiap route harus punya controller + view + operasi DB nyata.
- Jangan mengklaim fitur selesai tanpa implementasi dan bukti pengujian (`docs/TEST_RESULTS.md` dari run nyata).
- Perbarui dokumentasi Markdown setiap kali ada perubahan arsitektur, database, workflow, permission, atau fitur.
- Catat asumsi dan keputusan bisnis yang belum disetujui sebagai `PENDING_BUSINESS_DECISION` di `docs/DECISIONS_AND_OPEN_QUESTIONS.md`.
- Jalankan test relevan setelah perubahan dan perbaiki regresi (`php artisan test` memakai MySQL `kazaerp_testing`).
- Jangan mengekspos secret, password, data pribadi, atau file sensitif (audit log tidak menyimpan secret).
- Jangan mengubah dokumen posted dengan menghapus histori; gunakan reversal atau mekanisme koreksi yang terotorisasi.
- Utamakan implementasi end-to-end yang dapat dijalankan daripada banyak halaman kosong.
""")

w("docs/PRODUCT_OVERVIEW.md", """# Product Overview

## Tujuan
Menyatukan operasional banyak perusahaan/brand/unit usaha Kazaka Group & MZA Holdings dalam satu ERP: penjualan, stok, pembelian, keuangan, HR, layanan pet, apparel, thrift, studio, rental.

## Pengguna
Super Admin, Holding Owner, Executive, Entity Admin, Finance (Controller/Staff), HR, Purchasing, Inventory, Branch Manager, Sales, Cashier, Operations, Operator modul, Auditor, Viewer.

## Ruang lingkup (terimplementasi inti)
Organisasi multi-entitas + konteks aktif; RBAC 16 role/60+ permission; CRM + konversi lead; sales order→delivery→invoice→bayar; POS + shift; inventory ledger + reservasi + partial receipt; purchasing PR→PO→GRN→vendor invoice (three-way matching) ; jurnal balance/idempotent/reversal; AR/AP aging; kas; expense; tutup periode; payroll komponen; pet care/vet/grooming/boarding/transport; desain apparel berversi; BOM + WO terkunci; thrift unik + live order; studio; rental anti-bentrok; voucher/loyalty/membership; approval workflow + SoD; laporan + CSV; import CSV bervalidasi; PDF invoice/payslip; PWA aset半年.

## Batasan
- Blueprint PDF (`Blueprint_ERP_Kazaka_Group_MZA_Holdings.pdf`) TIDAK ditemukan di workspace (dicari di root proyek) — mapping kebutuhan disusun dari Master Build Command; berisiko menyimpang dari dokumen resmi.
- Metode HPP: `none` (stok×harga). FIFO/average butuh keputusan Finance.
- Tidak ada integrasi payment gateway, marketplace, WhatsApp/SMS, GPS live — hanya adapter internal + status jujur.
- PWA hanya cache aset `/build/`; tidak ada transaksi offline.
- Konsolidasi antar-entitas: register + laporan per-entitas; eliminasi otomatis BELUM ada (butuh kebijakan Finance).

## Asumsi
Lihat `docs/DECISIONS_AND_OPEN_QUESTIONS.md`.
""")

w("docs/BLUEPRINT_MAPPING.md", """# Blueprint Mapping

> Blueprint PDF tidak ditemukan di workspace saat build (`D:\\project laravel\\kazaerp`, root dicek, 0 file awal).
> Pemetaan di bawah merujuk ke section Master Build Command (§4–§21) sebagai pengganti sementara.
> Status: `implemented` | `partial` | `pending`.

| Sumber § | Kebutuhan | Modul/Route | Tabel | Test | Status |
|---|---|---|---|---|---|
| §4 | Holding/entity/brand/cabang/gudang + konteks | `org.*`, context switcher | holdings..warehouses | IsolationTest | implemented |
| §5 | Login, role, permission, scope, audit, brute-force | AuthController, `perm`, throttle | roles, permissions, audit_logs, login_activities | AuthTest, IsolationTest | implemented |
| §6 | Customer/supplier/produk/lead→customer | `crm.*`, `master.*`, import | customers..opportunities | BusinessRulesTest | implemented |
| §7 | SO→invoice→bayar, POS+shift, numbering | `sales.*` | sales_orders..cash_movements | SalesFlowTest | implemented |
| §8 | Ledger, reservasi, partial, count, adjust | `inventory.*` | stock_ledgers..quality_checks | InventoryConcurrencyTest | implemented |
| §9 | PR→PO→GRN→VIN, 3-way match, duplikat | `purchasing.*` | purchase_requisitions..purchase_returns | PurchasingMatchTest | implemented |
| §10 | COA, jurnal balance/idempotent/reversal, AR/AP, closing, intercompany | `finance.*` | coas..intercompany_txns | FinancePostingTest | implemented (intercompany: register `partial`) |
| §11 | Karyawan, absensi, payroll configurable | `hr.*` | employees..employee_documents | manual/E2E | implemented (pajak/BPJS configurable, PENDING) |
| §12–13 | Pet, vet, grooming, boarding, transport | `pet.*` | pet_owners..trips | BusinessRulesTest | implemented |
| §14 | Produk/batch/QC/karantina, harga tier | master.products, inventory | products, batches | partial | partial (blokir jual barang karantina: konfigurasi parsial) |
| §15 | Koleksi, desain versi, BOM terkunci, WO | `mfg.*` | collections..work_order_lines | partial | partial (output/variance dasar) |
| §16 | Lot, item unik, live order | `thrift.*` | thrift_lots..live_orders | BusinessRulesTest | implemented (inti; alokasi biaya lot: konfigurasi) |
| §17 | Proyek studio, deliverable, approval | `studio.*` | studio_projects..approvals | — | partial (file private + auth: dasar) |
| §18 | Unit unik, kalender, deposit=liabilitas | `rental.*` | rental_catalogs..rental_txns | BusinessRulesTest | implemented (inti) |
| §19 | Campaign, voucher, loyalty, komisi | `marketing.*`, sales_commissions | campaigns..memberships | — | partial (aturan promo dasar; komisi tercatat) |
| §20 | Approval matrix, SoD, histori | `gov.*` | approval_matrices..histories | FinancePostingTest | implemented |
| §21 | Dashboard holding/finance/ops/HR + laporan | `/`, `reports.*`, `finance.*` | — | BusinessRulesTest | implemented (inti; grafik tren: pending) |
""")

w("docs/REQUIREMENTS.md", """# Requirements

## Functional (ID)
- FR-01 Multi-entitas: holding → legal entity → cabang → gudang; konteks aktif per sesi; isolasi default.
- FR-02 Auth: login/logout, akun nonaktif ditolak, throttle 10/menit, login activity, audit log.
- FR-03 RBAC: 16 role, 60+ permission, middleware `perm`, scope entity/branch.
- FR-04 CRM: lead → opportunity → konversi tanpa duplikasi; follow-up; komplain.
- FR-05 Sales: quotation/SO/DO/invoice/credit/return; snapshot harga; nomor urut aman konkurensi; partial payment; status dari total.
- FR-06 POS: shift buka/tutup + selisih; checkout memotong stok; tanpa shift tidak bisa jual.
- FR-07 Inventory: setiap mutasi → ledger + saldo; reservasi anti-oversell (locking); partial receipt; transfer; count; adjustment via approval.
- FR-08 Purchasing: PR → RFQ → PO (versi) → GRN → vendor invoice; three-way matching (exception→HOLD); deteksi duplikat vendor_ref; retur.
- FR-09 Finance: jurnal wajib balance; idempotent; reversal bukan hapus; periode tutup menolak posting; AR/AP aging; kas; expense approval; budget.
- FR-10 HR: karyawan, absensi, cuti, lembur, payroll dari komponen configurable + approval + slip.
- FR-11 Pet: owner/pet, janji vet, grooming, boarding anti-overcapacity, transport + trip cost.
- FR-12 Apparel/MFG: desain berversi + approval; BOM versi terkunci di WO.
- FR-13 Thrift: item unik; reservasi live anti double-sell.
- FR-14 Rental: unit fisik anti-bentrok termasuk buffer; deposit dicatat bukan pendapatan.
- FR-15 Marketing: campaign, voucher (kuota/periode/minimum), loyalty, membership.
- FR-16 Approval: matrix per doc-type/entitas/level; SoD (pemohon ≠ penyetuju); delegasi tercatat (pending: UI delegasi).
- FR-17 Laporan: filter periode + entitas; export CSV hormat permission.
- FR-18 Import CSV dengan laporan error per baris.
- FR-19 PDF invoice/payslip; print-friendly.
- FR-20 PWA: manifest + SW hanya cache `/build/`.

## Non-functional
- NFR-01 Bahasa Indonesia, timezone Asia/Jakarta, IDR (`idr()`, `tgl_id()`).
- NFR-02 MySQL satu-satunya DB; desimal 18,2 (uang) / 18,4 (qty); FK + unique business key.
- NFR-03 Tanpa CDN; aset lokal via Vite build.
- NFR-04 Error production tanpa stack trace (error pages); secret tidak di-log.
- NFR-05 Test integrasi di MySQL (locking, constraints).

## Acceptance criteria
Lihat `docs/ACCEPTANCE_CHECKLIST.md`.
""")

w("docs/ARCHITECTURE.md", """# Architecture

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

Alasan alternatif vs `app/Modules/...`: mengikuti konvensi Laravel (autoload `App\\`, artisan, policy discovery) sambil mempertahankan boundary domain lewat namespace + route files + services. Didokumentasikan di sini sesuai mandat.

## Aturan transaksi
- Semua mutasi stok/keuangan dalam `DB::transaction` + `lockForUpdate` untuk resource diperebutkan (saldo, sequence, invoice saat bayar, thrift item).
- Dokumen posted: tidak bisa edit/hapus; koreksi via reversal/status.
- Idempotency: `journals.idempotency_key` unik; service kembalikan ID lama bila kunci sama.
- Error: RuntimeException bisnis → `back()->withErrors(['msg'])`, bukan 500.

## Events/jobs
Antrian DB tersedia (`jobs` table) untuk notifikasi/export berat (pending: worker større). Scheduler: `routes/console.php` (kunci periode bulanan — pending).

## Authorization
`perm:<nama>` di route + `hasPermission()` (super_admin bypass) + scope entity di `CrudController::baseQuery()` + `inScope()` untuk context switch. UI hanya menyembunyikan menu; keputusan selalu di server.
""")

w("docs/DATABASE.md", """# Database (MySQL 8.4)

- 173 tabel (lihat migrasi `database/migrations/2026_10_15_*.php`). Semua migrasi rerunnable dari DB kosong (`migrate:fresh` hijau).
- Uang `decimal(18,2)`, qty `decimal(18,4)`. Tidak ada float untuk uang.
- Business key unik: `doc_sequences.seq_key`, `*.number` unik per dokumen, `(legal_entity_id, code/sku)` untuk master, `(supplier_id, vendor_ref)` anti duplikat invoice, `live_orders.thrift_item_id` unik, `stock_balances` unik (wh, produk, varian, batch).
- Index pada FK + kolom query (status, tanggal, ref_type/ref_id).
- Status eksplisit (`draft/submitted/approved/posted/paid/partial/hold/matched/exception/...`) + `doc_status_histories`.
- Soft delete hanya master (holdings, entities, brands, units, branches, categories, customers, suppliers, products, employees, owners, pets). Dokumen posted TIDAK boleh dihapus (guard + reversal).
- Kebijakan waktu: Laravel TIMESTAMP (UTC) → tampil Asia/Jakarta (`config/kazaerp.php`, helper `tgl_id()`).
- HPP: `KAZA_COGS_METHOD=none` — valuasi sederhana; FIFO/average PENDING_BUSINESS_DECISION.
- Backup/restore/retensi: `docs/OPERATIONS_RUNBOOK.md`.
""")

w("docs/ERD.md", """# ERD (Mermaid)

```mermaid
erDiagram
    HOLDINGS ||--o{ LEGAL_ENTITIES : owns
    LEGAL_ENTITIES ||--o{ BRANCHES : has
    LEGAL_ENTITIES ||--o{ ENTITY_OWNERSHIPS : parent
    BRANCHES ||--o{ WAREHOUSES : has
    BRANCHES ||--o{ LOCATIONS : has
    LEGAL_ENTITIES ||--o{ FISCAL_YEARS : has
    FISCAL_YEARS ||--o{ FISCAL_PERIODS : has
    USERS }o--o{ ROLES : has
    ROLES }o--o{ PERMISSIONS : has
    USERS ||--o{ USER_SCOPES : scopes
    USERS ||--o{ AUDIT_LOGS : acts
    CUSTOMERS ||--o{ SALES_ORDERS : places
    SALES_ORDERS ||--o{ SALES_ORDER_LINES : has
    SALES_ORDERS ||--o{ INVOICES : billed
    INVOICES ||--o{ INVOICE_LINES : has
    INVOICES ||--o{ SALES_PAYMENTS : paid
    WAREHOUSES ||--o{ STOCK_LEDGERS : records
    WAREHOUSES ||--o{ STOCK_BALANCES : balances
    PRODUCTS ||--o{ STOCK_LEDGERS : moves
    PURCHASE_REQUISITIONS ||--o{ PURCHASE_ORDERS : sources
    PURCHASE_ORDERS ||--o{ PURCHASE_ORDER_LINES : has
    PURCHASE_ORDERS ||--o{ GOODS_RECEIPTS : received
    PURCHASE_ORDERS ||--o{ VENDOR_INVOICES : billed
    COAS ||--o{ JOURNAL_LINES : posts
    JOURNALS ||--o{ JOURNAL_LINES : has
    EMPLOYEES ||--o{ PAYROLL_LINES : earns
    PAYROLLS ||--o{ PAYROLL_LINES : has
    PET_OWNERS ||--o{ PETS : owns
    PETS ||--o{ VET_APPOINTMENTS : visits
    PETS ||--o{ GROOMING_ORDERS : grooms
    PETS ||--o{ BOARDING_RESERVATIONS : boards
    BOARDING_ROOMS ||--o{ BOARDING_RESERVATIONS : hosts
    TRANSPORT_ORDERS ||--o{ TRIPS : trips
    DESIGNS ||--o{ DESIGN_VERSIONS : versions
    PRODUCTS ||--o{ BOMS : has
    BOMS ||--o{ WORK_ORDERS : locked
    THRIFT_LOTS ||--o{ THRIFT_ITEMS : contains
    THRIFT_ITEMS ||--o{ LIVE_ORDERS : sold
    STUDIO_PROJECTS ||--o{ STUDIO_DELIVERABLES : delivers
    RENTAL_CATALOGS ||--o{ RENTAL_UNITS : has
    RENTAL_UNITS ||--o{ RENTAL_RESERVATIONS : booked
    APPROVALS ||--o{ APPROVAL_HISTORIES : tracks
```
Relasi lintas modul: sales↔inventory (reservasi/issue), purchasing↔inventory (GRN), sales/purchasing↔finance (posting piutang/utang, kas), HR→finance (mapping jurnal payroll: pending), semua→governance (approvals, audit).
""")

w("docs/MODULES.md", """# Modules

## Identity & Organization (`org.*`, users, roles)
Holding, badan usaha (+kepemilikan), brand, unit bisnis, cabang, lokasi, gudang, departemen, cost/profit center, tahun/periode fiskal, rekening bank, config, dokumen legal. Konteks aktif (holding/entity/cabang) via switcher terotorisasi.

## CRM & Master (`crm.*`, `master.*`)
Customer, supplier, kontak, alamat, produk (+varian, barcode), kategori, satuan, pajak, price list, payment terms, channel, lead source, tags, notes, attachments. Lead→opportunity→konversi anti-duplikat.

## Sales & POS (`sales.*`)
Quotation, SO (+approval+reservasi), DO, invoice (+snapshot, posting otomatis), credit note, retur, deposit, pembayaran partial, komisi, POS + shift + selisih kas.

## Inventory (`inventory.*`)
Bin, batch/lot + expiry + QC/karantina, ledger (sumber kebenaran), saldo, GRN/GI, transfer, reservasi, count, adjustment, serial, kartu stok, alert menipis.

## Purchasing (`purchasing.*`)
PR (+darurat beralasan), RFQ + komparasi, PO (+versi/amendment), partial receipt, QC, vendor invoice + three-way matching (exception→HOLD), deteksi duplikat, retur, evaluasi vendor.

## Finance (`finance.*`)
COA per entitas, jurnal (balance/idempotent/reversal), kas/bank, transfer, rekonsiliasi, expense, petty cash, budget, aturan pajak (konfigurasi), approval jurnal, tutup periode, TB/PL/aging/ledger, register intercompany.

## HR (`hr.*`)
Karyawan, jabatan, riwayat, shift, absensi, cuti + saldo, lembur, periode payroll, komponen configurable, hitung + approval + slip PDF.

## Pet & Transport (`pet.*`)
Owner, pet, janji vet, konsultasi (akses peran), treatment, grooming + antrean, kamar + kalender + log harian, transport order + trip + biaya.

## Apparel & MFG (`mfg.*`)
Koleksi, desain + versi file + approval, BOM versi terkunci di WO, output/reject.

## Thrift & Live (`thrift.*`)
Lot + alokasi biaya, sortir/grade, item unik + foto, sesi live, reservasi anti double-sell, deadline bayar, komisi.

## Studio (`studio.*`), Rental (`rental.*`), Marketing (`marketing.*`)
Proyek + deliverable + approval; katalog + unit fisik + kalender + deposit; campaign + voucher + loyalty + membership.

## Governance (`gov.*`)
Matrix approval, antrean, SoD, histori, audit log.

Permission tiap modul: `<modul>.view/create/update/...` (lihat RolesPermissionsSeeder + USER_ROLES.md).
""")

w("docs/FEATURES.md", """# Features (ID, prioritas, status, bukti)

| ID | Fitur | Prioritas | Status | Bukti |
|---|---|---|---|---|
| F-AUTH-01 | Login/logout, akun nonaktif ditolak, throttle | P0 | DONE | AuthController, AuthTest |
| F-AUTH-02 | Konteks entity/cabang terotorisasi | P0 | DONE | switchContext + inScope |
| F-RBAC-01 | 16 role, 60+ permission, middleware | P0 | DONE | RolesPermissionsSeeder, route `perm:` |
| F-RBAC-02 | Isolasi lintas entitas server-side | P0 | DONE | IsolationTest |
| F-ORG-01 | CRUD holding/entity/cabang/gudang | P0 | DONE | org.* E2E 200 |
| F-CRM-01 | CRUD customer/supplier/produk + import CSV | P0 | DONE | ImportController + test validasi |
| F-CRM-02 | Lead convert anti-duplikat | P0 | DONE | BusinessRulesTest |
| F-SAL-01 | SO + numbering aman konkurensi | P0 | DONE | DocNumberService + SalesFlowTest |
| F-SAL-02 | Approve + reservasi stok | P0 | DONE | SalesFlowTest |
| F-SAL-03 | Invoice snapshot + posting + potong stok | P0 | DONE | InvoiceController, E2E verified |
| F-SAL-04 | Partial payment + status dari total | P0 | DONE | pay(), SalesFlowTest |
| F-SAL-05 | Overpayment & oversell ditolak ramah | P0 | DONE | withErrors (fix1) + test |
| F-POS-01 | Shift + checkout + selisih | P1 | DONE | PosController, E2E page 200 |
| F-INV-01 | Ledger + saldo + locking | P0 | DONE | StockService, concurrency test |
| F-INV-02 | Partial receipt PO | P0 | DONE | InventoryConcurrencyTest |
| F-PUR-01 | PR→PO + approval | P0 | DONE | Requisition/PurchaseOrderController |
| F-PUR-02 | Three-way matching + HOLD + duplikat guard | P0 | DONE | PurchasingMatchTest |
| F-FIN-01 | Jurnal balance/idempotent | P0 | DONE | FinancePostingTest |
| F-FIN-02 | Reversal + tutup periode guard | P0 | DONE | FinancePostingTest |
| F-FIN-03 | TB/PL/ledger/aging | P0 | DONE | FinanceReportController, E2E 200 |
| F-FIN-04 | Expense + approval | P1 | DONE | ExpenseController |
| F-HR-01 | Karyawan + absensi | P1 | DONE | hr.* E2E 200 |
| F-HR-02 | Payroll configurable + approval + slip | P1 | DONE | PayrollController (aturan pajak PENDING) |
| F-PET-01 | Owner/pet/vet/grooming | P1 | DONE | pet.* E2E 200 |
| F-PET-02 | Boarding anti-overcapacity | P1 | DONE | BusinessRulesTest |
| F-RNT-01 | Rental anti-bentrok + deposit | P1 | DONE | BusinessRulesTest |
| F-THR-01 | Item unik anti double-sell | P1 | DONE | BusinessRulesTest |
| F-MFG-01 | Desain versi + BOM terkunci | P2 | DONE (inti) | mfg.* E2E 200 |
| F-MKT-01 | Campaign + voucher + loyalty | P2 | DONE (inti) | marketing.* |
| F-GOV-01 | Approval + SoD + histori | P0 | DONE | FinancePostingTest (SoD) |
| F-REP-01 | Laporan + CSV + import validasi | P1 | DONE | Report/ImportController |
| F-UI-01 | Tabler lokal, sidebar per-permission, toast, empty state | P0 | DONE | build 835KB CSS lokal, smoke 22/22 |
| F-PWA-01 | Manifest + SW aset-only | P2 | DONE | public/manifest, sw.js |
| F-DELTA-01 | Grafik tren dashboard, eliminasi intercompany otomatis, worker/queue, approval delegasi UI | P3 | PENDING | ROADMAP |
""")

w("docs/USER_ROLES.md", """# User Roles & Matrix

16 role baseline (dapat dikonfigurasi via `roles` + `role_user`): super_admin, holding_owner, executive,
entity_admin, finance_controller, finance_staff, hr_manager, purchasing_manager, inventory_manager,
branch_manager, sales_staff, cashier, operations_manager, module_operator, auditor, viewer.

Permission groups: org, users, roles, crm, sales (+pos), inventory, purchasing, finance, hr, pet, mfg,
thrift, studio, rental, marketing, reports, governance. Lihat `RolesPermissionsSeeder` untuk daftar 60+ permission.

## Matriks (ringkas; ✓ = diberikan default)
| Kemampuan | super_admin | holding_owner | branch_manager | cashier | module_operator | viewer |
|---|---|---|---|---|---|---|
| Semua data lintas entitas | ✓ | ✓ | — | — | — | view |
| Konteks switch | ✓ | ✓ | cabang sendiri | — | ✓ | — |
| CRM Kelola | ✓ | ✓ | ✓ | — | ✓ | view |
| Sales + approve/void | ✓ | ✓ | buat | — | ✓ | view |
| POS operate/shift | ✓ | — | — | ✓ | — | — |
| Inventory terima/kirim | ✓ | — | — | — | ✓ | view |
| Purchasing approve | ✓ | ✓ | — | — | — | view |
| Finance posting/approve/close | ✓ | — | — | — | — | view |
| Gaji (hr.salary) | ✓ | — | — | — | — | — |
| Payroll proses | ✓ | — | — | — | — | — |
| Approve governance | ✓ | ✓ | — | — | — | view |

Entity scope: `user_scopes(scope_type=legal_entity|branch)`. Branch manager terikat cabang; super_admin/holding_owner/executive/auditor lintas (auditor read-only via viewer-like + audit.view — PENDING: batasi ke read di semua modul).
""")

w("docs/WORKFLOWS.md", """# Workflows

## Sales-to-cash
Quotation → SO (draft→approved + reservasi) → DO → Invoice (posted + potong stok + jurnal AR/pendapatan) → Payment (partial/paid dari total) → [Credit/Return bila perlu].

## Procure-to-pay
PR (submitted + approval; darurat wajib alasan) → RFQ/vendor quotes → PO (approval; versi saat amend) → GRN (partial ok; status PO partial/received) → Vendor invoice (3-way match; exception→HOLD) → bayar (pending: payment AP UI).

## Stock transfer / adjustment
Transfer antar gudang (kirim→terima, in-transit tercatat); adjustment hanya via approval + alasan; count → selisih → adjustment.

## Service order (grooming/vet)
Booking → check-in (kondisi awal) → checklist/progress → selesai → pickup → invoice → feedback.

## Manufacturing
Desain (versi + approval) → BOM (versi) → WO (BOM terkunci) → issue material → output good/reject/rework → FG receipt.

## Rental lifecycle
Katalog → unit fisik → reservasi (cek bentrok + buffer) → fitting/persiapan → serah terima → kembali → inspeksi → denda/kerusakan → settlement deposit → cleaning.

## Payroll
Periode → komponen → hitung (draft) → approval (terkunci) → slip → (mapping jurnal: pending).

## Finance posting & closing
Sumber (invoice/payment/expense) → jurnal balance → TB/PL → tutup periode (posting ditolak) → buka kembali (terotorisasi + tercatat).

## Intercompany
Transaksi from/to entity → register → laporan; eliminasi manual (otomatis: PENDING_BUSINESS_DECISION).
""")

w("docs/FINANCE_RULES.md", """# Finance Rules

- Double-entry: setiap jurnal total debit = kredit (validasi server; selisih > 0.005 ditolak).
- Posting idempotent via `idempotency_key` unik (invoice `inv-{id}`, bayar `pay-{number}`).
- Dokumen posted tidak bisa edit/hapus; koreksi via `PostingService::reverse()` (jurnal lawan + status reversed).
- AR: invoice → payments; pay_status dihitung dari total, bukan dari form. Overpayment ditolak.
- Deposit pelanggan = liabilitas (akun 2200), bukan pendapatan.
- Pajak: konfigurasi (`taxes`, `tax_rules`); tarif contoh PPN 11% pada transaksi — VERIFIKASI ke konsultan pajak sebelum produksi (PENDING).
- HPP: `none` — JANGAN klaim FIFO/average; butuh persetujuan Finance (PENDING_BUSINESS_DECISION).
- Closing: periode tutup menolak posting (dicek di PostingService); buka kembali butuh `finance.close` + alasan tercatat.
- Konsolidasi: dasar per-entitas; eliminasi otomatis BELUM dibangun (PENDING_BUSINESS_DECISION).
""")

w("docs/SECURITY.md", """# Security

## Threat model
Aktor: pengguna internal lintas cabang, mantan karyawan (akun aktif), penyerang kredensial. Aset: data keuangan, gaji, rekam medis, file pelanggan. Batasan: single-server Laravel/MySQL.

## Kontrol terimplementasi
- Auth: hash bcrypt, akun nonaktif ditolak, throttle login 10/menit, session regenerate, CSRF, logout invalidasi.
- Authorization: middleware `perm` + scope entity di semua query sensitif + `inScope()`; super_admin bypass eksplisit.
- Isolasi dibuktikan IsolationTest (404 lintas entitas, payroll 403 untuk cashier).
- Audit: `audit_logs` (aktor, waktu, entitas, aksi, ref, perubahan — tanpa secret) + `login_activities` + `doc_status_histories`.
- File: upload validasi tipe/ukuran (CSV 2MB; desain via storage), download lewat controller terotorisasi (pending: pindahkan ke disk private + signed URL).
- Error: halaman 403/404/500 tanpa stack trace; log server-side saja.
- Secret: `.env` tidak di-commit (hanya `.env.example`); tidak ada secret di log/audit.

## Belum / hardening produksi
HTTPS, header keamanan (CSP/HSTS), 2FA, password policy + reset mandiri, session invalidation massal saat revoke (parsial: cek `is_active` tiap request — pending middleware), rate-limit global, backup terenkripsi, disk private + antivirus upload. Lihat DEPLOYMENT + ROADMAP.
""")
print("gen12a done")

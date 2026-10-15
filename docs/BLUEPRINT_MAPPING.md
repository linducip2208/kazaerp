# Blueprint Mapping

> Blueprint PDF tidak ditemukan di workspace saat build (`D:\project laravel\kazaerp`, root dicek, 0 file awal).
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

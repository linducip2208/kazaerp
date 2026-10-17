<?php

namespace Database\Seeders;

use App\Services\ApprovalService;
use App\Services\PostingService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo end-to-end yang IDEMPOTEN untuk database dev/test KHUSUS demo.
 *
 * - DILARANG dijalankan di production (guard environment + guard flag).
 * - Aman diulang: setiap dokumen memakai nomor demo tetap dan dicek
 *   keberadaannya dulu; jurnal memakai idempotency key stabil.
 * - Alur: stok awal → SO → approve → invoice → lunas →
 *         PO → approve → GRN → vendor invoice (matched) → bayar parsial →
 *         karyawan + absensi + 1 approval pending + 1 item stok menipis.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Demo#2026';

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->call([RolesPermissionsSeeder::class, OrgSeeder::class]);

        $eid = DB::table('legal_entities')->where('code', 'MZA-HOLD')->value('id');
        $br = DB::table('branches')->where(['legal_entity_id' => $eid, 'code' => 'PST'])->value('id');
        $wh = DB::table('warehouses')->where('branch_id', $br)->value('id');
        $hid = DB::table('holdings')->where('code', 'KZK')->value('id');

        $admin = $this->demoUser('Administrator', 'admin@kazaka.local', 'super_admin', $hid, $eid, $br);
        $finance = $this->demoUser('Finance Demo', 'finance.demo@kazaka.local', 'finance_controller', $hid, $eid, $br);
        $this->demoUser('Gudang Demo', 'gudang.demo@kazaka.local', 'inventory_manager', $hid, $eid, $br);
        $this->demoUser('Kasir Demo', 'kasir.demo@kazaka.local', 'cashier', $hid, $eid, $br);
        $this->demoUser('HR Demo', 'hr.demo@kazaka.local', 'hr_manager', $hid, $eid, $br);
        $this->demoUser('Viewer Demo', 'viewer.demo@kazaka.local', 'viewer', $hid, $eid, $br);

        Auth::setUser($admin);
        $today = now()->toDateString();

        // ---- Master demo ----
        $cat = DB::table('categories')->where(['kind' => 'product', 'code' => 'UMUM'])->first();
        $catId = $cat ? $cat->id : DB::table('categories')->insertGetId(['kind' => 'product', 'code' => 'UMUM', 'name' => 'Umum', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $uom = DB::table('uoms')->where('code', 'pcs')->value('id');
        $p1 = $this->demoProduct($eid, $catId, $uom, 'DEMO-001', 'Produk Demo', 50000, 75000);
        $p2 = $this->demoProduct($eid, $catId, $uom, 'DEMO-002', 'Produk Demo Grosir', 30000, 45000);
        $p3 = $this->demoProduct($eid, $catId, $uom, 'DEMO-003', 'Produk Stok Menipis', 20000, 35000);
        $cust = $this->demoCustomer($eid, $br);
        $sup = $this->demoSupplier($eid);
        $emp = $this->demoEmployee($eid, $br);

        // ---- Stok awal (idempotent via ref demo-opening) ----
        if (! DB::table('stock_ledgers')->where(['ref_type' => 'demo-opening', 'ref_id' => 0])->exists()) {
            StockService::move(['warehouse_id' => $wh, 'product_id' => $p1, 'qty' => 100, 'ref_type' => 'demo-opening', 'ref_id' => 0, 'unit_cost' => 50000, 'note' => 'Stok awal demo']);
            StockService::move(['warehouse_id' => $wh, 'product_id' => $p2, 'qty' => 50, 'ref_type' => 'demo-opening', 'ref_id' => 0, 'unit_cost' => 30000, 'note' => 'Stok awal demo']);
            StockService::move(['warehouse_id' => $wh, 'product_id' => $p3, 'qty' => 2, 'ref_type' => 'demo-opening', 'ref_id' => 0, 'unit_cost' => 20000, 'note' => 'Stok menipis demo']);
        }

        $this->demoSalesCycle($eid, $br, $wh, $cust->id, $p1, $admin->id, $today);
        $this->demoPurchaseCycle($eid, $br, $wh, $sup->id, $p1, $admin, $finance, $today);
        $this->demoHr($eid, $br, $emp, $today);
        $this->demoMfg($eid, $br, $wh, $catId, $uom, $admin->id, $today);
        $this->demoRental($eid, $br, $cust->id, $today);
        $this->demoThrift($eid, $today);
        $this->demoStudio($eid, $cust->id);
        $this->demoPet($eid, $br, $cust->id, $today);
    }

    // ---------- helpers ----------

    private function demoUser(string $name, string $email, string $role, $hid, $eid, $br)
    {
        $row = DB::table('users')->where('email', $email)->first();
        if (! $row) {
            $id = DB::table('users')->insertGetId(['name' => $name, 'email' => $email,
                'password' => Hash::make(self::PASSWORD), 'is_active' => true,
                'holding_id' => $hid, 'legal_entity_id' => $eid, 'branch_id' => $br,
                'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('users')->where('id', $id)->first();
        } else {
            // Idempotent demo = kredensial dikenal: samakan password bila pernah diubah manual.
            DB::table('users')->where('id', $row->id)->update([
                'password' => Hash::make(self::PASSWORD), 'is_active' => true, 'updated_at' => now()]);
        }
        $rid = DB::table('roles')->where('name', $role)->value('id');
        if ($rid) {
            DB::table('role_user')->updateOrInsert(['role_id' => $rid, 'user_id' => $row->id], []);
        }

        return \App\Models\User::find($row->id);
    }

    private function demoProduct($eid, $catId, $uom, string $sku, string $name, float $cost, float $sell): int
    {
        $row = DB::table('products')->where(['legal_entity_id' => $eid, 'sku' => $sku])->first();
        if ($row) {
            return (int) $row->id;
        }

        return DB::table('products')->insertGetId(['legal_entity_id' => $eid, 'category_id' => $catId,
            'uom_id' => $uom, 'sku' => $sku, 'name' => $name, 'kind' => 'goods',
            'cost_price' => $cost, 'sell_price' => $sell, 'is_sellable' => true,
            'is_purchasable' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function demoCustomer($eid, $br)
    {
        $row = DB::table('customers')->where(['legal_entity_id' => $eid, 'code' => 'CUST-001'])->first();
        if (! $row) {
            $id = DB::table('customers')->insert(['legal_entity_id' => $eid, 'branch_id' => $br, 'code' => 'CUST-001',
                'name' => 'Pelanggan Demo', 'kind' => 'retail', 'tier' => 'retail', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('customers')->where('id', $id)->first();
        }

        return $row;
    }

    private function demoSupplier($eid)
    {
        $row = DB::table('suppliers')->where(['legal_entity_id' => $eid, 'code' => 'SUP-001'])->first();
        if (! $row) {
            $id = DB::table('suppliers')->insertGetId(['legal_entity_id' => $eid, 'code' => 'SUP-001',
                'name' => 'Supplier Demo', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('suppliers')->where('id', $id)->first();
        }

        return $row;
    }

    private function demoEmployee($eid, $br): int
    {
        $row = DB::table('employees')->where(['legal_entity_id' => $eid, 'nip' => 'DEMO-001'])->first();
        if ($row) {
            return (int) $row->id;
        }

        return DB::table('employees')->insertGetId(['legal_entity_id' => $eid, 'branch_id' => $br,
            'nip' => 'DEMO-001', 'name' => 'Karyawan Demo', 'join_date' => now()->toDateString(),
            'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function coa($eid, string $code)
    {
        return DB::table('coas')->where(['legal_entity_id' => $eid, 'code' => $code])->value('id');
    }

    // ---------- siklus penjualan: SO → approve → invoice → lunas ----------

    private function demoSalesCycle($eid, $br, $wh, int $custId, int $prodId, int $adminId, string $today): void
    {
        if (DB::table('sales_orders')->where('number', 'DEMO-SO-001')->exists()) {
            return;
        }
        DB::transaction(function () use ($eid, $br, $wh, $custId, $prodId, $adminId, $today) {
            // SO 5 × 75.000
            $sub = 5 * 75000;
            $tax = $sub * 0.11;
            $grand = $sub + $tax;
            $soId = DB::table('sales_orders')->insertGetId(['legal_entity_id' => $eid, 'branch_id' => $br,
                'number' => 'DEMO-SO-001', 'date' => $today, 'customer_id' => $custId,
                'paid_total' => 0, 'status' => 'approved', 'subtotal' => $sub, 'discount' => 0,
                'tax' => $tax, 'grand_total' => $grand, 'note' => 'SO demo',
                'created_by' => $adminId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('sales_order_lines')->insert(['sales_order_id' => $soId, 'product_id' => $prodId,
                'snapshot_name' => 'Produk Demo', 'qty' => 5, 'price' => 75000, 'discount' => 0,
                'tax_rate' => 11, 'line_total' => $sub * 1.11, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('doc_status_histories')->insert(['doc_type' => 'sales_orders', 'doc_id' => $soId,
                'from_status' => null, 'to_status' => 'draft', 'actor_id' => $adminId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('doc_status_histories')->insert(['doc_type' => 'sales_orders', 'doc_id' => $soId,
                'from_status' => 'draft', 'to_status' => 'approved', 'actor_id' => $adminId, 'created_at' => now(), 'updated_at' => now()]);
            StockService::reserve($wh, $prodId, 5);

            // Invoice 2 × 75.000 (lepas reservasi 2, keluarkan stok 2)
            $isub = 2 * 75000;
            $itax = $isub * 0.11;
            $igrand = $isub + $itax;
            $invId = DB::table('invoices')->insertGetId(['legal_entity_id' => $eid, 'branch_id' => $br,
                'number' => 'DEMO-INV-001', 'date' => $today, 'customer_id' => $custId, 'sales_order_id' => $soId,
                'due_date' => now()->addDays(14)->toDateString(), 'paid_total' => 0, 'pay_status' => 'unpaid',
                'status' => 'posted', 'subtotal' => $isub, 'discount' => 0, 'tax' => $itax,
                'grand_total' => $igrand, 'created_by' => $adminId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('invoice_lines')->insert(['invoice_id' => $invId, 'product_id' => $prodId,
                'snapshot_name' => 'Produk Demo', 'qty' => 2, 'price' => 75000, 'discount' => 0,
                'tax_rate' => 11, 'line_total' => $igrand, 'created_at' => now(), 'updated_at' => now()]);
            StockService::release($wh, $prodId, 2);
            StockService::move(['warehouse_id' => $wh, 'product_id' => $prodId, 'qty' => -2,
                'ref_type' => 'invoice', 'ref_id' => $invId, 'note' => 'INV DEMO-INV-001']);
            if ($this->coa($eid, '1200') && $this->coa($eid, '4000')) {
                PostingService::postJournal(['legal_entity_id' => $eid, 'branch_id' => $br, 'date' => $today,
                    'number' => 'J-DEMO-INV-001', 'description' => 'Penjualan DEMO-INV-001',
                    'source_type' => 'invoice', 'source_id' => $invId],
                    [['coa_id' => $this->coa($eid, '1200'), 'debit' => $igrand, 'credit' => 0],
                     ['coa_id' => $this->coa($eid, '4000'), 'debit' => 0, 'credit' => $igrand]], 'demo-inv-001');
            }

            // Pelunasan penuh
            DB::table('sales_payments')->insert(['legal_entity_id' => $eid, 'branch_id' => $br,
                'number' => 'DEMO-PAY-001', 'date' => $today, 'invoice_id' => $invId, 'customer_id' => $custId,
                'method' => 'cash', 'amount' => $igrand, 'created_by' => $adminId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('invoices')->where('id', $invId)->update(['paid_total' => $igrand, 'pay_status' => 'paid']);
            \App\Services\VoucherService::accrueOnPaid($invId);
            \App\Services\CommissionService::accrue($invId);
            if ($this->coa($eid, '1000') && $this->coa($eid, '1200')) {
                PostingService::postJournal(['legal_entity_id' => $eid, 'branch_id' => $br, 'date' => $today,
                    'number' => 'J-DEMO-PAY-001', 'description' => 'Bayar DEMO-INV-001',
                    'source_type' => 'sales_payment', 'source_id' => $invId],
                    [['coa_id' => $this->coa($eid, '1000'), 'debit' => $igrand, 'credit' => 0],
                     ['coa_id' => $this->coa($eid, '1200'), 'debit' => 0, 'credit' => $igrand]], 'demo-pay-001');
            }
        });
    }

    // ---------- siklus pembelian: PO → GRN → VIN (matched) → bayar parsial ----------

    private function demoPurchaseCycle($eid, $br, $wh, int $supId, int $prodId, $admin, $finance, string $today): void
    {
        // PO-002 draft + approval PENDING (mengisi panel "Perlu perhatian" dashboard)
        if (! DB::table('purchase_orders')->where('number', 'DEMO-PO-002')->exists()) {
            DB::transaction(function () use ($eid, $br, $supId, $prodId, $admin, $today) {
                Auth::setUser($admin);
                $sub = 4 * 50000;
                $grand = $sub * 1.11;
                $poId = DB::table('purchase_orders')->insertGetId(['legal_entity_id' => $eid, 'branch_id' => $br,
                    'number' => 'DEMO-PO-002', 'date' => $today, 'supplier_id' => $supId,
                    'version' => 1, 'status' => 'draft', 'subtotal' => $sub, 'tax' => $sub * 0.11,
                    'grand_total' => $grand, 'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('purchase_order_lines')->insert(['purchase_order_id' => $poId, 'product_id' => $prodId,
                    'qty' => 4, 'qty_received' => 0, 'price' => 50000, 'line_total' => $sub,
                    'created_at' => now(), 'updated_at' => now()]);
                ApprovalService::request('purchase_order', $poId, (int) $eid, $grand);
            });
        }

        if (DB::table('purchase_orders')->where('number', 'DEMO-PO-001')->exists()) {
            return;
        }
        DB::transaction(function () use ($eid, $br, $wh, $supId, $prodId, $admin, $finance, $today) {
            // PO 10 × 50.000 → approved (diputuskan finance, bukan pemohon)
            Auth::setUser($admin);
            $sub = 10 * 50000;
            $grand = $sub * 1.11;
            $poId = DB::table('purchase_orders')->insertGetId(['legal_entity_id' => $eid, 'branch_id' => $br,
                'number' => 'DEMO-PO-001', 'date' => $today, 'supplier_id' => $supId,
                'version' => 1, 'status' => 'draft', 'subtotal' => $sub, 'tax' => $sub * 0.11,
                'grand_total' => $grand, 'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('purchase_order_lines')->insert(['purchase_order_id' => $poId, 'product_id' => $prodId,
                'qty' => 10, 'qty_received' => 0, 'price' => 50000, 'line_total' => $sub,
                'created_at' => now(), 'updated_at' => now()]);
            $apId = ApprovalService::request('purchase_order', $poId, (int) $eid, $grand);
            Auth::setUser($finance);
            ApprovalService::decide($apId, 'approved', 'Setuju (demo)');
            DB::table('purchase_orders')->where('id', $poId)->update(['status' => 'approved']);
            Auth::setUser($admin);

            // GRN penuh → stok masuk + PO received
            $grn = DB::table('goods_receipts')->insertGetId(['legal_entity_id' => $eid, 'branch_id' => $br,
                'warehouse_id' => $wh, 'number' => 'DEMO-GRN-001', 'date' => $today,
                'ref_type' => 'purchase_order', 'ref_id' => $poId, 'status' => 'posted',
                'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('goods_receipt_lines')->insert(['goods_receipt_id' => $grn, 'product_id' => $prodId,
                'batch_id' => null, 'qty_ordered' => 10, 'qty_received' => 10, 'unit_cost' => 50000,
                'created_at' => now(), 'updated_at' => now()]);
            StockService::move(['warehouse_id' => $wh, 'product_id' => $prodId, 'qty' => 10,
                'ref_type' => 'goods_receipt', 'ref_id' => $grn, 'unit_cost' => 50000]);
            DB::table('purchase_order_lines')->where(['purchase_order_id' => $poId, 'product_id' => $prodId])
                ->update(['qty_received' => 10]);
            DB::table('purchase_orders')->where('id', $poId)->update(['status' => 'received']);

            // Vendor invoice (matched karena GRN penuh) → bayar parsial
            $vId = DB::table('vendor_invoices')->insertGetId(['legal_entity_id' => $eid, 'branch_id' => $br,
                'purchase_order_id' => $poId, 'supplier_id' => $supId, 'number' => 'DEMO-VIN-001',
                'vendor_ref' => 'DEMO-V-001', 'date' => $today, 'due_date' => now()->addDays(30)->toDateString(),
                'grand_total' => $grand, 'paid_total' => 0, 'status' => 'draft', 'match_status' => 'matched',
                'created_at' => now(), 'updated_at' => now()]);
            DB::table('vendor_invoice_lines')->insert(['vendor_invoice_id' => $vId, 'product_id' => $prodId,
                'qty' => 10, 'price' => 50000, 'line_total' => $sub, 'created_at' => now(), 'updated_at' => now()]);
            $pay = 255000;
            DB::table('ap_payments')->insert(['legal_entity_id' => $eid, 'branch_id' => $br,
                'number' => 'DEMO-AP-001', 'date' => $today, 'vendor_invoice_id' => $vId,
                'supplier_id' => $supId, 'method' => 'transfer', 'amount' => $pay,
                'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('vendor_invoices')->where('id', $vId)->update(['paid_total' => $pay, 'status' => 'partial']);
            if ($this->coa($eid, '2000') && $this->coa($eid, '1000')) {
                PostingService::postJournal(['legal_entity_id' => $eid, 'branch_id' => $br, 'date' => $today,
                    'number' => 'J-DEMO-AP-001', 'description' => 'Bayar utang DEMO-VIN-001',
                    'source_type' => 'ap_payment', 'source_id' => $vId],
                    [['coa_id' => $this->coa($eid, '2000'), 'debit' => $pay, 'credit' => 0],
                     ['coa_id' => $this->coa($eid, '1000'), 'debit' => 0, 'credit' => $pay]], 'demo-appay-001');
            }
        });
    }

    // ---------- HR minimal: absensi hari ini ----------

    private function demoHr($eid, $br, int $empId, string $today): void
    {
        DB::table('attendances')->updateOrInsert(['employee_id' => $empId, 'date' => $today],
            ['check_in' => '08:00:00', 'status' => 'present', 'created_at' => now(), 'updated_at' => now()]);
    }

    // ---------- MFG: desain → BOM → WO selesai + variance ----------

    private function demoMfg($eid, $br, $wh, $catId, $uom, int $adminId, string $today): void
    {
        if (DB::table('work_orders')->where('number', 'DEMO-WO-001')->exists()) {
            return;
        }
        DB::transaction(function () use ($eid, $br, $wh, $catId, $uom, $adminId, $today) {
            $mat = $this->demoProduct($eid, $catId, $uom, 'DEMO-MAT-001', 'Kain Demo', 5000, 0);
            $fg = $this->demoProduct($eid, $catId, $uom, 'DEMO-FG-001', 'Kaos Jadi Demo', 0, 120000);
            if (! DB::table('stock_ledgers')->where(['ref_type' => 'demo-opening-mat', 'ref_id' => 0])->exists()) {
                StockService::move(['warehouse_id' => $wh, 'product_id' => $mat, 'qty' => 50,
                    'ref_type' => 'demo-opening-mat', 'ref_id' => 0, 'unit_cost' => 5000, 'note' => 'Stok awal material demo']);
            }

            $designId = DB::table('designs')->where(['legal_entity_id' => $eid, 'code' => 'DEMO-DSN-001'])->value('id');
            if (! $designId) {
                $designId = DB::table('designs')->insertGetId(['legal_entity_id' => $eid, 'code' => 'DEMO-DSN-001',
                    'name' => 'Desain Kaos Demo', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
                DB::table('design_versions')->insert(['design_id' => $designId, 'version' => 1,
                    'status' => 'approved', 'approved_by' => $adminId, 'created_at' => now(), 'updated_at' => now()]);
            }

            $bom = DB::table('boms')->where(['legal_entity_id' => $eid, 'product_id' => $fg, 'version' => 1])->first();
            if (! $bom) {
                $bomId = DB::table('boms')->insertGetId(['legal_entity_id' => $eid, 'product_id' => $fg,
                    'version' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
                DB::table('bom_lines')->insert(['bom_id' => $bomId, 'material_id' => $mat, 'qty' => 1,
                    'created_at' => now(), 'updated_at' => now()]);
            } else {
                $bomId = $bom->id;
            }

            // WO 10 pcs: snapshot standar @5000 lalu biaya aktual naik @6000 → variance −10.000
            $woId = DB::table('work_orders')->insertGetId(['legal_entity_id' => $eid, 'number' => 'DEMO-WO-001',
                'date' => $today, 'bom_id' => $bomId, 'bom_version_locked' => 1, 'warehouse_id' => $wh,
                'qty_planned' => 10, 'status' => 'released', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('work_order_lines')->insert(['work_order_id' => $woId, 'material_id' => $mat,
                'qty_required' => 10, 'qty_issued' => 0, 'unit_cost' => 5000, 'issued_unit_cost' => 0,
                'created_at' => now(), 'updated_at' => now()]);
            DB::table('doc_status_histories')->insert(['doc_type' => 'work_orders', 'doc_id' => $woId,
                'from_status' => null, 'to_status' => 'released', 'actor_id' => $adminId, 'created_at' => now(), 'updated_at' => now()]);

            DB::table('products')->where('id', $mat)->update(['cost_price' => 6000]);
            StockService::move(['warehouse_id' => $wh, 'product_id' => $mat, 'qty' => -10,
                'ref_type' => 'work_order_issue', 'ref_id' => $woId, 'unit_cost' => 6000, 'note' => 'WO DEMO-WO-001']);
            DB::table('work_order_lines')->where('work_order_id', $woId)
                ->update(['qty_issued' => 10, 'issued_unit_cost' => 6000, 'updated_at' => now()]);
            DB::table('work_orders')->where('id', $woId)->update(['status' => 'in_progress', 'started_at' => now(), 'updated_at' => now()]);
            DB::table('doc_status_histories')->insert(['doc_type' => 'work_orders', 'doc_id' => $woId,
                'from_status' => 'released', 'to_status' => 'in_progress', 'actor_id' => $adminId,
                'note' => 'Material issued (demo)', 'created_at' => now(), 'updated_at' => now()]);

            StockService::move(['warehouse_id' => $wh, 'product_id' => $fg, 'qty' => 10,
                'ref_type' => 'work_order_output', 'ref_id' => $woId, 'unit_cost' => 6000, 'note' => 'WO DEMO-WO-001 good']);
            DB::table('work_orders')->where('id', $woId)->update(['qty_good' => 10, 'status' => 'completed',
                'completed_at' => now(), 'updated_at' => now()]);
            \App\Http\Controllers\Mfg\WorkOrderController::recalcCost($woId);
            DB::table('doc_status_histories')->insert(['doc_type' => 'work_orders', 'doc_id' => $woId,
                'from_status' => 'in_progress', 'to_status' => 'completed', 'actor_id' => $adminId,
                'note' => 'Output demo: good 10', 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    // ---------- Rental: katalog → unit → reservasi + deposit ----------

    private function demoRental($eid, $br, int $custId, string $today): void
    {
        if (DB::table('rental_reservations')->where('number', 'DEMO-RNT-001')->exists()) {
            return;
        }
        DB::transaction(function () use ($eid, $custId, $today) {
            $cat = DB::table('rental_catalogs')->where(['legal_entity_id' => $eid, 'code' => 'DEMO-RENT-001'])->first();
            $catId = $cat ? $cat->id : DB::table('rental_catalogs')->insertGetId(['legal_entity_id' => $eid,
                'code' => 'DEMO-RENT-001', 'name' => 'Tenda Demo', 'rate' => 250000, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now()]);
            $unit = DB::table('rental_units')->where('code', 'DEMO-UNIT-001')->first();
            $unitId = $unit ? $unit->id : DB::table('rental_units')->insertGetId(['rental_catalog_id' => $catId,
                'code' => 'DEMO-UNIT-001', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('rental_reservations')->insert(['legal_entity_id' => $eid, 'number' => 'DEMO-RNT-001',
                'rental_unit_id' => $unitId, 'customer_id' => $custId,
                'start_date' => now()->addDays(7)->toDateString(), 'end_date' => now()->addDays(9)->toDateString(),
                'rate' => 250000, 'deposit' => 100000, 'status' => 'reserved',
                'created_at' => now(), 'updated_at' => now()]);
        });
    }

    // ---------- Thrift: lot → 2 item unik ----------

    private function demoThrift($eid, string $today): void
    {
        if (DB::table('thrift_lots')->where('number', 'DEMO-LOT-001')->exists()) {
            return;
        }
        DB::transaction(function () use ($eid, $today) {
            $lot = DB::table('thrift_lots')->insertGetId(['legal_entity_id' => $eid, 'number' => 'DEMO-LOT-001',
                'date' => $today, 'acquisition_cost' => 1000000, 'status' => 'received',
                'created_at' => now(), 'updated_at' => now()]);
            DB::table('thrift_items')->insert([
                ['thrift_lot_id' => $lot, 'code' => 'DEMO-THR-001', 'name' => 'Denim Demo 1',
                    'cost_share' => 500000, 'price' => 150000, 'status' => 'sorted',
                    'created_at' => now(), 'updated_at' => now()],
                ['thrift_lot_id' => $lot, 'code' => 'DEMO-THR-002', 'name' => 'Denim Demo 2',
                    'cost_share' => 500000, 'price' => 250000, 'status' => 'sorted',
                    'created_at' => now(), 'updated_at' => now()],
            ]);
        });
    }

    // ---------- Studio: proyek + deliverable v1 ----------

    private function demoStudio($eid, int $custId): void
    {
        $proj = DB::table('studio_projects')->where(['legal_entity_id' => $eid, 'title' => 'Katalog Demo'])->first();
        $projId = $proj ? $proj->id : DB::table('studio_projects')->insertGetId(['legal_entity_id' => $eid,
            'customer_id' => $custId, 'title' => 'Katalog Demo', 'brief' => 'Paket katalog demo (idempotent)',
            'status' => 'brief', 'price' => 5000000, 'cost' => 2000000,
            'created_at' => now(), 'updated_at' => now()]);
        if (! DB::table('studio_deliverables')->where(['studio_project_id' => $projId, 'version' => 1])->exists()) {
            DB::table('studio_deliverables')->insert(['studio_project_id' => $projId, 'title' => 'Draft 1',
                'version' => 1, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    // ---------- Pet: pemilik + hewan + grooming + boarding ----------

    private function demoPet($eid, $br, int $custId, string $today): void
    {
        DB::transaction(function () use ($eid, $br, $custId, $today) {
            $owner = DB::table('pet_owners')->where(['legal_entity_id' => $eid, 'name' => 'Pemilik Demo'])->first();
            $ownerId = $owner ? $owner->id : DB::table('pet_owners')->insertGetId(['legal_entity_id' => $eid,
                'customer_id' => $custId, 'name' => 'Pemilik Demo', 'phone' => '081200000001',
                'created_at' => now(), 'updated_at' => now()]);
            $pet = DB::table('pets')->where(['pet_owner_id' => $ownerId, 'name' => 'Momo Demo'])->first();
            $petId = $pet ? $pet->id : DB::table('pets')->insertGetId(['pet_owner_id' => $ownerId,
                'name' => 'Momo Demo', 'species' => 'cat', 'breed' => 'Anggora',
                'created_at' => now(), 'updated_at' => now()]);

            $svc = DB::table('grooming_services')->where(['legal_entity_id' => $eid, 'code' => 'DEMO-GRM-001'])->first();
            $svcId = $svc ? $svc->id : DB::table('grooming_services')->insertGetId(['legal_entity_id' => $eid,
                'code' => 'DEMO-GRM-001', 'name' => 'Mandi Demo', 'price' => 80000, 'duration_min' => 60,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            if (! DB::table('grooming_orders')->where('number', 'DEMO-GRM-ORD-001')->exists()) {
                DB::table('grooming_orders')->insert(['legal_entity_id' => $eid, 'branch_id' => $br,
                    'number' => 'DEMO-GRM-ORD-001', 'date' => $today, 'pet_id' => $petId,
                    'grooming_service_id' => $svcId, 'status' => 'queued', 'grand_total' => 80000,
                    'created_at' => now(), 'updated_at' => now()]);
            }

            $room = DB::table('boarding_rooms')->where(['branch_id' => $br, 'code' => 'D-ROOM-1'])->first();
            $roomId = $room ? $room->id : DB::table('boarding_rooms')->insertGetId(['branch_id' => $br,
                'code' => 'D-ROOM-1', 'name' => 'Kamar Demo 1', 'capacity' => 5, 'rate' => 100000,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            if (! DB::table('boarding_reservations')->where('number', 'DEMO-BRD-001')->exists()) {
                DB::table('boarding_reservations')->insert(['legal_entity_id' => $eid, 'branch_id' => $br,
                    'number' => 'DEMO-BRD-001', 'pet_id' => $petId, 'boarding_room_id' => $roomId,
                    'check_in' => now()->addDay()->toDateString(), 'check_out' => now()->addDays(3)->toDateString(),
                    'deposit' => 50000, 'grand_total' => 200000, 'status' => 'reserved',
                    'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }
}

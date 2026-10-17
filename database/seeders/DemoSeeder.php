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
}

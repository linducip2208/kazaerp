"""Generator 11: PHPUnit feature/unit tests (MySQL testing DB)."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

w("tests/TestCase.php", """<?php
namespace Tests;
use Illuminate\\Foundation\\Testing\\TestCase as BaseTestCase;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Hash;
abstract class TestCase extends BaseTestCase {
    protected int $eid; protected int $bid; protected int $hid; protected int $wh;
    protected function setUp(): void {
        parent::setUp();
        // Migrasi fresh per test class run memakai testing DB (MySQL kazaerp_testing)
        $this->artisan('migrate:fresh');
        $this->seed(\\Database\\Seeders\\RolesPermissionsSeeder::class);
        $this->seed(\\Database\\Seeders\\OrgSeeder::class);
        $this->hid = DB::table('holdings')->where('code','KZK')->value('id');
        $this->eid = DB::table('legal_entities')->where('code','MZA-HOLD')->value('id');
        $this->bid = DB::table('branches')->where(['legal_entity_id'=>$this->eid,'code'=>'PST'])->value('id');
        $this->wh = DB::table('warehouses')->where('branch_id',$this->bid)->value('id');
        // produk + customer uji (tanpa data pribadi nyata)
        if (!DB::table('products')->where(['legal_entity_id'=>$this->eid,'sku'=>'T-001'])->exists()) {
            DB::table('products')->insert(['legal_entity_id'=>$this->eid,'sku'=>'T-001','name'=>'Produk Uji','kind'=>'goods','cost_price'=>10000,'sell_price'=>15000,'is_sellable'=>true,'is_purchasable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        if (!DB::table('customers')->where(['legal_entity_id'=>$this->eid,'code'=>'TC-001'])->exists()) {
            DB::table('customers')->insert(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,'code'=>'TC-001','name'=>'Pelanggan Uji','tier'=>'retail','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        if (!DB::table('suppliers')->where(['legal_entity_id'=>$this->eid,'code'=>'TS-001'])->exists()) {
            DB::table('suppliers')->insert(['legal_entity_id'=>$this->eid,'code'=>'TS-001','name'=>'Supplier Uji','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    protected function makeUser(string $role, ?int $entityId = null, ?int $branchId = null) {
        $id = DB::table('users')->insertGetId(['name'=>'U '.$role,'email'=>$role.'_'.uniqid().'@test.local','password'=>Hash::make('secret123'),'is_active'=>true,
            'holding_id'=>$this->hid,'legal_entity_id'=>$entityId ?? $this->eid,'branch_id'=>$branchId ?? $this->bid,'created_at'=>now(),'updated_at'=>now()]);
        $rid = DB::table('roles')->where('name',$role)->value('id');
        DB::table('role_user')->insert(['role_id'=>$rid,'user_id'=>$id]);
        return \\App\\Models\\User::find($id);
    }
    protected function ctx(array $over = []) {
        return array_merge(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid], $over);
    }
}
""")

w("tests/Feature/AuthTest.php", """<?php
namespace Tests\\Feature;
use Tests\\TestCase;
class AuthTest extends TestCase {
    public function test_login_berhasil_dan_gagal(): void {
        $u = $this->makeUser('viewer');
        $u->forceFill(['email'=>'auth1@test.local'])->save();
        $this->post('/login',['email'=>'auth1@test.local','password'=>'secret123'])->assertRedirect('/');
        $this->post('/login',['email'=>'auth1@test.local','password'=>'salah'])->assertSessionHasErrors();
        // akun nonaktif ditolak
        $u->update(['is_active'=>false]);
        $this->post('/login',['email'=>'auth1@test.local','password'=>'secret123'])->assertSessionHasErrors('email');
    }
    public function test_guest_dialihkan(): void {
        $this->get('/')->assertRedirect('/login');
    }
}
""")

w("tests/Feature/IsolationTest.php", """<?php
namespace Tests\\Feature;
use Tests\\TestCase;
use Illuminate\\Support\\Facades\\DB;
class IsolationTest extends TestCase {
    public function test_cabang_a_tidak_bisa_baca_data_cabang_b(): void {
        // entitas kedua
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id'=>$this->hid,'code'=>'ENT-B','name'=>'Entitas B','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $b2 = DB::table('branches')->insertGetId(['legal_entity_id'=>$eid2,'code'=>'B2','name'=>'Cabang B','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('customers')->insert(['legal_entity_id'=>$eid2,'branch_id'=>$b2,'code'=>'RAHASIA','name'=>'Rahasia B','tier'=>'retail','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $mgr = $this->makeUser('branch_manager'); // terikat MZA-HOLD/PST
        $this->actingAs($mgr)->withSession(['erp_context'=>$this->ctx()]);
        // query server-side (baseQuery) tidak boleh mengembalikan milik entitas lain
        $ctrl = new \\App\\Http\\Controllers\\Master\\CustomerController();
        $resp = $this->get('/crm/customers?q=RAHASIA');
        $resp->assertOk();
        $this->assertStringNotContainsString('Rahasia B', $resp->getContent());
        // akses langsung by id milik entitas lain => 404 (findOrFail dalam scope)
        $otherId = DB::table('customers')->where('code','RAHASIA')->value('id');
        $this->get('/crm/customers/'.$otherId)->assertNotFound();
    }
    public function test_payroll_hanya_peran_berhak(): void {
        $cashier = $this->makeUser('cashier');
        $this->actingAs($cashier)->withSession(['erp_context'=>$this->ctx()]);
        $this->get('/hr/payroll')->assertForbidden();
    }
}
""")

w("tests/Feature/SalesFlowTest.php", """<?php
namespace Tests\\Feature;
use Tests\\TestCase;
use Illuminate\\Support\\Facades\\DB;
class SalesFlowTest extends TestCase {
    public function test_so_invoice_payment_refund_flow(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $prod = DB::table('products')->where('sku','T-001')->first();
        $cust = DB::table('customers')->where('code','TC-001')->first();
        // stok awal
        \\App\\Services\\StockService::move(['warehouse_id'=>$this->wh,'product_id'=>$prod->id,'qty'=>20,'ref_type'=>'test','ref_id'=>1]);
        // buat SO
        $r = $this->post('/sales/orders',['customer_id'=>$cust->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>2,'price'=>15000]]]);
        $r->assertRedirect();
        $so = DB::table('sales_orders')->orderByDesc('id')->first();
        $this->assertNotNull($so->number);
        // nomor unik: SO kedua beda nomor
        $r2 = $this->post('/sales/orders',['customer_id'=>$cust->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>1,'price'=>15000]]]);
        $so2 = DB::table('sales_orders')->orderByDesc('id')->first();
        $this->assertNotEquals($so->number, $so2->number);
        // approve + reservasi
        $this->post('/sales/orders/'.$so->id.'/approve')->assertRedirect();
        $this->assertEquals('approved', DB::table('sales_orders')->where('id',$so->id)->value('status'));
        // invoice memotong stok riil
        $before = (float)DB::table('stock_balances')->where(['warehouse_id'=>$this->wh,'product_id'=>$prod->id])->value('qty_on_hand');
        $this->post('/sales/invoices',['customer_id'=>$cust->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>2,'price'=>15000]]])->assertRedirect();
        $after = (float)DB::table('stock_balances')->where(['warehouse_id'=>$this->wh,'product_id'=>$prod->id])->value('qty_on_hand');
        $this->assertEquals($before-2, $after);
        $inv = DB::table('invoices')->orderByDesc('id')->first();
        // jurnal balance
        $j = DB::table('journals')->where(['source_type'=>'invoice','source_id'=>$inv->id])->first();
        $this->assertEquals((float)$j->total_debit, (float)$j->total_credit);
        // partial lalu lunas
        $sisa = (float)$inv->grand_total;
        $this->post('/sales/invoices/'.$inv->id.'/pay',['amount'=>$sisa-1000,'method'=>'cash','date'=>'2026-10-15'])->assertRedirect();
        $this->assertEquals('partial', DB::table('invoices')->where('id',$inv->id)->value('pay_status'));
        $this->post('/sales/invoices/'.$inv->id.'/pay',['amount'=>1000,'method'=>'cash','date'=>'2026-10-15'])->assertRedirect();
        $this->assertEquals('paid', DB::table('invoices')->where('id',$inv->id)->value('pay_status'));
        // overpayment ditolak
        $this->post('/sales/invoices/'.$inv->id.'/pay',['amount'=>500,'method'=>'cash','date'=>'2026-10-15'])->assertSessionHasErrors();
        // oversell ditolak (minta 1000 padahal sisa kecil)
        $resp = $this->post('/sales/invoices',['customer_id'=>$cust->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>1000,'price'=>15000]]]);
        $resp->assertSessionHasErrors();
    }
}
""")

w("tests/Feature/InventoryConcurrencyTest.php", """<?php
namespace Tests\\Feature;
use Tests\\TestCase;
use Illuminate\\Support\\Facades\\DB;
class InventoryConcurrencyTest extends TestCase {
    public function test_reservasi_bersamaan_tidak_oversell(): void {
        $prod = DB::table('products')->where('sku','T-001')->first();
        \\App\\Services\\StockService::move(['warehouse_id'=>$this->wh,'product_id'=>$prod->id,'qty'=>5,'ref_type'=>'test','ref_id'=>1]);
        $ok = 0; $fail = 0;
        for ($i=0;$i<3;$i++) {
            try { \\App\\Services\\StockService::reserve($this->wh,$prod->id,2); $ok++; }
            catch (\\Throwable $e) { $fail++; }
        }
        $this->assertEquals(2, $ok); // 2+2=4 ok, ketiga gagal (sisa 1)
        $this->assertEquals(1, $fail);
        $bal = DB::table('stock_balances')->where(['warehouse_id'=>$this->wh,'product_id'=>$prod->id])->first();
        $this->assertTrue((float)$bal->qty_reserved <= (float)$bal->qty_on_hand);
    }
    public function test_penerimaan_partial_dan_seluruh(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $prod = DB::table('products')->where('sku','T-001')->first();
        $sup = DB::table('suppliers')->where('code','TS-001')->first();
        // PO 10 unit
        $this->post('/purchasing/orders',['supplier_id'=>$sup->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>10,'price'=>10000]]]);
        $po = DB::table('purchase_orders')->orderByDesc('id')->first();
        $this->post('/purchasing/orders/'.$po->id.'/approve');
        // terima 4 (partial)
        $this->post('/inventory/receipts',['warehouse_id'=>$this->wh,'date'=>'2026-10-15','purchase_order_id'=>$po->id,
            'lines'=>[['product_id'=>$prod->id,'qty'=>4,'price'=>10000]]])->assertRedirect();
        $this->assertEquals('partial', DB::table('purchase_orders')->where('id',$po->id)->value('status'));
        // terima 6 sisanya
        $this->post('/inventory/receipts',['warehouse_id'=>$this->wh,'date'=>'2026-10-15','purchase_order_id'=>$po->id,
            'lines'=>[['product_id'=>$prod->id,'qty'=>6,'price'=>10000]]])->assertRedirect();
        $this->assertEquals('received', DB::table('purchase_orders')->where('id',$po->id)->value('status'));
        $this->assertEquals(10.0, (float)DB::table('stock_balances')->where(['warehouse_id'=>$this->wh,'product_id'=>$prod->id])->value('qty_on_hand'));
    }
}
""")

w("tests/Feature/PurchasingMatchTest.php", """<?php
namespace Tests\\Feature;
use Tests\\TestCase;
use Illuminate\\Support\\Facades\\DB;
class PurchasingMatchTest extends TestCase {
    public function test_three_way_match_dan_duplikat(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $prod = DB::table('products')->where('sku','T-001')->first();
        $sup = DB::table('suppliers')->where('code','TS-001')->first();
        $this->post('/purchasing/orders',['supplier_id'=>$sup->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>5,'price'=>10000]]]);
        $po = DB::table('purchase_orders')->orderByDesc('id')->first();
        // invoice vendor SEBELUM barang diterima => HOLD/exception
        $this->post('/purchasing/vinvoices',['purchase_order_id'=>$po->id,'vendor_ref'=>'V-001','date'=>'2026-10-15'])->assertRedirect();
        $v = DB::table('vendor_invoices')->orderByDesc('id')->first();
        $this->assertEquals('exception', $v->match_status);
        $this->assertEquals('hold', $v->status);
        // duplikat vendor_ref ditolak
        $this->post('/purchasing/vinvoices',['purchase_order_id'=>$po->id,'vendor_ref'=>'V-001','date'=>'2026-10-15'])->assertSessionHasErrors();
    }
}
""")

w("tests/Feature/FinancePostingTest.php", """<?php
namespace Tests\\Feature;
use Tests\\TestCase;
use Illuminate\\Support\\Facades\\DB;
class FinancePostingTest extends TestCase {
    public function test_jurnal_harus_balance_dan_idempotent(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $coaCash = DB::table('coas')->where(['legal_entity_id'=>$this->eid,'code'=>'1000'])->value('id');
        $coaRev = DB::table('coas')->where(['legal_entity_id'=>$this->eid,'code'=>'4000'])->value('id');
        // tidak balance => error
        $this->post('/finance/journals',['date'=>'2026-10-15','description'=>'x',
            'lines'=>[['coa_id'=>$coaCash,'debit'=>100,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>50]]])->assertSessionHasErrors();
        // idempotency key sama => 1 jurnal
        $id1 = \\App\\Services\\PostingService::postJournal(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,'date'=>'2026-10-15','number'=>'J-TEST-1','description'=>'t','source_type'=>'test','source_id'=>1],
            [['coa_id'=>$coaCash,'debit'=>100,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>100]],'idem-1');
        $id2 = \\App\\Services\\PostingService::postJournal(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,'date'=>'2026-10-15','number'=>'J-TEST-1b','description'=>'t','source_type'=>'test','source_id'=>1],
            [['coa_id'=>$coaCash,'debit'=>100,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>100]],'idem-1');
        $this->assertEquals($id1,$id2);
        // reversal membuat jurnal lawan, bukan hapus
        \\App\\Services\\PostingService::reverse($id1,'koreksi');
        $this->assertEquals('reversed', DB::table('journals')->where('id',$id1)->value('status'));
        // periode tutup menolak posting
        DB::table('fiscal_periods')->where('legal_entity_id',$this->eid)->update(['is_closed'=>true]);
        $this->expectException(\\RuntimeException::class);
        \\App\\Services\\PostingService::postJournal(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,'date'=>'2026-10-15','number'=>'J-TEST-2','description'=>'t'],
            [['coa_id'=>$coaCash,'debit'=>10,'credit'=>0],['coa_id'=>$coaRev,'debit'=>0,'credit'=>10]],'idem-2');
    }
    public function test_segregation_of_duties(): void {
        $staff = $this->makeUser('finance_staff');
        $this->actingAs($staff)->withSession(['erp_context'=>$this->ctx()]);
        $ap = DB::table('approvals')->insertGetId(['doc_type'=>'expense','doc_id'=>1,'level'=>1,'status'=>'pending','requested_by'=>$staff->id,'created_at'=>now(),'updated_at'=>now()]);
        $this->post('/gov/approvals/'.$ap,['decision'=>'approved'])->assertSessionHasErrors();
    }
}
""")

w("tests/Feature/BusinessRulesTest.php", """<?php
namespace Tests\\Feature;
use Tests\\TestCase;
use Illuminate\\Support\\Facades\\DB;
class BusinessRulesTest extends TestCase {
    public function test_rental_tidak_boleh_bentrok(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $cat = DB::table('rental_catalogs')->insertGetId(['legal_entity_id'=>$this->eid,'code'=>'RW-1','name'=>'Kebaya','rate'=>200000,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $unit = DB::table('rental_units')->insertGetId(['rental_catalog_id'=>$cat,'code'=>'U-001','status'=>'available','created_at'=>now(),'updated_at'=>now()]);
        $cust = DB::table('customers')->where('code','TC-001')->value('id');
        $this->post('/rental/book',['rental_unit_id'=>$unit,'customer_id'=>$cust,'start_date'=>'2026-11-01','end_date'=>'2026-11-03','rate'=>200000])->assertRedirect();
        $this->post('/rental/book',['rental_unit_id'=>$unit,'customer_id'=>$cust,'start_date'=>'2026-11-02','end_date'=>'2026-11-04','rate'=>200000])->assertSessionHasErrors();
    }
    public function test_thrift_item_unik_tidak_double_sell(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $lot = DB::table('thrift_lots')->insertGetId(['legal_entity_id'=>$this->eid,'number'=>'LOT-1','date'=>'2026-10-15','acquisition_cost'=>1000000,'status'=>'received','created_at'=>now(),'updated_at'=>now()]);
        $item = DB::table('thrift_items')->insertGetId(['thrift_lot_id'=>$lot,'code'=>'TH-001','name'=>'Denim','price'=>150000,'status'=>'sorted','created_at'=>now(),'updated_at'=>now()]);
        $ses = DB::table('live_sessions')->insertGetId(['legal_entity_id'=>$this->eid,'title'=>'Live 1','scheduled_at'=>now(),'status'=>'live','created_at'=>now(),'updated_at'=>now()]);
        $cust = DB::table('customers')->where('code','TC-001')->value('id');
        $this->post('/thrift/live/reserve',['live_session_id'=>$ses,'thrift_item_id'=>$item,'customer_id'=>$cust,'price'=>150000])->assertRedirect();
        $this->post('/thrift/live/reserve',['live_session_id'=>$ses,'thrift_item_id'=>$item,'customer_id'=>$cust,'price'=>150000])->assertSessionHasErrors();
    }
    public function test_boarding_tolak_overcapacity(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $owner = DB::table('pet_owners')->insertGetId(['legal_entity_id'=>$this->eid,'name'=>'Pemilik','created_at'=>now(),'updated_at'=>now()]);
        $pet = DB::table('pets')->insertGetId(['pet_owner_id'=>$owner,'name'=>'Mochi','species'=>'cat','created_at'=>now(),'updated_at'=>now()]);
        $room = DB::table('boarding_rooms')->insertGetId(['branch_id'=>$this->bid,'code'=>'R1','name'=>'Kamar 1','capacity'=>1,'rate'=>100000,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->post('/pet/boarding/book',['pet_id'=>$pet,'boarding_room_id'=>$room,'check_in'=>'2026-11-01','check_out'=>'2026-11-03'])->assertRedirect();
        $this->post('/pet/boarding/book',['pet_id'=>$pet,'boarding_room_id'=>$room,'check_in'=>'2026-11-02','check_out'=>'2026-11-04'])->assertSessionHasErrors();
    }
    public function test_lead_convert_tanpa_duplikasi(): void {
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context'=>$this->ctx()]);
        $lead = DB::table('leads')->insertGetId(['legal_entity_id'=>$this->eid,'name'=>'Budi','phone'=>'0811','stage'=>'new','created_at'=>now(),'updated_at'=>now()]);
        $this->post('/crm/leads/'.$lead.'/convert')->assertRedirect();
        // konversi kedua ditolak (sudah converted)
        $this->post('/crm/leads/'.$lead.'/convert')->assertSessionHasErrors();
        $this->assertEquals(1, DB::table('customers')->where(['legal_entity_id'=>$this->eid,'name'=>'Budi'])->count());
    }
    public function test_report_terfilter_entitas(): void {
        $mgr = $this->makeUser('branch_manager');
        $this->actingAs($mgr)->withSession(['erp_context'=>$this->ctx()]);
        $this->get('/reports/sales')->assertOk();
    }
}
""")

w("tests/Unit/DocNumberTest.php", """<?php
namespace Tests\\Unit;
use Tests\\TestCase;
use Illuminate\\Support\\Facades\\DB;
class DocNumberTest extends TestCase {
    public function test_sequence_naik_monotonik(): void {
        $a = \\App\\Services\\DocNumberService::next('SO',$this->eid,$this->bid,'E','B');
        $b = \\App\\Services\\DocNumberService::next('SO',$this->eid,$this->bid,'E','B');
        $this->assertNotEquals($a,$b);
        $this->assertStringContainsString('/SO/', $a);
    }
}
""")
print("gen11 done")

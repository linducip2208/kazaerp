<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
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

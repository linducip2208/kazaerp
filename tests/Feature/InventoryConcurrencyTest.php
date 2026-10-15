<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
class InventoryConcurrencyTest extends TestCase {
    public function test_reservasi_bersamaan_tidak_oversell(): void {
        $prod = DB::table('products')->where('sku','T-001')->first();
        \App\Services\StockService::move(['warehouse_id'=>$this->wh,'product_id'=>$prod->id,'qty'=>5,'ref_type'=>'test','ref_id'=>1]);
        $ok = 0; $fail = 0;
        for ($i=0;$i<3;$i++) {
            try { \App\Services\StockService::reserve($this->wh,$prod->id,2); $ok++; }
            catch (\Throwable $e) { $fail++; }
        }
        $this->assertEquals(2, $ok); // 2+2=4 ok, ketiga gagal (sisa 1)
        $this->assertEquals(1, $fail);
        $bal = DB::table('stock_balances')->where(['warehouse_id'=>$this->wh,'product_id'=>$prod->id])->first();
        $this->assertTrue((float)$bal->qty_reserved <= (float)$bal->qty_on_hand);
    }
    public function test_penerimaan_partial_dan_seluruh(): void {
        $creator = $this->makeUser('super_admin');
        $approver = $this->makeUser('super_admin');
        $this->actingAs($creator)->withSession(['erp_context'=>$this->ctx()]);
        $prod = DB::table('products')->where('sku','T-001')->first();
        $sup = DB::table('suppliers')->where('code','TS-001')->first();
        // PO 10 unit
        $this->post('/purchasing/orders',['supplier_id'=>$sup->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>10,'price'=>10000]]]);
        $po = DB::table('purchase_orders')->orderByDesc('id')->first();
        // SoD: penyetuju harus orang berbeda dari pemohon
        $this->actingAs($approver)->withSession(['erp_context'=>$this->ctx()]);
        $this->post('/purchasing/orders/'.$po->id.'/approve')->assertRedirect();
        $this->actingAs($creator)->withSession(['erp_context'=>$this->ctx()]);
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

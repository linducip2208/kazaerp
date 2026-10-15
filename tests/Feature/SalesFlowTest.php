<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
class SalesFlowTest extends TestCase {
    public function test_so_invoice_payment_refund_flow(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $prod = DB::table('products')->where('sku','T-001')->first();
        $cust = DB::table('customers')->where('code','TC-001')->first();
        // stok awal
        \App\Services\StockService::move(['warehouse_id'=>$this->wh,'product_id'=>$prod->id,'qty'=>20,'ref_type'=>'test','ref_id'=>1]);
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

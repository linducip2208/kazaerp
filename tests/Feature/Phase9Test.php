<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Services\ApprovalService;

/**
 * Phase9 — anti double-submit pembayaran (idempotency_key) + anti stale approval.
 * Bukti penutup R1 FINALIZATION_AUDIT + celah edit-setelah-pengajuan expense.
 */
class Phase9Test extends TestCase {
    private function invoiceSiapBayar() {
        $prod = DB::table('products')->where('sku','T-001')->first();
        $cust = DB::table('customers')->where('code','TC-001')->first();
        \App\Services\StockService::move(['warehouse_id'=>$this->wh,'product_id'=>$prod->id,'qty'=>20,'ref_type'=>'test','ref_id'=>9]);
        $this->post('/sales/invoices',['customer_id'=>$cust->id,'date'=>'2026-10-15','lines'=>[['product_id'=>$prod->id,'qty'=>2,'price'=>15000]]])->assertRedirect();
        return DB::table('invoices')->orderByDesc('id')->first();
    }

    public function test_bayar_invoice_double_submit_satu_kunci(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $inv = $this->invoiceSiapBayar();
        $sisa = (float)$inv->grand_total;
        $key = 'idem-test-001';
        $j0 = DB::table('journals')->where(['source_type'=>'sales_payment','source_id'=>$inv->id])->count();
        $this->post('/sales/invoices/'.$inv->id.'/pay',['amount'=>5000,'method'=>'cash','date'=>'2026-10-15','idempotency_key'=>$key])->assertRedirect();
        // kirim ulang persis (double-click / retry): harus replay, bukan baris baru
        $r = $this->post('/sales/invoices/'.$inv->id.'/pay',['amount'=>5000,'method'=>'cash','date'=>'2026-10-15','idempotency_key'=>$key]);
        $r->assertRedirect();
        $this->assertEquals(1, DB::table('sales_payments')->where('idempotency_key',$key)->count());
        $this->assertEquals(1, DB::table('sales_payments')->where('invoice_id',$inv->id)->count());
        $this->assertEquals(5000.0, (float)DB::table('invoices')->where('id',$inv->id)->value('paid_total'));
        $this->assertEquals($j0+1, DB::table('journals')->where(['source_type'=>'sales_payment','source_id'=>$inv->id])->count());
    }

    public function test_bayar_invoice_kunci_beda_tetap_dua_pembayaran_sah(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $inv = $this->invoiceSiapBayar();
        $sisa = (float)$inv->grand_total;
        $this->post('/sales/invoices/'.$inv->id.'/pay',['amount'=>$sisa-1000,'method'=>'cash','date'=>'2026-10-15','idempotency_key'=>'k-a'])->assertRedirect();
        $this->post('/sales/invoices/'.$inv->id.'/pay',['amount'=>1000,'method'=>'transfer','date'=>'2026-10-15','idempotency_key'=>'k-b'])->assertRedirect();
        $this->assertEquals(2, DB::table('sales_payments')->where('invoice_id',$inv->id)->count());
        $this->assertEquals('paid', DB::table('invoices')->where('id',$inv->id)->value('pay_status'));
    }

    public function test_bayar_utang_double_submit_satu_kunci(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $sup = DB::table('suppliers')->where('code','TS-001')->first();
        $vid = DB::table('vendor_invoices')->insertGetId(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid,
            'supplier_id'=>$sup->id,'number'=>'VIN-TEST-1','vendor_ref'=>'VREF-1','date'=>'2026-10-15',
            'grand_total'=>100000,'paid_total'=>0,'status'=>'matched','match_status'=>'matched','created_at'=>now(),'updated_at'=>now()]);
        $this->post('/purchasing/appayments',['vendor_invoice_id'=>$vid,'amount'=>40000,'method'=>'transfer','date'=>'2026-10-15','idempotency_key'=>'ap-k1'])->assertRedirect();
        $this->post('/purchasing/appayments',['vendor_invoice_id'=>$vid,'amount'=>40000,'method'=>'transfer','date'=>'2026-10-15','idempotency_key'=>'ap-k1'])->assertRedirect();
        $this->assertEquals(1, DB::table('ap_payments')->where('idempotency_key','ap-k1')->count());
        $this->assertEquals(40000.0, (float)DB::table('vendor_invoices')->where('id',$vid)->value('paid_total'));
    }

    public function test_edit_expense_membatalkan_approval_lama(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context'=>$this->ctx()]);
        $this->post('/finance/expenses',['title'=>'ATK','date'=>'2026-10-15','amount'=>100000])->assertRedirect();
        $exp = DB::table('expenses')->orderByDesc('id')->first();
        $lama = DB::table('approvals')->where(['doc_type'=>'expense','doc_id'=>$exp->id,'status'=>'pending'])->orderByDesc('id')->first();
        $this->assertNotNull($lama);
        // ubah nominal setelah pengajuan
        $this->put('/finance/expenses/'.$exp->id,['title'=>'ATK revisi','date'=>'2026-10-15','amount'=>150000])->assertRedirect();
        $this->assertEquals('superseded', DB::table('approvals')->where('id',$lama->id)->value('status'));
        $this->assertTrue(DB::table('approval_histories')->where(['approval_id'=>$lama->id,'action'=>'superseded'])->exists());
        $baru = DB::table('approvals')->where(['doc_type'=>'expense','doc_id'=>$exp->id,'status'=>'pending'])->orderByDesc('id')->first();
        $this->assertNotNull($baru);
        $this->assertNotEquals($lama->id, $baru->id);
        // keputusan atas approval kedaluwarsa harus ditolak
        try {
            ApprovalService::decide($lama->id,'approved','coba');
            $this->fail('decide atas approval superseded harus melempar.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('pending', $e->getMessage());
        }
        // approval baru bisa diputus user lain (SoD) → expense approved
        $approver = $this->makeUser('super_admin');
        $this->actingAs($approver)->withSession(['erp_context'=>$this->ctx()]);
        $this->post('/finance/expenses/'.$exp->id.'/approve')->assertRedirect();
        $this->assertEquals('approved', DB::table('expenses')->where('id',$exp->id)->value('status'));
        // ubah SETELAH disetujui → kembali draft + pengajuan baru
        $this->put('/finance/expenses/'.$exp->id,['title'=>'ATK final','date'=>'2026-10-15','amount'=>160000])->assertRedirect();
        $this->assertEquals('draft', DB::table('expenses')->where('id',$exp->id)->value('status'));
        $this->assertEquals(1, DB::table('approvals')->where(['doc_type'=>'expense','doc_id'=>$exp->id,'status'=>'pending'])->count());
    }
}

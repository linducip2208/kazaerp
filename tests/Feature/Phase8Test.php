<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

/** Regresi temuan audit finalisasi: scoping, injectContext, reservasi, impor, CSV. */
class Phase8Test extends TestCase {
    public function test_crud_inject_context_branch_dan_creator(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $cc = new \App\Http\Controllers\Master\CustomerController();
        $m = new \ReflectionMethod($cc, 'injectContext');
        $m->setAccessible(true);
        $v = [];
        $m->invokeArgs($cc, [&$v]);
        $this->assertEquals($this->eid, $v['legal_entity_id']);
        $this->assertEquals($this->bid, $v['branch_id']); // dulu TypeError array_key_exists(bool)
        $ic = new \App\Http\Controllers\Sales\InvoiceController();
        $m2 = new \ReflectionMethod($ic, 'injectContext');
        $m2->setAccessible(true);
        $w = [];
        $m2->invokeArgs($ic, [&$w]);
        $this->assertEquals($admin->id, $w['created_by']); // dulu tidak pernah terisi
        $this->assertEquals($this->bid, $w['branch_id']);
    }

    public function test_files_index_tidak_bocor_lintas_entitas(): void {
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id' => $this->hid, 'code' => 'ENT-F', 'name' => 'Entitas F', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $b2 = DB::table('branches')->insertGetId(['legal_entity_id' => $eid2, 'code' => 'FB', 'name' => 'Cab F', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $cb = DB::table('customers')->insertGetId(['legal_entity_id' => $eid2, 'branch_id' => $b2, 'code' => 'RAHASIA-F', 'name' => 'Rahasia File B', 'tier' => 'retail', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $mgr = $this->makeUser('branch_manager');
        DB::table('attachments')->insert(['owner_type' => 'Customer', 'owner_id' => $cb, 'file_path' => 'private/x/y.pdf', 'file_name' => 'RAHASIA-FILE-B.pdf', 'mime' => 'application/pdf', 'size' => 10, 'version' => 1, 'uploaded_by' => $mgr->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($mgr)->withSession(['erp_context' => $this->ctx()]);
        $resp = $this->get('/files');
        $resp->assertOk();
        $this->assertStringNotContainsString('RAHASIA-FILE-B.pdf', $resp->getContent());
    }

    public function test_lead_convert_lintas_entitas_ditolak(): void {
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id' => $this->hid, 'code' => 'ENT-G', 'name' => 'Entitas G', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $lead = DB::table('leads')->insertGetId(['legal_entity_id' => $eid2, 'name' => 'Lead Asing', 'stage' => 'new', 'created_at' => now(), 'updated_at' => now()]);
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/crm/leads/' . $lead . '/convert')->assertNotFound();
        $this->assertNull(DB::table('leads')->where('id', $lead)->value('converted_customer_id'));
    }

    public function test_so_approve_stok_kurang_tanpa_reservasi_hantu(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        $this->post('/sales/orders', ['customer_id' => $cust->id, 'date' => '2026-10-17', 'lines' => [['product_id' => $prod->id, 'qty' => 999, 'price' => 15000]]])->assertRedirect();
        $so = DB::table('sales_orders')->orderByDesc('id')->first();
        $this->post('/sales/orders/' . $so->id . '/approve')->assertRedirect();
        $this->assertEquals(0, DB::table('stock_reservations')->where(['ref_type' => 'sales_order', 'ref_id' => $so->id, 'status' => 'active'])->count());
        $this->assertTrue(DB::table('doc_status_histories')->where(['doc_type' => 'sales_orders', 'doc_id' => $so->id])->where('note', 'like', 'Reservasi gagal%')->exists());
    }

    public function test_import_menolak_header_rusak_tanpa_data_masuk(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $before = DB::table('customers')->where('legal_entity_id', $this->eid)->count();
        $file = UploadedFile::fake()->createWithContent('bad.csv', "kolom_salah\n1\n");
        $this->post('/reports/import/customers', ['file' => $file])->assertSessionHasErrors();
        $this->assertEquals($before, DB::table('customers')->where('legal_entity_id', $this->eid)->count());
    }

    public function test_csv_penjualan_menetralkan_injeksi_formula(): void {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'qty' => 20, 'ref_type' => 'test', 'ref_id' => 88]);
        $custId = DB::table('customers')->insertGetId(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid, 'code' => 'TC-F', 'name' => '=CMD|/C calc', 'tier' => 'retail', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->post('/sales/invoices', ['customer_id' => $custId, 'date' => '2026-10-17', 'lines' => [['product_id' => $prod->id, 'qty' => 1, 'price' => 15000]]])->assertRedirect();
        $resp = $this->get('/reports/sales?export=csv');
        $resp->assertOk();
        $this->assertStringContainsString("'=CMD", $resp->getContent());
    }
}

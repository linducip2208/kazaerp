<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class Phase3Test extends TestCase
{
    public function test_2fa_flow(): void
    {
        $u = $this->makeUser('viewer');
        $u->forceFill(['email' => 'tfa@test.local'])->save();
        $this->actingAs($u)->withSession(['erp_context' => $this->ctx()]);
        // setup menghasilkan QR
        $this->get('/2fa/setup')->assertOk();
        $secret = DB::table('users')->where('id', $u->id)->value('two_factor_secret');
        $this->assertNotEmpty($secret);
        $code = (new \PragmaRX\Google2FA\Google2FA)->getCurrentOtp($secret);
        $this->post('/2fa/confirm', ['code' => $code])->assertRedirect();
        $this->assertNotNull(DB::table('users')->where('id', $u->id)->value('two_factor_confirmed_at'));
        // logout lalu login → wajib challenge
        $this->post('/logout')->assertRedirect();
        $this->post('/login', ['email' => 'tfa@test.local', 'password' => 'secret123'])->assertRedirect(route('2fa.challenge'));
        $this->get('/')->assertRedirect('/login'); // belum lolos 2FA
        $code2 = (new \PragmaRX\Google2FA\Google2FA)->getCurrentOtp($secret);
        $this->post('/2fa/verify', ['code' => $code2])->assertRedirect('/');
        $this->get('/')->assertOk();
    }

    public function test_private_file_isolation(): void
    {
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id' => $this->hid, 'code' => 'ENT-F', 'name' => 'Entitas F', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $custB = DB::table('customers')->insertGetId(['legal_entity_id' => $eid2, 'code' => 'FB-001', 'name' => 'Rahasia F', 'tier' => 'retail', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        // upload untuk customer entitas sendiri OK
        $custA = DB::table('customers')->where('code', 'TC-001')->value('id');
        $this->post('/files', ['owner_type' => 'Customer', 'owner_id' => $custA,
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])->assertRedirect();
        $att = DB::table('attachments')->orderByDesc('id')->first();
        $this->get('/files/'.$att->id.'/download')->assertOk();
        // file milik entitas lain: upload maupun download ditolak
        $this->post('/files', ['owner_type' => 'Customer', 'owner_id' => $custB,
            'file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')])->assertForbidden();
        $attB = DB::table('attachments')->insertGetId(['owner_type' => 'Customer', 'owner_id' => $custB,
            'file_path' => 'private/nope.pdf', 'file_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1,
            'uploaded_by' => $op->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/files/'.$attB.'/download')->assertForbidden();
    }

    public function test_vet_needs_clinical_permission(): void
    {
        $viewer = $this->makeUser('viewer');
        $this->actingAs($viewer)->withSession(['erp_context' => $this->ctx()]);
        $this->get('/pet/vet')->assertForbidden();
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $this->get('/pet/vet')->assertOk();
    }

    public function test_thrift_allocation(): void
    {
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $lot = DB::table('thrift_lots')->insertGetId(['legal_entity_id' => $this->eid, 'number' => 'LOT-A', 'date' => '2026-10-15', 'acquisition_cost' => 1000000, 'status' => 'received', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['TH-A1', 100000], ['TH-A2', 300000]] as [$code, $price]) {
            DB::table('thrift_items')->insert(['thrift_lot_id' => $lot, 'code' => $code, 'name' => 'Item', 'price' => $price, 'status' => 'sorted', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->post('/thrift/lots/'.$lot.'/allocate', ['method' => 'equal'])->assertRedirect();
        $this->assertEquals(500000.0, (float) DB::table('thrift_items')->where('code', 'TH-A1')->value('cost_share'));
        $this->post('/thrift/lots/'.$lot.'/allocate', ['method' => 'price'])->assertRedirect();
        $this->assertEquals(250000.0, (float) DB::table('thrift_items')->where('code', 'TH-A1')->value('cost_share'));
        $this->assertEquals(750000.0, (float) DB::table('thrift_items')->where('code', 'TH-A2')->value('cost_share'));
    }

    public function test_voucher_and_loyalty(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'qty' => 20, 'ref_type' => 'test', 'ref_id' => 1]);
        DB::table('vouchers')->insert(['legal_entity_id' => $this->eid, 'code' => 'HEMAT10', 'name' => 'Hemat',
            'value' => 10000, 'min_txn' => 0, 'quota' => 5, 'used' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        // voucher palsu ditolak
        $this->post('/sales/invoices', ['customer_id' => $cust->id, 'date' => '2026-10-15', 'voucher_code' => 'SALAH',
            'lines' => [['product_id' => $prod->id, 'qty' => 2, 'price' => 15000]]])->assertSessionHasErrors();
        $this->post('/sales/invoices', ['customer_id' => $cust->id, 'date' => '2026-10-15', 'voucher_code' => 'HEMAT10',
            'lines' => [['product_id' => $prod->id, 'qty' => 2, 'price' => 15000]]])->assertRedirect();
        $inv = DB::table('invoices')->orderByDesc('id')->first();
        $this->assertEquals(10000.0, (float) $inv->discount);
        $this->assertEquals(1, DB::table('vouchers')->where('code', 'HEMAT10')->value('used'));
        // lunasi → poin masuk (grand 23000+... /10000 = 2 poin)
        $this->post('/sales/invoices/'.$inv->id.'/pay', ['amount' => (float) $inv->grand_total, 'method' => 'cash', 'date' => '2026-10-15'])->assertRedirect();
        $pts = DB::table('loyalty_points')->where(['ref_type' => 'invoice', 'ref_id' => $inv->id])->first();
        $this->assertNotNull($pts);
        $this->assertEquals((int) floor((float) $inv->grand_total / 10000), (int) $pts->points);
    }

    public function test_bankrec_and_budget_pages(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $bank = DB::table('bank_accounts')->insertGetId(['legal_entity_id' => $this->eid, 'bank_name' => 'Bank Uji', 'account_no' => '999', 'holder' => 'PT', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->post('/finance/bankrec', ['bank_account_id' => $bank, 'period' => '2026-10-15', 'book_balance' => 1000, 'bank_balance' => 1000])->assertRedirect();
        $this->assertEquals('matched', DB::table('bank_reconciliations')->orderByDesc('id')->value('status'));
        $this->post('/finance/bankrec', ['bank_account_id' => $bank, 'period' => '2026-10-15', 'book_balance' => 1000, 'bank_balance' => 900])->assertRedirect();
        $this->assertEquals('draft', DB::table('bank_reconciliations')->orderByDesc('id')->value('status'));
        $this->get('/finance/budget')->assertOk();
        $this->get('/reports/aging')->assertOk();
        $this->get('/finance/intercompany')->assertOk();
    }
}

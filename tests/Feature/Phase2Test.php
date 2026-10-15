<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class Phase2Test extends TestCase
{
    public function test_ap_payment_flow(): void
    {
        $creator = $this->makeUser('super_admin');
        $approver = $this->makeUser('super_admin');
        $this->actingAs($creator)->withSession(['erp_context' => $this->ctx()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        $sup = DB::table('suppliers')->where('code', 'TS-001')->first();
        $this->post('/purchasing/orders', ['supplier_id' => $sup->id, 'date' => '2026-10-15', 'lines' => [['product_id' => $prod->id, 'qty' => 5, 'price' => 10000]]]);
        $po = DB::table('purchase_orders')->orderByDesc('id')->first();
        $this->actingAs($approver)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/purchasing/orders/'.$po->id.'/approve')->assertRedirect();
        $this->actingAs($creator)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/inventory/receipts', ['warehouse_id' => $this->wh, 'date' => '2026-10-15', 'purchase_order_id' => $po->id,
            'lines' => [['product_id' => $prod->id, 'qty' => 5, 'price' => 10000]]])->assertRedirect();
        $this->post('/purchasing/vinvoices', ['purchase_order_id' => $po->id, 'vendor_ref' => 'AP-1', 'date' => '2026-10-15'])->assertRedirect();
        $v = DB::table('vendor_invoices')->orderByDesc('id')->first();
        $this->assertEquals('matched', $v->match_status);
        $sisa = (float) $v->grand_total;
        $this->post('/purchasing/appayments', ['vendor_invoice_id' => $v->id, 'amount' => $sisa - 1000, 'method' => 'transfer', 'date' => '2026-10-15'])->assertRedirect();
        $this->assertEquals('partial', DB::table('vendor_invoices')->where('id', $v->id)->value('status'));
        $this->post('/purchasing/appayments', ['vendor_invoice_id' => $v->id, 'amount' => 1000, 'method' => 'transfer', 'date' => '2026-10-15'])->assertRedirect();
        $this->assertEquals('paid', DB::table('vendor_invoices')->where('id', $v->id)->value('status'));
        $this->post('/purchasing/appayments', ['vendor_invoice_id' => $v->id, 'amount' => 100, 'method' => 'transfer', 'date' => '2026-10-15'])->assertSessionHasErrors();
        // jurnal utang→kas balance
        $j = DB::table('journals')->where('source_type', 'ap_payment')->first();
        $this->assertEquals((float) $j->total_debit, (float) $j->total_credit);
    }

    public function test_payroll_approve_posts_journal(): void
    {
        $creator = $this->makeUser('super_admin');
        $approver = $this->makeUser('super_admin');
        $this->actingAs($creator)->withSession(['erp_context' => $this->ctx()]);
        $per = DB::table('payroll_periods')->insertGetId(['legal_entity_id' => $this->eid, 'name' => 'Okt-26', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        $emp = DB::table('employees')->insertGetId(['legal_entity_id' => $this->eid, 'nip' => 'E1', 'name' => 'Karyawan', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->post('/hr/payroll', ['payroll_period_id' => $per, 'items' => [['employee_id' => $emp, 'gross' => 5000000, 'deduction' => 500000]]])->assertRedirect();
        $pr = DB::table('payrolls')->orderByDesc('id')->first();
        $this->actingAs($approver)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/hr/payroll/'.$pr->id.'/approve')->assertRedirect();
        $pj = DB::table('payroll_journals')->where('payroll_id', $pr->id)->first();
        $this->assertNotNull($pj);
        $j = DB::table('journals')->where('id', $pj->journal_id)->first();
        $this->assertEquals((float) $j->total_debit, (float) $j->total_credit);
        $this->assertEquals(4500000.0, (float) $j->total_debit);
    }

    public function test_delegation_allows_decide(): void
    {
        $requester = $this->makeUser('finance_staff');
        $boss = $this->makeUser('super_admin');
        $delegate = $this->makeUser('finance_staff');
        // boss punya governance.approve (super_admin), delegasikan ke delegate
        DB::table('approval_delegations')->insert(['delegator_id' => $boss->id, 'delegate_id' => $delegate->id,
            'from_date' => now()->subDay()->toDateString(), 'to_date' => now()->addDay()->toDateString(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($requester)->withSession(['erp_context' => $this->ctx()]);
        $ap = DB::table('approvals')->insertGetId(['doc_type' => 'expense', 'doc_id' => 9, 'level' => 1, 'status' => 'pending', 'requested_by' => $requester->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($delegate)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/gov/approvals/'.$ap, ['decision' => 'approved'])->assertRedirect();
        $this->assertEquals('approved', DB::table('approvals')->where('id', $ap)->value('status'));
    }

    public function test_api_token_scope(): void
    {
        $u = $this->makeUser('module_operator');
        $u->forceFill(['email' => 'api1@test.local'])->save();
        // module_operator tidak punya api.access eksplisit — beri dulu (seperti integrasi nyata)
        $rid = DB::table('roles')->where('name', 'module_operator')->value('id');
        $pid = DB::table('permissions')->where('name', 'api.access')->value('id');
        if ($pid) {
            DB::table('permission_role')->updateOrInsert(['role_id' => $rid, 'permission_id' => $pid], []);
        }
        $login = $this->postJson('/api/v1/login', ['email' => 'api1@test.local', 'password' => 'secret123']);
        $login->assertOk();
        $token = $login->json('token');
        $this->assertNotEmpty($token);
        $this->getJson('/api/v1/invoices', ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->getJson('/api/v1/stock', ['Authorization' => 'Bearer '.$token])->assertOk();
        // entitas lain ditolak
        $this->getJson('/api/v1/invoices?legal_entity_id=999999', ['Authorization' => 'Bearer '.$token])->assertForbidden();
        // logout mencabut token (refresh app: guard auth di-cache per proses test, bukan per request HTTP nyata)
        $this->postJson('/api/v1/logout', [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->refreshApplication();
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
        // akun nonaktif tidak bisa login
        $u->update(['is_active' => false]);
        $this->postJson('/api/v1/login', ['email' => 'api1@test.local', 'password' => 'secret123'])->assertUnauthorized();
    }
}

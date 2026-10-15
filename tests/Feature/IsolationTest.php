<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
class IsolationTest extends TestCase {
    public function test_cabang_a_tidak_bisa_baca_data_cabang_b(): void {
        // entitas kedua
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id'=>$this->hid,'code'=>'ENT-B','name'=>'Entitas B','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $b2 = DB::table('branches')->insertGetId(['legal_entity_id'=>$eid2,'code'=>'B2','name'=>'Cabang B','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('customers')->insert(['legal_entity_id'=>$eid2,'branch_id'=>$b2,'code'=>'RAHASIA','name'=>'Rahasia B','tier'=>'retail','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $mgr = $this->makeUser('branch_manager'); // terikat MZA-HOLD/PST
        $this->actingAs($mgr)->withSession(['erp_context'=>$this->ctx()]);
        // query server-side (baseQuery) tidak boleh mengembalikan milik entitas lain
        $ctrl = new \App\Http\Controllers\Master\CustomerController();
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

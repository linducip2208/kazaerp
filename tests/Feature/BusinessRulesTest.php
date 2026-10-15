<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
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

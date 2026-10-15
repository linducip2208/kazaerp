<?php
namespace Tests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
abstract class TestCase extends BaseTestCase {
    protected int $eid; protected int $bid; protected int $hid; protected int $wh;
    protected function setUp(): void {
        parent::setUp();
        // Migrasi fresh per test class run memakai testing DB (MySQL kazaerp_testing)
        $this->artisan('migrate:fresh');
        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);
        $this->seed(\Database\Seeders\OrgSeeder::class);
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
        return \App\Models\User::find($id);
    }
    protected function ctx(array $over = []) {
        return array_merge(['legal_entity_id'=>$this->eid,'branch_id'=>$this->bid], $over);
    }
}

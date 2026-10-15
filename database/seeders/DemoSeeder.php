<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class DemoSeeder extends Seeder {
    public function run(): void {
        // Hanya untuk development/testing — JANGAN jalankan di production.
        if (app()->environment('production')) return;
        $eid = DB::table('legal_entities')->where('code','MZA-HOLD')->value('id');
        $br = DB::table('branches')->where(['legal_entity_id'=>$eid,'code'=>'PST'])->value('id');
        $hid = DB::table('holdings')->where('code','KZK')->value('id');
        $uid = DB::table('users')->where('email','admin@kazaka.local')->value('id');
        if (!$uid) $uid = DB::table('users')->insertGetId(['name'=>'Administrator','email'=>'admin@kazaka.local','password'=>Hash::make('Kazaka#2026'),'is_active'=>true,'holding_id'=>$hid,'legal_entity_id'=>$eid,'branch_id'=>$br,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        $rid = DB::table('roles')->where('name','super_admin')->value('id');
        DB::table('role_user')->updateOrInsert(['role_id'=>$rid,'user_id'=>$uid],[]);
        $cat = DB::table('categories')->where(['kind'=>'product','code'=>'UMUM'])->first();
        if (!$cat) $catId = DB::table('categories')->insertGetId(['kind'=>'product','code'=>'UMUM','name'=>'Umum','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]); else $catId = $cat->id;
        $uom = DB::table('uoms')->where('code','pcs')->value('id');
        if (!DB::table('products')->where(['legal_entity_id'=>$eid,'sku'=>'DEMO-001'])->exists()) {
            DB::table('products')->insert(['legal_entity_id'=>$eid,'category_id'=>$catId,'uom_id'=>$uom,'sku'=>'DEMO-001','name'=>'Produk Demo','kind'=>'goods','cost_price'=>50000,'sell_price'=>75000,'is_sellable'=>true,'is_purchasable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        if (!DB::table('customers')->where(['legal_entity_id'=>$eid,'code'=>'CUST-001'])->exists()) {
            DB::table('customers')->insert(['legal_entity_id'=>$eid,'branch_id'=>$br,'code'=>'CUST-001','name'=>'Pelanggan Demo','kind'=>'retail','tier'=>'retail','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        if (!DB::table('suppliers')->where(['legal_entity_id'=>$eid,'code'=>'SUP-001'])->exists()) {
            DB::table('suppliers')->insert(['legal_entity_id'=>$eid,'code'=>'SUP-001','name'=>'Supplier Demo','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}

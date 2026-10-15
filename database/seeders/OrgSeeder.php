<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class OrgSeeder extends Seeder {
    public function run(): void {
        $holding = DB::table('holdings')->where('code','KZK')->first();
        if (!$holding) $holdingId = DB::table('holdings')->insertGetId(['code'=>'KZK','name'=>'Kazaka Group','address'=>'Jakarta, Indonesia','phone'=>'021-0000000','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        else $holdingId = $holding->id;
        $entities = [
            ['code'=>'MZA-HOLD','name'=>'MZA Holdings','npwp'=>'00.000.000.0-000.000'],
            ['code'=>'KZK-PET','name'=>'PT Kazaka Pet Care','npwp'=>'00.000.000.0-000.001'],
            ['code'=>'ZAYAN','name'=>'PT Zayan Style','npwp'=>'00.000.000.0-000.002'],
        ];
        $entityIds = [];
        foreach ($entities as $e) {
            $row = DB::table('legal_entities')->where('code',$e['code'])->first();
            if (!$row) $entityIds[$e['code']] = DB::table('legal_entities')->insertGetId(['holding_id'=>$holdingId,'code'=>$e['code'],'name'=>$e['name'],'npwp'=>$e['npwp'],'address'=>'Jakarta','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            else $entityIds[$e['code']] = $row->id;
        }
        $fy = DB::table('fiscal_years')->where(['legal_entity_id'=>$entityIds['MZA-HOLD'],'name'=>'2026'])->first();
        foreach ($entityIds as $code=>$eid) {
            $fyRow = DB::table('fiscal_years')->where(['legal_entity_id'=>$eid,'name'=>'2026'])->first();
            $fyId = $fyRow ? $fyRow->id : DB::table('fiscal_years')->insertGetId(['legal_entity_id'=>$eid,'name'=>'2026','start_date'=>'2026-01-01','end_date'=>'2026-12-31','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
            if (!DB::table('fiscal_periods')->where(['fiscal_year_id'=>$fyId,'name'=>'2026-10'])->exists()) {
                DB::table('fiscal_periods')->insert(['fiscal_year_id'=>$fyId,'legal_entity_id'=>$eid,'name'=>'2026-10','start_date'=>'2026-10-01','end_date'=>'2026-10-31','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
            }
            // COA dasar per entitas
            $coas = [
                ['1000','Kas','asset','debit'],['1100','Bank','asset','debit'],['1200','Piutang Usaha','asset','debit'],
                ['1300','Persediaan','asset','debit'],['2000','Utang Usaha','liability','credit'],['2100','Utang Pajak','liability','credit'],
                ['2200','Deposit Pelanggan','liability','credit'],['2300','Utang Gaji','liability','credit'],['3000','Modal','equity','credit'],['4000','Pendapatan','revenue','credit'],
                ['5000','HPP','expense','debit'],['6000','Beban Operasional','expense','debit'],['6100','Beban Gaji','expense','debit'],
            ];
            foreach ($coas as $c) {
                DB::table('coas')->updateOrInsert(['legal_entity_id'=>$eid,'code'=>$c[0]],['name'=>$c[1],'type'=>$c[2],'normal_balance'=>$c[3],'is_postable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            }
            if (!DB::table('branches')->where(['legal_entity_id'=>$eid,'code'=>'PST'])->exists()) {
                $br = DB::table('branches')->insertGetId(['legal_entity_id'=>$eid,'code'=>'PST','name'=>'Kantor Pusat','address'=>'Jakarta','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
                DB::table('warehouses')->updateOrInsert(['branch_id'=>$br,'code'=>'GDG-UTM'],['name'=>'Gudang Utama','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
                DB::table('cash_accounts')->updateOrInsert(['legal_entity_id'=>$eid,'code'=>'KAS-PST'],['branch_id'=>$br,'name'=>'Kas Pusat','balance'=>0,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            }
        }
        foreach ([['ZRYN','ZRYN ID'],['ZRYN-DNM','ZRYN Denim'],['KZK-PC','Kazaka PetCare']] as $b) {
            DB::table('brands')->updateOrInsert(['code'=>$b[0]],['holding_id'=>$holdingId,'name'=>$b[1],'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        foreach (['pcs'=>'Pieces','kg'=>'Kilogram','box'=>'Box','svc'=>'Jasa'] as $c=>$n) {
            DB::table('uoms')->updateOrInsert(['code'=>$c],['name'=>$n,'created_at'=>now(),'updated_at'=>now()]);
        }
        DB::table('taxes')->updateOrInsert(['code'=>'PPN11'],['name'=>'PPN 11%','rate'=>11,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        foreach ([['CASH','Tunai',0],['NET14','Net 14',14],['NET30','Net 30',30]] as $pt) {
            DB::table('payment_terms')->updateOrInsert(['code'=>$pt[0]],['name'=>$pt[1],'days'=>$pt[2],'created_at'=>now(),'updated_at'=>now()]);
        }
        // approval matrix default
        foreach (['purchase_requisition','purchase_order','expense','payment','journal','stock_adjustment','payroll','refund','discount_exception'] as $dt) {
            if (!DB::table('approval_matrices')->where(['doc_type'=>$dt,'level'=>1])->exists()) {
                DB::table('approval_matrices')->insert(['doc_type'=>$dt,'legal_entity_id'=>null,'level'=>1,'min_amount'=>0,'allow_self_approve'=>false,'created_at'=>now(),'updated_at'=>now()]);
            }
        }
    }
}

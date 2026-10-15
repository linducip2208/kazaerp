<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class RolesPermissionsSeeder extends Seeder {
    public function run(): void {
        $groups = [
            'org' => ['org.view','org.create','org.update','org.delete'],
            'users' => ['users.view','users.create','users.update','users.deactivate'],
            'roles' => ['roles.view','roles.assign'],
            'crm' => ['crm.view','crm.create','crm.update','crm.delete','crm.convert'],
            'sales' => ['sales.view','sales.create','sales.update','sales.approve','sales.refund','sales.void','pos.operate','pos.shift'],
            'inventory' => ['inventory.view','inventory.receive','inventory.issue','inventory.transfer','inventory.adjust','inventory.count'],
            'purchasing' => ['purchasing.view','purchasing.create','purchasing.approve','purchasing.receive','purchasing.return'],
            'finance' => ['finance.view','finance.post','finance.approve','finance.close','finance.sensitive'],
            'hr' => ['hr.view','hr.manage','hr.payroll','hr.salary'],
            'pet' => ['pet.view','pet.treat','pet.groom','pet.board','pet.transport'],
            'mfg' => ['mfg.view','mfg.plan','mfg.execute'],
            'thrift' => ['thrift.view','thrift.manage','thrift.live'],
            'studio' => ['studio.view','studio.manage'],
            'rental' => ['rental.view','rental.manage'],
            'marketing' => ['marketing.view','marketing.manage'],
            'reports' => ['reports.view','reports.finance','reports.export'],
            'governance' => ['governance.view','governance.approve','audit.view'],
        ];
        foreach ($groups as $g => $perms) {
            foreach ($perms as $p) {
                DB::table('permissions')->updateOrInsert(['name'=>$p],['group'=>$g,'label'=>ucfirst(str_replace(['.','_'],' ',$p)),'created_at'=>now(),'updated_at'=>now()]);
            }
        }
        $roles = [
            'super_admin'=>'Super Administrator','holding_owner'=>'Holding Owner','executive'=>'Executive',
            'entity_admin'=>'Entity Administrator','finance_controller'=>'Finance Controller','finance_staff'=>'Finance Staff',
            'hr_manager'=>'HR Manager','purchasing_manager'=>'Purchasing Manager','inventory_manager'=>'Inventory Manager',
            'branch_manager'=>'Branch Manager','sales_staff'=>'Sales Staff','cashier'=>'Cashier',
            'operations_manager'=>'Operations Manager','module_operator'=>'Module Operator','auditor'=>'Auditor','viewer'=>'Read-only Viewer',
        ];
        foreach ($roles as $name=>$label) {
            DB::table('roles')->updateOrInsert(['name'=>$name],['label'=>$label,'is_system'=>in_array($name,['super_admin','viewer']),'created_at'=>now(),'updated_at'=>now()]);
        }
        $allIds = DB::table('permissions')->pluck('id')->all();
        $superId = DB::table('roles')->where('name','super_admin')->value('id');
        foreach ($allIds as $pid) DB::table('permission_role')->updateOrInsert(['role_id'=>$superId,'permission_id'=>$pid],[]);
        $viewerId = DB::table('roles')->where('name','viewer')->value('id');
        $viewPerms = DB::table('permissions')->where('name','like','%.view')->pluck('id')->all();
        foreach ($viewPerms as $pid) DB::table('permission_role')->updateOrInsert(['role_id'=>$viewerId,'permission_id'=>$pid],[]);
        // Branch manager: operasional terbatas
        $bm = DB::table('roles')->where('name','branch_manager')->value('id');
        foreach (['org.view','crm.view','crm.create','crm.update','sales.view','sales.create','inventory.view','reports.view'] as $p) {
            $pid = DB::table('permissions')->where('name',$p)->value('id');
            if ($pid) DB::table('permission_role')->updateOrInsert(['role_id'=>$bm,'permission_id'=>$pid],[]);
        }
        // Cashier
        $cs = DB::table('roles')->where('name','cashier')->value('id');
        foreach (['sales.view','pos.operate','pos.shift'] as $p) {
            $pid = DB::table('permissions')->where('name',$p)->value('id');
            if ($pid) DB::table('permission_role')->updateOrInsert(['role_id'=>$cs,'permission_id'=>$pid],[]);
        }
        // Auditor: read-only lintas modul + audit + laporan
        $au = DB::table('roles')->where('name','auditor')->value('id');
        foreach (DB::table('permissions')->where(function($q){ $q->where('name','like','%.view')->orWhereIn('name',['audit.view','reports.view','reports.export','api.access']); })->pluck('id') as $pid) {
            DB::table('permission_role')->updateOrInsert(['role_id'=>$au,'permission_id'=>$pid],[]);
        }
        // Module operator: operasional lintas modul non-sensitif (tanpa finance.post/approve/close, hr.salary/payroll, audit)
        $mo = DB::table('roles')->where('name','module_operator')->value('id');
        foreach (['org.view','crm.view','crm.create','crm.update','crm.convert','sales.view','sales.create','sales.approve','sales.void','inventory.view','inventory.receive','inventory.issue','inventory.transfer','purchasing.view','purchasing.create','purchasing.receive','finance.view','hr.view','hr.manage','pet.view','pet.treat','pet.groom','pet.board','pet.transport','mfg.view','mfg.plan','thrift.view','thrift.manage','thrift.live','studio.view','studio.manage','rental.view','rental.manage','marketing.view','marketing.manage','reports.view','reports.export','governance.view'] as $p) {
            $pid = DB::table('permissions')->where('name',$p)->value('id');
            if ($pid) DB::table('permission_role')->updateOrInsert(['role_id'=>$mo,'permission_id'=>$pid],[]);
        }
    }
}

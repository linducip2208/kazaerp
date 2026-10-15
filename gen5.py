"""Generator 5: models, User, seeders, auth, bootstrap, routes base."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

MODEL = """<?php
namespace App\\Models\\{ns};
use Illuminate\\Database\\Eloquent\\Model;{soft}{fact}
class {cls} extends Model {{
    protected $table = '{tbl}';
    protected $fillable = [{fill}];
    protected $casts = [{casts}];{rel}
}}
"""
def model(ns, cls, tbl, fill, casts="", soft=False, rel=""):
    fill_s = ", ".join([f"'{f}'" for f in fill])
    return MODEL.format(ns=ns, cls=cls, tbl=tbl, fill=fill_s, casts=casts,
        soft="\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;" if soft else "",
        fact="", rel=("\n" + rel if rel else ""))

# fix: soft delete trait inside class — handle by replacement
def model2(ns, cls, tbl, fill, casts="", soft=False, rel=""):
    fill_s = ", ".join([f"'{f}'" for f in fill])
    trait = "\n    use \\Illuminate\\Database\\Eloquent\\SoftDeletes;" if soft else ""
    return f"""<?php
namespace App\\Models\\{ns};
use Illuminate\\Database\\Eloquent\\Model;
class {cls} extends Model {{{trait}
    protected $table = '{tbl}';
    protected $fillable = [{fill_s}];
    protected $casts = [{casts}];{rel}
}}
"""

SPECS = [
 ("Org","Holding","holdings",["code","name","address","phone","is_active"],"",True),
 ("Org","LegalEntity","legal_entities",["holding_id","code","name","npwp","address","phone","email","is_active"],"",True),
 ("Org","Brand","brands",["holding_id","code","name","is_active"],"",True),
 ("Org","BusinessUnit","business_units",["holding_id","legal_entity_id","brand_id","code","name","is_active"],"",True),
 ("Org","Branch","branches",["legal_entity_id","brand_id","code","name","address","phone","is_active"],"",True),
 ("Org","Location","locations",["branch_id","code","name","kind","is_active"],"",False),
 ("Org","Warehouse","warehouses",["branch_id","code","name","is_active"],"",False),
 ("Org","Department","departments",["legal_entity_id","code","name","is_active"],"",False),
 ("Org","CostCenter","cost_centers",["legal_entity_id","code","name","is_active"],"",False),
 ("Org","ProfitCenter","profit_centers",["legal_entity_id","code","name","is_active"],"",False),
 ("Org","FiscalYear","fiscal_years",["legal_entity_id","name","start_date","end_date","is_closed"],"'is_closed'=>'boolean'",False),
 ("Org","FiscalPeriod","fiscal_periods",["fiscal_year_id","legal_entity_id","name","start_date","end_date","is_closed"],"'is_closed'=>'boolean'",False),
 ("Org","BankAccount","bank_accounts",["legal_entity_id","bank_name","account_no","holder","is_active"],"",False),
 ("Org","CompanyConfig","company_configs",["legal_entity_id","key","value"],"",False),
 ("Identity","Role","roles",["name","label","is_system"],"'is_system'=>'boolean'",False),
 ("Identity","Permission","permissions",["name","group","label"],"",False),
 ("Identity","AuditLog","audit_logs",["actor_id","action","entity","record_id","changes","ip"],"",False),
 ("Identity","LoginActivity","login_activities",["user_id","email","ip","agent","success"],"'success'=>'boolean'",False),
 ("Master","Category","categories",["kind","code","name","parent_id","is_active"],"",True),
 ("Master","Uom","uoms",["code","name"],"",False),
 ("Master","Tax","taxes",["code","name","rate","is_active"],"'rate'=>'decimal:4'",False),
 ("Master","PaymentTerm","payment_terms",["code","name","days"],"'days'=>'integer'",False),
 ("Master","Customer","customers",["legal_entity_id","branch_id","code","name","kind","email","phone","tier","sales_owner_id","is_active"],"",True),
 ("Master","Supplier","suppliers",["legal_entity_id","code","name","email","phone","bank_account","rating","is_active"],"'rating'=>'integer'",True),
 ("Master","Product","products",["legal_entity_id","brand_id","category_id","uom_id","sku","barcode","name","kind","cost_price","sell_price","wholesale_price","reseller_price","is_sellable","is_purchasable","is_active"],"'cost_price'=>'decimal:2','sell_price'=>'decimal:2'",True),
 ("Master","ProductVariant","product_variants",["product_id","sku","option_name","option_value","sell_price","is_active"],"'sell_price'=>'decimal:2'",False),
 ("Master","PriceList","price_lists",["legal_entity_id","code","name","valid_from","valid_to","is_active"],"",False),
 ("Master","Lead","leads",["legal_entity_id","name","phone","email","source_id","stage","lost_reason","owner_id","converted_customer_id"],"",False),
 ("Master","Opportunity","opportunities",["legal_entity_id","lead_id","customer_id","title","amount","stage","probability","owner_id"],"'amount'=>'decimal:2'",False),
 ("Crm","Followup","followups",["owner_type","owner_id","assigned_to","due_at","status","note","done_at"],"",False),
 ("Crm","Complaint","complaints",["legal_entity_id","customer_id","subject","body","status","resolution"],"",False),
 ("Sales","Quotation","quotations",["legal_entity_id","branch_id","number","date","customer_id","channel_id","valid_until","status","subtotal","discount","tax","grand_total","note","created_by"],"'grand_total'=>'decimal:2'",False),
 ("Sales","SalesOrder","sales_orders",["legal_entity_id","branch_id","number","date","customer_id","quotation_id","paid_total","status","subtotal","discount","tax","grand_total","note","created_by"],"'grand_total'=>'decimal:2'",False),
 ("Sales","DeliveryOrder","delivery_orders",["legal_entity_id","branch_id","number","date","sales_order_id","customer_id","warehouse_id","status","subtotal","discount","tax","grand_total","note","created_by"],"",False),
 ("Sales","Invoice","invoices",["legal_entity_id","branch_id","number","date","customer_id","sales_order_id","due_date","paid_total","pay_status","status","subtotal","discount","tax","grand_total","note","created_by"],"'grand_total'=>'decimal:2'",False),
 ("Sales","CreditNote","credit_notes",["legal_entity_id","branch_id","number","date","invoice_id","customer_id","status","subtotal","discount","tax","grand_total","note","created_by"],"",False),
 ("Sales","SalesReturn","sales_returns",["legal_entity_id","branch_id","number","date","invoice_id","customer_id","status","subtotal","discount","tax","grand_total","note","created_by"],"",False),
 ("Sales","CustomerDeposit","customer_deposits",["legal_entity_id","branch_id","number","date","customer_id","amount","used_amount","status"],"'amount'=>'decimal:2'",False),
 ("Sales","SalesPayment","sales_payments",["legal_entity_id","branch_id","number","date","invoice_id","customer_id","method","amount","reference","created_by"],"'amount'=>'decimal:2'",False),
 ("Sales","PosShift","pos_shifts",["legal_entity_id","branch_id","cashier_id","opened_at","closed_at","opening_cash","closing_cash","system_total","difference","status"],"",False),
 ("Sales","PosSale","pos_sales",["legal_entity_id","branch_id","number","date","shift_id","customer_id","status","subtotal","discount","tax","grand_total","pay_method","created_by"],"",False),
 ("Inventory","Batch","batches",["product_id","lot_no","produced_at","expired_at","qc_status"],"",False),
 ("Inventory","StockLedger","stock_ledgers",["warehouse_id","product_id","variant_id","batch_id","qty","unit_cost","ref_type","ref_id","note","created_by"],"'qty'=>'decimal:4'",False),
 ("Inventory","StockBalance","stock_balances",["warehouse_id","product_id","variant_id","batch_id","qty_on_hand","qty_reserved"],"'qty_on_hand'=>'decimal:4'",False),
 ("Inventory","GoodsReceipt","goods_receipts",["legal_entity_id","branch_id","warehouse_id","number","date","ref_type","ref_id","status","note","created_by"],"",False),
 ("Inventory","GoodsIssue","goods_issues",["legal_entity_id","branch_id","warehouse_id","number","date","ref_type","ref_id","status","note","created_by"],"",False),
 ("Inventory","StockTransfer","stock_transfers",["legal_entity_id","number","date","from_warehouse_id","to_warehouse_id","status","created_by"],"",False),
 ("Inventory","StockAdjustment","stock_adjustments",["legal_entity_id","warehouse_id","number","date","kind","status","reason","created_by"],"",False),
 ("Inventory","StockCount","stock_counts",["warehouse_id","legal_entity_id","number","date","status"],"",False),
 ("Purchasing","PurchaseRequisition","purchase_requisitions",["legal_entity_id","branch_id","cost_center_id","number","date","status","is_emergency","emergency_reason","requested_by"],"'is_emergency'=>'boolean'",False),
 ("Purchasing","Rfq","rfqs",["legal_entity_id","purchase_requisition_id","number","date","deadline","status"],"",False),
 ("Purchasing","VendorQuote","vendor_quotes",["rfq_id","supplier_id","price","lead_days","is_winner"],"'price'=>'decimal:2'",False),
 ("Purchasing","PurchaseOrder","purchase_orders",["legal_entity_id","branch_id","number","date","supplier_id","requisition_id","version","status","subtotal","tax","grand_total","created_by"],"'grand_total'=>'decimal:2'",False),
 ("Purchasing","VendorInvoice","vendor_invoices",["legal_entity_id","purchase_order_id","supplier_id","number","vendor_ref","date","due_date","grand_total","paid_total","status","match_status"],"'grand_total'=>'decimal:2'",False),
 ("Purchasing","PurchaseReturn","purchase_returns",["legal_entity_id","purchase_order_id","supplier_id","number","date","status","grand_total"],"",False),
 ("Finance","Coa","coas",["legal_entity_id","code","name","type","normal_balance","parent_id","is_postable","is_active"],"",False),
 ("Finance","Journal","journals",["legal_entity_id","branch_id","date","number","description","source_type","source_id","total_debit","total_credit","status","idempotency_key","created_by"],"",False),
 ("Finance","JournalLine","journal_lines",["journal_id","coa_id","debit","credit","cost_center_id","note"],"",False),
 ("Finance","CashAccount","cash_accounts",["legal_entity_id","branch_id","code","name","balance","is_active"],"'balance'=>'decimal:2'",False),
 ("Finance","CashReceipt","cash_receipts",["legal_entity_id","cash_account_id","number","date","amount","payer","description","status"],"",False),
 ("Finance","CashPayment","cash_payments",["legal_entity_id","cash_account_id","number","date","amount","payee","description","status"],"",False),
 ("Finance","BankTransfer","bank_transfers",["legal_entity_id","from_bank_id","to_bank_id","number","date","amount","status"],"",False),
 ("Finance","Expense","expenses",["legal_entity_id","branch_id","cost_center_id","number","date","title","amount","status","requested_by"],"",False),
 ("Finance","Budget","budgets",["legal_entity_id","fiscal_year_id","cost_center_id","name","status"],"",False),
 ("Finance","IntercompanyTxn","intercompany_txns",["from_entity_id","to_entity_id","number","date","amount","status","note"],"",False),
 ("Hr","Position","positions",["legal_entity_id","department_id","code","name","is_active"],"",False),
 ("Hr","Employee","employees",["legal_entity_id","branch_id","department_id","position_id","user_id","nip","name","gender","birth_date","phone","address","join_date","status"],"",True),
 ("Hr","Attendance","attendances",["employee_id","date","check_in","check_out","status"],"",False),
 ("Hr","LeaveRequest","leave_requests",["employee_id","from_date","to_date","kind","reason","status"],"",False),
 ("Hr","Overtime","overtimes",["employee_id","date","hours","amount","status"],"",False),
 ("Hr","PayrollPeriod","payroll_periods",["legal_entity_id","name","start_date","end_date","status"],"",False),
 ("Hr","PayrollComponent","payroll_components",["legal_entity_id","code","name","kind","formula","is_active"],"",False),
 ("Hr","Payroll","payrolls",["payroll_period_id","legal_entity_id","number","status","grand_total","approved_by"],"",False),
 ("Hr","Payslip","payslips",["payroll_id","employee_id","number","net"],"",False),
 ("Pet","PetOwner","pet_owners",["legal_entity_id","customer_id","name","phone","email","address"],"",True),
 ("Pet","Pet","pets",["pet_owner_id","name","species","breed","gender","birth_date","weight","color","notes"],"",True),
 ("Pet","VetAppointment","vet_appointments",["legal_entity_id","branch_id","pet_id","scheduled_at","vet_id","status","complaint"],"",False),
 ("Pet","Consultation","consultations",["vet_appointment_id","pet_id","clinical_notes","diagnosis","treatment_plan","vet_id"],"",False),
 ("Pet","GroomingService","grooming_services",["legal_entity_id","code","name","price","duration_min","is_active"],"",False),
 ("Pet","GroomingOrder","grooming_orders",["legal_entity_id","branch_id","number","date","pet_id","grooming_service_id","groomer_id","status","checklist","before_condition","grand_total"],"",False),
 ("Pet","BoardingRoom","boarding_rooms",["branch_id","code","name","capacity","rate","is_active"],"",False),
 ("Pet","BoardingReservation","boarding_reservations",["legal_entity_id","branch_id","number","pet_id","boarding_room_id","check_in","check_out","feeding","medication_note","deposit","grand_total","status"],"",False),
 ("Pet","Vehicle","vehicles",["legal_entity_id","plate","name","capacity","status"],"",False),
 ("Pet","TransportOrder","transport_orders",["legal_entity_id","number","date","pet_id","pickup_address","delivery_address","scheduled_at","price","status"],"",False),
 ("Pet","Trip","trips",["transport_order_id","vehicle_id","driver_id","depart_at","arrive_at","status","fuel_cost","toll_cost","handover_proof"],"",False),
 ("Mfg","Collection","collections",["brand_id","code","name","season"],"",False),
 ("Mfg","Design","designs",["collection_id","legal_entity_id","code","name","status"],"",False),
 ("Mfg","DesignVersion","design_versions",["design_id","version","file_path","print_placement","print_size","status","approved_by"],"",False),
 ("Mfg","Bom","boms",["legal_entity_id","product_id","version","status"],"",False),
 ("Mfg","WorkOrder","work_orders",["legal_entity_id","number","date","bom_id","bom_version_locked","qty_planned","qty_good","qty_reject","status"],"",False),
 ("Thrift","ThriftLot","thrift_lots",["legal_entity_id","number","date","acquisition_cost","status"],"",False),
 ("Thrift","ThriftItem","thrift_items",["thrift_lot_id","code","name","grade","condition_note","cost_share","process_cost","price","status","rack_id"],"",False),
 ("Thrift","LiveSession","live_sessions",["legal_entity_id","title","scheduled_at","host_id","status"],"",False),
 ("Thrift","LiveOrder","live_orders",["live_session_id","thrift_item_id","customer_id","price","pay_deadline","status"],"",False),
 ("Studio","StudioProject","studio_projects",["legal_entity_id","customer_id","title","brief","status","price","cost"],"",False),
 ("Studio","StudioDeliverable","studio_deliverables",["studio_project_id","title","version","file_path","status"],"",False),
 ("Rental","RentalCatalog","rental_catalogs",["legal_entity_id","code","name","rate","is_active"],"",False),
 ("Rental","RentalUnit","rental_units",["rental_catalog_id","code","size","condition_note","status"],"",False),
 ("Rental","RentalReservation","rental_reservations",["legal_entity_id","number","rental_unit_id","customer_id","start_date","end_date","rate","deposit","status"],"",False),
 ("Marketing","Campaign","campaigns",["legal_entity_id","name","start_date","end_date","budget","status"],"",False),
 ("Marketing","Voucher","vouchers",["legal_entity_id","code","name","value","min_txn","valid_from","valid_to","quota","used","is_active"],"",False),
 ("Marketing","Membership","memberships",["customer_id","tier","joined_at","expires_at"],"",False),
 ("Gov","ApprovalMatrix","approval_matrices",["doc_type","legal_entity_id","level","min_amount","role_id","allow_self_approve"],"",False),
 ("Gov","Approval","approvals",["doc_type","doc_id","level","status","requested_by","decided_by","note"],"",False),
]
for ns, cls, tbl, fill, casts, soft in SPECS:
    w(f"app/Models/{ns}/{cls}.php", model2(ns, cls, tbl, fill, casts, soft))

# ---------- User model ----------
w("app/Models/User.php", """<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Foundation\\Auth\\User as Authenticatable;
use Illuminate\\Notifications\\Notifiable;
use Illuminate\\Support\\Facades\\DB;
class User extends Authenticatable {
    use HasFactory, Notifiable;
    protected $fillable = ['name','email','password','is_active','holding_id','legal_entity_id','branch_id','last_login_at'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array {
        return ['email_verified_at' => 'datetime','password' => 'hashed','is_active'=>'boolean','last_login_at'=>'datetime'];
    }
    public function roles() { return $this->belongsToMany(\\App\\Models\\Identity\\Role::class, 'role_user'); }
    public function hasRole(string ...$names): bool {
        $roles = $this->relationLoaded('roles') ? $this->roles->pluck('name') : $this->roles()->pluck('name');
        foreach ($names as $n) if ($roles->contains($n)) return true;
        return false;
    }
    public function hasPermission(string $perm): bool {
        if (!$this->is_active) return false;
        if ($this->hasRole('super_admin')) return true;
        return DB::table('permission_role')
            ->join('role_user','role_user.role_id','=','permission_role.role_id')
            ->join('permissions','permissions.id','=','permission_role.permission_id')
            ->where('role_user.user_id',$this->id)->where('permissions.name',$perm)->exists();
    }
    public function inScope(string $type, int $id): bool {
        if ($this->hasRole('super_admin','holding_owner','executive','auditor')) return true;
        if ($type==='legal_entity' && (int)$this->legal_entity_id===$id) return true;
        if ($type==='branch' && (int)$this->branch_id===$id) return true;
        return DB::table('user_scopes')->where('user_id',$this->id)->where('scope_type',$type)->where('scope_id',$id)->exists();
    }
    public function scopeIds(string $type): array {
        return DB::table('user_scopes')->where('user_id',$this->id)->where('scope_type',$type)->pluck('scope_id')->all();
    }
}
""")

# ---------- seeders ----------
w("database/seeders/RolesPermissionsSeeder.php", """<?php
namespace Database\\Seeders;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;
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
    }
}
""")
w("database/seeders/OrgSeeder.php", """<?php
namespace Database\\Seeders;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;
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
                ['2200','Deposit Pelanggan','liability','credit'],['3000','Modal','equity','credit'],['4000','Pendapatan','revenue','credit'],
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
""")
w("database/seeders/DemoSeeder.php", """<?php
namespace Database\\Seeders;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Hash;
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
""")
w("database/seeders/DatabaseSeeder.php", """<?php
namespace Database\\Seeders;
use Illuminate\\Database\\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([RolesPermissionsSeeder::class, OrgSeeder::class]);
        if (!app()->environment('production')) $this->call([DemoSeeder::class]);
    }
}
""")

# ---------- bootstrap/app.php ----------
w("bootstrap/app.php", """<?php
use Illuminate\\Foundation\\Application;
use Illuminate\\Foundation\\Configuration\\Exceptions;
use Illuminate\\Foundation\\Configuration\\Middleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'perm' => \\App\\Http\\Middleware\\CheckPermission::class,
            'ctx' => \\App\\Http\\Middleware\\ActiveContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
""")

# ---------- auth controllers ----------
w("app/Http/Controllers/AuthController.php", """<?php
namespace App\\Http\\Controllers;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Facades\\DB;
class AuthController extends Controller {
    public function showLogin() {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }
    public function login(Request $r) {
        $r->validate(['email'=>'required|email','password'=>'required'],[],['email'=>'Email','password'=>'Kata sandi']);
        $email = strtolower(trim($r->email));
        $user = \\App\\Models\\User::where('email',$email)->first();
        if ($user && !$user->is_active) {
            DB::table('login_activities')->insert(['user_id'=>$user->id,'email'=>$email,'ip'=>$r->ip(),'agent'=>substr((string)$r->userAgent(),0,250),'success'=>false,'created_at'=>now()]);
            return back()->withErrors(['email'=>'Akun nonaktif. Hubungi administrator.'])->onlyInput('email');
        }
        if (!Auth::attempt(['email'=>$email,'password'=>$r->password], $r->boolean('remember'))) {
            DB::table('login_activities')->insert(['user_id'=>$user?->id,'email'=>$email,'ip'=>$r->ip(),'agent'=>substr((string)$r->userAgent(),0,250),'success'=>false,'created_at'=>now()]);
            return back()->withErrors(['email'=>'Email atau kata sandi salah.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        $u = Auth::user();
        $u->forceFill(['last_login_at'=>now()])->save();
        $r->session()->put('erp_context',['holding_id'=>$u->holding_id,'legal_entity_id'=>$u->legal_entity_id,'branch_id'=>$u->branch_id]);
        DB::table('login_activities')->insert(['user_id'=>$u->id,'email'=>$email,'ip'=>$r->ip(),'agent'=>substr((string)$r->userAgent(),0,250),'success'=>true,'created_at'=>now()]);
        return redirect()->intended(route('dashboard'));
    }
    public function logout(Request $r) {
        Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function profile() { return view('auth.profile'); }
    public function updateProfile(Request $r) {
        $r->validate(['name'=>'required|string|max:100']);
        $r->user()->update(['name'=>$r->name]);
        return back()->with('ok','Profil diperbarui.');
    }
    public function switchContext(Request $r) {
        $r->validate(['legal_entity_id'=>'nullable|exists:legal_entities,id','branch_id'=>'nullable|exists:branches,id']);
        $u = $r->user();
        if ($r->legal_entity_id && !$u->inScope('legal_entity',(int)$r->legal_entity_id) && !$u->hasRole('super_admin','holding_owner','executive')) abort(403);
        if ($r->branch_id && !$u->inScope('branch',(int)$r->branch_id) && (int)$u->branch_id!==(int)$r->branch_id && !$u->hasRole('super_admin','holding_owner','executive')) abort(403);
        $ctx = $r->session()->get('erp_context',[]);
        if ($r->legal_entity_id) $ctx['legal_entity_id'] = (int)$r->legal_entity_id;
        if ($r->branch_id) $ctx['branch_id'] = (int)$r->branch_id;
        $r->session()->put('erp_context',$ctx);
        return back()->with('ok','Konteks aktif diperbarui.');
    }
}
""")
w("app/Http/Controllers/DashboardController.php", """<?php
namespace App\\Http\\Controllers;
use Illuminate\\Support\\Facades\\DB;
class DashboardController extends Controller {
    public function index() {
        $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $bid = $ctx['branch_id'] ?? auth()->user()->branch_id;
        $inv = DB::table('invoices')->when($eid, fn($q)=>$q->where('legal_entity_id',$eid));
        $salesMonth = (clone $inv)->where('date','>=',now()->startOfMonth()->toDateString())->sum('grand_total');
        $receivable = (clone $inv)->whereIn('pay_status',['unpaid','partial'])->sum(DB::raw('grand_total - paid_total'));
        $payable = DB::table('vendor_invoices')->when($eid, fn($q)=>$q->where('legal_entity_id',$eid))->whereIn('status',['approved','partial','draft'])->sum(DB::raw('grand_total - paid_total'));
        $lowStock = DB::table('stock_balances')->join('products','products.id','=','stock_balances.product_id')
            ->when($eid, fn($q)=>$q->where('products.legal_entity_id',$eid))
            ->whereRaw('qty_on_hand - qty_reserved <= 5')->count();
        $pendingApprovals = DB::table('approvals')->where('status','pending')->count();
        $orders = DB::table('sales_orders')->when($eid, fn($q)=>$q->where('legal_entity_id',$eid))->where('status','!=','cancelled')->orderByDesc('id')->limit(8)->get();
        $perEntity = [];
        if (auth()->user()->hasRole('super_admin','holding_owner','executive')) {
            $perEntity = DB::table('invoices')->join('legal_entities','legal_entities.id','=','invoices.legal_entity_id')
                ->selectRaw('legal_entities.code, legal_entities.name, SUM(invoices.grand_total) as total')
                ->where('invoices.date','>=',now()->startOfMonth()->toDateString())
                ->groupBy('legal_entities.code','legal_entities.name')->orderByDesc('total')->get();
        }
        return view('dashboard', compact('salesMonth','receivable','payable','lowStock','pendingApprovals','orders','perEntity','eid','bid'));
    }
    public function health() { return response()->json(['ok'=>true,'time'=>now()->toIso8601String()]); }
}
""")

# ---------- composer autoload helpers ----------
print("gen5 done")

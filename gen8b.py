"""Generator 8b: pet/business/gov/report controllers + routes."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

# ---------- generic simple controllers for pet/business via CrudController ----------
GEN = [
 ("Pet","OwnerController","App\\Models\\Pet\\PetOwner","pet.owners","modules.pet.owners","Pemilik Hewan","pet",
  {'name':'Nama','phone':'Telepon'},{'name':'Nama','phone':'Telepon'},{'name':'required|max:150'},['name','phone']),
 ("Pet","PetController2","App\\Models\\Pet\\Pet","pet.pets","modules.pet.pets","Hewan","pet",
  {'name':'Nama','species':'Spesies'},{'name':'Nama','species':'Spesies'},{'name':'required|max:100'},['name']),
 ("Pet","VetAppointmentController","App\\Models\\Pet\\VetAppointment","pet.vet","modules.pet.vet","Janji Dokter Hewan","pet",
  {'scheduled_at':'Jadwal','status':'Status'},{'status':'Status'},{},['status']),
 ("Pet","GroomingOrderController","App\\Models\\Pet\\GroomingOrder","pet.grooming","modules.pet.grooming","Grooming","pet",
  {'number':'Nomor','status':'Status'},{'status':'Status'},{},['number']),
 ("Pet","BoardingReservationController","App\\Models\\Pet\\BoardingReservation","pet.boarding","modules.pet.boarding","Boarding","pet",
  {'number':'Nomor','check_in':'Masuk','status':'Status'},{'status':'Status'},{},['number']),
 ("Pet","TransportOrderController","App\\Models\\Pet\\TransportOrder","pet.transport","modules.pet.transport","Transport","pet",
  {'number':'Nomor','status':'Status'},{'status':'Status'},{},['number']),
 ("Mfg","DesignController","App\\Models\\Mfg\\Design","mfg.designs","modules.mfg.designs","Desain","mfg",
  {'code':'Kode','name':'Nama','status':'Status'},{'code':'Kode','name':'Nama'},{'code':'required|max:30','name':'required|max:150'},['code','name']),
 ("Mfg","BomController","App\\Models\\Mfg\\Bom","mfg.boms","modules.mfg.boms","BOM","mfg",
  {'version':'Versi','status':'Status'},{'status':'Status'},{},['status']),
 ("Mfg","WorkOrderController","App\\Models\\Mfg\\WorkOrder","mfg.workorders","modules.mfg.workorders","Work Order","mfg",
  {'number':'Nomor','status':'Status'},{'status':'Status'},{},['number']),
 ("Thrift","LotController","App\\Models\\Thrift\\ThriftLot","thrift.lots","modules.thrift.lots","Lot Thrift","thrift",
  {'number':'Nomor','status':'Status'},{'status':'Status'},{},['number']),
 ("Thrift","ItemController","App\\Models\\Thrift\\ThriftItem","thrift.items","modules.thrift.items","Item Thrift","thrift",
  {'code':'Kode','name':'Nama','status':'Status'},{'code':'Kode','name':'Nama'},{'code':'required','name':'required'},['code','name']),
 ("Studio","ProjectController","App\\Models\\Studio\\StudioProject","studio.projects","modules.studio.projects","Proyek Studio","studio",
  {'title':'Judul','status':'Status'},{'title':'Judul'},{'title':'required|max:150'},['title']),
 ("Rental","CatalogController","App\\Models\\Rental\\RentalCatalog","rental.catalogs","modules.rental.catalogs","Katalog Sewa","rental",
  {'code':'Kode','name':'Nama'},{'code':'Kode','name':'Nama'},{'code':'required','name':'required'},['code','name']),
 ("Rental","ReservationController","App\\Models\\Rental\\RentalReservation","rental.reservations","modules.rental.reservations","Reservasi Sewa","rental",
  {'number':'Nomor','status':'Status'},{'status':'Status'},{},['number']),
 ("Marketing","CampaignController","App\\Models\\Marketing\\Campaign","marketing.campaigns","modules.marketing.campaigns","Kampanye","marketing",
  {'name':'Nama','status':'Status'},{'name':'Nama'},{'name':'required|max:150'},['name']),
 ("Marketing","VoucherController","App\\Models\\Marketing\\Voucher","marketing.vouchers","modules.marketing.vouchers","Voucher","marketing",
  {'code':'Kode','name':'Nama'},{'code':'Kode','name':'Nama'},{'code':'required','name':'required'},['code','name']),
]
for ns, cls, model, route, view, title, perm, cols_def, fields_def, rules, search in GEN:
    if cls=="PetController2": cls="PetController"
    cols = ",\n        ".join([f"'{k}' => '{v}'" for k,v in cols_def.items()])
    # fields: build from fields_def keys with text type (keep minimal; full form via custom views later)
    flds = ",\n        ".join([f"['name'=>'{k}','label'=>'{v}','type'=>'text']" for k,v in fields_def.items()])
    rules_s = ",\n        ".join([f"'{k}' => '{v}'" for k,v in rules.items()]) if rules else ""
    search_s = ", ".join([f"'{s}'" for s in search])
    w(f"app/Http/Controllers/{ns}/{cls}.php", f"""<?php
namespace App\\Http\\Controllers\\{ns};
use App\\Http\\Controllers\\CrudController;
class {cls} extends CrudController {{
    protected string $model = \\{model}::class;
    protected string $routePrefix = '{route}';
    protected string $viewPrefix = '{view}';
    protected string $title = '{title}';
    protected string $perm = '{perm}';
    protected array $columns = [
        {cols}
    ];
    protected array $fields = [
        {flds}
    ];
    protected array $rules = [
        {rules_s}
    ];
    protected array $searchable = [{search_s}];
    protected ?string $entityField = {repr('legal_entity_id') if ('legal_entity_id' in (model)) else 'null'};
}}
""")

# ---------- business logic extras ----------
w("app/Http/Controllers/Rental/RentalBookingController.php", """<?php
namespace App\\Http\\Controllers\\Rental;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
class RentalBookingController extends Controller {
    public function store(Request $r) {
        $r->validate(['rental_unit_id'=>'required|exists:rental_units,id','customer_id'=>'required|exists:customers,id',
            'start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','rate'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        // cegah bentrok: unit sama + periode overlap + status aktif
        $clash = DB::table('rental_reservations')->where('rental_unit_id',$r->rental_unit_id)
            ->whereIn('status',['reserved','active','fitting','preparation'])
            ->where('start_date','<=',$r->end_date)->where('end_date','>=',$r->start_date)->exists();
        if ($clash) return back()->withErrors(['msg'=>'Unit fisik bentrok pada periode tersebut (termasuk fitting/persiapan).'])->withInput();
        $ent = \\App\\Models\\Org\\LegalEntity::find($eid);
        $number = DocNumberService::next('RNT',$eid,null,$ent?->code);
        DB::table('rental_reservations')->insert(['legal_entity_id'=>$eid,'number'=>$number,'rental_unit_id'=>$r->rental_unit_id,
            'customer_id'=>$r->customer_id,'start_date'=>$r->start_date,'end_date'=>$r->end_date,'rate'=>$r->rate,
            'deposit'=>$r->deposit ?? 0,'status'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('rental.reservations.index')->with('ok','Reservasi sewa tersimpan (deposit = kewajiban, bukan pendapatan).');
    }
}
""")
w("app/Http/Controllers/Thrift/LiveOrderController.php", """<?php
namespace App\\Http\\Controllers\\Thrift;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
class LiveOrderController extends Controller {
    public function reserve(Request $r) {
        $r->validate(['live_session_id'=>'required|exists:live_sessions,id','thrift_item_id'=>'required|exists:thrift_items,id','customer_id'=>'required|exists:customers,id','price'=>'required|numeric|min:0']);
        try {
            DB::transaction(function() use ($r) {
                $item = DB::table('thrift_items')->where('id',$r->thrift_item_id)->lockForUpdate()->first();
                if ($item->status==='sold') throw new \\RuntimeException('Barang sudah terjual.');
                if (DB::table('live_orders')->where('thrift_item_id',$r->thrift_item_id)->exists()) throw new \\RuntimeException('Barang sudah direservasi sesi lain.');
                DB::table('live_orders')->insert(['live_session_id'=>$r->live_session_id,'thrift_item_id'=>$r->thrift_item_id,
                    'customer_id'=>$r->customer_id,'price'=>$r->price,'pay_deadline'=>now()->addHours(24),'status'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
                DB::table('thrift_items')->where('id',$r->thrift_item_id)->update(['status'=>'reserved']);
            });
        } catch (\\Throwable $e) { return back()->withErrors(['msg'=>$e->getMessage()]); }
        return back()->with('ok','Item direservasi (identitas unik, anti double-sell).');
    }
}
""")
w("app/Http/Controllers/Pet/BoardingCheckinController.php", """<?php
namespace App\\Http\\Controllers\\Pet;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
class BoardingCheckinController extends Controller {
    public function store(Request $r) {
        $r->validate(['pet_id'=>'required|exists:pets,id','boarding_room_id'=>'required|exists:boarding_rooms,id',
            'check_in'=>'required|date','check_out'=>'required|date|after:check_in']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $room = DB::table('boarding_rooms')->where('id',$r->boarding_room_id)->first();
        $overlap = DB::table('boarding_reservations')->where('boarding_room_id',$room->id)
            ->whereIn('status',['reserved','checked_in'])
            ->where('check_in','<',$r->check_out)->where('check_out','>',$r->check_in)->count();
        if ($overlap >= (int)$room->capacity) return back()->withErrors(['msg'=>'Kapasitas kamar penuh pada periode tersebut (anti overcapacity).']);
        $ent = \\App\\Models\\Org\\LegalEntity::find($eid);
        $number = DocNumberService::next('BRD',$eid,$ctx['branch_id'] ?? $u->branch_id,$ent?->code);
        $days = max(1, (int)ceil((strtotime($r->check_out)-strtotime($r->check_in))/86400));
        DB::table('boarding_reservations')->insert(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,
            'number'=>$number,'pet_id'=>$r->pet_id,'boarding_room_id'=>$room->id,'check_in'=>$r->check_in,'check_out'=>$r->check_out,
            'feeding'=>$r->feeding,'medication_note'=>$r->medication_note,'deposit'=>$r->deposit ?? 0,
            'grand_total'=>$days*(float)$room->rate,'status'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('pet.boarding.index')->with('ok','Reservasi boarding tersimpan (kapasitas dicek).');
    }
}
""")
w("app/Http/Controllers/Gov/ApprovalController.php", """<?php
namespace App\\Http\\Controllers\\Gov;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\ApprovalService;
class ApprovalController extends Controller {
    public function index(Request $r) {
        $rows = DB::table('approvals')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('modules.gov.approvals.index', compact('rows'));
    }
    public function decide(Request $r, $id) {
        $r->validate(['decision'=>'required|in:approved,rejected','note'=>'nullable|max:500']);
        ApprovalService::decide((int)$id, $r->decision, $r->note);
        return back()->with('ok','Keputusan approval dicatat.');
    }
}
""")
w("app/Http/Controllers/ReportController.php", """<?php
namespace App\\Http\\Controllers;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
class ReportController extends Controller {
    public function sales(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = DB::table('invoices')->join('customers','customers.id','=','invoices.customer_id')
            ->where('invoices.legal_entity_id',$eid)
            ->when($r->from,fn($q)=>$q->where('invoices.date','>=',$r->from))->when($r->to,fn($q)=>$q->where('invoices.date','<=',$r->to))
            ->select('invoices.number','invoices.date','customers.name as customer','invoices.grand_total','invoices.paid_total','invoices.pay_status')
            ->orderByDesc('invoices.id')->paginate(25)->withQueryString();
        $sum = DB::table('invoices')->where('legal_entity_id',$eid)
            ->when($r->from,fn($q)=>$q->where('date','>=',$r->from))->when($r->to,fn($q)=>$q->where('date','<=',$r->to))
            ->selectRaw('SUM(grand_total) t, SUM(paid_total) p')->first();
        if ($r->get('export')==='csv') {
            $csv="nomor,tanggal,pelanggan,total,bayar,status\\n";
            foreach ($rows->items() as $x) $csv .= "\\"{$x->number}\\",{$x->date},\\"{$x->customer}\\",{$x->grand_total},{$x->paid_total},{$x->pay_status}\\n";
            return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="penjualan.csv"']);
        }
        return view('modules.reports.sales', compact('rows','sum'));
    }
    public function inventory(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = DB::table('stock_ledgers')->join('products','products.id','=','stock_ledgers.product_id')
            ->where('products.legal_entity_id',$eid)
            ->when($r->from,fn($q)=>$q->where('stock_ledgers.created_at','>=',$r->from))->when($r->to,fn($q)=>$q->where('stock_ledgers.created_at','<=',$r->to))
            ->select('stock_ledgers.*','products.sku','products.name as product')->orderByDesc('stock_ledgers.id')->paginate(25)->withQueryString();
        return view('modules.reports.inventory', compact('rows'));
    }
    public function purchase(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = DB::table('purchase_orders')->join('suppliers','suppliers.id','=','purchase_orders.supplier_id')
            ->where('purchase_orders.legal_entity_id',$eid)->select('purchase_orders.*','suppliers.name as supplier')
            ->orderByDesc('purchase_orders.id')->paginate(25)->withQueryString();
        return view('modules.reports.purchase', compact('rows'));
    }
}
""")
w("app/Http/Controllers/ImportController.php", """<?php
namespace App\\Http\\Controllers;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
class ImportController extends Controller {
    public function customers() { return view('modules.import.customers'); }
    public function customersStore(Request $r) {
        $r->validate(['file'=>'required|file|mimes:csv,txt|max:2048']);
        $path = $r->file('file')->getRealPath();
        $h = fopen($path,'r'); $head = fgetcsv($h); $ok=0; $errs=[];
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $ln=1;
        while (($row=fgetcsv($h))!==false) {
            $ln++; $d = array_combine($head,$row);
            if (empty($d['code'])||empty($d['name'])) { $errs[]="Baris $ln: code/nama wajib"; continue; }
            if (DB::table('customers')->where(['legal_entity_id'=>$eid,'code'=>$d['code']])->exists()) { $errs[]="Baris $ln: kode {$d['code']} duplikat"; continue; }
            DB::table('customers')->insert(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,'code'=>$d['code'],'name'=>$d['name'],'phone'=>$d['phone'] ?? null,'email'=>$d['email'] ?? null,'tier'=>'retail','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            $ok++;
        }
        fclose($h);
        return back()->with('ok',"Import selesai: $ok berhasil, ".count($errs)." gagal.")->with('import_errors',$errs);
    }
    public function products() { return view('modules.import.products'); }
    public function productsStore(Request $r) {
        $r->validate(['file'=>'required|file|mimes:csv,txt|max:2048']);
        $path = $r->file('file')->getRealPath();
        $h = fopen($path,'r'); $head = fgetcsv($h); $ok=0; $errs=[];
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $ln=1;
        while (($row=fgetcsv($h))!==false) {
            $ln++; $d = array_combine($head,$row);
            if (empty($d['sku'])||empty($d['name'])) { $errs[]="Baris $ln: sku/nama wajib"; continue; }
            if (!is_numeric($d['sell_price'] ?? null)) { $errs[]="Baris $ln: harga tidak valid"; continue; }
            if (DB::table('products')->where(['legal_entity_id'=>$eid,'sku'=>$d['sku']])->exists()) { $errs[]="Baris $ln: sku duplikat"; continue; }
            DB::table('products')->insert(['legal_entity_id'=>$eid,'sku'=>$d['sku'],'name'=>$d['name'],'kind'=>'goods','cost_price'=>$d['cost_price'] ?? 0,'sell_price'=>$d['sell_price'],'is_sellable'=>true,'is_purchasable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            $ok++;
        }
        fclose($h);
        return back()->with('ok',"Import selesai: $ok berhasil, ".count($errs)." gagal.")->with('import_errors',$errs);
    }
}
""")

# ---------- routes ----------
w("routes/modules/org.php", """<?php
use Illuminate\\Support\\Facades\\Route;
Route::middleware(['auth','ctx'])->prefix('org')->name('org.')->group(function () {
    Route::resource('holdings', \\App\\Http\\Controllers\\Org\\HoldingController::class)->middleware('perm:org.view');
    Route::resource('entities', \\App\\Http\\Controllers\\Org\\LegalEntityController::class)->middleware('perm:org.view');
    Route::resource('branches', \\App\\Http\\Controllers\\Org\\BranchController::class)->middleware('perm:org.view');
    Route::resource('warehouses', \\App\\Http\\Controllers\\Org\\WarehouseController::class)->middleware('perm:org.view');
});
Route::middleware(['auth','ctx'])->group(function () {
    Route::resource('users', \\App\\Http\\Controllers\\Identity\\UserController::class)->middleware('perm:users.view');
    Route::resource('roles', \\App\\Http\\Controllers\\Identity\\RoleController::class)->middleware('perm:roles.view');
});
""")
w("routes/modules/master.php", """<?php
use Illuminate\\Support\\Facades\\Route;
Route::middleware(['auth','ctx'])->prefix('master')->name('master.')->group(function () {
    Route::resource('products', \\App\\Http\\Controllers\\Master\\ProductController::class)->middleware('perm:crm.view');
    Route::resource('categories', \\App\\Http\\Controllers\\Master\\CategoryController::class)->middleware('perm:crm.view');
});
Route::middleware(['auth','ctx'])->prefix('crm')->name('crm.')->group(function () {
    Route::resource('customers', \\App\\Http\\Controllers\\Master\\CustomerController::class)->middleware('perm:crm.view');
    Route::resource('suppliers', \\App\\Http\\Controllers\\Master\\SupplierController::class)->middleware('perm:crm.view');
    Route::resource('leads', \\App\\Http\\Controllers\\Master\\LeadController::class)->middleware('perm:crm.view');
    Route::resource('opportunities', \\App\\Http\\Controllers\\Master\\OpportunityController::class)->middleware('perm:crm.view');
    Route::post('leads/{id}/convert', function ($id) {
        $lead = \\App\\Models\\Master\\Lead::findOrFail($id);
        if ($lead->converted_customer_id) return back()->withErrors(['msg'=>'Lead sudah dikonversi.']);
        $dup = \\App\\Models\\Master\\Customer::where('legal_entity_id',$lead->legal_entity_id)->where(function($q) use ($lead) {
            $q->where('name',$lead->name);
            if ($lead->phone) $q->orWhere('phone',$lead->phone);
        })->first();
        if ($dup) return back()->withErrors(['msg'=>'Calon duplikat dengan pelanggan '.$dup->code.' — konversi dibatalkan agar tidak duplikasi.']);
        $code = 'CUST-'.date('ym').'-'.str_pad((string)(\\App\\Models\\Master\\Customer::where('legal_entity_id',$lead->legal_entity_id)->count()+1),4,'0',STR_PAD_LEFT);
        $c = \\App\\Models\\Master\\Customer::create(['legal_entity_id'=>$lead->legal_entity_id,'code'=>$code,'name'=>$lead->name,'phone'=>$lead->phone,'email'=>$lead->email,'tier'=>'retail','sales_owner_id'=>$lead->owner_id,'is_active'=>true]);
        $lead->update(['stage'=>'converted','converted_customer_id'=>$c->id]);
        return redirect()->route('crm.customers.show',$c->id)->with('ok','Lead dikonversi tanpa duplikasi.');
    })->name('leads.convert')->middleware('perm:crm.convert');
});
""")
w("routes/modules/sales.php", """<?php
use Illuminate\\Support\\Facades\\Route;
Route::middleware(['auth','ctx'])->prefix('sales')->name('sales.')->group(function () {
    Route::resource('quotes', \\App\\Http\\Controllers\\Sales\\QuotationController::class)->middleware('perm:sales.view');
    Route::post('quotes/{id}/approve', [\\App\\Http\\Controllers\\Sales\\QuotationController::class,'approve'])->name('quotes.approve')->middleware('perm:sales.approve');
    Route::resource('orders', \\App\\Http\\Controllers\\Sales\\SalesOrderController::class)->middleware('perm:sales.view');
    Route::post('orders/{id}/approve', [\\App\\Http\\Controllers\\Sales\\SalesOrderController::class,'approve'])->name('orders.approve')->middleware('perm:sales.approve');
    Route::post('orders/{id}/cancel', [\\App\\Http\\Controllers\\Sales\\SalesOrderController::class,'cancel'])->name('orders.cancel')->middleware('perm:sales.void');
    Route::resource('deliveries', \\App\\Http\\Controllers\\Sales\\DeliveryOrderController::class)->middleware('perm:sales.view');
    Route::post('deliveries/{id}/approve', [\\App\\Http\\Controllers\\Sales\\DeliveryOrderController::class,'approve'])->name('deliveries.approve')->middleware('perm:sales.approve');
    Route::resource('invoices', \\App\\Http\\Controllers\\Sales\\InvoiceController::class)->middleware('perm:sales.view');
    Route::post('invoices/{id}/pay', [\\App\\Http\\Controllers\\Sales\\InvoiceController::class,'pay'])->name('invoices.pay')->middleware('perm:sales.create');
    Route::get('invoices/{id}/pdf', [\\App\\Http\\Controllers\\Sales\\InvoiceController::class,'pdf'])->name('invoices.pdf')->middleware('perm:sales.view');
    Route::resource('creditnotes', \\App\\Http\\Controllers\\Sales\\CreditNoteController::class)->middleware('perm:sales.view');
    Route::resource('returns', \\App\\Http\\Controllers\\Sales\\SalesReturnController::class)->middleware('perm:sales.view');
    Route::get('pos', [\\App\\Http\\Controllers\\Sales\\PosController::class,'index'])->name('pos.index')->middleware('perm:pos.operate');
    Route::post('pos/open', [\\App\\Http\\Controllers\\Sales\\PosController::class,'openShift'])->name('pos.open')->middleware('perm:pos.shift');
    Route::post('pos/checkout', [\\App\\Http\\Controllers\\Sales\\PosController::class,'checkout'])->name('pos.checkout')->middleware('perm:pos.operate');
    Route::post('pos/close', [\\App\\Http\\Controllers\\Sales\\PosController::class,'closeShift'])->name('pos.close')->middleware('perm:pos.shift');
});
""")
w("routes/modules/ops.php", """<?php
use Illuminate\\Support\\Facades\\Route;
Route::middleware(['auth','ctx'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('stock', [\\App\\Http\\Controllers\\Inventory\\StockController::class,'index'])->name('stock.index')->middleware('perm:inventory.view');
    Route::get('stock/export', [\\App\\Http\\Controllers\\Inventory\\StockController::class,'export'])->name('stock.export')->middleware('perm:reports.export');
    Route::get('stock/{id}/card', [\\App\\Http\\Controllers\\Inventory\\StockController::class,'card'])->name('stock.card')->middleware('perm:inventory.view');
    Route::get('receipts', [\\App\\Http\\Controllers\\Inventory\\ReceiptController::class,'index'])->name('receipts.index')->middleware('perm:inventory.view');
    Route::get('receipts/create', [\\App\\Http\\Controllers\\Inventory\\ReceiptController::class,'create'])->name('receipts.create')->middleware('perm:inventory.receive');
    Route::post('receipts', [\\App\\Http\\Controllers\\Inventory\\ReceiptController::class,'store'])->name('receipts.store')->middleware('perm:inventory.receive');
});
Route::middleware(['auth','ctx'])->prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('requisitions', [\\App\\Http\\Controllers\\Purchasing\\RequisitionController::class,'index'])->name('requisitions.index')->middleware('perm:purchasing.view');
    Route::get('requisitions/create', [\\App\\Http\\Controllers\\Purchasing\\RequisitionController::class,'create'])->name('requisitions.create')->middleware('perm:purchasing.create');
    Route::post('requisitions', [\\App\\Http\\Controllers\\Purchasing\\RequisitionController::class,'store'])->name('requisitions.store')->middleware('perm:purchasing.create');
    Route::resource('orders', \\App\\Http\\Controllers\\Purchasing\\PurchaseOrderController::class)->middleware('perm:purchasing.view');
    Route::post('orders/{id}/approve', [\\App\\Http\\Controllers\\Purchasing\\PurchaseOrderController::class,'approve'])->name('orders.approve')->middleware('perm:purchasing.approve');
    Route::get('vinvoices', [\\App\\Http\\Controllers\\Purchasing\\VendorInvoiceController::class,'index'])->name('vinvoices.index')->middleware('perm:purchasing.view');
    Route::get('vinvoices/create', [\\App\\Http\\Controllers\\Purchasing\\VendorInvoiceController::class,'create'])->name('vinvoices.create')->middleware('perm:purchasing.create');
    Route::post('vinvoices', [\\App\\Http\\Controllers\\Purchasing\\VendorInvoiceController::class,'store'])->name('vinvoices.store')->middleware('perm:purchasing.create');
});
""")
w("routes/modules/finance_hr.php", """<?php
use Illuminate\\Support\\Facades\\Route;
Route::middleware(['auth','ctx'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('journals', [\\App\\Http\\Controllers\\Finance\\JournalController::class,'index'])->name('journals.index')->middleware('perm:finance.view');
    Route::get('journals/create', [\\App\\Http\\Controllers\\Finance\\JournalController::class,'create'])->name('journals.create')->middleware('perm:finance.post');
    Route::post('journals', [\\App\\Http\\Controllers\\Finance\\JournalController::class,'store'])->name('journals.store')->middleware('perm:finance.post');
    Route::post('journals/{id}/reverse', [\\App\\Http\\Controllers\\Finance\\JournalController::class,'reverse'])->name('journals.reverse')->middleware('perm:finance.approve');
    Route::resource('expenses', \\App\\Http\\Controllers\\Finance\\ExpenseController::class)->middleware('perm:finance.view');
    Route::post('expenses/{id}/approve', [\\App\\Http\\Controllers\\Finance\\ExpenseController::class,'approve'])->name('expenses.approve')->middleware('perm:finance.approve');
    Route::get('ledger', [\\App\\Http\\Controllers\\Finance\\FinanceReportController::class,'ledger'])->name('ledger')->middleware('perm:finance.view');
    Route::get('trial', [\\App\\Http\\Controllers\\Finance\\FinanceReportController::class,'trialBalance'])->name('trial')->middleware('perm:finance.view');
    Route::get('pl', [\\App\\Http\\Controllers\\Finance\\FinanceReportController::class,'pl'])->name('pl')->middleware('perm:finance.view');
    Route::get('aging', [\\App\\Http\\Controllers\\Finance\\FinanceReportController::class,'aging'])->name('aging')->middleware('perm:finance.view');
    Route::get('closing', [\\App\\Http\\Controllers\\Finance\\FinanceReportController::class,'closing'])->name('closing')->middleware('perm:finance.close');
    Route::post('closing/close', [\\App\\Http\\Controllers\\Finance\\FinanceReportController::class,'doClose'])->name('closing.close')->middleware('perm:finance.close');
    Route::post('closing/reopen', [\\App\\Http\\Controllers\\Finance\\FinanceReportController::class,'reopen'])->name('closing.reopen')->middleware('perm:finance.close');
});
Route::middleware(['auth','ctx'])->prefix('hr')->name('hr.')->group(function () {
    Route::resource('employees', \\App\\Http\\Controllers\\Hr\\EmployeeController::class)->middleware('perm:hr.view');
    Route::get('attendance', [\\App\\Http\\Controllers\\Hr\\AttendanceController::class,'index'])->name('attendance.index')->middleware('perm:hr.view');
    Route::post('attendance', [\\App\\Http\\Controllers\\Hr\\AttendanceController::class,'store'])->name('attendance.store')->middleware('perm:hr.manage');
    Route::get('payroll', [\\App\\Http\\Controllers\\Hr\\PayrollController::class,'index'])->name('payroll.index')->middleware('perm:hr.payroll');
    Route::get('payroll/create', [\\App\\Http\\Controllers\\Hr\\PayrollController::class,'create'])->name('payroll.create')->middleware('perm:hr.payroll');
    Route::post('payroll', [\\App\\Http\\Controllers\\Hr\\PayrollController::class,'store'])->name('payroll.store')->middleware('perm:hr.payroll');
    Route::post('payroll/{id}/approve', [\\App\\Http\\Controllers\\Hr\\PayrollController::class,'approve'])->name('payroll.approve')->middleware('perm:hr.payroll');
    Route::get('payslip/{id}/pdf', [\\App\\Http\\Controllers\\Hr\\PayrollController::class,'payslip'])->name('payslip.pdf')->middleware('perm:hr.view');
});
""")
w("routes/modules/business.php", """<?php
use Illuminate\\Support\\Facades\\Route;
Route::middleware(['auth','ctx'])->prefix('pet')->name('pet.')->group(function () {
    Route::resource('owners', \\App\\Http\\Controllers\\Pet\\OwnerController::class)->middleware('perm:pet.view');
    Route::resource('pets', \\App\\Http\\Controllers\\Pet\\PetController::class)->middleware('perm:pet.view');
    Route::resource('vet', \\App\\Http\\Controllers\\Pet\\VetAppointmentController::class)->middleware('perm:pet.view');
    Route::resource('grooming', \\App\\Http\\Controllers\\Pet\\GroomingOrderController::class)->middleware('perm:pet.groom');
    Route::resource('boarding', \\App\\Http\\Controllers\\Pet\\BoardingReservationController::class)->middleware('perm:pet.board');
    Route::post('boarding/book', [\\App\\Http\\Controllers\\Pet\\BoardingCheckinController::class,'store'])->name('boarding.book')->middleware('perm:pet.board');
    Route::resource('transport', \\App\\Http\\Controllers\\Pet\\TransportOrderController::class)->middleware('perm:pet.transport');
});
Route::middleware(['auth','ctx'])->prefix('mfg')->name('mfg.')->group(function () {
    Route::resource('designs', \\App\\Http\\Controllers\\Mfg\\DesignController::class)->middleware('perm:mfg.view');
    Route::resource('boms', \\App\\Http\\Controllers\\Mfg\\BomController::class)->middleware('perm:mfg.view');
    Route::resource('workorders', \\App\\Http\\Controllers\\Mfg\\WorkOrderController::class)->middleware('perm:mfg.plan');
});
Route::middleware(['auth','ctx'])->prefix('thrift')->name('thrift.')->group(function () {
    Route::resource('lots', \\App\\Http\\Controllers\\Thrift\\LotController::class)->middleware('perm:thrift.view');
    Route::resource('items', \\App\\Http\\Controllers\\Thrift\\ItemController::class)->middleware('perm:thrift.view');
    Route::post('live/reserve', [\\App\\Http\\Controllers\\Thrift\\LiveOrderController::class,'reserve'])->name('live.reserve')->middleware('perm:thrift.live');
});
Route::middleware(['auth','ctx'])->prefix('studio')->name('studio.')->group(function () {
    Route::resource('projects', \\App\\Http\\Controllers\\Studio\\ProjectController::class)->middleware('perm:studio.view');
});
Route::middleware(['auth','ctx'])->prefix('rental')->name('rental.')->group(function () {
    Route::resource('catalogs', \\App\\Http\\Controllers\\Rental\\CatalogController::class)->middleware('perm:rental.view');
    Route::resource('reservations', \\App\\Http\\Controllers\\Rental\\ReservationController::class)->middleware('perm:rental.view');
    Route::post('book', [\\App\\Http\\Controllers\\Rental\\RentalBookingController::class,'store'])->name('book')->middleware('perm:rental.manage');
});
Route::middleware(['auth','ctx'])->prefix('marketing')->name('marketing.')->group(function () {
    Route::resource('campaigns', \\App\\Http\\Controllers\\Marketing\\CampaignController::class)->middleware('perm:marketing.view');
    Route::resource('vouchers', \\App\\Http\\Controllers\\Marketing\\VoucherController::class)->middleware('perm:marketing.view');
});
Route::middleware(['auth','ctx'])->prefix('gov')->name('gov.')->group(function () {
    Route::get('approvals', [\\App\\Http\\Controllers\\Gov\\ApprovalController::class,'index'])->name('approvals.index')->middleware('perm:governance.view');
    Route::post('approvals/{id}', [\\App\\Http\\Controllers\\Gov\\ApprovalController::class,'decide'])->name('approvals.decide')->middleware('perm:governance.approve');
});
Route::middleware(['auth','ctx'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('sales', [\\App\\Http\\Controllers\\ReportController::class,'sales'])->name('sales')->middleware('perm:reports.view');
    Route::get('inventory', [\\App\\Http\\Controllers\\ReportController::class,'inventory'])->name('inventory')->middleware('perm:reports.view');
    Route::get('purchase', [\\App\\Http\\Controllers\\ReportController::class,'purchase'])->name('purchase')->middleware('perm:reports.view');
    Route::get('import/customers', [\\App\\Http\\Controllers\\ImportController::class,'customers'])->name('import.customers')->middleware('perm:crm.create');
    Route::post('import/customers', [\\App\\Http\\Controllers\\ImportController::class,'customersStore'])->name('import.customers.store')->middleware('perm:crm.create');
    Route::get('import/products', [\\App\\Http\\Controllers\\ImportController::class,'products'])->name('import.products')->middleware('perm:crm.create');
    Route::post('import/products', [\\App\\Http\\Controllers\\ImportController::class,'productsStore'])->name('import.products.store')->middleware('perm:crm.create');
});
""")

w("routes/web.php", """<?php
use Illuminate\\Support\\Facades\\Route;
Route::get('/up', fn() => response()->json(['ok'=>true]));
Route::get('/login', [\\App\\Http\\Controllers\\AuthController::class,'showLogin'])->name('login');
Route::post('/login', [\\App\\Http\\Controllers\\AuthController::class,'login'])->name('login.attempt')->middleware('throttle:10,1');
Route::post('/logout', [\\App\\Http\\Controllers\\AuthController::class,'logout'])->name('logout')->middleware('auth');
Route::middleware(['auth','ctx'])->group(function () {
    Route::get('/', [\\App\\Http\\Controllers\\DashboardController::class,'index'])->name('dashboard');
    Route::get('/health', [\\App\\Http\\Controllers\\DashboardController::class,'health'])->name('health');
    Route::get('/profile', [\\App\\Http\\Controllers\\AuthController::class,'profile'])->name('profile');
    Route::put('/profile', [\\App\\Http\\Controllers\\AuthController::class,'updateProfile'])->name('profile.update');
    Route::post('/context', [\\App\\Http\\Controllers\\AuthController::class,'switchContext'])->name('context.switch');
});
foreach (glob(__DIR__.'/modules/*.php') as $f) require $f;
""")
print("gen8b done")

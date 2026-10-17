<?php
namespace App\Http\Controllers\Sales;
use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\StockService;
use App\Services\AuditService;
use App\Services\ApprovalService;
class SalesOrderController extends CrudController {
    protected string $model = \App\Models\Sales\SalesOrder::class;
    protected string $routePrefix = 'sales.orders';
    protected string $viewPrefix = 'modules.sales.orders';
    protected string $title = 'Sales Order';
    protected string $perm = 'sales';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','grand_total'=>'Total','status'=>'Status'];
    protected array $fields = [];
    protected array $rules = [];
    protected array $searchable = ['number'];
    public function index(Request $r) {
        $rows = $this->baseQuery()->with('customer')->latest('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index', ['rows'=>$rows,'meta'=>$this->meta()]);
    }
    public function create() {
        $r = request();
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $customers = \App\Models\Master\Customer::where('legal_entity_id',$eid)->orderBy('name')->get();
        $customer = $r->filled('customer_id') ? $customers->firstWhere('id',(int)$r->get('customer_id')) : $customers->first();
        return view($this->viewPrefix.'.form', ['meta'=>$this->meta(),
            'customers'=>$customers,'customer'=>$customer,
            'products'=>\App\Services\PriceService::productOptions($eid, $customer?->tier)]);
    }
    public function store(Request $r) {
        $r->validate(['customer_id'=>'required|exists:customers,id','date'=>'required|date',
            'lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id',
            'lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'nullable|numeric|min:0',
            'discount'=>'nullable|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $cust = \App\Models\Master\Customer::findOrFail($r->customer_id);
        if ((int)$cust->legal_entity_id !== (int)$eid) abort(403,'Pelanggan beda entitas.');
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u,$cust) {
            $ent = \App\Models\Org\LegalEntity::find($eid); $br = \App\Models\Org\Branch::find($bid);
            $number = DocNumberService::next('SO',$eid,$bid,$ent?->code,$br?->code);
            $sub=0; $lines=[];
            foreach ($r->lines as $l) {
                $p = \App\Models\Master\Product::findOrFail($l['product_id']);
                if ((int)$p->legal_entity_id !== (int)$eid) throw new \RuntimeException('Produk beda entitas: '.$p->sku);
                if (!$p->is_sellable) throw new \RuntimeException('Produk tidak dapat dijual: '.$p->sku);
                $inPrice = $l['price'] ?? null;
                $price = ($inPrice === null || $inPrice === '') ? \App\Services\PriceService::forCustomer($p,$cust) : (float)$inPrice;
                if ($price < 0) throw new \RuntimeException('Harga tidak valid.');
                $lt = (float)$l['qty'] * $price;
                $sub += $lt;
                $lines[] = ['product_id'=>$p->id,'snapshot_name'=>$p->name,'qty'=>$l['qty'],'price'=>$price,'discount'=>0,'tax_rate'=>11,'line_total'=>$lt*1.11,'created_at'=>now(),'updated_at'=>now()];
            }
            $disc = (float)($r->discount ?? 0);
            if ($disc > $sub) throw new \RuntimeException('Diskon melebihi subtotal.');
            $tax = ($sub-$disc)*0.11; $grand = $sub-$disc+$tax;
            $so = \App\Models\Sales\SalesOrder::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'customer_id'=>$r->customer_id,'status'=>'draft','subtotal'=>$sub,'discount'=>$disc,'tax'=>$tax,'grand_total'=>$grand,'created_by'=>$u->id]);
            foreach ($lines as &$ln) $ln['sales_order_id']=$so->id;
            DB::table('sales_order_lines')->insert($lines);
            DB::table('doc_status_histories')->insert(['doc_type'=>'sales_orders','doc_id'=>$so->id,'from_status'=>null,'to_status'=>'draft','actor_id'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            return $so->id;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        AuditService::log('create','sales_orders',(int)$id,[]);
        return redirect()->route('sales.orders.show',$id)->with('ok','Sales order tersimpan.');
    }
    public function show($id) {
        $row = $this->baseQuery()->with(['customer','lines.product'])->findOrFail($id);
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta()]);
    }
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
        $this->setStatus($row,'draft','approved','Disetujui');
        // reservasi stok di gudang utama cabang
        $wh = DB::table('warehouses')->where('branch_id',$row->branch_id)->value('id') ?? DB::table('warehouses')->value('id');
        if ($wh) {
            $lines = DB::table('sales_order_lines')->where('sales_order_id',$row->id)->get();
            foreach ($lines as $l) {
                try {
                    StockService::reserve($wh,$l->product_id,(float)$l->qty);
                    DB::table('stock_reservations')->insert(['warehouse_id'=>$wh,'product_id'=>$l->product_id,'ref_type'=>'sales_order','ref_id'=>$row->id,'qty'=>$l->qty,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
                } catch (\Throwable $e) {
                    // Stok kurang: SO tetap approved, tetapi TIDAK ada baris reservasi aktif
                    // agar qty_reserved (ledger) konsisten dengan stock_reservations.
                    DB::table('doc_status_histories')->insert(['doc_type'=>'sales_orders','doc_id'=>$row->id,'from_status'=>'approved','to_status'=>'approved','actor_id'=>auth()->id(),'note'=>'Reservasi gagal: '.$e->getMessage(),'created_at'=>now(),'updated_at'=>now()]);
                }
            }
        }
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','SO disetujui + stok direservasi.');
    }
    public function cancel($id) {
        $row = $this->baseQuery()->findOrFail($id);
        if ($row->status==='invoiced') abort(422,'SO yang sudah menjadi invoice tidak dapat dibatalkan langsung.');
        try {
        $this->setStatus($row,$row->status,'cancelled','Dibatalkan');
        DB::table('stock_reservations')->where(['ref_type'=>'sales_order','ref_id'=>$row->id])->update(['status'=>'released']);
        $wh = DB::table('warehouses')->where('branch_id',$row->branch_id)->value('id');
        if ($wh) { foreach (DB::table('sales_order_lines')->where('sales_order_id',$row->id)->get() as $l) { try { StockService::release($wh,$l->product_id,(float)$l->qty); } catch (\Throwable $e) {} } }
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','SO dibatalkan, reservasi dilepas.');
    }
}

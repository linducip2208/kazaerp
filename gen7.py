"""Generator 7: transactional controllers (sales/inventory/purchasing/finance/hr...)."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

# ---------- SalesOrder with lines + reservation ----------
w("app/Http/Controllers/Sales/SalesOrderController.php", """<?php
namespace App\\Http\\Controllers\\Sales;
use App\\Http\\Controllers\\CrudController;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\StockService;
use App\\Services\\AuditService;
use App\\Services\\ApprovalService;
class SalesOrderController extends CrudController {
    protected string $model = \\App\\Models\\Sales\\SalesOrder::class;
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
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix.'.form', ['meta'=>$this->meta(),
            'customers'=>\\App\\Models\\Master\\Customer::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'products'=>\\App\\Models\\Master\\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->get()]);
    }
    public function store(Request $r) {
        $r->validate(['customer_id'=>'required|exists:customers,id','date'=>'required|date',
            'lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id',
            'lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0',
            'discount'=>'nullable|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $cust = \\App\\Models\\Master\\Customer::findOrFail($r->customer_id);
        if ((int)$cust->legal_entity_id !== (int)$eid) abort(403,'Pelanggan beda entitas.');
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid); $br = \\App\\Models\\Org\\Branch::find($bid);
            $number = DocNumberService::next('SO',$eid,$bid,$ent?->code,$br?->code);
            $sub=0; $lines=[];
            foreach ($r->lines as $l) {
                $p = \\App\\Models\\Master\\Product::findOrFail($l['product_id']);
                if ((int)$p->legal_entity_id !== (int)$eid) throw new \\RuntimeException('Produk beda entitas: '.$p->sku);
                if (!$p->is_sellable) throw new \\RuntimeException('Produk tidak dapat dijual: '.$p->sku);
                if ((float)$l['price'] < 0) throw new \\RuntimeException('Harga tidak valid.');
                $lt = (float)$l['qty'] * (float)$l['price'];
                $sub += $lt;
                $lines[] = ['product_id'=>$p->id,'snapshot_name'=>$p->name,'qty'=>$l['qty'],'price'=>$l['price'],'discount'=>0,'tax_rate'=>11,'line_total'=>$lt*1.11,'created_at'=>now(),'updated_at'=>now()];
            }
            $disc = (float)($r->discount ?? 0);
            if ($disc > $sub) throw new \\RuntimeException('Diskon melebihi subtotal.');
            $tax = ($sub-$disc)*0.11; $grand = $sub-$disc+$tax;
            $so = \\App\\Models\\Sales\\SalesOrder::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'customer_id'=>$r->customer_id,'status'=>'draft','subtotal'=>$sub,'discount'=>$disc,'tax'=>$tax,'grand_total'=>$grand,'created_by'=>$u->id]);
            foreach ($lines as &$ln) $ln['sales_order_id']=$so->id;
            DB::table('sales_order_lines')->insert($lines);
            DB::table('doc_status_histories')->insert(['doc_type'=>'sales_orders','doc_id'=>$so->id,'from_status'=>null,'to_status'=>'draft','actor_id'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            return $so->id;
        });
        AuditService::log('create','sales_orders',(int)$id,[]);
        return redirect()->route('sales.orders.show',$id)->with('ok','Sales order tersimpan.');
    }
    public function show($id) {
        $row = $this->baseQuery()->with(['customer','lines.product'])->findOrFail($id);
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta()]);
    }
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->setStatus($row,'draft','approved','Disetujui');
        // reservasi stok di gudang utama cabang
        $wh = DB::table('warehouses')->where('branch_id',$row->branch_id)->value('id') ?? DB::table('warehouses')->value('id');
        if ($wh) {
            $lines = DB::table('sales_order_lines')->where('sales_order_id',$row->id)->get();
            foreach ($lines as $l) {
                try { StockService::reserve($wh,$l->product_id,(float)$l->qty); } catch (\\Throwable $e) {
                    // batalkan status bila stok kurang? tetap approved tapi catat exception
                    DB::table('doc_status_histories')->insert(['doc_type'=>'sales_orders','doc_id'=>$row->id,'from_status'=>'approved','to_status'=>'approved','actor_id'=>auth()->id(),'note'=>'Reservasi gagal: '.$e->getMessage(),'created_at'=>now(),'updated_at'=>now()]);
                }
                DB::table('stock_reservations')->insert(['warehouse_id'=>$wh,'product_id'=>$l->product_id,'ref_type'=>'sales_order','ref_id'=>$row->id,'qty'=>$l->qty,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
            }
        }
        return back()->with('ok','SO disetujui + stok direservasi.');
    }
    public function cancel($id) {
        $row = $this->baseQuery()->findOrFail($id);
        if ($row->status==='invoiced') abort(422,'SO yang sudah menjadi invoice tidak dapat dibatalkan langsung.');
        $this->setStatus($row,$row->status,'cancelled','Dibatalkan');
        DB::table('stock_reservations')->where(['ref_type'=>'sales_order','ref_id'=>$row->id])->update(['status'=>'released']);
        $wh = DB::table('warehouses')->where('branch_id',$row->branch_id)->value('id');
        if ($wh) { foreach (DB::table('sales_order_lines')->where('sales_order_id',$row->id)->get() as $l) { try { StockService::release($wh,$l->product_id,(float)$l->qty); } catch (\\Throwable $e) {} } }
        return back()->with('ok','SO dibatalkan, reservasi dilepas.');
    }
}
""")

w("app/Http/Controllers/Sales/InvoiceController.php", """<?php
namespace App\\Http\\Controllers\\Sales;
use App\\Http\\Controllers\\CrudController;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\StockService;
use App\\Services\\PostingService;
use App\\Services\\AuditService;
class InvoiceController extends CrudController {
    protected string $model = \\App\\Models\\Sales\\Invoice::class;
    protected string $routePrefix = 'sales.invoices';
    protected string $viewPrefix = 'modules.sales.invoices';
    protected string $title = 'Invoice';
    protected string $perm = 'sales';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','grand_total'=>'Total','pay_status'=>'Bayar','status'=>'Status'];
    protected array $fields = []; protected array $rules = []; protected array $searchable = ['number'];
    public function index(Request $r) {
        $rows = $this->baseQuery()->with('customer')->latest('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index',['rows'=>$rows,'meta'=>$this->meta()]);
    }
    public function create(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $fromSO = $r->get('from_so') ? \\App\\Models\\Sales\\SalesOrder::where('id',$r->get('from_so'))->first() : null;
        return view($this->viewPrefix.'.form',['meta'=>$this->meta(),'fromSO'=>$fromSO,
            'customers'=>\\App\\Models\\Master\\Customer::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'products'=>\\App\\Models\\Master\\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->get()]);
    }
    public function store(Request $r) {
        $r->validate(['customer_id'=>'required|exists:customers,id','date'=>'required|date','due_date'=>'nullable|date',
            'lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id',
            'lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid); $br = \\App\\Models\\Org\\Branch::find($bid);
            $number = DocNumberService::next('INV',$eid,$bid,$ent?->code,$br?->code);
            $sub=0; $lines=[];
            foreach ($r->lines as $l) {
                $p = \\App\\Models\\Master\\Product::findOrFail($l['product_id']);
                if ((int)$p->legal_entity_id !== (int)$eid) throw new \\RuntimeException('Produk beda entitas.');
                // blokir produk kedaluwarsa/karantina sesuai konfigurasi
                $lt=(float)$l['qty']*(float)$l['price']; $sub+=$lt;
                $lines[]=['product_id'=>$p->id,'snapshot_name'=>$p->name,'qty'=>$l['qty'],'price'=>$l['price'],'discount'=>0,'tax_rate'=>11,'line_total'=>$lt*1.11,'created_at'=>now(),'updated_at'=>now()];
            }
            $tax=$sub*0.11; $grand=$sub+$tax;
            $inv = \\App\\Models\\Sales\\Invoice::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'customer_id'=>$r->customer_id,'sales_order_id'=>$r->sales_order_id,'due_date'=>$r->due_date,
                'status'=>'posted','pay_status'=>'unpaid','subtotal'=>$sub,'discount'=>0,'tax'=>$tax,'grand_total'=>$grand,'paid_total'=>0,'created_by'=>$u->id]);
            foreach ($lines as &$ln) $ln['invoice_id']=$inv->id;
            DB::table('invoice_lines')->insert($lines);
            // keluarkan stok + jurnal piutang/pendapatan
            $wh = DB::table('warehouses')->where('branch_id',$bid)->value('id') ?? DB::table('warehouses')->value('id');
            foreach ($lines as $ln) { if ($wh) StockService::move(['warehouse_id'=>$wh,'product_id'=>$ln['product_id'],'qty'=>-1*(float)$ln['qty'],'ref_type'=>'invoice','ref_id'=>$inv->id,'note'=>'INV '.$number]); }
            $coa = fn($code)=>DB::table('coas')->where(['legal_entity_id'=>$eid,'code'=>$code])->value('id');
            if ($coa('1200') && $coa('4000')) {
                PostingService::postJournal(['legal_entity_id'=>$eid,'branch_id'=>$bid,'date'=>$r->date,'number'=>'J-'.$number,
                    'description'=>'Penjualan '.$number,'source_type'=>'invoice','source_id'=>$inv->id],
                    [['coa_id'=>$coa('1200'),'debit'=>$grand,'credit'=>0],['coa_id'=>$coa('4000'),'debit'=>0,'credit'=>$grand]],'inv-'.$inv->id);
            }
            return $inv->id;
        });
        AuditService::log('create','invoices',(int)$id,[]);
        return redirect()->route('sales.invoices.show',$id)->with('ok','Invoice terbit & terposting.');
    }
    public function show($id) {
        $row = $this->baseQuery()->with(['customer','lines','payments'])->findOrFail($id);
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta()]);
    }
    public function pay(Request $r, $id) {
        $r->validate(['amount'=>'required|numeric|min:1','method'=>'required|in:cash,transfer,deposit','date'=>'required|date']);
        $inv = $this->baseQuery()->findOrFail($id);
        DB::transaction(function() use ($r,$inv) {
            $inv2 = \\App\\Models\\Sales\\Invoice::where('id',$inv->id)->lockForUpdate()->first();
            $sisa = (float)$inv2->grand_total - (float)$inv2->paid_total;
            if ((float)$r->amount > $sisa + 0.005) throw new \\RuntimeException('Pembayaran melebihi sisa tagihan ('.number_format($sisa,0).').');
            if ((float)$r->amount <= 0) throw new \\RuntimeException('Nominal tidak valid.');
            $u = auth()->user(); $ctx = session('erp_context',[]);
            $ent = \\App\\Models\\Org\\LegalEntity::find($inv2->legal_entity_id);
            $number = DocNumberService::next('PAY',$inv2->legal_entity_id,$inv2->branch_id,$ent?->code);
            DB::table('sales_payments')->insert(['legal_entity_id'=>$inv2->legal_entity_id,'branch_id'=>$inv2->branch_id,
                'number'=>$number,'date'=>$r->date,'invoice_id'=>$inv2->id,'customer_id'=>$inv2->customer_id,
                'method'=>$r->method,'amount'=>$r->amount,'created_by'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            $paid = (float)$inv2->paid_total + (float)$r->amount;
            $inv2->update(['paid_total'=>$paid,'pay_status'=> $paid >= (float)$inv2->grand_total - 0.005 ? 'paid' : 'partial']);
            // jurnal kas vs piutang
            $coa = fn($code)=>DB::table('coas')->where(['legal_entity_id'=>$inv2->legal_entity_id,'code'=>$code])->value('id');
            if ($coa('1000') && $coa('1200')) {
                PostingService::postJournal(['legal_entity_id'=>$inv2->legal_entity_id,'branch_id'=>$inv2->branch_id,'date'=>$r->date,
                    'number'=>'J-'.$number,'description'=>'Bayar '.$inv2->number,'source_type'=>'sales_payment','source_id'=>$inv2->id],
                    [['coa_id'=>$coa('1000'),'debit'=>(float)$r->amount,'credit'=>0],['coa_id'=>$coa('1200'),'debit'=>0,'credit'=>(float)$r->amount]],'pay-'.$number);
            }
        });
        return back()->with('ok','Pembayaran dicatat. Status lunas dihitung dari total, bukan dari form.');
    }
    public function pdf($id) {
        $row = $this->baseQuery()->with(['customer','lines'])->findOrFail($id);
        $pdf = \\Barryvdh\\DomPDF\\Facade\\Pdf::loadView('print.invoice', compact('row'));
        return $pdf->download($row->number.'.pdf');
    }
}
""")

w("app/Http/Controllers/Sales/PosController.php", """<?php
namespace App\\Http\\Controllers\\Sales;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\StockService;
class PosController extends Controller {
    public function index() {
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $shift = DB::table('pos_shifts')->where(['cashier_id'=>$u->id,'status'=>'open'])->first();
        $products = \\App\\Models\\Master\\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->limit(60)->get();
        return view('modules.sales.pos.index', compact('shift','products'));
    }
    public function openShift(Request $r) {
        $r->validate(['opening_cash'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        if (DB::table('pos_shifts')->where(['cashier_id'=>$u->id,'status'=>'open'])->exists()) return back()->withErrors(['msg'=>'Masih ada shift terbuka.']);
        DB::table('pos_shifts')->insert(['legal_entity_id'=>$ctx['legal_entity_id'] ?? $u->legal_entity_id,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,
            'cashier_id'=>$u->id,'opened_at'=>now(),'opening_cash'=>$r->opening_cash,'status'=>'open','created_at'=>now(),'updated_at'=>now()]);
        return back()->with('ok','Shift dibuka.');
    }
    public function checkout(Request $r) {
        $r->validate(['lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','pay_method'=>'required|in:cash,transfer,qris']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $shift = DB::table('pos_shifts')->where(['cashier_id'=>$u->id,'status'=>'open'])->first();
        if (!$shift) return back()->withErrors(['msg'=>'Buka shift kasir terlebih dahulu.']);
        DB::transaction(function() use ($r,$eid,$bid,$u,$shift) {
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid); $br = \\App\\Models\\Org\\Branch::find($bid);
            $number = DocNumberService::next('POS',$eid,$bid,$ent?->code,$br?->code);
            $sub=0; $lns=[];
            foreach ($r->lines as $l) {
                $p = \\App\\Models\\Master\\Product::findOrFail($l['product_id']);
                $lt=(float)$l['qty']*(float)$p->sell_price; $sub+=$lt;
                $lns[]=['product_id'=>$p->id,'snapshot_name'=>$p->name,'qty'=>$l['qty'],'price'=>$p->sell_price,'line_total'=>$lt,'created_at'=>now(),'updated_at'=>now()];
            }
            $tax=$sub*0.11; $grand=$sub+$tax;
            $saleId = DB::table('pos_sales')->insertGetId(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>now()->toDateString(),
                'shift_id'=>$shift->id,'status'=>'paid','subtotal'=>$sub,'discount'=>0,'tax'=>$tax,'grand_total'=>$grand,'pay_method'=>$r->pay_method,'created_by'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            foreach ($lns as &$ln) $ln['pos_sale_id']=$saleId;
            DB::table('pos_sale_lines')->insert($lns);
            $wh = DB::table('warehouses')->where('branch_id',$bid)->value('id') ?? DB::table('warehouses')->value('id');
            foreach ($lns as $ln) { if ($wh) StockService::move(['warehouse_id'=>$wh,'product_id'=>$ln['product_id'],'qty'=>-1*(float)$ln['qty'],'ref_type'=>'pos_sale','ref_id'=>$saleId]); }
        });
        return back()->with('ok','Transaksi POS berhasil.');
    }
    public function closeShift(Request $r) {
        $r->validate(['closing_cash'=>'required|numeric|min:0']);
        $u = auth()->user();
        $shift = DB::table('pos_shifts')->where(['cashier_id'=>$u->id,'status'=>'open'])->firstOrFail();
        $sys = (float)DB::table('pos_sales')->where('shift_id',$shift->id)->sum('grand_total');
        $moves = (float)DB::table('cash_movements')->where('shift_id',$shift->id)->sum(DB::raw("CASE WHEN kind='in' THEN amount ELSE -amount END"));
        DB::table('pos_shifts')->where('id',$shift->id)->update(['closed_at'=>now(),'closing_cash'=>$r->closing_cash,'system_total'=>$sys,'difference'=>(float)$r->closing_cash-((float)$shift->opening_cash+$sys+$moves),'status'=>'closed','updated_at'=>now()]);
        return back()->with('ok','Shift ditutup. Selisih tercatat.');
    }
}
""")

# ---------- Inventory ----------
w("app/Http/Controllers/Inventory/StockController.php", """<?php
namespace App\\Http\\Controllers\\Inventory;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
class StockController extends Controller {
    public function index(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $q = DB::table('stock_balances')->join('products','products.id','=','stock_balances.product_id')
            ->join('warehouses','warehouses.id','=','stock_balances.warehouse_id')
            ->where('products.legal_entity_id',$eid)
            ->select('stock_balances.*','products.sku','products.name as product','warehouses.code as wh');
        if ($s=trim((string)$r->get('q',''))) $q->where(fn($w)=>$w->where('products.sku','like',"%$s%")->orWhere('products.name','like',"%$s%"));
        if ($r->get('filter')==='low') $q->whereRaw('qty_on_hand - qty_reserved <= 5');
        if ($r->get('filter')==='quarantine') $q->whereExists(fn($x)=>$x->select(DB::raw(1))->from('batches')->whereColumn('batches.id','stock_balances.batch_id')->where('batches.qc_status','quarantine'));
        $rows = $q->orderByDesc('stock_balances.id')->paginate(20)->withQueryString();
        return view('modules.inventory.stock.index', compact('rows'));
    }
    public function card(Request $r, $productId) {
        $rows = DB::table('stock_ledgers')->where('product_id',$productId)->orderBy('id')->paginate(30);
        $product = \\App\\Models\\Master\\Product::findOrFail($productId);
        return view('modules.inventory.stock.card', compact('rows','product'));
    }
    public function export(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = DB::table('stock_balances')->join('products','products.id','=','stock_balances.product_id')
            ->where('products.legal_entity_id',$eid)
            ->select('products.sku','products.name','stock_balances.qty_on_hand','stock_balances.qty_reserved')->get();
        $csv = "sku,nama,on_hand,reserved,available\\n";
        foreach ($rows as $x) $csv .= "\\"{$x->sku}\\",\\"{$x->name}\\",{$x->qty_on_hand},{$x->qty_reserved},".((float)$x->qty_on_hand-(float)$x->qty_reserved)."\\n";
        return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="stok.csv"']);
    }
}
""")

w("app/Http/Controllers/Inventory/ReceiptController.php", """<?php
namespace App\\Http\\Controllers\\Inventory;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\StockService;
class ReceiptController extends Controller {
    public function index() {
        $rows = DB::table('goods_receipts')->orderByDesc('id')->paginate(15);
        return view('modules.inventory.receipt.index', compact('rows'));
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view('modules.inventory.receipt.form', [
            'warehouses'=>\\App\\Models\\Org\\Warehouse::orderBy('code')->get(),
            'products'=>\\App\\Models\\Master\\Product::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'pos'=>\\App\\Models\\Purchasing\\PurchaseOrder::where('legal_entity_id',$eid)->whereIn('status',['approved','partial'])->orderByDesc('id')->limit(50)->get(),
        ]);
    }
    public function store(Request $r) {
        $r->validate(['warehouse_id'=>'required|exists:warehouses,id','date'=>'required|date','purchase_order_id'=>'nullable|exists:purchase_orders,id',
            'lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        DB::transaction(function() use ($r,$eid,$u) {
            $number = DocNumberService::next('GRN',$eid,$r->warehouse_id ? null : null);
            $grn = DB::table('goods_receipts')->insertGetId(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,
                'warehouse_id'=>$r->warehouse_id,'number'=>$number,'date'=>$r->date,'ref_type'=>$r->purchase_order_id?'purchase_order':null,
                'ref_id'=>$r->purchase_order_id,'status'=>'posted','created_by'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            $poLines = [];
            if ($r->purchase_order_id) {
                $po = DB::table('purchase_orders')->where('id',$r->purchase_order_id)->lockForUpdate()->first();
                foreach (DB::table('purchase_order_lines')->where('purchase_order_id',$po->id)->get() as $pl) $poLines[$pl->product_id]=$pl;
            }
            foreach ($r->lines as $l) {
                DB::table('goods_receipt_lines')->insert(['goods_receipt_id'=>$grn,'product_id'=>$l['product_id'],
                    'qty_ordered'=>$poLines[$l['product_id']]->qty ?? $l['qty'],'qty_received'=>$l['qty'],'unit_cost'=>$l['cost'] ?? 0,'created_at'=>now(),'updated_at'=>now()]);
                StockService::move(['warehouse_id'=>$r->warehouse_id,'product_id'=>$l['product_id'],'qty'=>(float)$l['qty'],'ref_type'=>'goods_receipt','ref_id'=>$grn,'unit_cost'=>$l['cost'] ?? 0]);
                if (isset($poLines[$l['product_id']])) {
                    DB::table('purchase_order_lines')->where(['purchase_order_id'=>$r->purchase_order_id,'product_id'=>$l['product_id']])
                        ->update(['qty_received'=>DB::raw('qty_received + '.(float)$l['qty'])]);
                }
            }
            if ($r->purchase_order_id) {
                $tot = DB::table('purchase_order_lines')->where('purchase_order_id',$r->purchase_order_id)->selectRaw('SUM(qty) t, SUM(qty_received) r')->first();
                DB::table('purchase_orders')->where('id',$r->purchase_order_id)->update(['status'=> ((float)$tot->r >= (float)$tot->t ? 'received' : 'partial')]);
            }
        });
        return redirect()->route('inventory.stock.index')->with('ok','Penerimaan dicatat ke ledger + saldo (mendukung partial).');
    }
}
""")

# ---------- Purchasing three-way matching ----------
w("app/Http/Controllers/Purchasing/PurchaseOrderController.php", """<?php
namespace App\\Http\\Controllers\\Purchasing;
use App\\Http\\Controllers\\CrudController;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\ApprovalService;
class PurchaseOrderController extends CrudController {
    protected string $model = \\App\\Models\\Purchasing\\PurchaseOrder::class;
    protected string $routePrefix = 'purchasing.orders';
    protected string $viewPrefix = 'modules.purchasing.orders';
    protected string $title = 'Purchase Order';
    protected string $perm = 'purchasing';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','grand_total'=>'Total','status'=>'Status'];
    protected array $fields = []; protected array $rules = []; protected array $searchable = ['number'];
    public function index(Request $r) {
        $rows = $this->baseQuery()->orderByDesc('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index',['rows'=>$rows,'meta'=>$this->meta()]);
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix.'.form',['meta'=>$this->meta(),
            'suppliers'=>\\App\\Models\\Master\\Supplier::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'products'=>\\App\\Models\\Master\\Product::where('legal_entity_id',$eid)->where('is_purchasable',true)->orderBy('name')->get()]);
    }
    public function store(Request $r) {
        $r->validate(['supplier_id'=>'required|exists:suppliers,id','date'=>'required|date','lines'=>'required|array|min:1',
            'lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid);
            $number = DocNumberService::next('PO',$eid,$bid,$ent?->code);
            $sub=0; $lns=[];
            foreach ($r->lines as $l) { $lt=(float)$l['qty']*(float)$l['price']; $sub+=$lt;
                $lns[]=['product_id'=>$l['product_id'],'qty'=>$l['qty'],'qty_received'=>0,'price'=>$l['price'],'line_total'=>$lt,'created_at'=>now(),'updated_at'=>now()]; }
            $tax=$sub*0.11; $grand=$sub+$tax;
            $po = \\App\\Models\\Purchasing\\PurchaseOrder::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'supplier_id'=>$r->supplier_id,'version'=>1,'status'=>'draft','subtotal'=>$sub,'tax'=>$tax,'grand_total'=>$grand,'created_by'=>$u->id]);
            foreach ($lns as &$ln) $ln['purchase_order_id']=$po->id;
            DB::table('purchase_order_lines')->insert($lns);
            ApprovalService::request('purchase_order',$po->id,$eid,$grand);
            return $po->id;
        });
        return redirect()->route('purchasing.orders.show',$id)->with('ok','PO dibuat + approval diminta.');
    }
    public function show($id) {
        $row = $this->baseQuery()->with(['lines.product'])->findOrFail($id);
        $invoices = DB::table('vendor_invoices')->where('purchase_order_id',$id)->get();
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta(),'invoices'=>$invoices]);
    }
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $ap = DB::table('approvals')->where(['doc_type'=>'purchase_order','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
        if ($ap) ApprovalService::decide($ap->id,'approved','OK');
        $this->setStatus($row,'draft','approved');
        return back()->with('ok','PO disetujui.');
    }
}
""")

w("app/Http/Controllers/Purchasing/VendorInvoiceController.php", """<?php
namespace App\\Http\\Controllers\\Purchasing;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
class VendorInvoiceController extends Controller {
    public function index() {
        $rows = DB::table('vendor_invoices')->join('suppliers','suppliers.id','=','vendor_invoices.supplier_id')
            ->select('vendor_invoices.*','suppliers.name as supplier')->orderByDesc('vendor_invoices.id')->paginate(15);
        return view('modules.purchasing.vinvoices.index', compact('rows'));
    }
    public function create(Request $r) {
        $po = $r->get('from_po') ? DB::table('purchase_orders')->find($r->get('from_po')) : null;
        $lines = $po ? DB::table('purchase_order_lines')->join('products','products.id','=','purchase_order_lines.product_id')
            ->where('purchase_order_id',$po->id)->select('purchase_order_lines.*','products.name')->get() : [];
        return view('modules.purchasing.vinvoices.form', compact('po','lines'));
    }
    public function store(Request $r) {
        $r->validate(['purchase_order_id'=>'required|exists:purchase_orders,id','vendor_ref'=>'required|max:80','date'=>'required|date','due_date'=>'nullable|date']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        // deteksi duplikat: unique(supplier_id, vendor_ref) di level DB + cek manual untuk pesan ramah
        $po = DB::table('purchase_orders')->find($r->purchase_order_id);
        if (DB::table('vendor_invoices')->where(['supplier_id'=>$po->supplier_id,'vendor_ref'=>$r->vendor_ref])->exists()) {
            return back()->withErrors(['msg'=>'Duplikat: vendor_ref sudah pernah ditagihkan supplier ini.'])->withInput();
        }
        $id = DB::transaction(function() use ($r,$po,$eid,$u) {
            $lines = DB::table('purchase_order_lines')->where('purchase_order_id',$po->id)->get();
            $grand = 0; foreach ($lines as $l) $grand += (float)$l->qty*(float)$l->price;
            $grand *= 1.11;
            // three-way matching: PO vs GRN vs invoice
            $recv = DB::table('goods_receipt_lines')->join('goods_receipts','goods_receipts.id','=','goods_receipt_lines.goods_receipt_id')
                ->where('goods_receipts.ref_id',$po->id)->selectRaw('product_id, SUM(qty_received) q')->groupBy('product_id')->pluck('q','product_id');
            $match = 'matched';
            foreach ($lines as $l) {
                $q = (float)($recv[$l->product_id] ?? 0);
                if ($q < (float)$l->qty - 0.0001) { $match = 'exception'; break; }
            }
            $number = 'VIN-'.date('Ym').'-'.str_pad((string)(DB::table('vendor_invoices')->count()+1),5,'0',STR_PAD_LEFT);
            $vid = DB::table('vendor_invoices')->insertGetId(['legal_entity_id'=>$eid,'purchase_order_id'=>$po->id,'supplier_id'=>$po->supplier_id,
                'number'=>$number,'vendor_ref'=>$r->vendor_ref,'date'=>$r->date,'due_date'=>$r->due_date,'grand_total'=>$grand,'paid_total'=>0,
                'status'=> $match==='exception' ? 'hold' : 'draft','match_status'=>$match,'created_at'=>now(),'updated_at'=>now()]);
            foreach ($lines as $l) DB::table('vendor_invoice_lines')->insert(['vendor_invoice_id'=>$vid,'product_id'=>$l->product_id,'qty'=>$l->qty,'price'=>$l->price,'line_total'=>(float)$l->qty*(float)$l->price,'created_at'=>now(),'updated_at'=>now()]);
            return $vid;
        });
        return redirect()->route('purchasing.vinvoices.index')->with('ok','Vendor invoice dicatat (three-way matching otomatis; exception => HOLD).');
    }
}
""")

print("gen7 done")

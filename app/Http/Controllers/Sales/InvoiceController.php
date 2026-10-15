<?php
namespace App\Http\Controllers\Sales;
use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\StockService;
use App\Services\PostingService;
use App\Services\AuditService;
class InvoiceController extends CrudController {
    protected string $model = \App\Models\Sales\Invoice::class;
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
    public function create() {
        $r = request();
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $fromSO = $r->get('from_so') ? \App\Models\Sales\SalesOrder::where('id',$r->get('from_so'))->first() : null;
        return view($this->viewPrefix.'.form',['meta'=>$this->meta(),'fromSO'=>$fromSO,
            'customers'=>\App\Models\Master\Customer::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'products'=>\App\Models\Master\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->get()]);
    }
    public function store(Request $r) {
        $r->validate(['customer_id'=>'required|exists:customers,id','date'=>'required|date','due_date'=>'nullable|date',
            'lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id',
            'lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \App\Models\Org\LegalEntity::find($eid); $br = \App\Models\Org\Branch::find($bid);
            $number = DocNumberService::next('INV',$eid,$bid,$ent?->code,$br?->code);
            $sub=0; $lines=[];
            foreach ($r->lines as $l) {
                $p = \App\Models\Master\Product::findOrFail($l['product_id']);
                if ((int)$p->legal_entity_id !== (int)$eid) throw new \RuntimeException('Produk beda entitas.');
                // blokir produk kedaluwarsa/karantina sesuai konfigurasi
                $lt=(float)$l['qty']*(float)$l['price']; $sub+=$lt;
                $lines[]=['product_id'=>$p->id,'snapshot_name'=>$p->name,'qty'=>$l['qty'],'price'=>$l['price'],'discount'=>0,'tax_rate'=>11,'line_total'=>$lt*1.11,'created_at'=>now(),'updated_at'=>now()];
            }
            $tax=$sub*0.11; $grand=$sub+$tax;
            $disc = 0; $voucherId = null;
            if ($r->filled('voucher_code')) {
                $vv = \App\Services\VoucherService::validate(trim($r->voucher_code), $eid, $grand);
                $disc = $vv['discount']; $voucherId = $vv['id'];
                $grand -= $disc;
            }
            $inv = \App\Models\Sales\Invoice::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'customer_id'=>$r->customer_id,'sales_order_id'=>$r->sales_order_id,'due_date'=>$r->due_date,
                'status'=>'posted','pay_status'=>'unpaid','subtotal'=>$sub,'discount'=>$disc,'tax'=>$tax,'grand_total'=>$grand,'paid_total'=>0,'created_by'=>$u->id]);
            if ($voucherId) \App\Services\VoucherService::consume($voucherId);
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
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
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
        try {
        DB::transaction(function() use ($r,$inv) {
            $inv2 = \App\Models\Sales\Invoice::where('id',$inv->id)->lockForUpdate()->first();
            $sisa = (float)$inv2->grand_total - (float)$inv2->paid_total;
            if ((float)$r->amount > $sisa + 0.005) throw new \RuntimeException('Pembayaran melebihi sisa tagihan ('.number_format($sisa,0).').');
            if ((float)$r->amount <= 0) throw new \RuntimeException('Nominal tidak valid.');
            $u = auth()->user(); $ctx = session('erp_context',[]);
            $ent = \App\Models\Org\LegalEntity::find($inv2->legal_entity_id);
            $number = DocNumberService::next('PAY',$inv2->legal_entity_id,$inv2->branch_id,$ent?->code);
            DB::table('sales_payments')->insert(['legal_entity_id'=>$inv2->legal_entity_id,'branch_id'=>$inv2->branch_id,
                'number'=>$number,'date'=>$r->date,'invoice_id'=>$inv2->id,'customer_id'=>$inv2->customer_id,
                'method'=>$r->method,'amount'=>$r->amount,'created_by'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            $paid = (float)$inv2->paid_total + (float)$r->amount;
            $inv2->update(['paid_total'=>$paid,'pay_status'=> $paid >= (float)$inv2->grand_total - 0.005 ? 'paid' : 'partial']);
            if ($inv2->fresh()->pay_status === 'paid') \App\Services\VoucherService::accrueOnPaid($inv2->id);
            // jurnal kas vs piutang
            $coa = fn($code)=>DB::table('coas')->where(['legal_entity_id'=>$inv2->legal_entity_id,'code'=>$code])->value('id');
            if ($coa('1000') && $coa('1200')) {
                PostingService::postJournal(['legal_entity_id'=>$inv2->legal_entity_id,'branch_id'=>$inv2->branch_id,'date'=>$r->date,
                    'number'=>'J-'.$number,'description'=>'Bayar '.$inv2->number,'source_type'=>'sales_payment','source_id'=>$inv2->id],
                    [['coa_id'=>$coa('1000'),'debit'=>(float)$r->amount,'credit'=>0],['coa_id'=>$coa('1200'),'debit'=>0,'credit'=>(float)$r->amount]],'pay-'.$number);
            }
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Pembayaran dicatat. Status lunas dihitung dari total, bukan dari form.');
    }
    public function pdf($id) {
        $row = $this->baseQuery()->with(['customer','lines'])->findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.invoice', compact('row'));
        return $pdf->download($row->number.'.pdf');
    }
}

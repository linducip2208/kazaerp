<?php
namespace App\Http\Controllers\Purchasing;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class VendorInvoiceController extends Controller {
    public function index() {
        $rows = DB::table('vendor_invoices')->join('suppliers','suppliers.id','=','vendor_invoices.supplier_id')
            ->select('vendor_invoices.*','suppliers.name as supplier')->orderByDesc('vendor_invoices.id')->paginate(15);
        return view('modules.purchasing.vinvoices.index', compact('rows'));
    }
    public function create() {
        $r = request();
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
        try {
        $id = DB::transaction(function() use ($r,$po,$eid,$u,$ctx) {
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
            $vid = DB::table('vendor_invoices')->insertGetId(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,'purchase_order_id'=>$po->id,'supplier_id'=>$po->supplier_id,
                'number'=>$number,'vendor_ref'=>$r->vendor_ref,'date'=>$r->date,'due_date'=>$r->due_date,'grand_total'=>$grand,'paid_total'=>0,
                'status'=> $match==='exception' ? 'hold' : 'draft','match_status'=>$match,'created_at'=>now(),'updated_at'=>now()]);
            foreach ($lines as $l) DB::table('vendor_invoice_lines')->insert(['vendor_invoice_id'=>$vid,'product_id'=>$l->product_id,'qty'=>$l->qty,'price'=>$l->price,'line_total'=>(float)$l->qty*(float)$l->price,'created_at'=>now(),'updated_at'=>now()]);
            return $vid;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('purchasing.vinvoices.index')->with('ok','Vendor invoice dicatat (three-way matching otomatis; exception => HOLD).');
    }
}

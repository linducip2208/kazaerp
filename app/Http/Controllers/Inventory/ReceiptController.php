<?php
namespace App\Http\Controllers\Inventory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\StockService;
class ReceiptController extends Controller {
    public function index() {
        $rows = DB::table('goods_receipts')->orderByDesc('id')->paginate(15);
        return view('modules.inventory.receipt.index', compact('rows'));
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view('modules.inventory.receipt.form', [
            'warehouses'=>\App\Models\Org\Warehouse::orderBy('code')->get(),
            'products'=>\App\Models\Master\Product::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'pos'=>\App\Models\Purchasing\PurchaseOrder::where('legal_entity_id',$eid)->whereIn('status',['approved','partial'])->orderByDesc('id')->limit(50)->get(),
        ]);
    }
    public function store(Request $r) {
        $r->validate(['warehouse_id'=>'required|exists:warehouses,id','date'=>'required|date','purchase_order_id'=>'nullable|exists:purchase_orders,id',
            'lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        try {
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
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('inventory.stock.index')->with('ok','Penerimaan dicatat ke ledger + saldo (mendukung partial).');
    }
}

<?php
namespace App\Http\Controllers\Inventory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $product = \App\Models\Master\Product::findOrFail($productId);
        return view('modules.inventory.stock.card', compact('rows','product'));
    }
    public function export(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = DB::table('stock_balances')->join('products','products.id','=','stock_balances.product_id')
            ->where('products.legal_entity_id',$eid)
            ->select('products.sku','products.name','stock_balances.qty_on_hand','stock_balances.qty_reserved')->get();
        $csv = "sku,nama,on_hand,reserved,available\n";
        foreach ($rows as $x) $csv .= "\"{$x->sku}\",\"{$x->name}\",{$x->qty_on_hand},{$x->qty_reserved},".((float)$x->qty_on_hand-(float)$x->qty_reserved)."\n";
        return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="stok.csv"']);
    }
}

<?php
namespace App\Http\Controllers\Sales;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\StockService;
class PosController extends Controller {
    public function index() {
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $shift = DB::table('pos_shifts')->where(['cashier_id'=>$u->id,'status'=>'open'])->first();
        $products = \App\Models\Master\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->limit(60)->get();
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
        try {
        DB::transaction(function() use ($r,$eid,$bid,$u,$shift) {
            $ent = \App\Models\Org\LegalEntity::find($eid); $br = \App\Models\Org\Branch::find($bid);
            $number = DocNumberService::next('POS',$eid,$bid,$ent?->code,$br?->code);
            $sub=0; $lns=[];
            foreach ($r->lines as $l) {
                $p = \App\Models\Master\Product::findOrFail($l['product_id']);
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
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
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

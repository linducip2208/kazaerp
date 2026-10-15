<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
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

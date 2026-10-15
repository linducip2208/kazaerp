<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $csv="nomor,tanggal,pelanggan,total,bayar,status\n";
            foreach ($rows->items() as $x) $csv .= "\"{$x->number}\",{$x->date},\"{$x->customer}\",{$x->grand_total},{$x->paid_total},{$x->pay_status}\n";
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

<?php
namespace App\Http\Controllers\Sales;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PosService;
class PosController extends Controller {
    public function index() {
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $shift = PosService::currentShift($u->id);
        $products = \App\Models\Master\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->limit(60)->get()
            ->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,
                'retail'=>\App\Services\PriceService::forProduct($p,'retail'),
                'wholesale'=>\App\Services\PriceService::forProduct($p,'wholesale'),
                'reseller'=>\App\Services\PriceService::forProduct($p,'reseller')]);
        $customers = \App\Models\Master\Customer::where('legal_entity_id',$eid)->where('is_active',true)->orderBy('name')->limit(100)->get();
        return view('modules.sales.pos.index', compact('shift','products','customers'));
    }
    public function openShift(Request $r) {
        $r->validate(['opening_cash'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        try {
            PosService::openShift((int)($ctx['legal_entity_id'] ?? $u->legal_entity_id), $ctx['branch_id'] ?? $u->branch_id, $u->id, (float)$r->opening_cash);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()]);
        }
        return back()->with('ok','Shift dibuka.');
    }
    public function checkout(Request $r) {
        $r->validate(['lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','pay_method'=>'required|in:cash,transfer,qris','customer_id'=>'nullable|exists:customers,id']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        try {
            PosService::checkout((int)($ctx['legal_entity_id'] ?? $u->legal_entity_id), $ctx['branch_id'] ?? $u->branch_id, $u->id, $r->lines, $r->pay_method, $r->customer_id ? (int)$r->customer_id : null);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Transaksi POS berhasil.');
    }
    public function closeShift(Request $r) {
        $r->validate(['closing_cash'=>'required|numeric|min:0']);
        $u = auth()->user();
        try {
            PosService::closeShift($u->id, (float)$r->closing_cash);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()]);
        }
        return back()->with('ok','Shift ditutup. Selisih tercatat.');
    }
}

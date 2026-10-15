<?php
namespace App\Http\Controllers\Thrift;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class LiveOrderController extends Controller {
    public function reserve(Request $r) {
        $r->validate(['live_session_id'=>'required|exists:live_sessions,id','thrift_item_id'=>'required|exists:thrift_items,id','customer_id'=>'required|exists:customers,id','price'=>'required|numeric|min:0']);
        try {
            DB::transaction(function() use ($r) {
                $item = DB::table('thrift_items')->where('id',$r->thrift_item_id)->lockForUpdate()->first();
                if ($item->status==='sold') throw new \RuntimeException('Barang sudah terjual.');
                if (DB::table('live_orders')->where('thrift_item_id',$r->thrift_item_id)->exists()) throw new \RuntimeException('Barang sudah direservasi sesi lain.');
                DB::table('live_orders')->insert(['live_session_id'=>$r->live_session_id,'thrift_item_id'=>$r->thrift_item_id,
                    'customer_id'=>$r->customer_id,'price'=>$r->price,'pay_deadline'=>now()->addHours(24),'status'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
                DB::table('thrift_items')->where('id',$r->thrift_item_id)->update(['status'=>'reserved']);
            });
        } catch (\Throwable $e) { return back()->withErrors(['msg'=>$e->getMessage()]); }
        return back()->with('ok','Item direservasi (identitas unik, anti double-sell).');
    }
}

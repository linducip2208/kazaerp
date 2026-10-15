<?php
namespace App\Http\Controllers\Rental;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
class RentalBookingController extends Controller {
    public function store(Request $r) {
        $r->validate(['rental_unit_id'=>'required|exists:rental_units,id','customer_id'=>'required|exists:customers,id',
            'start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','rate'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        // cegah bentrok: unit sama + periode overlap + status aktif
        $clash = DB::table('rental_reservations')->where('rental_unit_id',$r->rental_unit_id)
            ->whereIn('status',['reserved','active','fitting','preparation'])
            ->where('start_date','<=',$r->end_date)->where('end_date','>=',$r->start_date)->exists();
        if ($clash) return back()->withErrors(['msg'=>'Unit fisik bentrok pada periode tersebut (termasuk fitting/persiapan).'])->withInput();
        $ent = \App\Models\Org\LegalEntity::find($eid);
        $number = DocNumberService::next('RNT',$eid,null,$ent?->code);
        DB::table('rental_reservations')->insert(['legal_entity_id'=>$eid,'number'=>$number,'rental_unit_id'=>$r->rental_unit_id,
            'customer_id'=>$r->customer_id,'start_date'=>$r->start_date,'end_date'=>$r->end_date,'rate'=>$r->rate,
            'deposit'=>$r->deposit ?? 0,'status'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('rental.reservations.index')->with('ok','Reservasi sewa tersimpan (deposit = kewajiban, bukan pendapatan).');
    }
}

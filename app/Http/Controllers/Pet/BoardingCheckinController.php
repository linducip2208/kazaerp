<?php
namespace App\Http\Controllers\Pet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
class BoardingCheckinController extends Controller {
    public function store(Request $r) {
        $r->validate(['pet_id'=>'required|exists:pets,id','boarding_room_id'=>'required|exists:boarding_rooms,id',
            'check_in'=>'required|date','check_out'=>'required|date|after:check_in']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $room = DB::table('boarding_rooms')->where('id',$r->boarding_room_id)->first();
        $overlap = DB::table('boarding_reservations')->where('boarding_room_id',$room->id)
            ->whereIn('status',['reserved','checked_in'])
            ->where('check_in','<',$r->check_out)->where('check_out','>',$r->check_in)->count();
        if ($overlap >= (int)$room->capacity) return back()->withErrors(['msg'=>'Kapasitas kamar penuh pada periode tersebut (anti overcapacity).']);
        $ent = \App\Models\Org\LegalEntity::find($eid);
        $number = DocNumberService::next('BRD',$eid,$ctx['branch_id'] ?? $u->branch_id,$ent?->code);
        $days = max(1, (int)ceil((strtotime($r->check_out)-strtotime($r->check_in))/86400));
        DB::table('boarding_reservations')->insert(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,
            'number'=>$number,'pet_id'=>$r->pet_id,'boarding_room_id'=>$room->id,'check_in'=>$r->check_in,'check_out'=>$r->check_out,
            'feeding'=>$r->feeding,'medication_note'=>$r->medication_note,'deposit'=>$r->deposit ?? 0,
            'grand_total'=>$days*(float)$room->rate,'status'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('pet.boarding.index')->with('ok','Reservasi boarding tersimpan (kapasitas dicek).');
    }
}

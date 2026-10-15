<?php
namespace App\Http\Controllers\Purchasing;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\ApprovalService;
class RequisitionController extends Controller {
    public function index() {
        $rows = DB::table('purchase_requisitions')->orderByDesc('id')->paginate(15);
        return view('modules.purchasing.requisitions.index', compact('rows'));
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view('modules.purchasing.requisitions.form', [
            'products'=>\App\Models\Master\Product::where('legal_entity_id',$eid)->where('is_purchasable',true)->orderBy('name')->get(),
            'ccs'=>\App\Models\Org\CostCenter::where('legal_entity_id',$eid)->get()]);
    }
    public function store(Request $r) {
        $r->validate(['date'=>'required|date','lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','is_emergency'=>'nullable|boolean','emergency_reason'=>'nullable|max:255']);
        if ($r->boolean('is_emergency') && !$r->emergency_reason) return back()->withErrors(['msg'=>'Pengadaan darurat wajib menyertakan alasan.'])->withInput();
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$u,$ctx) {
            $ent = \App\Models\Org\LegalEntity::find($eid);
            $number = DocNumberService::next('PR',$eid,$ctx['branch_id'] ?? $u->branch_id,$ent?->code);
            $rid = DB::table('purchase_requisitions')->insertGetId(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,
                'cost_center_id'=>$r->cost_center_id,'number'=>$number,'date'=>$r->date,'status'=>'submitted',
                'is_emergency'=>$r->boolean('is_emergency'),'emergency_reason'=>$r->emergency_reason,'requested_by'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            $total=0;
            foreach ($r->lines as $l) { DB::table('purchase_requisition_lines')->insert(['purchase_requisition_id'=>$rid,'product_id'=>$l['product_id'],'qty'=>$l['qty'],'est_price'=>$l['est_price'] ?? 0,'created_at'=>now(),'updated_at'=>now()]); $total += (float)$l['qty']*(float)($l['est_price'] ?? 0); }
            ApprovalService::request('purchase_requisition',$rid,$eid,$total);
            return $rid;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('purchasing.requisitions.index')->with('ok','Permintaan pembelian diajukan (darurat ditandai + butuh review).');
    }
}

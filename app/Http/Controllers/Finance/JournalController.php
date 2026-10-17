<?php
namespace App\Http\Controllers\Finance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\PostingService;
use App\Services\ApprovalService;
use App\Services\AuditService;
class JournalController extends Controller {
    public function index(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = DB::table('journals')->where('legal_entity_id',$eid)->orderByDesc('id')->paginate(15)->withQueryString();
        return view('modules.finance.journals.index', compact('rows'));
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view('modules.finance.journals.form', ['coas'=>DB::table('coas')->where('legal_entity_id',$eid)->where('is_postable',true)->orderBy('code')->get()]);
    }
    public function store(Request $r) {
        $r->validate(['date'=>'required|date','description'=>'required|max:255','lines'=>'required|array|min:2',
            'lines.*.coa_id'=>'required|exists:coas,id','lines.*.debit'=>'required|numeric|min:0','lines.*.credit'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \App\Models\Org\LegalEntity::find($eid);
            $number = DocNumberService::next('J',$eid,$bid,$ent?->code);
            $lines = [];
            foreach ($r->lines as $l) $lines[]=['coa_id'=>$l['coa_id'],'debit'=>(float)$l['debit'],'credit'=>(float)$l['credit']];
            return PostingService::postJournal(['legal_entity_id'=>$eid,'branch_id'=>$bid,'date'=>$r->date,'number'=>$number,
                'description'=>$r->description,'source_type'=>'manual','source_id'=>null],$lines,'manual-'.$number);
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('finance.journals.index')->with('ok','Jurnal terposting (balance + idempotent).');
    }
    public function reverse(Request $r, $id) {
        $r->validate(['reason'=>'required|max:255']);
        try {
            PostingService::reverse((int)$id, $r->reason);
            AuditService::log('reverse','journals',(int)$id,['reason'=>$r->reason]);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Jurnal direversal (bukan dihapus).');
    }
}

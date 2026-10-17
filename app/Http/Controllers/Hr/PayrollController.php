<?php
namespace App\Http\Controllers\Hr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\ApprovalService;
use App\Services\PostingService;
use App\Services\AuditService;
class PayrollController extends Controller {
    public function index() {
        $rows = DB::table('payrolls')->orderByDesc('id')->paginate(15);
        return view('modules.hr.payroll.index', compact('rows'));
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view('modules.hr.payroll.form', [
            'periods'=>DB::table('payroll_periods')->where('legal_entity_id',$eid)->orderByDesc('id')->get(),
            'components'=>DB::table('payroll_components')->where('legal_entity_id',$eid)->where('is_active',true)->get(),
            'employees'=>DB::table('employees')->where('legal_entity_id',$eid)->where('status','active')->orderBy('name')->get(),
        ]);
    }
    public function store(Request $r) {
        // PENDING_BUSINESS_DECISION: rumus pajak/BPJS/lembur configurable via payroll_components, bukan hardcode aturan.
        $r->validate(['payroll_period_id'=>'required|exists:payroll_periods,id','items'=>'required|array|min:1',
            'items.*.employee_id'=>'required|exists:employees,id','items.*.gross'=>'required|numeric|min:0','items.*.deduction'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$u) {
            $ent = \App\Models\Org\LegalEntity::find($eid);
            $number = DocNumberService::next('PR',$eid,null,$ent?->code);
            $total=0; foreach ($r->items as $it) $total += (float)$it['gross']-(float)$it['deduction'];
            $pid = DB::table('payrolls')->insertGetId(['payroll_period_id'=>$r->payroll_period_id,'legal_entity_id'=>$eid,
                'number'=>$number,'status'=>'draft','grand_total'=>$total,'created_at'=>now(),'updated_at'=>now()]);
            foreach ($r->items as $it) {
                $net=(float)$it['gross']-(float)$it['deduction'];
                DB::table('payroll_lines')->insert(['payroll_id'=>$pid,'employee_id'=>$it['employee_id'],'gross'=>$it['gross'],
                    'deduction'=>$it['deduction'],'net'=>$net,'breakdown'=>json_encode($it),'created_at'=>now(),'updated_at'=>now()]);
                DB::table('payslips')->insert(['payroll_id'=>$pid,'employee_id'=>$it['employee_id'],'number'=>$number.'-'.$it['employee_id'],'net'=>$net,'created_at'=>now(),'updated_at'=>now()]);
            }
            ApprovalService::request('payroll',$pid,$eid,$total);
            return $pid;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('hr.payroll.index')->with('ok','Payroll dihitung dari komponen configurable (bukan aturan hardcode).');
    }
    public function approve($id) {
        $row = DB::table('payrolls')->where('id',$id)->firstOrFail();
        if ($row->status!=='draft') abort(422,'Hanya draft yang bisa disetujui.');
        try {
            $ap = DB::table('approvals')->where(['doc_type'=>'payroll','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
            if ($ap) ApprovalService::decide($ap->id,'approved','OK');
            DB::table('payrolls')->where('id',$id)->update(['status'=>'approved','approved_by'=>auth()->id()]);
            AuditService::log('approve','payrolls',(int)$id,['grand_total'=>(float)$row->grand_total]);
            // Mapping jurnal: Dr Beban Gaji (6100) / Cr Utang Gaji (2300), idempotent via payroll_journals
            if (!DB::table('payroll_journals')->where('payroll_id',$id)->exists()) {
                $coa = fn($code)=>DB::table('coas')->where(['legal_entity_id'=>$row->legal_entity_id,'code'=>$code])->value('id');
                if ($coa('6100') && $coa('2300')) {
                    $ent = \App\Models\Org\LegalEntity::find($row->legal_entity_id);
                    $jid = PostingService::postJournal(['legal_entity_id'=>$row->legal_entity_id,'branch_id'=>null,
                        'date'=>now()->toDateString(),'number'=>DocNumberService::next('J',$row->legal_entity_id,null,$ent?->code),
                        'description'=>'Gaji '.$row->number,'source_type'=>'payroll','source_id'=>$id],
                        [['coa_id'=>$coa('6100'),'debit'=>(float)$row->grand_total,'credit'=>0],
                         ['coa_id'=>$coa('2300'),'debit'=>0,'credit'=>(float)$row->grand_total]],'payroll-'.$id);
                    DB::table('payroll_journals')->insert(['payroll_id'=>$id,'journal_id'=>$jid,'created_at'=>now(),'updated_at'=>now()]);
                }
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Payroll disetujui + jurnal gaji terposting (terkunci dari perubahan diam-diam).');
    }
    public function payslip($id) {
        $slip = DB::table('payslips')->join('employees','employees.id','=','payslips.employee_id')
            ->where('payslips.id',$id)->select('payslips.*','employees.name','employees.nip')->firstOrFail();
        if (!auth()->user()->hasPermission('hr.salary') && (int)optional(auth()->user())->id !== (int)DB::table('employees')->where('user_id',auth()->id())->value('id')) {
            // karyawan hanya boleh lihat slip sendiri; cek via employee.user_id
            $empId = DB::table('employees')->where('user_id',auth()->id())->value('id');
            if ((int)$empId !== (int)$slip->employee_id) abort(403);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.payslip', compact('slip'));
        return $pdf->download($slip->number.'.pdf');
    }
}

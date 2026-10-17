<?php
namespace App\Http\Controllers\Finance;
use App\Http\Controllers\CrudController;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\ApprovalService;
class ExpenseController extends CrudController {
    protected string $model = \App\Models\Finance\Expense::class;
    protected string $routePrefix = 'finance.expenses';
    protected string $viewPrefix = 'modules.finance.expenses';
    protected string $title = 'Biaya/Expense';
    protected string $perm = 'finance';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','title'=>'Judul','amount'=>'Nominal','status'=>'Status'];
    protected array $fields = [
        ['name'=>'title','label'=>'Judul','type'=>'text'],
        ['name'=>'date','label'=>'Tanggal','type'=>'date'],
        ['name'=>'amount','label'=>'Nominal','type'=>'number'],
        ['name'=>'cost_center_id','label'=>'Cost Center','type'=>'select_model','model'=>'App\\Models\\Org\\CostCenter'],
    ];
    protected array $rules = ['title'=>'required|max:150','date'=>'required|date','amount'=>'required|numeric|min:1'];
    protected array $searchable = ['number','title'];
    public function store(\Illuminate\Http\Request $r) {
        $v = $r->validate($this->rules);
        $this->injectContext($v);
        $ent = \App\Models\Org\LegalEntity::find($v['legal_entity_id']);
        $v['number'] = DocNumberService::next('EXP',$v['legal_entity_id'],$v['branch_id'] ?? null,$ent?->code);
        $v['status']='draft'; $v['requested_by']=auth()->id();
        try {
            $row = (new ($this->model))::create($v);
            ApprovalService::request('expense',$row->id,(int)$row->legal_entity_id,(float)$row->amount);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok','Expense dibuat + approval diminta.');
    }
    public function update(\Illuminate\Http\Request $r, $id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->guardPosted($row);
        $v = $r->validate($this->rules);
        $reRequested = false; $wasApproved = ($row->status === 'approved');
        try {
            \Illuminate\Support\Facades\DB::transaction(function() use ($v,$row,&$reRequested,$wasApproved) {
                $before = $row->toArray();
                $row->update($v);
                \App\Services\AuditService::log('update', $row->getTable(), $row->id, ['before'=>$before,'after'=>$row->fresh()->toArray()]);
                // Ubah setelah pengajuan: batalkan approval pending (anti stale approval)
                // lalu minta persetujuan ulang atas angka terbaru.
                $n = \App\Services\ApprovalService::invalidatePending('expense',$row->id,'Dokumen diubah setelah pengajuan');
                if ($wasApproved) {
                    $row->update(['status'=>'draft']);
                    \Illuminate\Support\Facades\DB::table('doc_status_histories')->insert(['doc_type'=>$row->getTable(),'doc_id'=>$row->id,
                        'from_status'=>'approved','to_status'=>'draft','actor_id'=>auth()->id(),
                        'note'=>'Diubah setelah disetujui — perlu persetujuan ulang','created_at'=>now(),'updated_at'=>now()]);
                }
                if ($n > 0 || $wasApproved) {
                    \App\Services\ApprovalService::request('expense',$row->id,(int)$row->legal_entity_id,(float)$row->fresh()->amount);
                    $reRequested = true;
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        $msg = $this->title.' berhasil diperbarui.'
            .($reRequested ? ' Approval lama dibatalkan & pengajuan baru dibuat — perlu disetujui ulang.' : '');
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok',$msg);
    }
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
            $ap = DB::table('approvals')->where(['doc_type'=>'expense','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
            if ($ap) ApprovalService::decide($ap->id,'approved','OK');
            $this->setStatus($row,'draft','approved');
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Expense disetujui.');
    }
}

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

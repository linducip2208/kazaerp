<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditService;
abstract class CrudController extends Controller {
    protected string $model;
    protected string $routePrefix = '';
    protected string $viewPrefix = '';
    protected string $title = 'Data';
    protected string $perm = '';
    /** @return array<string,string> kolom => label untuk tabel */
    protected array $columns = [];
    /** @return array field config: name,label,type,options */
    protected array $fields = [];
    protected array $rules = [];
    protected array $searchable = [];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = 'branch_id';
    protected bool $sortable = true;

    protected function baseQuery() {
        $m = $this->model;
        $q = $m::query();
        // Isolasi entitas default: batasi ke konteks aktif kecuali role lintas entitas
        $u = auth()->user();
        if ($this->entityField && !$u->hasRole('super_admin','holding_owner','executive','auditor')) {
            $ctx = session('erp_context',[]);
            $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
            if ($eid && in_array($this->entityField, (new $m)->getFillable())) {
                $q->where($m::query()->getModel()->getTable().'.'.$this->entityField, $eid);
            }
        }
        return $q;
    }
    public function index(Request $r) {
        $q = $this->baseQuery();
        if ($s = trim((string)$r->get('q',''))) {
            $q->where(function($w) use ($s) {
                foreach ($this->searchable as $i=>$f) {
                    $i===0 ? $w->where($f,'like',"%$s%") : $w->orWhere($f,'like',"%$s%");
                }
            });
        }
        if ($r->filled('status') && in_array('status',(new ($this->model))->getFillable())) {
            $q->where('status',$r->status);
        }
        $rows = $q->latest('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index', ['rows'=>$rows,'meta'=>$this->meta()]);
    }
    public function create() { return view($this->viewPrefix.'.form', ['row'=>new ($this->model),'meta'=>$this->meta(),'method'=>'POST','action'=>route($this->routePrefix.'.store')]); }
    public function store(Request $r) {
        $v = $r->validate($this->rules);
        $this->injectContext($v);
        DB::beginTransaction();
        try {
            $row = ($this->model)::create($v);
            AuditService::log('create', (new ($this->model))->getTable(), $row->id, []);
            DB::commit();
        } catch (\Throwable $e) { DB::rollBack(); return back()->withErrors(['msg'=>$e->getMessage()])->withInput(); }
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok',$this->title.' berhasil disimpan.');
    }
    public function show($id) {
        $row = $this->baseQuery()->findOrFail($id);
        return view($this->viewPrefix.'.show', ['row'=>$row,'meta'=>$this->meta()]);
    }
    public function edit($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->guardPosted($row);
        return view($this->viewPrefix.'.form', ['row'=>$row,'meta'=>$this->meta(),'method'=>'PUT','action'=>route($this->routePrefix.'.update',$row->id)]);
    }
    public function update(Request $r, $id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->guardPosted($row);
        $v = $r->validate($this->rules);
        try {
            $before = $row->toArray();
            $row->update($v);
            AuditService::log('update', $row->getTable(), $row->id, ['before'=>$before,'after'=>$row->fresh()->toArray()]);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()])->withInput();
        }
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok',$this->title.' berhasil diperbarui.');
    }
    public function destroy($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
        $this->guardPosted($row);
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($row))) { $row->delete(); }
        else {
            if (($row->status ?? null)==='posted') return back()->withErrors(['msg'=>'Dokumen posted tidak boleh dihapus. Gunakan reversal.']);
            $row->delete();
        }
        } catch (\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()]);
        }
        AuditService::log('delete', $row->getTable(), (int)$id, []);
        return redirect()->route($this->routePrefix.'.index')->with('ok',$this->title.' dihapus.');
    }
    protected function injectContext(array &$v): void {
        $u = auth()->user(); $ctx = session('erp_context',[]);
        if ($this->entityField && empty($v[$this->entityField]) && in_array($this->entityField,(new ($this->model))->getFillable())) {
            $v[$this->entityField] = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        }
        if ($this->branchField && array_key_exists($this->branchField,(new ($this->model))->getFillable()) && empty($v[$this->branchField])) {
            $v[$this->branchField] = $ctx['branch_id'] ?? $u->branch_id;
        }
        if (array_key_exists('created_by',(new ($this->model))->getFillable()) && empty($v['created_by'])) $v['created_by'] = $u->id;
    }
    protected function guardPosted($row): void {
        if (($row->status ?? null)==='posted') abort(422,'Dokumen posted tidak dapat diubah. Gunakan reversal/koreksi.');
    }
    protected function meta(): array {
        return ['title'=>$this->title,'route'=>$this->routePrefix,'view'=>$this->viewPrefix,'columns'=>$this->columns,'fields'=>$this->fields];
    }
    protected function setStatus($row, string $from, string $to, ?string $note=null): void {
        DB::transaction(function() use ($row,$from,$to,$note) {
            if ($row->status!==$from) throw new \RuntimeException('Status dokumen bukan '.$from.'.');
            $row->update(['status'=>$to]);
            DB::table('doc_status_histories')->insert(['doc_type'=>$row->getTable(),'doc_id'=>$row->id,'from_status'=>$from,'to_status'=>$to,'actor_id'=>auth()->id(),'note'=>$note,'created_at'=>now(),'updated_at'=>now()]);
        });
    }
}

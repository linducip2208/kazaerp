"""Generator 6: base controller + org/identity/master/sales controllers."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

w("app/Http/Controllers/CrudController.php", """<?php
namespace App\\Http\\Controllers;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\AuditService;
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
        } catch (\\Throwable $e) { DB::rollBack(); return back()->withErrors(['msg'=>$e->getMessage()])->withInput(); }
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
        $before = $row->toArray();
        $row->update($v);
        AuditService::log('update', $row->getTable(), $row->id, ['before'=>$before,'after'=>$row->fresh()->toArray()]);
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok',$this->title.' berhasil diperbarui.');
    }
    public function destroy($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->guardPosted($row);
        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive($row))) { $row->delete(); }
        else {
            if (($row->status ?? null)==='posted') return back()->withErrors(['msg'=>'Dokumen posted tidak boleh dihapus. Gunakan reversal.']);
            $row->delete();
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
            if ($row->status!==$from) throw new \\RuntimeException('Status dokumen bukan '.$from.'.');
            $row->update(['status'=>$to]);
            DB::table('doc_status_histories')->insert(['doc_type'=>$row->getTable(),'doc_id'=>$row->id,'from_status'=>$from,'to_status'=>$to,'actor_id'=>auth()->id(),'note'=>$note,'created_at'=>now(),'updated_at'=>now()]);
        });
    }
}
""")

def simple(ns, cls, model, route, view, title, perm, columns, fields, rules, searchable, entity='legal_entity_id', branch='branch_id'):
    cols = ",\n        ".join([f"'{k}' => '{v}'" for k,v in columns.items()])
    flds = []
    for f in fields:
        opts = ""
        if 'options' in f: opts = f", 'options'=>{f['options']}"
        elif 'model' in f: opts = f", 'model'=>'{f['model']}', 'option_label'=>'{f.get('option_label','name')}'"
        flds.append(f"['name'=>'{f['name']}','label'=>'{f['label']}','type'=>'{f['type']}'{opts}]")
    flds_s = ",\n        ".join(flds)
    rules_s = ",\n        ".join([f"'{k}' => '{v}'" for k,v in rules.items()])
    search_s = ", ".join([f"'{s}'" for s in searchable])
    return f"""<?php
namespace App\\Http\\Controllers\\{ns};
use App\\Http\\Controllers\\CrudController;
class {cls} extends CrudController {{
    protected string $model = \\{model}::class;
    protected string $routePrefix = '{route}';
    protected string $viewPrefix = '{view}';
    protected string $title = '{title}';
    protected string $perm = '{perm}';
    protected array $columns = [
        {cols}
    ];
    protected array $fields = [
        {flds_s}
    ];
    protected array $rules = [
        {rules_s}
    ];
    protected array $searchable = [{search_s}];
    protected ?string $entityField = {'null' if entity is None else repr(entity)};
    protected ?string $branchField = {'null' if branch is None else repr(branch)};
}}
"""

CTR = []
# Org
CTR.append(("Org","HoldingController","App\\Models\\Org\\Holding","org.holdings","modules.org.holdings","Holding","org",
  {'code':'Kode','name':'Nama','is_active':'Aktif'},
  [{'name':'code','label':'Kode','type':'text'},{'name':'name','label':'Nama','type':'text'},{'name':'phone','label':'Telepon','type':'text'},{'name':'address','label':'Alamat','type':'textarea'}],
  {'code':'required|max:20','name':'required|max:150'},['code','name'],None,None))
CTR.append(("Org","LegalEntityController","App\\Models\\Org\\LegalEntity","org.entities","modules.org.entities","Badan Usaha","org",
  {'code':'Kode','name':'Nama','npwp':'NPWP'},
  [{'name':'holding_id','label':'Holding','type':'select_model','model':'App\\\\Models\\\\Org\\\\Holding'},{'name':'code','label':'Kode','type':'text'},{'name':'name','label':'Nama','type':'text'},{'name':'npwp','label':'NPWP','type':'text'},{'name':'address','label':'Alamat','type':'textarea'}],
  {'holding_id':'required|exists:holdings,id','code':'required|max:20','name':'required|max:150'},['code','name'],None,None))
CTR.append(("Org","BranchController","App\\Models\\Org\\Branch","org.branches","modules.org.branches","Cabang","org",
  {'code':'Kode','name':'Nama'},
  [{'name':'legal_entity_id','label':'Badan Usaha','type':'select_model','model':'App\\\\Models\\\\Org\\\\LegalEntity'},{'name':'code','label':'Kode','type':'text'},{'name':'name','label':'Nama','type':'text'},{'name':'address','label':'Alamat','type':'textarea'}],
  {'legal_entity_id':'required|exists:legal_entities,id','code':'required|max:20','name':'required|max:150'},['code','name'],'legal_entity_id',None))
CTR.append(("Org","WarehouseController","App\\Models\\Org\\Warehouse","org.warehouses","modules.org.warehouses","Gudang","org",
  {'code':'Kode','name':'Nama'},
  [{'name':'branch_id','label':'Cabang','type':'select_model','model':'App\\\\Models\\\\Org\\\\Branch'},{'name':'code','label':'Kode','type':'text'},{'name':'name','label':'Nama','type':'text'}],
  {'branch_id':'required|exists:branches,id','code':'required|max:20','name':'required|max:150'},['code','name'],None,None))
# Identity
CTR.append(("Identity","UserController","App\\Models\\User","users","modules.users","Pengguna","users",
  {'name':'Nama','email':'Email','is_active':'Aktif'},
  [{'name':'name','label':'Nama','type':'text'},{'name':'email','label':'Email','type':'email'},{'name':'password','label':'Kata sandi (kosongkan jika tidak diubah)','type':'password'},{'name':'legal_entity_id','label':'Badan Usaha','type':'select_model','model':'App\\\\Models\\\\Org\\\\LegalEntity'},{'name':'branch_id','label':'Cabang','type':'select_model','model':'App\\\\Models\\\\Org\\\\Branch'}],
  {'name':'required|max:100','email':'required|email|max:150'},['name','email'],None,None))
CTR.append(("Identity","RoleController","App\\Models\\Identity\\Role","roles","modules.roles","Peran","roles",
  {'name':'Kode','label':'Label'},
  [{'name':'name','label':'Kode','type':'text'},{'name':'label','label':'Label','type':'text'}],
  {'name':'required|max:60','label':'required|max:100'},['name','label'],None,None))
# Master
CTR.append(("Master","CustomerController","App\\Models\\Master\\Customer","crm.customers","modules.crm.customers","Pelanggan","crm",
  {'code':'Kode','name':'Nama','phone':'Telepon','tier':'Tier'},
  [{'name':'code','label':'Kode','type':'text'},{'name':'name','label':'Nama','type':'text'},{'name':'kind','label':'Jenis','type':'select','options':"['retail'=>'Retail','reseller'=>'Reseller','corporate'=>'Corporate']"},{'name':'tier','label':'Tier Harga','type':'select','options':"['retail'=>'Retail','wholesale'=>'Wholesale','reseller'=>'Reseller']"},{'name':'phone','label':'Telepon','type':'text'},{'name':'email','label':'Email','type':'email'}],
  {'code':'required|max:30','name':'required|max:150'},['code','name','phone'],'legal_entity_id','branch_id'))
CTR.append(("Master","SupplierController","App\\Models\\Master\\Supplier","crm.suppliers","modules.crm.suppliers","Supplier","crm",
  {'code':'Kode','name':'Nama','phone':'Telepon'},
  [{'name':'code','label':'Kode','type':'text'},{'name':'name','label':'Nama','type':'text'},{'name':'phone','label':'Telepon','type':'text'},{'name':'email','label':'Email','type':'email'},{'name':'bank_account','label':'Rekening','type':'text'}],
  {'code':'required|max:30','name':'required|max:150'},['code','name'],'legal_entity_id',None))
CTR.append(("Master","ProductController","App\\Models\\Master\\Product","master.products","modules.master.products","Produk","crm",
  {'sku':'SKU','name':'Nama','sell_price':'Harga'},
  [{'name':'sku','label':'SKU','type':'text'},{'name':'barcode','label':'Barcode','type':'text'},{'name':'name','label':'Nama','type':'text'},{'name':'kind','label':'Jenis','type':'select','options':"['goods'=>'Barang','service'=>'Jasa','package'=>'Paket']"},{'name':'category_id','label':'Kategori','type':'select_model','model':'App\\\\Models\\\\Master\\\\Category'},{'name':'cost_price','label':'HPP/Modal','type':'number'},{'name':'sell_price','label':'Harga Jual','type':'number'},{'name':'wholesale_price','label':'Harga Grosir','type':'number'},{'name':'reseller_price','label':'Harga Reseller','type':'number'}],
  {'sku':'required|max:40','name':'required|max:150','sell_price':'required|numeric|min:0'},['sku','name','barcode'],'legal_entity_id',None))
CTR.append(("Master","CategoryController","App\\Models\\Master\\Category","master.categories","modules.master.categories","Kategori","crm",
  {'kind':'Jenis','code':'Kode','name':'Nama'},
  [{'name':'kind','label':'Jenis','type':'select','options':"['product'=>'Produk','service'=>'Jasa']"},{'name':'code','label':'Kode','type':'text'},{'name':'name','label':'Nama','type':'text'}],
  {'code':'required|max:30','name':'required|max:150'},['code','name'],None,None))
CTR.append(("Master","LeadController","App\\Models\\Master\\Lead","crm.leads","modules.crm.leads","Lead","crm",
  {'name':'Nama','phone':'Telepon','stage':'Tahap'},
  [{'name':'name','label':'Nama','type':'text'},{'name':'phone','label':'Telepon','type':'text'},{'name':'email','label':'Email','type':'email'},{'name':'stage','label':'Tahap','type':'select','options':"['new'=>'Baru','contacted'=>'Dihubungi','qualified'=>'Qualified','lost'=>'Lost','converted'=>'Converted']"},{'name':'lost_reason','label':'Alasan Lost','type':'text'}],
  {'name':'required|max:150'},['name','phone'],'legal_entity_id',None))
CTR.append(("Master","OpportunityController","App\\Models\\Master\\Opportunity","crm.opportunities","modules.crm.opportunities","Opportunity","crm",
  {'title':'Judul','amount':'Nilai','stage':'Tahap'},
  [{'name':'title','label':'Judul','type':'text'},{'name':'amount','label':'Nilai','type':'number'},{'name':'stage','label':'Tahap','type':'select','options':"['prospect'=>'Prospek','negotiation'=>'Negosiasi','won'=>'Menang','lost'=>'Kalah']"},{'name':'probability','label':'Probabilitas %','type':'number'}],
  {'title':'required|max:150','amount':'required|numeric|min:0'},['title'],'legal_entity_id',None))

for ns, cls, model, route, view, title, perm, cols, fields, rules, search, ent, br in CTR:
    w(f"app/Http/Controllers/{ns}/{cls}.php", simple(ns, cls, model, route, view, title, perm, cols, fields, rules, search, ent, br))
print("gen6 done", len(CTR))

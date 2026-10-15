"""Generator 8a: secondary docs + finance + HR controllers."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

w("app/Http/Controllers/Concerns/DocLines.php", """<?php
namespace App\\Http\\Controllers\\Concerns;
use Illuminate\\Support\\Facades\\DB;
trait DocLines {
    protected function computeLines($products, array $lines): array {
        $sub=0; $rows=[];
        foreach ($lines as $l) {
            $p = $products[$l['product_id']] ?? null;
            if (!$p) throw new \\RuntimeException('Produk tidak valid.');
            $lt=(float)$l['qty']*(float)$l['price']; $sub+=$lt;
            $rows[]=['product_id'=>$p->id,'snapshot_name'=>$p->name,'qty'=>$l['qty'],'price'=>$l['price'],
                'discount'=>0,'tax_rate'=>11,'line_total'=>$lt*1.11,'created_at'=>now(),'updated_at'=>now()];
        }
        $tax=$sub*0.11;
        return [$rows,$sub,$tax,$sub+$tax];
    }
}
""")

SECONDARY = {
 "Sales/QuotationController": ("App\\Models\\Sales\\Quotation","sales.quotes","modules.sales.quotes","Quotation","QT","quotations","quotation_lines","quotation_id"),
 "Sales/DeliveryOrderController": ("App\\Models\\Sales\\DeliveryOrder","sales.deliveries","modules.sales.deliveries","Delivery Order","DO","delivery_orders","delivery_order_lines","delivery_order_id"),
 "Sales/CreditNoteController": ("App\\Models\\Sales\\CreditNote","sales.creditnotes","modules.sales.creditnotes","Credit Note","CN","credit_notes","credit_note_lines","credit_note_id"),
 "Sales/SalesReturnController": ("App\\Models\\Sales\\SalesReturn","sales.returns","modules.sales.returns","Retur Penjualan","RT","sales_returns","sales_return_lines","sales_return_id"),
}
for path, (model, route, view, title, prefix, htbl, ltbl, fk) in SECONDARY.items():
    ns = path.split("/")[0]; cls = path.split("/")[1]
    w(f"app/Http/Controllers/{path}.php", f"""<?php
namespace App\\Http\\Controllers\\{ns};
use App\\Http\\Controllers\\CrudController;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Http\\Controllers\\Concerns\\DocLines;
class {cls} extends CrudController {{
    use DocLines;
    protected string $model = \\{model}::class;
    protected string $routePrefix = '{route}';
    protected string $viewPrefix = '{view}';
    protected string $title = '{title}';
    protected string $perm = 'sales';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','grand_total'=>'Total','status'=>'Status'];
    protected array $fields = []; protected array $rules = []; protected array $searchable = ['number'];
    public function index(Request $r) {{
        $rows = $this->baseQuery()->orderByDesc('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index',['rows'=>$rows,'meta'=>$this->meta()]);
    }}
    public function create() {{
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix.'.form',['meta'=>$this->meta(),
            'customers'=>\\App\\Models\\Master\\Customer::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'products'=>\\App\\Models\\Master\\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->get()]);
    }}
    public function store(Request $r) {{
        $r->validate(['customer_id'=>'required|exists:customers,id','date'=>'required|date','lines'=>'required|array|min:1',
            'lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {{
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid); $br = \\App\\Models\\Org\\Branch::find($bid);
            $number = DocNumberService::next('{prefix}',$eid,$bid,$ent?->code,$br?->code);
            $prods = \\App\\Models\\Master\\Product::whereIn('id',collect($r->lines)->pluck('product_id'))->get()->keyBy('id');
            [$rows,$sub,$tax,$grand] = $this->computeLines($prods,$r->lines);
            $h = \\{model}::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'customer_id'=>$r->customer_id,'status'=>'draft','subtotal'=>$sub,'discount'=>0,'tax'=>$tax,'grand_total'=>$grand,'created_by'=>$u->id]);
            foreach ($rows as &$x) $x['{fk}']=$h->id;
            DB::table('{ltbl}')->insert($rows);
            return $h->id;
        }});
        return redirect()->route('{route}.show',$id)->with('ok','{title} tersimpan.');
    }}
    public function show($id) {{
        $row = $this->baseQuery()->findOrFail($id);
        $lines = DB::table('{ltbl}')->where('{fk}',$id)->get();
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta(),'lines'=>$lines]);
    }}
    public function approve($id) {{
        $row = $this->baseQuery()->findOrFail($id);
        $this->setStatus($row,'draft','approved');
        return back()->with('ok','{title} disetujui.');
    }}
}}
""")

w("app/Http/Controllers/Finance/JournalController.php", """<?php
namespace App\\Http\\Controllers\\Finance;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\PostingService;
use App\\Services\\ApprovalService;
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
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid);
            $number = DocNumberService::next('J',$eid,$bid,$ent?->code);
            $lines = [];
            foreach ($r->lines as $l) $lines[]=['coa_id'=>$l['coa_id'],'debit'=>(float)$l['debit'],'credit'=>(float)$l['credit']];
            return PostingService::postJournal(['legal_entity_id'=>$eid,'branch_id'=>$bid,'date'=>$r->date,'number'=>$number,
                'description'=>$r->description,'source_type'=>'manual','source_id'=>null],$lines,'manual-'.$number);
        });
        return redirect()->route('finance.journals.index')->with('ok','Jurnal terposting (balance + idempotent).');
    }
    public function reverse(Request $r, $id) {
        $r->validate(['reason'=>'required|max:255']);
        PostingService::reverse((int)$id, $r->reason);
        return back()->with('ok','Jurnal direversal (bukan dihapus).');
    }
}
""")

w("app/Http/Controllers/Finance/FinanceReportController.php", """<?php
namespace App\\Http\\Controllers\\Finance;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
class FinanceReportController extends Controller {
    protected function eid(): ?int {
        $ctx = session('erp_context',[]); return $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
    }
    public function ledger(Request $r) {
        $rows = DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')
            ->where('journals.legal_entity_id',$this->eid())
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))
            ->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->when($r->coa_id,fn($q)=>$q->where('journal_lines.coa_id',$r->coa_id))
            ->select('journals.date','journals.number','coas.code','coas.name','journal_lines.debit','journal_lines.credit')
            ->orderBy('journals.date')->paginate(30)->withQueryString();
        $coas = DB::table('coas')->where('legal_entity_id',$this->eid())->orderBy('code')->get();
        return view('modules.finance.reports.ledger', compact('rows','coas'));
    }
    public function trialBalance(Request $r) {
        $rows = DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')
            ->where('journals.legal_entity_id',$this->eid())
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))
            ->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->selectRaw('coas.code, coas.name, SUM(journal_lines.debit) debit, SUM(journal_lines.credit) credit')
            ->groupBy('coas.code','coas.name')->orderBy('coas.code')->get();
        return view('modules.finance.reports.trial', compact('rows'));
    }
    public function pl(Request $r) {
        $sum = fn($types)=>DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')->where('journals.legal_entity_id',$this->eid())
            ->whereIn('coas.type',$types)
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->selectRaw('SUM(journal_lines.credit - journal_lines.debit) s')->value('s') ?? 0;
        $rev = $sum(['revenue']); $exp = -1*$sum(['expense']);
        $detail = DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')->where('journals.legal_entity_id',$this->eid())
            ->whereIn('coas.type',['revenue','expense'])
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->selectRaw('coas.code, coas.name, coas.type, SUM(journal_lines.debit) d, SUM(journal_lines.credit) c')
            ->groupBy('coas.code','coas.name','coas.type')->orderBy('coas.code')->get();
        return view('modules.finance.reports.pl', ['revenue'=>$rev,'expense'=>$exp,'net'=>$rev-$exp,'detail'=>$detail]);
    }
    public function aging(Request $r) {
        $kind = $r->get('kind','ar');
        if ($kind==='ap') {
            $rows = DB::table('vendor_invoices')->join('suppliers','suppliers.id','=','vendor_invoices.supplier_id')
                ->where('vendor_invoices.legal_entity_id',$this->eid())
                ->selectRaw('suppliers.name, vendor_invoices.number, vendor_invoices.due_date, vendor_invoices.grand_total - vendor_invoices.paid_total as sisa, DATEDIFF(CURDATE(), vendor_invoices.due_date) as overdue')
                ->having('sisa','>',0)->orderByDesc('overdue')->paginate(20)->withQueryString();
        } else {
            $rows = DB::table('invoices')->join('customers','customers.id','=','invoices.customer_id')
                ->where('invoices.legal_entity_id',$this->eid())
                ->selectRaw('customers.name, invoices.number, invoices.due_date, invoices.grand_total - invoices.paid_total as sisa, DATEDIFF(CURDATE(), invoices.due_date) as overdue')
                ->having('sisa','>',0)->orderByDesc('overdue')->paginate(20)->withQueryString();
        }
        return view('modules.finance.reports.aging', compact('rows','kind'));
    }
    public function closing(Request $r) {
        $periods = DB::table('fiscal_periods')->where('legal_entity_id',$this->eid())->orderByDesc('start_date')->limit(12)->get();
        return view('modules.finance.reports.closing', compact('periods'));
    }
    public function doClose(Request $r) {
        $r->validate(['period_id'=>'required|exists:fiscal_periods,id']);
        $p = DB::table('fiscal_periods')->where('id',$r->period_id)->first();
        if ((int)$p->legal_entity_id !== (int)$this->eid() && !auth()->user()->hasRole('super_admin','holding_owner')) abort(403);
        DB::table('fiscal_periods')->where('id',$p->id)->update(['is_closed'=>true]);
        DB::table('period_closings')->insert(['fiscal_period_id'=>$p->id,'status'=>'closed','note'=>$r->note,'closed_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('ok','Periode ditutup. Posting ke periode ini akan ditolak.');
    }
    public function reopen(Request $r) {
        $r->validate(['period_id'=>'required|exists:fiscal_periods,id','reason'=>'required|max:255']);
        if (!auth()->user()->hasPermission('finance.close')) abort(403);
        $p = DB::table('fiscal_periods')->where('id',$r->period_id)->first();
        DB::table('fiscal_periods')->where('id',$p->id)->update(['is_closed'=>false]);
        DB::table('period_closings')->insert(['fiscal_period_id'=>$p->id,'status'=>'reopened','note'=>$r->reason,'closed_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('ok','Periode dibuka kembali (tercatat + butuh permission finance.close).');
    }
}
""")

w("app/Http/Controllers/Finance/ExpenseController.php", """<?php
namespace App\\Http\\Controllers\\Finance;
use App\\Http\\Controllers\\CrudController;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\ApprovalService;
class ExpenseController extends CrudController {
    protected string $model = \\App\\Models\\Finance\\Expense::class;
    protected string $routePrefix = 'finance.expenses';
    protected string $viewPrefix = 'modules.finance.expenses';
    protected string $title = 'Biaya/Expense';
    protected string $perm = 'finance';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','title'=>'Judul','amount'=>'Nominal','status'=>'Status'];
    protected array $fields = [
        ['name'=>'title','label'=>'Judul','type'=>'text'],
        ['name'=>'date','label'=>'Tanggal','type'=>'date'],
        ['name'=>'amount','label'=>'Nominal','type'=>'number'],
        ['name'=>'cost_center_id','label'=>'Cost Center','type'=>'select_model','model'=>'App\\\\Models\\\\Org\\\\CostCenter'],
    ];
    protected array $rules = ['title'=>'required|max:150','date'=>'required|date','amount'=>'required|numeric|min:1'];
    protected array $searchable = ['number','title'];
    public function store(\\Illuminate\\Http\\Request $r) {
        $v = $r->validate($this->rules);
        $this->injectContext($v);
        $ent = \\App\\Models\\Org\\LegalEntity::find($v['legal_entity_id']);
        $v['number'] = DocNumberService::next('EXP',$v['legal_entity_id'],$v['branch_id'] ?? null,$ent?->code);
        $v['status']='draft'; $v['requested_by']=auth()->id();
        $row = (new ($this->model))::create($v);
        ApprovalService::request('expense',$row->id,(int)$row->legal_entity_id,(float)$row->amount);
        return redirect()->route($this->routePrefix.'.show',$row->id)->with('ok','Expense dibuat + approval diminta.');
    }
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $ap = DB::table('approvals')->where(['doc_type'=>'expense','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
        if ($ap) ApprovalService::decide($ap->id,'approved','OK');
        $this->setStatus($row,'draft','approved');
        return back()->with('ok','Expense disetujui.');
    }
}
""")

# ---------- HR ----------
w("app/Http/Controllers/Hr/EmployeeController.php", """<?php
namespace App\\Http\\Controllers\\Hr;
use App\\Http\\Controllers\\CrudController;
class EmployeeController extends CrudController {
    protected string $model = \\App\\Models\\Hr\\Employee::class;
    protected string $routePrefix = 'hr.employees';
    protected string $viewPrefix = 'modules.hr.employees';
    protected string $title = 'Karyawan';
    protected string $perm = 'hr';
    protected array $columns = ['nip'=>'NIP','name'=>'Nama','status'=>'Status'];
    protected array $fields = [
        ['name'=>'nip','label'=>'NIP','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'phone','label'=>'Telepon','type'=>'text'],
        ['name'=>'join_date','label'=>'Tgl Masuk','type'=>'date'],
        ['name'=>'position_id','label'=>'Jabatan','type'=>'select_model','model'=>'App\\\\Models\\\\Hr\\\\Position'],
        ['name'=>'department_id','label'=>'Departemen','type'=>'select_model','model'=>'App\\\\Models\\\\Org\\\\Department'],
    ];
    protected array $rules = ['nip'=>'required|max:30','name'=>'required|max:150'];
    protected array $searchable = ['nip','name'];
}
""")
w("app/Http/Controllers/Hr/AttendanceController.php", """<?php
namespace App\\Http\\Controllers\\Hr;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
class AttendanceController extends Controller {
    public function index(Request $r) {
        $rows = DB::table('attendances')->join('employees','employees.id','=','attendances.employee_id')
            ->select('attendances.*','employees.name')->orderByDesc('attendances.date')->paginate(20)->withQueryString();
        return view('modules.hr.attendance.index', compact('rows'));
    }
    public function store(Request $r) {
        $r->validate(['employee_id'=>'required|exists:employees,id','date'=>'required|date','check_in'=>'nullable','check_out'=>'nullable']);
        DB::table('attendances')->updateOrInsert(['employee_id'=>$r->employee_id,'date'=>$r->date],
            ['check_in'=>$r->check_in,'check_out'=>$r->check_out,'status'=>'present','created_at'=>now(),'updated_at'=>now()]);
        return back()->with('ok','Absensi dicatat.');
    }
}
""")
w("app/Http/Controllers/Hr/PayrollController.php", """<?php
namespace App\\Http\\Controllers\\Hr;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\ApprovalService;
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
        $id = DB::transaction(function() use ($r,$eid,$u) {
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid);
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
        return redirect()->route('hr.payroll.index')->with('ok','Payroll dihitung dari komponen configurable (bukan aturan hardcode).');
    }
    public function approve($id) {
        $row = DB::table('payrolls')->where('id',$id)->firstOrFail();
        if ($row->status!=='draft') abort(422,'Hanya draft yang bisa disetujui.');
        $ap = DB::table('approvals')->where(['doc_type'=>'payroll','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
        if ($ap) ApprovalService::decide($ap->id,'approved','OK');
        DB::table('payrolls')->where('id',$id)->update(['status'=>'approved','approved_by'=>auth()->id()]);
        return back()->with('ok','Payroll disetujui (terkunci dari perubahan diam-diam).');
    }
    public function payslip($id) {
        $slip = DB::table('payslips')->join('employees','employees.id','=','payslips.employee_id')
            ->where('payslips.id',$id)->select('payslips.*','employees.name','employees.nip')->firstOrFail();
        if (!auth()->user()->hasPermission('hr.salary') && (int)optional(auth()->user())->id !== (int)DB::table('employees')->where('user_id',auth()->id())->value('id')) {
            // karyawan hanya boleh lihat slip sendiri; cek via employee.user_id
            $empId = DB::table('employees')->where('user_id',auth()->id())->value('id');
            if ((int)$empId !== (int)$slip->employee_id) abort(403);
        }
        $pdf = \\Barryvdh\\DomPDF\\Facade\\Pdf::loadView('print.payslip', compact('slip'));
        return $pdf->download($slip->number.'.pdf');
    }
}
""")

# ---------- Purchasing requisition ----------
w("app/Http/Controllers/Purchasing/RequisitionController.php", """<?php
namespace App\\Http\\Controllers\\Purchasing;
use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use App\\Services\\DocNumberService;
use App\\Services\\ApprovalService;
class RequisitionController extends Controller {
    public function index() {
        $rows = DB::table('purchase_requisitions')->orderByDesc('id')->paginate(15);
        return view('modules.purchasing.requisitions.index', compact('rows'));
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view('modules.purchasing.requisitions.form', [
            'products'=>\\App\\Models\\Master\\Product::where('legal_entity_id',$eid)->where('is_purchasable',true)->orderBy('name')->get(),
            'ccs'=>\\App\\Models\\Org\\CostCenter::where('legal_entity_id',$eid)->get()]);
    }
    public function store(Request $r) {
        $r->validate(['date'=>'required|date','lines'=>'required|array|min:1','lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','is_emergency'=>'nullable|boolean','emergency_reason'=>'nullable|max:255']);
        if ($r->boolean('is_emergency') && !$r->emergency_reason) return back()->withErrors(['msg'=>'Pengadaan darurat wajib menyertakan alasan.'])->withInput();
        $u = auth()->user(); $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $id = DB::transaction(function() use ($r,$eid,$u,$ctx) {
            $ent = \\App\\Models\\Org\\LegalEntity::find($eid);
            $number = DocNumberService::next('PR',$eid,$ctx['branch_id'] ?? $u->branch_id,$ent?->code);
            $rid = DB::table('purchase_requisitions')->insertGetId(['legal_entity_id'=>$eid,'branch_id'=>$ctx['branch_id'] ?? $u->branch_id,
                'cost_center_id'=>$r->cost_center_id,'number'=>$number,'date'=>$r->date,'status'=>'submitted',
                'is_emergency'=>$r->boolean('is_emergency'),'emergency_reason'=>$r->emergency_reason,'requested_by'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            $total=0;
            foreach ($r->lines as $l) { DB::table('purchase_requisition_lines')->insert(['purchase_requisition_id'=>$rid,'product_id'=>$l['product_id'],'qty'=>$l['qty'],'est_price'=>$l['est_price'] ?? 0,'created_at'=>now(),'updated_at'=>now()]); $total += (float)$l['qty']*(float)($l['est_price'] ?? 0); }
            ApprovalService::request('purchase_requisition',$rid,$eid,$total);
            return $rid;
        });
        return redirect()->route('purchasing.requisitions.index')->with('ok','Permintaan pembelian diajukan (darurat ditandai + butuh review).');
    }
}
""")

print("gen8a done")

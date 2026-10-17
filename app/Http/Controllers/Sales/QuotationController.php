<?php
namespace App\Http\Controllers\Sales;
use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Http\Controllers\Concerns\DocLines;
class QuotationController extends CrudController {
    use DocLines;
    protected string $model = \App\Models\Sales\Quotation::class;
    protected string $routePrefix = 'sales.quotes';
    protected string $viewPrefix = 'modules.sales.quotes';
    protected string $title = 'Quotation';
    protected string $perm = 'sales';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','grand_total'=>'Total','status'=>'Status'];
    protected array $fields = []; protected array $rules = []; protected array $searchable = ['number'];
    public function index(Request $r) {
        $rows = $this->baseQuery()->orderByDesc('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index',['rows'=>$rows,'meta'=>$this->meta()]);
    }
    public function create() {
        $r = request();
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $customers = \App\Models\Master\Customer::where('legal_entity_id',$eid)->orderBy('name')->get();
        $customer = $r->filled('customer_id') ? $customers->firstWhere('id',(int)$r->get('customer_id')) : $customers->first();
        return view($this->viewPrefix.'.form',['meta'=>$this->meta(),
            'customers'=>$customers,'customer'=>$customer,
            'products'=>\App\Services\PriceService::productOptions($eid, $customer?->tier)]);
    }
    public function store(Request $r) {
        $r->validate(['customer_id'=>'required|exists:customers,id','date'=>'required|date','valid_until'=>'nullable|date|after_or_equal:date','lines'=>'required|array|min:1',
            'lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \App\Models\Org\LegalEntity::find($eid); $br = \App\Models\Org\Branch::find($bid);
            $number = DocNumberService::next('QT',$eid,$bid,$ent?->code,$br?->code);
            $prods = \App\Models\Master\Product::whereIn('id',collect($r->lines)->pluck('product_id'))->get()->keyBy('id');
            [$rows,$sub,$tax,$grand] = $this->computeLines($prods,$r->lines);
            $h = \App\Models\Sales\Quotation::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'customer_id'=>$r->customer_id,'valid_until'=>$r->valid_until,'status'=>'draft','version'=>1,'subtotal'=>$sub,'discount'=>0,'tax'=>$tax,'grand_total'=>$grand,'created_by'=>$u->id]);
            foreach ($rows as &$x) $x['quotation_id']=$h->id;
            DB::table('quotation_lines')->insert($rows);
            return $h->id;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('sales.quotes.show',$id)->with('ok','Quotation tersimpan.');
    }
    public function show($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $lines = DB::table('quotation_lines')->where('quotation_id',$id)->get();
        $versions = DB::table('quotation_versions')->where('quotation_id',$id)->orderBy('version')->get();
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta(),'lines'=>$lines,'versions'=>$versions]);
    }
    // CRM-04: penawaran berversi + masa berlaku. Approve menolak penawaran kedaluwarsa
    // (valid_until lewat) dan mengarsipkan snapshot versi yang disetujui (rincian +
    // total + diskon + masa berlaku) agar perubahan pasca-approve tertelusur.
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        if ($row->valid_until && $row->valid_until < today()->toDateString()) {
            return back()->withErrors(['msg' => 'Penawaran kedaluwarsa (' . $row->valid_until . ') — buat revisi baru dengan masa berlaku yang sah.']);
        }
        try {
            DB::transaction(function () use ($row) {
                $lines = DB::table('quotation_lines')->where('quotation_id', $row->id)->get()->map(fn($l) => [
                    'product_id' => $l->product_id, 'snapshot_name' => $l->snapshot_name,
                    'qty' => $l->qty, 'price' => $l->price, 'discount' => $l->discount, 'line_total' => $l->line_total,
                ])->all();
                DB::table('quotation_versions')->insert([
                    'quotation_id' => $row->id, 'version' => $row->version ?? 1,
                    'snapshot' => json_encode(['lines' => $lines, 'subtotal' => $row->subtotal,
                        'discount' => $row->discount, 'tax' => $row->tax, 'grand_total' => $row->grand_total,
                        'valid_until' => $row->valid_until, 'approved_at' => now()->toDateTimeString()]),
                    'actor_id' => auth()->id(), 'reason' => 'approve v' . ($row->version ?? 1),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->setStatus($row, 'draft', 'approved');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok', 'Quotation disetujui (versi ' . ($row->version ?? 1) . ' diarsipkan).');
    }
}

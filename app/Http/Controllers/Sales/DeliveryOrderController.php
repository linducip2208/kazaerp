<?php
namespace App\Http\Controllers\Sales;
use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\AuditService;
use App\Http\Controllers\Concerns\DocLines;
class DeliveryOrderController extends CrudController {
    use DocLines;
    protected string $model = \App\Models\Sales\DeliveryOrder::class;
    protected string $routePrefix = 'sales.deliveries';
    protected string $viewPrefix = 'modules.sales.deliveries';
    protected string $title = 'Delivery Order';
    protected string $perm = 'sales';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','grand_total'=>'Total','status'=>'Status'];
    protected array $fields = []; protected array $rules = []; protected array $searchable = ['number'];
    public function index(Request $r) {
        $rows = $this->baseQuery()->orderByDesc('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index',['rows'=>$rows,'meta'=>$this->meta()]);
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix.'.form',['meta'=>$this->meta(),
            'customers'=>\App\Models\Master\Customer::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'products'=>\App\Models\Master\Product::where('legal_entity_id',$eid)->where('is_sellable',true)->orderBy('name')->get()]);
    }
    public function store(Request $r) {
        $r->validate(['customer_id'=>'required|exists:customers,id','date'=>'required|date','lines'=>'required|array|min:1',
            'lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \App\Models\Org\LegalEntity::find($eid); $br = \App\Models\Org\Branch::find($bid);
            $number = DocNumberService::next('DO',$eid,$bid,$ent?->code,$br?->code);
            $prods = \App\Models\Master\Product::whereIn('id',collect($r->lines)->pluck('product_id'))->get()->keyBy('id');
            [$rows,$sub,$tax,$grand] = $this->computeLines($prods,$r->lines);
            $h = \App\Models\Sales\DeliveryOrder::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'customer_id'=>$r->customer_id,'status'=>'draft','subtotal'=>$sub,'discount'=>0,'tax'=>$tax,'grand_total'=>$grand,'created_by'=>$u->id]);
            foreach ($rows as &$x) $x['delivery_order_id']=$h->id;
            DB::table('delivery_order_lines')->insert($rows);
            return $h->id;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('sales.deliveries.show',$id)->with('ok','Delivery Order tersimpan.');
    }
    public function show($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $lines = DB::table('delivery_order_lines')->where('delivery_order_id',$id)->get();
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta(),'lines'=>$lines]);
    }
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $this->setStatus($row,'draft','approved');
        AuditService::log('approve','delivery_orders',$row->id,[]);
        return back()->with('ok','Delivery Order disetujui.');
    }
}

<?php
namespace App\Http\Controllers\Purchasing;
use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\ApprovalService;
class PurchaseOrderController extends CrudController {
    protected string $model = \App\Models\Purchasing\PurchaseOrder::class;
    protected string $routePrefix = 'purchasing.orders';
    protected string $viewPrefix = 'modules.purchasing.orders';
    protected string $title = 'Purchase Order';
    protected string $perm = 'purchasing';
    protected array $columns = ['number'=>'Nomor','date'=>'Tanggal','grand_total'=>'Total','status'=>'Status'];
    protected array $fields = []; protected array $rules = []; protected array $searchable = ['number'];
    public function index(Request $r) {
        $rows = $this->baseQuery()->orderByDesc('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index',['rows'=>$rows,'meta'=>$this->meta()]);
    }
    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix.'.form',['meta'=>$this->meta(),
            'suppliers'=>\App\Models\Master\Supplier::where('legal_entity_id',$eid)->orderBy('name')->get(),
            'products'=>\App\Models\Master\Product::where('legal_entity_id',$eid)->where('is_purchasable',true)->orderBy('name')->get()]);
    }
    public function store(Request $r) {
        $r->validate(['supplier_id'=>'required|exists:suppliers,id','date'=>'required|date','lines'=>'required|array|min:1',
            'lines.*.product_id'=>'required|exists:products,id','lines.*.qty'=>'required|numeric|min:0.0001','lines.*.price'=>'required|numeric|min:0']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $ent = \App\Models\Org\LegalEntity::find($eid);
            $number = DocNumberService::next('PO',$eid,$bid,$ent?->code);
            $sub=0; $lns=[];
            foreach ($r->lines as $l) { $lt=(float)$l['qty']*(float)$l['price']; $sub+=$lt;
                $lns[]=['product_id'=>$l['product_id'],'qty'=>$l['qty'],'qty_received'=>0,'price'=>$l['price'],'line_total'=>$lt,'created_at'=>now(),'updated_at'=>now()]; }
            $tax=$sub*0.11; $grand=$sub+$tax;
            $po = \App\Models\Purchasing\PurchaseOrder::create(['legal_entity_id'=>$eid,'branch_id'=>$bid,'number'=>$number,'date'=>$r->date,
                'supplier_id'=>$r->supplier_id,'version'=>1,'status'=>'draft','subtotal'=>$sub,'tax'=>$tax,'grand_total'=>$grand,'created_by'=>$u->id]);
            foreach ($lns as &$ln) $ln['purchase_order_id']=$po->id;
            DB::table('purchase_order_lines')->insert($lns);
            ApprovalService::request('purchase_order',$po->id,$eid,$grand);
            return $po->id;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route('purchasing.orders.show',$id)->with('ok','PO dibuat + approval diminta.');
    }
    public function show($id) {
        $row = $this->baseQuery()->with(['lines.product'])->findOrFail($id);
        $invoices = DB::table('vendor_invoices')->where('purchase_order_id',$id)->get();
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta(),'invoices'=>$invoices]);
    }
    public function approve($id) {
        $row = $this->baseQuery()->findOrFail($id);
        try {
            $ap = DB::table('approvals')->where(['doc_type'=>'purchase_order','doc_id'=>$id,'status'=>'pending'])->orderByDesc('id')->first();
            if ($ap) ApprovalService::decide($ap->id,'approved','OK');
            $this->setStatus($row,'draft','approved');
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','PO disetujui.');
    }
}

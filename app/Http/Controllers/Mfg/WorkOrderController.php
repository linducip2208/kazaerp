<?php
namespace App\Http\Controllers\Mfg;
use App\Http\Controllers\CrudController;
use App\Services\AuditService;
use App\Services\DocNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class WorkOrderController extends CrudController {
    protected string $model = \App\Models\Mfg\WorkOrder::class;
    protected string $routePrefix = 'mfg.workorders';
    protected string $viewPrefix = 'modules.mfg.workorders';
    protected string $title = 'Work Order';
    protected string $perm = 'mfg';
    protected array $columns = [
        'number' => 'Nomor', 'status' => 'Status', 'qty_planned' => 'Qty Rencana',
        'qty_good' => 'Qty Jadi', 'qty_reject' => 'Reject', 'variance_amount' => 'Variance',
    ];
    protected array $fields = [];
    protected array $rules = [];
    protected array $searchable = ['number'];
    protected ?string $entityField = null;

    public function index(Request $r) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $q = DB::table('work_orders')
            ->join('boms','boms.id','=','work_orders.bom_id')
            ->join('products','products.id','=','boms.product_id')
            ->where('work_orders.legal_entity_id',$eid)
            ->select('work_orders.*','products.sku','products.name as product');
        if ($r->filled('status')) $q->where('work_orders.status',$r->status);
        if ($s = trim((string)$r->get('q',''))) $q->where(fn($w)=>$w->where('work_orders.number','like',"%$s%")->orWhere('products.name','like',"%$s%")->orWhere('products.sku','like',"%$s%"));
        $rows = $q->orderByDesc('work_orders.id')->paginate(15)->withQueryString();
        return view($this->viewPrefix.'.index', ['rows'=>$rows,'meta'=>$this->meta()]);
    }

    public function create() {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix.'.form', [
            'meta'=>$this->meta(),
            'boms'=>DB::table('boms')->join('products','products.id','=','boms.product_id')
                ->where('boms.legal_entity_id',$eid)->where('boms.status','!=','obsolete')
                ->select('boms.*','products.sku','products.name as product')->orderByDesc('boms.id')->get(),
            'warehouses'=>DB::table('warehouses')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $r) {
        $r->validate(['bom_id'=>'required|exists:boms,id','date'=>'required|date','qty_planned'=>'required|numeric|min:0.0001',
            'warehouse_id'=>'nullable|exists:warehouses,id']);
        $u = auth()->user(); $ctx = session('erp_context',[]);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id; $bid = $ctx['branch_id'] ?? $u->branch_id;
        try {
        $id = DB::transaction(function() use ($r,$eid,$bid,$u) {
            $bom = DB::table('boms')->where('id',$r->bom_id)->lockForUpdate()->firstOrFail();
            if ((int)$bom->legal_entity_id !== (int)$eid) throw new \RuntimeException('BOM beda entitas.');
            $lines = DB::table('bom_lines')->where('bom_id',$bom->id)->get();
            if ($lines->isEmpty()) throw new \RuntimeException('BOM belum memiliki baris material.');
            $ent = \App\Models\Org\LegalEntity::find($eid); $br = \App\Models\Org\Branch::find($bid);
            $number = DocNumberService::next('WO',$eid,$bid,$ent?->code,$br?->code);
            $woId = DB::table('work_orders')->insertGetId(['legal_entity_id'=>$eid,'number'=>$number,'date'=>$r->date,
                'bom_id'=>$bom->id,'bom_version_locked'=>$bom->version,'qty_planned'=>$r->qty_planned,
                'warehouse_id'=>$r->warehouse_id,'status'=>'released','created_at'=>now(),'updated_at'=>now()]);
            foreach ($lines as $l) {
                $std = (float) (DB::table('products')->where('id', $l->material_id)->value('cost_price') ?? 0);
                DB::table('work_order_lines')->insert(['work_order_id'=>$woId,'material_id'=>$l->material_id,
                    'qty_required'=>(float)$l->qty*(float)$r->qty_planned,'qty_issued'=>0,'unit_cost'=>$std,'issued_unit_cost'=>0,
                    'created_at'=>now(),'updated_at'=>now()]);
            }
            DB::table('doc_status_histories')->insert(['doc_type'=>'work_orders','doc_id'=>$woId,'from_status'=>null,'to_status'=>'released','actor_id'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);
            return $woId;
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()])->withInput();
        }
        AuditService::log('create','work_orders',(int)$id,[]);
        return redirect()->route('mfg.workorders.show',$id)->with('ok','Work order dibuat (BOM di-snapshot).');
    }

    public function show($id) {
        $wo = DB::table('work_orders')->where('work_orders.id',$id)
            ->join('boms','boms.id','=','work_orders.bom_id')
            ->join('products','products.id','=','boms.product_id')
            ->select('work_orders.*','boms.product_id','products.sku','products.name as product')
            ->firstOrFail();
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $u = auth()->user();
        if (!$u->hasRole('super_admin','holding_owner','executive','auditor') && (int)$wo->legal_entity_id !== (int)$eid) abort(403);
        $lines = DB::table('work_order_lines')->where('work_order_id',$id)
            ->join('products','products.id','=','work_order_lines.material_id')
            ->select('work_order_lines.*','products.sku','products.name as material','products.cost_price')
            ->orderBy('work_order_lines.id')->get();
        $history = DB::table('doc_status_histories')->where(['doc_type'=>'work_orders','doc_id'=>$id])->orderByDesc('id')->get();
        return view($this->viewPrefix.'.show', ['wo'=>$wo,'lines'=>$lines,'history'=>$history,'meta'=>$this->meta()]);
    }

    public function edit($id) { return redirect()->route('mfg.workorders.show',$id)->withErrors(['msg'=>'Work order diubah melalui aksi Issue Material / Produce, bukan edit bebas.']); }
    public function update(Request $r, $id) { return redirect()->route('mfg.workorders.show',$id)->withErrors(['msg'=>'Work order diubah melalui aksi Issue Material / Produce.']); }

    public function destroy($id) {
        $wo = DB::table('work_orders')->where('id',$id)->firstOrFail();
        $issued = (float) DB::table('work_order_lines')->where('work_order_id',$id)->sum('qty_issued');
        if ($wo->status !== 'released' || $issued > 0) {
            return back()->withErrors(['msg'=>'WO yang sudah diproses tidak dapat dihapus (gunakan status, bukan delete).']);
        }
        DB::table('work_orders')->where('id',$id)->delete();
        AuditService::log('delete','work_orders',(int)$id,[]);
        return redirect()->route('mfg.workorders.index')->with('ok','Work order dihapus.');
    }

    /** Keluarkan seluruh sisa material BOM dari gudang (sekali jalan, atomik). */
    public function issue($id) {
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $u = auth()->user();
        try {
        DB::transaction(function() use ($id,$eid,$u) {
            $wo = DB::table('work_orders')->where('id',$id)->lockForUpdate()->firstOrFail();
            if (!in_array($wo->status,['released','in_progress'],true)) throw new \RuntimeException('Status WO '.$wo->status.' tidak bisa issue material.');
            if (!$u->hasRole('super_admin','holding_owner','executive') && (int)$wo->legal_entity_id !== (int)$eid) abort(403);
            $wh = $wo->warehouse_id ?: (DB::table('warehouses')->where('branch_id',$ctx['branch_id'] ?? null)->value('id') ?: DB::table('warehouses')->value('id'));
            if (!$wh) throw new \RuntimeException('Gudang belum ditentukan.');
            $lines = DB::table('work_order_lines')->where('work_order_id',$wo->id)->orderBy('id')->get();
            $issued = false;
            foreach ($lines as $l) {
                $remain = (float)$l->qty_required - (float)$l->qty_issued;
                if ($remain <= 0.0001) continue;
                $cost = (float)(DB::table('products')->where('id',$l->material_id)->value('cost_price') ?? 0);
                StockService::move(['warehouse_id'=>$wh,'product_id'=>$l->material_id,'qty'=>-1*$remain,
                    'ref_type'=>'work_order_issue','ref_id'=>$wo->id,'unit_cost'=>$cost,'note'=>'WO '.$wo->number]);
                DB::table('work_order_lines')->where('id',$l->id)->update(['qty_issued'=>DB::raw('qty_issued + '.$remain),'issued_unit_cost'=>$cost,'updated_at'=>now()]);
                $issued = true;
            }
            if (!$issued) throw new \RuntimeException('Seluruh material sudah di-issue.');
            self::recalcCost($wo->id);
            DB::table('work_orders')->where('id',$wo->id)->update(['status'=>'in_progress','started_at'=>$wo->started_at ?: now(),'updated_at'=>now()]);
            DB::table('doc_status_histories')->insert(['doc_type'=>'work_orders','doc_id'=>$wo->id,'from_status'=>$wo->status,'to_status'=>'in_progress','actor_id'=>$u->id,'note'=>'Material issued','created_at'=>now(),'updated_at'=>now()]);
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()]);
        }
        return back()->with('ok','Material ter-issue ke produksi (stok berkurang, biaya aktual dihitung).');
    }

    /** Catat output produksi: barang jadi masuk stok + reject + variance. */
    public function produce(Request $r, $id) {
        $r->validate(['qty_good'=>'required|numeric|min:0.0001','qty_reject'=>'nullable|numeric|min:0','note'=>'nullable|string|max:300']);
        $ctx = session('erp_context',[]); $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $u = auth()->user();
        try {
        DB::transaction(function() use ($r,$id,$eid,$u) {
            $wo = DB::table('work_orders')->where('id',$id)->lockForUpdate()->firstOrFail();
            if (!in_array($wo->status,['in_progress'],true)) throw new \RuntimeException('Status WO '.$wo->status.' — issue material terlebih dahulu.');
            if (!$u->hasRole('super_admin','holding_owner','executive') && (int)$wo->legal_entity_id !== (int)$eid) abort(403);
            $good = (float)$r->qty_good; $reject = (float)($r->qty_reject ?? 0);
            $producedNow = $good + $reject;
            $already = (float)$wo->qty_good + (float)$wo->qty_reject;
            if ($already + $producedNow > (float)$wo->qty_planned + 0.0001) {
                throw new \RuntimeException('Total output melebihi qty rencana ('.$wo->qty_planned.').');
            }
            $bom = DB::table('boms')->where('id',$wo->bom_id)->firstOrFail();
            $wh = $wo->warehouse_id ?: (DB::table('warehouses')->where('branch_id',$ctx['branch_id'] ?? null)->value('id') ?: DB::table('warehouses')->value('id'));
            if (!$wh) throw new \RuntimeException('Gudang belum ditentukan.');
            $costs = DB::table('work_order_lines')->where('work_order_id',$wo->id)->selectRaw('SUM(qty_issued*issued_unit_cost) actual')->first();
            $totalGood = (float)$wo->qty_good + $good;
            $unitCost = $totalGood > 0 ? (float)$costs->actual / $totalGood : 0;
            StockService::move(['warehouse_id'=>$wh,'product_id'=>$bom->product_id,'qty'=>$good,
                'ref_type'=>'work_order_output','ref_id'=>$wo->id,'unit_cost'=>$unitCost,'note'=>'WO '.$wo->number.' good']);
            DB::table('work_orders')->where('id',$wo->id)->update([
                'qty_good'=>DB::raw('qty_good + '.$good),'qty_reject'=>DB::raw('qty_reject + '.$reject),
                'status'=>'completed','completed_at'=>now(),'updated_at'=>now(),
            ]);
            self::recalcCost($wo->id);
            DB::table('doc_status_histories')->insert(['doc_type'=>'work_orders','doc_id'=>$wo->id,'from_status'=>$wo->status,'to_status'=>'completed','actor_id'=>$u->id,
                'note'=>'Output: good '.$good.($reject>0 ? ', reject '.$reject : '').($r->note ? ' — '.$r->note : ''),'created_at'=>now(),'updated_at'=>now()]);
        });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg'=>$e->getMessage()]);
        }
        return back()->with('ok','Output dicatat: barang jadi masuk stok, variance dihitung.');
    }

    public static function recalcCost(int $woId): void {
        $c = DB::table('work_order_lines')->where('work_order_id',$woId)
            ->selectRaw('COALESCE(SUM(qty_required*unit_cost),0) standard, COALESCE(SUM(qty_issued*issued_unit_cost),0) actual')->first();
        DB::table('work_orders')->where('id',$woId)->update([
            'standard_cost'=>$c->standard,'actual_cost'=>$c->actual,
            'variance_amount'=>(float)$c->standard-(float)$c->actual,'updated_at'=>now(),
        ]);
    }
}

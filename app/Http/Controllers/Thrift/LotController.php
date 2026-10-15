<?php
namespace App\Http\Controllers\Thrift;
use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class LotController extends CrudController {
    protected string $model = \App\Models\Thrift\ThriftLot::class;
    protected string $routePrefix = 'thrift.lots';
    protected string $viewPrefix = 'modules.thrift.lots';
    protected string $title = 'Lot Thrift';
    protected string $perm = 'thrift';
    protected array $columns = [
        'number' => 'Nomor',
        'status' => 'Status'
    ];
    protected array $fields = [
        ['name'=>'status','label'=>'Status','type'=>'text']
    ];
    protected array $rules = [

    ];
    protected array $searchable = ['number'];
    protected ?string $entityField = null;
    /** Alokasi biaya lot ke item: rata atau proporsional harga. */
    public function allocate(Request $r, $id) {
        $r->validate(['method'=>'required|in:equal,price']);
        $lot = $this->baseQuery()->findOrFail($id);
        $items = DB::table('thrift_items')->where('thrift_lot_id',$id)->get();
        if ($items->isEmpty()) return back()->withErrors(['msg'=>'Lot belum memiliki item.']);
        DB::transaction(function() use ($lot,$items,$r) {
            $total = (float)$lot->acquisition_cost;
            if ($r->method==='equal') {
                $share = $total / $items->count();
                foreach ($items as $it) DB::table('thrift_items')->where('id',$it->id)->update(['cost_share'=>$share]);
            } else {
                $sumPrice = $items->sum(fn($x)=>(float)$x->price);
                if ($sumPrice <= 0) throw new \RuntimeException('Harga item nol semua; gunakan metode rata.');
                foreach ($items as $it) DB::table('thrift_items')->where('id',$it->id)->update(['cost_share'=>$total * (float)$it->price / $sumPrice]);
            }
        });
        return back()->with('ok','Biaya lot dialokasikan ('.$r->method.') ke '.$items->count().' item.');
    }
    public function show($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $items = DB::table('thrift_items')->where('thrift_lot_id',$id)->get();
        return view($this->viewPrefix.'.show',['row'=>$row,'meta'=>$this->meta(),'items'=>$items]);
    }
}

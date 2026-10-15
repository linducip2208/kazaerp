<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\StockService;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        $rows = DB::table('purchase_returns')->join('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
            ->select('purchase_returns.*', 'suppliers.name as supplier')
            ->orderByDesc('purchase_returns.id')->paginate(15);

        return view('modules.purchasing.returns.index', compact('rows'));
    }

    public function create()
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;

        return view('modules.purchasing.returns.form', [
            'suppliers' => \App\Models\Master\Supplier::where('legal_entity_id', $eid)->orderBy('name')->get(),
            'products' => \App\Models\Master\Product::where('legal_entity_id', $eid)->where('is_purchasable', true)->orderBy('name')->get(),
            'warehouses' => \App\Models\Org\Warehouse::orderBy('code')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $r->validate([
            'supplier_id' => 'required|exists:suppliers,id', 'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date', 'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id', 'lines.*.qty' => 'required|numeric|min:0.0001',
        ]);
        $u = auth()->user();
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        try {
            DB::transaction(function () use ($r, $eid, $u, $ctx) {
                $ent = \App\Models\Org\LegalEntity::find($eid);
                $number = DocNumberService::next('PRT', $eid, $ctx['branch_id'] ?? $u->branch_id, $ent?->code);
                $total = 0;
                foreach ($r->lines as $l) {
                    $total += (float) $l['qty'] * (float) ($l['price'] ?? 0);
                }
                $id = DB::table('purchase_returns')->insertGetId([
                    'legal_entity_id' => $eid, 'purchase_order_id' => $r->purchase_order_id,
                    'supplier_id' => $r->supplier_id, 'number' => $number, 'date' => $r->date,
                    'status' => 'posted', 'grand_total' => $total, 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($r->lines as $l) {
                    DB::table('purchase_return_lines')->insert([
                        'purchase_return_id' => $id, 'product_id' => $l['product_id'],
                        'qty' => $l['qty'], 'price' => $l['price'] ?? 0, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    // Barang keluar kembali ke supplier: kurangi stok (dengan guard oversell)
                    StockService::move(['warehouse_id' => $r->warehouse_id, 'product_id' => $l['product_id'],
                        'qty' => -1 * (float) $l['qty'], 'ref_type' => 'purchase_return', 'ref_id' => $id,
                        'note' => 'Retur beli '.$number]);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchasing.returns.index')->with('ok', 'Retur pembelian tercatat + stok berkurang.');
    }
}

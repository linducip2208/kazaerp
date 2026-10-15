<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    public function index(Request $r)
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $q = DB::table('batches')
            ->join('products', 'products.id', '=', 'batches.product_id')
            ->leftJoinSub(
                DB::table('stock_balances')->selectRaw('batch_id, SUM(qty_on_hand) qty_on_hand, SUM(qty_reserved) qty_reserved')->groupBy('batch_id'),
                'sb', 'sb.batch_id', '=', 'batches.id'
            )
            ->where('products.legal_entity_id', $eid)
            ->select('batches.*', 'products.sku', 'products.name as product',
                DB::raw('COALESCE(sb.qty_on_hand,0) as qty_on_hand'),
                DB::raw('COALESCE(sb.qty_reserved,0) as qty_reserved'));
        if ($r->filled('qc') && in_array($r->qc, ['pending', 'released', 'quarantine', 'rejected'], true)) {
            $q->where('batches.qc_status', $r->qc);
        }
        if ($s = trim((string) $r->get('q', ''))) {
            $q->where(fn ($w) => $w->where('batches.lot_no', 'like', "%$s%")->orWhere('products.name', 'like', "%$s%")->orWhere('products.sku', 'like', "%$s%"));
        }
        $rows = $q->orderByDesc('batches.id')->paginate(20)->withQueryString();
        $counts = DB::table('batches')->join('products', 'products.id', '=', 'batches.product_id')
            ->where('products.legal_entity_id', $eid)
            ->selectRaw('qc_status, COUNT(*) as n')->groupBy('qc_status')->pluck('n', 'qc_status');

        return view('modules.inventory.batches.index', compact('rows', 'counts'));
    }

    public function qc(Request $r, $id)
    {
        $r->validate([
            'qc_status' => 'required|in:pending,released,quarantine,rejected',
            'note' => 'nullable|string|max:500',
        ]);
        $u = auth()->user();
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        $b = DB::table('batches')->where('id', $id)->firstOrFail();
        $entityId = DB::table('products')->where('id', $b->product_id)->value('legal_entity_id');
        if (!$u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor') && (int) $entityId !== (int) $eid) {
            abort(403, 'Batch di luar cakupan entitas.');
        }
        $from = $b->qc_status;
        if ($from === $r->qc_status && !$r->filled('note')) {
            return back()->withErrors(['msg' => 'Status QC tidak berubah.']);
        }
        DB::transaction(function () use ($b, $r, $u) {
            DB::table('batches')->where('id', $b->id)->update(['qc_status' => $r->qc_status, 'updated_at' => now()]);
            DB::table('quality_checks')->insert([
                'ref_type' => 'batch', 'ref_id' => $b->id, 'result' => $r->qc_status,
                'note' => $r->note, 'checked_by' => $u->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        AuditService::log('qc', 'batches', (int) $b->id, ['from' => $from, 'to' => $r->qc_status, 'note' => $r->note]);

        return back()->with('ok', 'Batch ' . $b->lot_no . ': QC ' . $from . ' → ' . $r->qc_status . '.');
    }
}

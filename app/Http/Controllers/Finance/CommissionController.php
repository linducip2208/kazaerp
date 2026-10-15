<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function index(Request $r)
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $u = auth()->user();
        $q = DB::table('sales_commissions')
            ->join('users', 'users.id', '=', 'sales_commissions.user_id')
            ->leftJoin('invoices', 'invoices.id', '=', 'sales_commissions.invoice_id')
            ->where('sales_commissions.legal_entity_id', $eid)
            ->select('sales_commissions.*', 'users.name as sales_name', 'invoices.number as invoice_number', 'invoices.date as invoice_date');
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive', 'finance_controller', 'auditor')) {
            $q->where('sales_commissions.user_id', $u->id);
        }
        if ($r->filled('status') && in_array($r->status, ['pending', 'approved', 'cancelled'], true)) {
            $q->where('sales_commissions.status', $r->status);
        }
        $rows = $q->orderByDesc('sales_commissions.id')->paginate(20)->withQueryString();
        $totals = DB::table('sales_commissions')->where('legal_entity_id', $eid)
            ->selectRaw("SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) pending, SUM(CASE WHEN status='approved' THEN amount ELSE 0 END) approved")
            ->first();

        return view('modules.finance.commissions.index', compact('rows', 'totals'));
    }

    public function approve($id)
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $u = auth()->user();
        $c = DB::table('sales_commissions')->where('id', $id)->firstOrFail();
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive') && (int) $c->legal_entity_id !== (int) $eid) {
            abort(403, 'Komisi di luar cakupan entitas.');
        }
        if ($c->status !== 'pending') {
            return back()->withErrors(['msg' => 'Komisi berstatus ' . $c->status . ' tidak dapat di-approve.']);
        }
        DB::table('sales_commissions')->where('id', $id)
            ->update(['status' => 'approved', 'approved_by' => $u->id, 'approved_at' => now(), 'updated_at' => now()]);
        AuditService::log('approve', 'sales_commissions', (int) $id, ['amount' => $c->amount]);

        return back()->with('ok', 'Komisi disetujui.');
    }

    public function cancel($id)
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $u = auth()->user();
        $c = DB::table('sales_commissions')->where('id', $id)->firstOrFail();
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive') && (int) $c->legal_entity_id !== (int) $eid) {
            abort(403, 'Komisi di luar cakupan entitas.');
        }
        if ($c->status === 'approved') {
            return back()->withErrors(['msg' => 'Komisi yang sudah disetujui tidak dapat dibatalkan tanpa reversal.']);
        }
        DB::table('sales_commissions')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
        AuditService::log('cancel', 'sales_commissions', (int) $id, []);

        return back()->with('ok', 'Komisi dibatalkan.');
    }
}

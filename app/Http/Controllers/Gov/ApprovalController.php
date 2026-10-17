<?php

namespace App\Http\Controllers\Gov;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ApprovalService;
use App\Services\AuditService;

class ApprovalController extends Controller
{
    /** Hak approve: punya permission ATAU delegate aktif dari pemilik permission. */
    protected function canApprove(): bool
    {
        $u = auth()->user();
        if ($u->hasPermission('governance.approve')) {
            return true;
        }
        $today = now()->toDateString();
        $delegators = DB::table('approval_delegations')->where('delegate_id', $u->id)
            ->where('is_active', true)->where('from_date', '<=', $today)->where('to_date', '>=', $today)
            ->pluck('delegator_id');
        foreach ($delegators as $did) {
            $du = \App\Models\User::find($did);
            if ($du && $du->is_active && $du->hasPermission('governance.approve')) {
                return true;
            }
        }

        return false;
    }

    public function index(Request $r)
    {
        $rows = DB::table('approvals')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('modules.gov.approvals.index', compact('rows'));
    }

    public function decide(Request $r, $id)
    {
        if (! $this->canApprove()) {
            abort(403, 'Butuh hak approval atau delegasi aktif.');
        }
        $r->validate(['decision' => 'required|in:approved,rejected', 'note' => 'nullable|max:500']);
        try {
            ApprovalService::decide((int) $id, $r->decision, $r->note);
            AuditService::log($r->decision === 'approved' ? 'approve' : 'reject', 'approvals', (int) $id, ['note' => $r->note]);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }

        return back()->with('ok', 'Keputusan approval dicatat.');
    }
}

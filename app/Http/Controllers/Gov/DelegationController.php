<?php

namespace App\Http\Controllers\Gov;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DelegationController extends Controller
{
    public function index()
    {
        $me = auth()->user();
        $q = DB::table('approval_delegations')
            ->join('users as a', 'a.id', '=', 'approval_delegations.delegator_id')
            ->join('users as b', 'b.id', '=', 'approval_delegations.delegate_id')
            ->select('approval_delegations.*', 'a.name as delegator', 'b.name as delegate')
            ->orderByDesc('approval_delegations.id');
        if (! $me->hasPermission('roles.assign')) {
            $q->where(function ($w) use ($me) {
                $w->where('approval_delegations.delegator_id', $me->id)
                    ->orWhere('approval_delegations.delegate_id', $me->id);
            });
        }
        $rows = $q->paginate(15);

        $users = $me->hasPermission('roles.assign')
            ? \App\Models\User::where('is_active', true)->orderBy('name')->get()
            : \App\Models\User::where('is_active', true)->where('id', $me->id)->orderBy('name')->get();

        return view('modules.gov.delegations.index', compact('rows', 'users'));
    }

    public function store(Request $r)
    {
        $r->validate([
            'delegator_id' => 'required|exists:users,id', 'delegate_id' => 'required|exists:users,id|different:delegator_id',
            'doc_type' => 'nullable|max:40', 'from_date' => 'required|date', 'to_date' => 'required|date|after_or_equal:from_date',
        ]);
        // Hanya delegator sendiri atau pemegang roles.assign yang boleh mendelegasikan
        if ((int) $r->delegator_id !== (int) auth()->id() && ! auth()->user()->hasPermission('roles.assign')) {
            abort(403);
        }
        DB::table('approval_delegations')->insert([
            'delegator_id' => $r->delegator_id, 'delegate_id' => $r->delegate_id, 'doc_type' => $r->doc_type,
            'from_date' => $r->from_date, 'to_date' => $r->to_date, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \App\Services\AuditService::log('delegate', 'approval_delegations', 0, ['delegator' => $r->delegator_id, 'delegate' => $r->delegate_id]);

        return back()->with('ok', 'Delegasi persetujuan tercatat (berbatas tanggal).');
    }

    public function toggle($id)
    {
        $d = DB::table('approval_delegations')->where('id', $id)->firstOrFail();
        if ((int) $d->delegator_id !== (int) auth()->id() && ! auth()->user()->hasPermission('roles.assign')) {
            abort(403);
        }
        DB::table('approval_delegations')->where('id', $id)->update(['is_active' => ! $d->is_active]);

        return back()->with('ok', 'Status delegasi diperbarui.');
    }
}

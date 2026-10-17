<?php
namespace App\Http\Controllers\Crm;

use App\Http\Controllers\CrudController;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// CRM-03: jadwal follow-up + pengingat + hasil + eskalasi lead terlantar.
// Alur: open -> done (selesai) ; open + lewat tempo -> overdue (flag baca) -> escalate (alihkan + catat).
class FollowupController extends CrudController {
    protected string $model = \App\Models\Crm\Followup::class;
    protected string $routePrefix = 'crm.followups';
    protected string $viewPrefix = 'modules.crm.followups';
    protected string $title = 'Follow-up';
    protected string $perm = 'crm';
    protected array $columns = ['id' => 'ID', 'owner_type' => 'Tipe', 'due_at' => 'Jatuh Tempo', 'status' => 'Status'];
    protected array $fields = [];
    protected array $rules = [];
    protected array $searchable = ['note'];
    protected ?string $branchField = null;

    public function index(Request $r) {
        $q = $this->baseQuery();
        if ($r->get('filter') === 'overdue') {
            $q->where('status', 'open')->whereNotNull('due_at')->where('due_at', '<', now());
        } elseif ($r->get('filter') === 'today') {
            $q->where('status', 'open')->whereDate('due_at', today());
        }
        $rows = $q->orderByRaw("CASE WHEN status='open' AND due_at IS NOT NULL AND due_at < NOW() THEN 0 ELSE 1 END")
            ->orderBy('due_at')->paginate(15)->withQueryString();
        return view($this->viewPrefix . '.index', ['rows' => $rows, 'meta' => $this->meta(), 'filter' => $r->get('filter')]);
    }

    public function create() {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix . '.form', ['meta' => $this->meta(),
            'leads' => \App\Models\Master\Lead::where('legal_entity_id', $eid)->whereNotIn('stage', ['converted', 'lost'])->orderBy('name')->limit(300)->get(),
            'customers' => \App\Models\Master\Customer::where('legal_entity_id', $eid)->where('is_active', true)->orderBy('name')->limit(300)->get(),
            'users' => \App\Models\User::where('is_active', true)->where(function ($w) use ($eid) {
                $w->where('legal_entity_id', $eid)->orWhereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'holding_owner', 'executive', 'auditor']));
            })->orderBy('name')->limit(300)->get()]);
    }

    public function store(Request $r) {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $r->validate([
            'lead_id' => 'nullable|integer|min:1',
            'customer_id' => 'nullable|integer|min:1',
            'assigned_to' => 'nullable|exists:users,id',
            'due_at' => 'required|date',
            'note' => 'required|string|max:2000',
        ]);
        if (empty($r->lead_id) && empty($r->customer_id)) {
            return back()->withErrors(['msg' => 'Pilih lead atau pelanggan.'])->withInput();
        }
        if (! empty($r->lead_id)) {
            $ownerType = \App\Models\Master\Lead::class; $ownerId = (int) $r->lead_id;
        } else {
            $ownerType = \App\Models\Master\Customer::class; $ownerId = (int) $r->customer_id;
        }
        $owner = $ownerType::where('id', $ownerId)->where('legal_entity_id', $eid)->first();
        if (! $owner) return back()->withErrors(['msg' => 'Data induk tidak ditemukan dalam entitas aktif.'])->withInput();
        if ($r->assigned_to && ! \App\Models\User::where('id', (int) $r->assigned_to)->where('is_active', true)->exists()) {
            return back()->withErrors(['msg' => 'Penerima tugas tidak valid.'])->withInput();
        }
        try {
            $id = DB::transaction(function () use ($r, $eid, $ownerType, $owner) {
                $row = \App\Models\Crm\Followup::create([
                    'legal_entity_id' => $eid,
                    'owner_type' => $ownerType, 'owner_id' => $owner->id,
                    'assigned_to' => $r->assigned_to ?: auth()->id(),
                    'created_by' => auth()->id(),
                    'due_at' => $r->due_at, 'status' => 'open', 'note' => $r->note,
                ]);
                AuditService::log('create', 'followups', $row->id, ['owner' => $ownerType . '#' . $owner->id]);
                return $row->id;
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route($this->routePrefix . '.show', $id)->with('ok', 'Follow-up terjadwal.');
    }

    public function show($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $owner = $row->owner_type && class_exists($row->owner_type)
            ? $row->owner_type::find($row->owner_id) : null;
        $history = DB::table('doc_status_histories')->where('doc_type', 'followups')->where('doc_id', $row->id)->orderByDesc('id')->get();
        return view($this->viewPrefix . '.show', ['row' => $row, 'meta' => $this->meta(), 'owner' => $owner, 'history' => $history]);
    }

    public function update(Request $r, $id) {
        $row = $this->baseQuery()->findOrFail($id);
        if ($row->status !== 'open') return back()->withErrors(['msg' => 'Follow-up ' . $row->status . ' tidak dapat diubah.']);
        return back()->withErrors(['msg' => 'Gunakan Selesaikan / Eskalasi agar histori terjaga.']);
    }

    public function edit($id) {
        return redirect()->route($this->routePrefix . '.show', $id);
    }

    public function destroy($id) {
        return back()->withErrors(['msg' => 'Follow-up tidak dihapus agar histori terjaga. Selesaikan bila sudah selesai.']);
    }

    // Tandai selesai + catat hasil. Guard konkurensi: lock + status harus open (double-submit = error jelas, bukan ganda).
    public function complete(Request $r, $id) {
        $r->validate(['result' => 'required|string|max:2000']);
        $u = auth()->user();
        try {
            DB::transaction(function () use ($r, $id, $u) {
                $row = \App\Models\Crm\Followup::where('id', $id)->lockForUpdate()->firstOrFail();
                if (! $this->inScope($row)) abort(404);
                if ($row->status !== 'open') throw new \RuntimeException('Follow-up sudah ' . $row->status . '.');
                $row->update(['status' => 'done', 'done_at' => now(), 'note' => trim($row->note . "\n[Hasil] " . $r->result)]);
                DB::table('doc_status_histories')->insert(['doc_type' => 'followups', 'doc_id' => $row->id,
                    'from_status' => 'open', 'to_status' => 'done', 'actor_id' => $u->id,
                    'note' => mb_substr($r->result, 0, 190), 'created_at' => now(), 'updated_at' => now()]);
                AuditService::log('complete', 'followups', $row->id, ['result' => $r->result]);
            });
        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok', 'Follow-up selesai.');
    }

    // Eskalasi lead terlantar: alihkan ke atasan/owner lain + catat alasan. Tanpa ubah histori lama.
    public function escalate(Request $r, $id) {
        $r->validate(['assigned_to' => 'required|exists:users,id', 'reason' => 'required|string|max:1000']);
        $u = auth()->user();
        try {
            DB::transaction(function () use ($r, $id, $u) {
                $row = \App\Models\Crm\Followup::where('id', $id)->lockForUpdate()->firstOrFail();
                if (! $this->inScope($row)) abort(404);
                if ($row->status !== 'open') throw new \RuntimeException('Hanya follow-up open yang bisa dieskalasi.');
                $new = \App\Models\User::where('id', (int) $r->assigned_to)->where('is_active', true)->firstOrFail();
                $before = $row->assigned_to;
                $row->update(['assigned_to' => $new->id, 'note' => trim($row->note . "\n[Eskalasi] " . $r->reason)]);
                DB::table('doc_status_histories')->insert(['doc_type' => 'followups', 'doc_id' => $row->id,
                    'from_status' => 'open', 'to_status' => 'open', 'actor_id' => $u->id,
                    'note' => 'Eskalasi ' . $before . ' -> ' . $new->id . ': ' . mb_substr($r->reason, 0, 150),
                    'created_at' => now(), 'updated_at' => now()]);
                \App\Models\Notification::create(['user_id' => $new->id,
                    'title' => 'Eskalasi follow-up #' . $row->id, 'link' => route($this->routePrefix . '.show', $row->id)]);
                AuditService::log('escalate', 'followups', $row->id, ['from' => $before, 'to' => $new->id]);
            });
        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok', 'Follow-up dieskalasi.');
    }

    private function inScope($row): bool {
        $u = auth()->user();
        if ($u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor')) return true;
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        return (int) $row->legal_entity_id === (int) $eid;
    }
}

<?php
namespace App\Http\Controllers\Crm;

use App\Http\Controllers\CrudController;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// RET-01/02/05: tiket komplain tertaut pelanggan (+rujukan transaksi/hewan opsional).
// Alur: open -> in_review -> resolved ; open/in_review -> rejected (beralasan).
// Kontrol: penyelesai (resolved_by) wajib beda dari pelapor (created_by) — pemohon ≠ penyetuju.
class ComplaintController extends CrudController {
    protected string $model = \App\Models\Crm\Complaint::class;
    protected string $routePrefix = 'crm.complaints';
    protected string $viewPrefix = 'modules.crm.complaints';
    protected string $title = 'Komplain';
    protected string $perm = 'crm';
    protected array $columns = ['id' => 'Tiket', 'subject' => 'Subjek', 'urgency' => 'Urgensi', 'status' => 'Status'];
    protected array $fields = [];
    protected array $rules = [];
    protected array $searchable = ['subject', 'body'];
    protected ?string $branchField = null;

    public const URGENCIES = ['low' => 'Rendah', 'normal' => 'Normal', 'high' => 'Tinggi', 'critical' => 'Kritis'];
    public const DECISIONS = [
        'fixed' => 'Perbaikan', 'replaced' => 'Penggantian', 'credited' => 'Kredit pelanggan',
        'refunded' => 'Refund (via arus keuangan resmi)', 'rejected' => 'Penolakan beralasan',
    ];

    public static function trackingNo(int $id): string {
        return 'CMP-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public function index(Request $r) {
        $q = $this->baseQuery();
        if ($r->filled('urgency') && isset(self::URGENCIES[$r->urgency])) $q->where('urgency', $r->urgency);
        $rows = $q->orderByRaw("FIELD(status,'open','in_review','resolved','rejected'), FIELD(urgency,'critical','high','normal','low')")
            ->orderByDesc('id')->paginate(15)->withQueryString();
        return view($this->viewPrefix . '.index', ['rows' => $rows, 'meta' => $this->meta(),
            'urgencies' => self::URGENCIES, 'f_urgency' => $r->urgency]);
    }

    public function create() {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix . '.form', ['meta' => $this->meta(),
            'urgencies' => self::URGENCIES,
            'customers' => \App\Models\Master\Customer::where('legal_entity_id', $eid)->orderBy('name')->limit(300)->get(),
            'users' => \App\Models\User::where('is_active', true)->where(function ($w) use ($eid) {
                $w->where('legal_entity_id', $eid)->orWhereHas('roles', fn($qq) => $qq->whereIn('name', ['super_admin', 'holding_owner', 'executive']));
            })->orderBy('name')->limit(300)->get()]);
    }

    public function store(Request $r) {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $r->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:150',
            'body' => 'required|string|max:4000',
            'urgency' => 'required|in:low,normal,high,critical',
            'related_type' => 'nullable|string|max:80',
            'related_id' => 'nullable|integer|min:1',
            'assignee_id' => 'nullable|exists:users,id',
        ]);
        if (! \App\Models\Master\Customer::where('id', (int) $r->customer_id)->where('legal_entity_id', $eid)->exists()) {
            return back()->withErrors(['msg' => 'Pelanggan di luar entitas aktif.'])->withInput();
        }
        try {
            $id = DB::transaction(function () use ($r, $eid) {
                $row = \App\Models\Crm\Complaint::create([
                    'legal_entity_id' => $eid, 'customer_id' => $r->customer_id,
                    'subject' => $r->subject, 'body' => $r->body, 'status' => 'open',
                    'urgency' => $r->urgency, 'related_type' => $r->related_type,
                    'related_id' => $r->related_id, 'assignee_id' => $r->assignee_id,
                    'created_by' => auth()->id(),
                ]);
                AuditService::log('create', 'complaints', $row->id, ['urgency' => $r->urgency]);
                return $row->id;
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
        return redirect()->route($this->routePrefix . '.show', $id)
            ->with('ok', 'Tiket ' . self::trackingNo($id) . ' tercatat. Nomor pelacakan untuk pelanggan: ' . self::trackingNo($id) . '.');
    }

    public function show($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $history = DB::table('doc_status_histories')->where('doc_type', 'complaints')->where('doc_id', $row->id)->orderByDesc('id')->get();
        return view($this->viewPrefix . '.show', ['row' => $row, 'meta' => $this->meta(),
            'history' => $history, 'decisions' => self::DECISIONS,
            'tracking' => self::trackingNo($row->id)]);
    }

    public function edit($id) {
        $row = $this->baseQuery()->findOrFail($id);
        if (in_array($row->status, ['resolved', 'rejected'], true)) {
            return redirect()->route($this->routePrefix . '.show', $row->id);
        }
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        return view($this->viewPrefix . '.form', ['meta' => $this->meta(), 'row' => $row,
            'method' => 'PUT', 'action' => route($this->routePrefix . '.update', $row->id),
            'urgencies' => self::URGENCIES,
            'customers' => \App\Models\Master\Customer::where('legal_entity_id', $eid)->orderBy('name')->limit(300)->get(),
            'users' => \App\Models\User::where('is_active', true)->orderBy('name')->limit(300)->get()]);
    }

    public function destroy($id) {
        return back()->withErrors(['msg' => 'Tiket tidak dihapus agar histori terjaga. Tolak beralasan bila tidak valid.']);
    }

    public function update(Request $r, $id) {
        $row = $this->baseQuery()->findOrFail($id);
        if (in_array($row->status, ['resolved', 'rejected'], true)) {
            return back()->withErrors(['msg' => 'Tiket selesai/ditolak tidak dapat diubah.']);
        }
        $r->validate(['subject' => 'required|string|max:150', 'body' => 'required|string|max:4000',
            'urgency' => 'required|in:low,normal,high,critical', 'assignee_id' => 'nullable|exists:users,id',
            'related_type' => 'nullable|string|max:80', 'related_id' => 'nullable|integer|min:1']);
        $row->update($r->only(['subject', 'body', 'urgency', 'assignee_id', 'related_type', 'related_id']));
        AuditService::log('update', 'complaints', $row->id, []);
        return redirect()->route($this->routePrefix . '.show', $row->id)->with('ok', 'Tiket diperbarui.');
    }

    // Mulai penanganan: open -> in_review + tetapkan petugas. Insiden hewan (urgency critical) wajib assignee klinis/ops.
    public function review(Request $r, $id) {
        $r->validate(['assignee_id' => 'required|exists:users,id']);
        try {
            DB::transaction(function () use ($r, $id) {
                $row = \App\Models\Crm\Complaint::where('id', $id)->lockForUpdate()->firstOrFail();
                $this->mustScope($row);
                if ($row->status !== 'open') throw new \RuntimeException('Tiket bukan open.');
                $row->update(['assignee_id' => (int) $r->assignee_id]);
                $this->setStatus($row, 'open', 'in_review', 'Ditangani oleh user #' . $r->assignee_id);
                AuditService::log('review', 'complaints', $row->id, ['assignee' => (int) $r->assignee_id]);
            });
        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok', 'Tiket mulai ditangani.');
    }

    // Selesaikan: in_review -> resolved + keputusan + alasan. Penyelesai ≠ pelapor (SoD).
    public function resolve(Request $r, $id) {
        $r->validate(['decision' => 'required|in:fixed,replaced,credited,refunded,rejected', 'resolution' => 'required|string|max:4000']);
        $u = auth()->user();
        try {
            DB::transaction(function () use ($r, $id, $u) {
                $row = \App\Models\Crm\Complaint::where('id', $id)->lockForUpdate()->firstOrFail();
                $this->mustScope($row);
                if ($row->status !== 'in_review') throw new \RuntimeException('Tiket harus in_review.');
                if ((int) $row->created_by === (int) $u->id) {
                    throw new \RuntimeException('Pelapor tidak boleh menyelesaikan tiket sendiri (SoD). Minta petugas lain.');
                }
                $row->update(['decision' => $r->decision, 'resolution' => $r->resolution,
                    'resolved_by' => $u->id, 'resolved_at' => now()]);
                $this->setStatus($row, 'in_review', 'resolved', $r->decision . ': ' . mb_substr($r->resolution, 0, 150));
                AuditService::log('resolve', 'complaints', $row->id, ['decision' => $r->decision]);
            });
        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok', 'Tiket diselesaikan.');
    }

    public function reject(Request $r, $id) {
        $r->validate(['resolution' => 'required|string|max:2000']);
        $u = auth()->user();
        try {
            DB::transaction(function () use ($r, $id, $u) {
                $row = \App\Models\Crm\Complaint::where('id', $id)->lockForUpdate()->firstOrFail();
                $this->mustScope($row);
                if (! in_array($row->status, ['open', 'in_review'], true)) throw new \RuntimeException('Tiket sudah final.');
                if ((int) $row->created_by === (int) $u->id) {
                    throw new \RuntimeException('Pelapor tidak boleh menolak tiket sendiri (SoD). Minta petugas lain.');
                }
                $row->update(['decision' => 'rejected', 'resolution' => $r->resolution,
                    'resolved_by' => $u->id, 'resolved_at' => now()]);
                $this->setStatus($row, $row->status, 'rejected', mb_substr($r->resolution, 0, 150));
                AuditService::log('reject', 'complaints', $row->id, []);
            });
        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok', 'Tiket ditolak beralasan.');
    }

    private function mustScope($row): void {
        $u = auth()->user();
        if ($u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor')) return;
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
        if ((int) $row->legal_entity_id !== (int) $eid) abort(404);
    }
}

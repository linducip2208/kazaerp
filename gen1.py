"""Generator 1: config, database migrations, seeders, core support classes."""
import os
BASE = r"D:\project laravel\kazaerp"

def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

# ---------- config/kazaerp.php ----------
w("config/kazaerp.php", """<?php
return [
    'timezone' => 'Asia/Jakarta',
    'currency' => 'IDR',
    'locale' => 'id',
    // Kebijakan waktu: DB menyimpan UTC (TIMESTAMP Laravel), tampil Asia/Jakarta.
    'db_stores_utc' => true,
    'display_timezone' => 'Asia/Jakarta',
    // Metode HPP default: PENDING_BUSINESS_DECISION — Finance harus menyetujui.
    // Nilai 'none' = valuasi sederhana (stok x harga), bukan klaim FIFO/average penuh.
    'cogs_method' => env('KAZA_COGS_METHOD', 'none'), // none|fifo|average (butuh persetujuan Finance)
    'doc_number' => [
        'pattern' => '{ENTITY}/{BRANCH}/{TYPE}/{YYYY}/{MM}/{SEQ:5}',
        'reset' => 'monthly', // per entitas+cabang+tipe+periode
    ],
];
""")

# ---------- helpers ----------
w("app/Support/helpers.php", """<?php
use Carbon\\Carbon;
if (!function_exists('idr')) {
    function idr($v): string {
        return 'Rp ' . number_format((float) $v, 0, ',', '.');
    }
}
if (!function_exists('tgl_id')) {
    function tgl_id($dt, bool $withTime = false): string {
        if (!$dt) return '-';
        $c = $dt instanceof Carbon ? $dt : Carbon::parse($dt);
        $c = $c->setTimezone(config('kazaerp.display_timezone', 'Asia/Jakarta'));
        return $withTime ? $c->translatedFormat('d M Y H:i') : $c->translatedFormat('d M Y');
    }
}
if (!function_exists('active_context')) {
    function active_context(): array {
        return session('erp_context', []);
    }
}
""")

# ---------- middleware ----------
w("app/Http/Middleware/CheckPermission.php", """<?php
namespace App\\Http\\Middleware;
use Closure;
use Illuminate\\Http\\Request;
class CheckPermission {
    public function handle(Request $request, Closure $next, string $permission) {
        $u = $request->user();
        if (!$u) abort(401);
        if (!$u->hasPermission($permission)) abort(403, 'Akses ditolak: ' . $permission);
        return $next($request);
    }
}
""")
w("app/Http/Middleware/ActiveContext.php", """<?php
namespace App\\Http\\Middleware;
use Closure;
use Illuminate\\Http\\Request;
class ActiveContext {
    public function handle(Request $request, Closure $next) {
        $u = $request->user();
        if ($u && !session()->has('erp_context')) {
            session(['erp_context' => [
                'holding_id' => $u->holding_id,
                'legal_entity_id' => $u->legal_entity_id,
                'branch_id' => $u->branch_id,
            ]]);
        }
        return $next($request);
    }
}
""")

# ---------- services ----------
w("app/Services/DocNumberService.php", """<?php
namespace App\\Services;
use Illuminate\\Support\\Facades\\DB;
class DocNumberService {
    public static function next(string $type, ?int $entityId = null, ?int $branchId = null, ?string $entityCode = null, ?string $branchCode = null): string {
        return DB::transaction(function () use ($type, $entityId, $branchId, $entityCode, $branchCode) {
            $now = now(config('kazaerp.timezone', 'Asia/Jakarta'));
            $key = implode('|', [$type, $entityId ?? 0, $branchId ?? 0, $now->format('Y'), $now->format('m')]);
            $row = DB::table('doc_sequences')->where('seq_key', $key)->lockForUpdate()->first();
            $seq = $row ? ((int) $row->last_seq + 1) : 1;
            DB::table('doc_sequences')->updateOrInsert(['seq_key' => $key], [
                'doc_type' => $type, 'legal_entity_id' => $entityId, 'branch_id' => $branchId,
                'year' => $now->format('Y'), 'month' => $now->format('m'),
                'last_seq' => $seq, 'updated_at' => now(),
                'created_at' => $row->created_at ?? now(),
            ]);
            $e = $entityCode ?: 'E' . ($entityId ?? 0);
            $b = $branchCode ?: 'B' . ($branchId ?? 0);
            return sprintf('%s/%s/%s/%s/%s/%05d', $e, $b, $type, $now->format('Y'), $now->format('m'), $seq);
        });
    }
}
""")
w("app/Services/StockService.php", """<?php
namespace App\\Services;
use Illuminate\\Support\\Facades\\DB;
class StockService {
    public static function move(array $p): void {
        // $p: warehouse_id, product_id, variant_id?, batch_id?, qty (+masuk/-keluar), ref_type, ref_id, unit_cost?, note?
        DB::transaction(function () use ($p) {
            $wh = (int) $p['warehouse_id']; $prod = (int) $p['product_id'];
            $var = $p['variant_id'] ?? null; $batch = $p['batch_id'] ?? null;
            $qty = (float) $p['qty'];
            if ($qty < 0) { // locking untuk pengeluaran agar cegah oversell
                $bal = DB::table('stock_balances')->where([
                    'warehouse_id' => $wh, 'product_id' => $prod,
                    'variant_id' => $var, 'batch_id' => $batch,
                ])->lockForUpdate()->first();
                $avail = $bal ? ((float) $bal->qty_on_hand - (float) $bal->qty_reserved) : 0;
                if ($avail + $qty < -0.0001) {
                    throw new \\RuntimeException('Stok tidak mencukupi (tersedia: ' . $avail . ')');
                }
            }
            DB::table('stock_ledgers')->insert([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
                'qty' => $qty, 'unit_cost' => $p['unit_cost'] ?? 0, 'ref_type' => $p['ref_type'], 'ref_id' => $p['ref_id'],
                'note' => $p['note'] ?? null, 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $row = DB::table('stock_balances')->where([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
            ])->first();
            if ($row) {
                DB::table('stock_balances')->where('id', $row->id)->update([
                    'qty_on_hand' => (float) $row->qty_on_hand + $qty, 'updated_at' => now(),
                ]);
            } else {
                DB::table('stock_balances')->insert([
                    'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
                    'qty_on_hand' => $qty, 'qty_reserved' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }
    public static function reserve(int $wh, int $prod, float $qty, $var = null, $batch = null): void {
        DB::transaction(function () use ($wh, $prod, $qty, $var, $batch) {
            $row = DB::table('stock_balances')->where([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
            ])->lockForUpdate()->first();
            $avail = $row ? ((float) $row->qty_on_hand - (float) $row->qty_reserved) : 0;
            if ($avail < $qty) throw new \\RuntimeException('Stok tersedia tidak cukup untuk reservasi.');
            if ($row) DB::table('stock_balances')->where('id', $row->id)->update(['qty_reserved' => (float) $row->qty_reserved + $qty]);
            else DB::table('stock_balances')->insert(['warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch, 'qty_on_hand' => 0, 'qty_reserved' => $qty, 'created_at' => now(), 'updated_at' => now()]);
        });
    }
    public static function release(int $wh, int $prod, float $qty, $var = null, $batch = null): void {
        DB::transaction(function () use ($wh, $prod, $qty, $var, $batch) {
            $row = DB::table('stock_balances')->where([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
            ])->lockForUpdate()->first();
            if ($row) DB::table('stock_balances')->where('id', $row->id)->update(['qty_reserved' => max(0, (float) $row->qty_reserved - $qty)]);
        });
    }
}
""")
w("app/Services/PostingService.php", """<?php
namespace App\\Services;
use Illuminate\\Support\\Facades\\DB;
class PostingService {
    public static function postJournal(array $head, array $lines, string $idempotencyKey): int {
        return DB::transaction(function () use ($head, $lines, $idempotencyKey) {
            $d = array_sum(array_map(fn($l) => (float) $l['debit'], $lines));
            $c = array_sum(array_map(fn($l) => (float) $l['credit'], $lines));
            if (abs($d - $c) > 0.005) throw new \\RuntimeException('Jurnal tidak balance.');
            if (count($lines) < 2) throw new \\RuntimeException('Jurnal minimal 2 baris.');
            $ex = DB::table('journals')->where('idempotency_key', $idempotencyKey)->first();
            if ($ex) return (int) $ex->id; // idempotent
            self::assertPeriodOpen($head['legal_entity_id'], $head['date']);
            $jid = DB::table('journals')->insertGetId([
                'legal_entity_id' => $head['legal_entity_id'], 'branch_id' => $head['branch_id'] ?? null,
                'date' => $head['date'], 'number' => $head['number'], 'description' => $head['description'] ?? null,
                'source_type' => $head['source_type'] ?? null, 'source_id' => $head['source_id'] ?? null,
                'total_debit' => $d, 'total_credit' => $c, 'status' => 'posted',
                'idempotency_key' => $idempotencyKey, 'created_by' => auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($lines as $l) {
                DB::table('journal_lines')->insert([
                    'journal_id' => $jid, 'coa_id' => $l['coa_id'], 'debit' => $l['debit'], 'credit' => $l['credit'],
                    'cost_center_id' => $l['cost_center_id'] ?? null, 'note' => $l['note'] ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return (int) $jid;
        });
    }
    public static function assertPeriodOpen(int $entityId, $date): void {
        $d = $date instanceof \\DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
        $p = DB::table('fiscal_periods')->where('legal_entity_id', $entityId)
            ->where('start_date', '<=', $d)->where('end_date', '>=', $d)->first();
        if ($p && (int) $p->is_closed === 1) throw new \\RuntimeException('Periode akuntansi sudah ditutup.');
    }
    public static function reverse(int $journalId, string $reason): int {
        return DB::transaction(function () use ($journalId, $reason) {
            $j = DB::table('journals')->where('id', $journalId)->lockForUpdate()->first();
            if (!$j) throw new \\RuntimeException('Jurnal tidak ditemukan.');
            if ($j->status === 'reversed') throw new \\RuntimeException('Jurnal sudah direversal.');
            $lines = DB::table('journal_lines')->where('journal_id', $journalId)->get();
            $newLines = [];
            foreach ($lines as $l) $newLines[] = ['coa_id' => $l->coa_id, 'debit' => $l->credit, 'credit' => $l->debit, 'note' => 'Reversal #' . $j->number];
            $id = self::postJournal([
                'legal_entity_id' => $j->legal_entity_id, 'branch_id' => $j->branch_id, 'date' => now()->toDateString(),
                'number' => 'RV-' . $j->number, 'description' => 'Reversal: ' . $reason,
                'source_type' => 'journal', 'source_id' => $journalId,
            ], $newLines, 'reverse-' . $journalId . '-' . substr(md5($reason), 0, 8));
            DB::table('journals')->where('id', $journalId)->update(['status' => 'reversed']);
            return $id;
        });
    }
}
""")
w("app/Services/ApprovalService.php", """<?php
namespace App\\Services;
use Illuminate\\Support\\Facades\\DB;
class ApprovalService {
    public static function request(string $docType, int $docId, int $entityId, float $amount = 0): int {
        $matrix = DB::table('approval_matrices')->where('doc_type', $docType)
            ->where('legal_entity_id', $entityId)->orderBy('level')->get();
        if ($matrix->isEmpty()) {
            $matrix = DB::table('approval_matrices')->where('doc_type', $docType)->whereNull('legal_entity_id')->orderBy('level')->get();
        }
        $level = 1;
        foreach ($matrix as $m) {
            if ($amount >= (float) $m->min_amount) $level = (int) $m->level;
        }
        return DB::table('approvals')->insertGetId([
            'doc_type' => $docType, 'doc_id' => $docId, 'level' => $level, 'status' => 'pending',
            'requested_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    public static function decide(int $approvalId, string $decision, ?string $note = null): void {
        DB::transaction(function () use ($approvalId, $decision, $note) {
            $a = DB::table('approvals')->where('id', $approvalId)->lockForUpdate()->first();
            if (!$a || $a->status !== 'pending') throw new \\RuntimeException('Approval tidak dalam status pending.');
            if ((int) $a->requested_by === (int) auth()->id()) {
                $sod = DB::table('approval_matrices')->where('doc_type', $a->doc_type)->where('level', $a->level)->value('allow_self_approve');
                if (!$sod) throw new \\RuntimeException('Pemohon tidak boleh menyetujui dokumen sendiri (segregation of duties).');
            }
            DB::table('approvals')->where('id', $approvalId)->update([
                'status' => $decision, 'decided_by' => auth()->id(), 'note' => $note, 'updated_at' => now(),
            ]);
            DB::table('approval_histories')->insert([
                'approval_id' => $approvalId, 'action' => $decision, 'actor_id' => auth()->id(),
                'note' => $note, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }
}
""")
w("app/Services/AuditService.php", """<?php
namespace App\\Services;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Request;
class AuditService {
    public static function log(string $action, string $entity, int $recordId, array $changes = []): void {
        try {
            DB::table('audit_logs')->insert([
                'actor_id' => auth()->id(), 'action' => $action, 'entity' => $entity, 'record_id' => $recordId,
                'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE), 'ip' => Request::ip(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\\Throwable $e) { /* audit tidak boleh menggagalkan transaksi utama */ }
    }
}
""")

print("part1-static done")

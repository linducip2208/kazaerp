<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class PostingService {
    public static function postJournal(array $head, array $lines, string $idempotencyKey): int {
        return DB::transaction(function () use ($head, $lines, $idempotencyKey) {
            $d = array_sum(array_map(fn($l) => (float) $l['debit'], $lines));
            $c = array_sum(array_map(fn($l) => (float) $l['credit'], $lines));
            if (abs($d - $c) > 0.005) throw new \RuntimeException('Jurnal tidak balance.');
            if (count($lines) < 2) throw new \RuntimeException('Jurnal minimal 2 baris.');
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
        $d = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
        $p = DB::table('fiscal_periods')->where('legal_entity_id', $entityId)
            ->where('start_date', '<=', $d)->where('end_date', '>=', $d)->first();
        if ($p && (int) $p->is_closed === 1) throw new \RuntimeException('Periode akuntansi sudah ditutup.');
    }
    public static function reverse(int $journalId, string $reason): int {
        return DB::transaction(function () use ($journalId, $reason) {
            $j = DB::table('journals')->where('id', $journalId)->lockForUpdate()->first();
            if (!$j) throw new \RuntimeException('Jurnal tidak ditemukan.');
            if ($j->status === 'reversed') throw new \RuntimeException('Jurnal sudah direversal.');
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

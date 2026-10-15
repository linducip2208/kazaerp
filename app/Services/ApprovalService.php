<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
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
            if (!$a || $a->status !== 'pending') throw new \RuntimeException('Approval tidak dalam status pending.');
            if ((int) $a->requested_by === (int) auth()->id()) {
                $sod = DB::table('approval_matrices')->where('doc_type', $a->doc_type)->where('level', $a->level)->value('allow_self_approve');
                if (!$sod) throw new \RuntimeException('Pemohon tidak boleh menyetujui dokumen sendiri (segregation of duties).');
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

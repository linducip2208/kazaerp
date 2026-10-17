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
        $id = DB::table('approvals')->insertGetId([
            'doc_type' => $docType, 'doc_id' => $docId, 'level' => $level, 'status' => 'pending',
            'requested_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        NotifyService::sendToPerm('governance.approve', 'Approval baru: '.$docType.' #'.$docId, '/gov/approvals');

        return $id;
    }
    /** Cek delegasi aktif: delegate boleh bertindak atas nama delegator. */
    public static function delegatedFrom(int $delegateId, int $delegatorId, ?string $docType = null): bool
    {
        $today = now()->toDateString();

        return DB::table('approval_delegations')
            ->where('delegate_id', $delegateId)->where('delegator_id', $delegatorId)
            ->where('is_active', true)->where('from_date', '<=', $today)->where('to_date', '>=', $today)
            ->where(function ($q) use ($docType) {
                $q->whereNull('doc_type');
                if ($docType) {
                    $q->orWhere('doc_type', $docType);
                }
            })->exists();
    }
    /**
     * Batalkan approval yang masih pending karena dokumen diubah setelah pengajuan.
     * Entri lama berstatus `superseded` + jejak histori, sehingga penyetuju tidak
     * bisa memutuskan konten kedaluwarsa. Kembalikan jumlah baris yang dibatalkan.
     */
    public static function invalidatePending(string $docType, int $docId, ?string $reason = null): int
    {
        $ids = DB::table('approvals')
            ->where(['doc_type' => $docType, 'doc_id' => $docId, 'status' => 'pending'])
            ->pluck('id');
        foreach ($ids as $aid) {
            DB::table('approvals')->where('id', $aid)->update([
                'status' => 'superseded', 'note' => $reason, 'updated_at' => now(),
            ]);
            DB::table('approval_histories')->insert([
                'approval_id' => $aid, 'action' => 'superseded', 'actor_id' => auth()->id(),
                'note' => $reason, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return count($ids);
    }
    public static function decide(int $approvalId, string $decision, ?string $note = null): void {        DB::transaction(function () use ($approvalId, $decision, $note) {
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
            if ($a->requested_by) {
                NotifyService::send((int) $a->requested_by, 'Approval '.$decision.': '.$a->doc_type.' #'.$a->doc_id, '/gov/approvals');
            }
        });
    }
}

<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
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

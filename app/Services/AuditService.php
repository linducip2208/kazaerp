<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
class AuditService {
    public static function log(string $action, string $entity, int $recordId, array $changes = []): void {
        try {
            DB::table('audit_logs')->insert([
                'actor_id' => auth()->id(), 'action' => $action, 'entity' => $entity, 'record_id' => $recordId,
                'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE), 'ip' => Request::ip(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) { /* audit tidak boleh menggagalkan transaksi utama */ }
    }
}

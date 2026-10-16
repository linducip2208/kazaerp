<?php

namespace App\Console\Commands;

use App\Jobs\SendBatchExpiryAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendExpiryAlerts extends Command
{
    protected $signature = 'kaza:expiry-alerts {--days=30 : Ambang hari sebelum kedaluwarsa} {--sync : Jalankan langsung tanpa queue}';

    protected $description = 'Dispatch peringatan batch kedaluwarsa/akan kedaluwarsa dengan stok tersisa (butuh queue worker; dev: --sync).';

    public function handle(): int
    {
        $cutoff = now()->addDays((int) $this->option('days'))->toDateString();
        $ids = DB::table('batches')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', $cutoff)
            ->orderBy('expired_at')
            ->limit(500)
            ->pluck('id');
        foreach ($ids as $id) {
            $job = new SendBatchExpiryAlert((int) $id, (int) $this->option('days'));
            $this->option('sync') ? dispatch_sync($job) : dispatch($job);
        }
        $this->info('Expiry alerts: '.$ids->count().' batch diproses'.($this->option('sync') ? ' (sync)' : ' (queued)'));

        return self::SUCCESS;
    }
}

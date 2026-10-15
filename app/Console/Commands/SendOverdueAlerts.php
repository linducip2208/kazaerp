<?php

namespace App\Console\Commands;

use App\Jobs\SendOverdueInvoiceAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendOverdueAlerts extends Command
{
    protected $signature = 'kaza:overdue-alerts {--days=0 : Ambang hari keterlambatan minimum} {--sync : Jalankan langsung tanpa queue}';

    protected $description = 'Dispatch job peringatan invoice jatuh tempo (butuh queue worker: php artisan queue:work).';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'))->toDateString();
        $ids = DB::table('invoices')
            ->where('status', 'posted')
            ->where('pay_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $cutoff)
            ->orderBy('id')
            ->limit(500)
            ->pluck('id');
        foreach ($ids as $id) {
            $job = new SendOverdueInvoiceAlert((int) $id);
            $this->option('sync') ? dispatch_sync($job) : dispatch($job);
        }
        $this->info('Overdue alerts: ' . $ids->count() . ' invoice diproses' . ($this->option('sync') ? ' (sync)' : ' (queued)'));

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Health-check operasional kecil (cron-friendly, exit 1 bila ada temuan):
 * koneksi DB, antrean macet/gagal, direktori storage writable,
 * dan peringatan APP_DEBUG aktif di production.
 */
class HealthCheck extends Command
{
    protected $signature = 'kaza:health {--queue-warn=100}';

    protected $description = 'Cek kesehatan: DB, antrean (jobs/failed_jobs), storage, APP_DEBUG.';

    public function handle(): int
    {
        $fail = 0;

        try {
            DB::select('SELECT 1');
            $this->info('DB: OK');
        } catch (\Throwable $e) {
            $this->error('DB: GAGAL ('.$e->getMessage().')');

            return 1;
        }

        foreach (['jobs' => 'Antrean tertunda', 'failed_jobs' => 'Antrean gagal'] as $t => $label) {
            try {
                $n = DB::table($t)->count();
            } catch (\Throwable) {
                $this->warn("{$label}: tabel {$t} tidak ada (lewati).");
                continue;
            }
            $limit = $t === 'jobs' ? (int) $this->option('queue-warn') : 0;
            if ($n > $limit) {
                $this->error("{$label}: {$n} baris di {$t}.");
                $fail = 1;
            } else {
                $this->info("{$label}: OK ({$n}).");
            }
        }

        foreach (['storage/app', 'storage/logs', 'storage/framework'] as $d) {
            if (! is_writable(base_path($d))) {
                $this->error("Storage tidak writable: {$d}.");
                $fail = 1;
            }
        }
        $this->info('Storage writable: OK');

        if (app()->environment('production') && config('app.debug')) {
            $this->error('APP_DEBUG=true di production (matikan).');
            $fail = 1;
        }

        $this->info($fail === 0 ? 'SEHAT' : 'ADA TEMUAN (lihat di atas).');

        return $fail;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pasang ulang data demo secara aman pada database KHUSUS demo/dev/test.
 *
 * - Menolak berjalan di production kecuali DEMO_ENABLED=true + --force.
 * - Idempotent: aman diulang, tidak menggandakan data (guard nomor demo).
 * - Tidak menyentuh database produksi.
 */
class DemoSeedCommand extends Command
{
    protected $signature = 'demo:seed {--force : Paksa jalan walau DEMO_ENABLED=true di production}';
    protected $description = 'Seed data demo end-to-end (idempotent; non-produksi kecuali diizinkan eksplisit).';

    public function handle(): int
    {
        if (app()->environment('production')) {
            if (config('kazaerp.demo_enabled') !== true || ! $this->option('force')) {
                $this->error('DITOLAK: demo:seed tidak boleh jalan di production tanpa DEMO_ENABLED=true + --force.');
                return self::FAILURE;
            }
        }
        $db = DB::connection()->getDatabaseName();
        $this->info("Menanam demo ke database [{$db}] ...");
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder']);
        $this->info('Demo siap. Login: admin@kazaka.local / lihat docs/DEMO_GUIDE.md');
        $this->warn('Kredensial demo HANYA untuk non-produksi.');

        return self::SUCCESS;
    }
}

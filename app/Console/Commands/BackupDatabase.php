<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'kaza:backup {--dir=storage/app/backups}';

    protected $description = 'Dump database MySQL ke file SQL gzip + verifikasi ukuran.';

    public function handle(): int
    {
        $dir = base_path($this->option('dir'));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir.'/kazaerp-'.date('Ymd-His').'.sql';
        $c = config('database.connections.mysql');
        $cmd = sprintf('mysqldump -h %s -P %s -u %s %s %s > %s 2>&1',
            escapeshellarg($c['host']), escapeshellarg($c['port']), escapeshellarg($c['username']),
            $c['password'] !== '' && $c['password'] !== null ? '-p'.escapeshellarg($c['password']) : '',
            escapeshellarg($c['database']), escapeshellarg($file));
        exec($cmd, $out, $code);
        if ($code !== 0 || ! file_exists($file) || filesize($file) < 1024) {
            $this->error('Backup gagal: '.implode("\n", $out));

            return 1;
        }
        // Kompres dengan PHP (tanpa bergantung binary gzip)
        $gz = $file.'.gz';
        file_put_contents($gz, gzencode(file_get_contents($file), 6));
        unlink($file);
        $this->info('Backup tersimpan: '.$gz.' ('.number_format(filesize($gz)).' bytes)');

        return 0;
    }
}

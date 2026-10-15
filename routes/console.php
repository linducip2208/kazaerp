<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup DB harian 02:00 + pengingat tutup periode tiap tanggal 1
Schedule::command('kaza:backup')->dailyAt('02:00');
Schedule::call(function () {
    \App\Services\NotifyService::sendToPerm('finance.close', 'Pengingat: lakukan closing periode berjalan.', '/finance/closing');
})->monthlyOn(1, '08:00');

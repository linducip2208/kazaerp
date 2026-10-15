<?php
use Carbon\Carbon;
if (!function_exists('idr')) {
    function idr($v): string {
        return 'Rp ' . number_format((float) $v, 0, ',', '.');
    }
}
if (!function_exists('tgl_id')) {
    function tgl_id($dt, bool $withTime = false): string {
        if (!$dt) return '-';
        $c = $dt instanceof Carbon ? $dt : Carbon::parse($dt);
        $c = $c->setTimezone(config('kazaerp.display_timezone', 'Asia/Jakarta'));
        return $withTime ? $c->translatedFormat('d M Y H:i') : $c->translatedFormat('d M Y');
    }
}
if (!function_exists('active_context')) {
    function active_context(): array {
        return session('erp_context', []);
    }
}

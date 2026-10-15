<?php

namespace App\Jobs;

use App\Services\NotifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Kirim peringatan invoice jatuh tempo ke pengguna ber-permission sales.view.
 * Idempotent dalam 24 jam (cek link notifikasi yang sama).
 */
class SendOverdueInvoiceAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $invoiceId) {}

    public function handle(): void
    {
        $inv = DB::table('invoices')->where('id', $this->invoiceId)->first();
        if (! $inv || $inv->pay_status === 'paid') {
            return;
        }
        $link = '/sales/invoices/' . $inv->id;
        $exists = DB::table('notifications')->where('link', $link)
            ->where('created_at', '>=', now()->subDay())->exists();
        if ($exists) {
            return;
        }
        $sisa = (float) $inv->grand_total - (float) $inv->paid_total;
        NotifyService::sendToPerm('sales.view',
            'Invoice ' . $inv->number . ' jatuh tempo ' . $inv->due_date . ' — sisa ' . number_format($sisa, 0, ',', '.'),
            $link);
    }
}

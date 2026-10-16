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
 * Peringatan batch kedaluwarsa / akan kedaluwarsa (hanya bila ada stok tersisa).
 * Idempotent 24 jam per batch (judul notifikasi unik memuat lot + SKU).
 */
class SendBatchExpiryAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $batchId, public int $days = 30) {}

    public function handle(): void
    {
        $b = DB::table('batches')->where('id', $this->batchId)->first();
        if (! $b || ! $b->expired_at) {
            return;
        }
        $sisa = (float) DB::table('stock_balances')->where('batch_id', $b->id)->sum('qty_on_hand');
        if ($sisa <= 0) {
            return;
        }
        $sku = DB::table('products')->where('id', $b->product_id)->value('sku') ?? ('#'.$b->product_id);
        $expired = substr((string) $b->expired_at, 0, 10);
        $lewat = $expired < now()->toDateString();
        $qty = rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',');
        $title = ($lewat ? 'Batch KEDALUWARSA ' : 'Batch akan kedaluwarsa ').$b->lot_no.' ('.$sku.') '.$expired.' — sisa stok '.$qty;

        $exists = DB::table('notifications')->where('title', $title)
            ->where('created_at', '>=', now()->subDay())->exists();
        if ($exists) {
            return;
        }
        NotifyService::sendToPerm('inventory.view', $title, '/inventory/batches');
    }
}

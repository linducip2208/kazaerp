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
 * CRM-03: pengingat follow-up ke penerima tugas (jatuh tempo <=24 jam / terlambat).
 * Idempotent 24 jam per follow-up (judul unik per tiket, dicek di command + job).
 */
class SendFollowupReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $followupId, public string $title, public bool $overdue = false) {}

    public function handle(): void
    {
        $f = DB::table('followups')->where('id', $this->followupId)->first();
        if (! $f || $f->status !== 'open' || ! $f->assigned_to) {
            return;
        }
        $exists = DB::table('notifications')->where('title', $this->title)
            ->where('created_at', '>=', now()->subDay())->exists();
        if ($exists) {
            return;
        }
        NotifyService::send((int) $f->assigned_to, $this->title, '/crm/followups/' . $f->id);
    }
}

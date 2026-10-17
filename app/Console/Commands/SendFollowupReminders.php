<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// CRM-03: pengingat follow-up — jatuh tempo <=24 jam atau sudah lewat, beri notifikasi ke penerima.
// Dedupe 24 jam per follow-up agar tidak spam (cek notifikasi sejenis yang belum dibaca).
class SendFollowupReminders extends Command
{
    protected $signature = 'kaza:followup-reminders {--sync : Jalankan langsung tanpa queue}';

    protected $description = 'Kirim pengingat follow-up CRM yang jatuh tempo / terlantar.';

    public function handle(): int
    {
        $due = now()->addDay();
        $rows = DB::table('followups')
            ->where('status', 'open')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', $due)
            ->whereNotNull('assigned_to')
            ->orderBy('due_at')
            ->limit(500)
            ->get(['id', 'assigned_to', 'due_at', 'owner_type', 'owner_id']);
        $n = 0;
        foreach ($rows as $f) {
            $overdue = strtotime((string) $f->due_at) < time();
            $title = ($overdue ? 'Follow-up TERLAMBAT #' : 'Pengingat follow-up #') . $f->id;
            $exists = DB::table('notifications')
                ->where('user_id', $f->assigned_to)
                ->where('title', $title)
                ->where('created_at', '>=', now()->subDay())
                ->exists();
            if ($exists) continue;
            $job = new \App\Jobs\SendFollowupReminder((int) $f->id, $title, (bool) $overdue);
            $this->option('sync') ? dispatch_sync($job) : dispatch($job);
            $n++;
        }
        $this->info('Follow-up reminders: ' . $n . ' diproses' . ($this->option('sync') ? ' (sync)' : ' (queued)'));
        return 0;
    }
}

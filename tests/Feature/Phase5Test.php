<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Jobs\SendOverdueInvoiceAlert;
use Tests\TestCase;

class Phase5Test extends TestCase
{
    public function test_auditor_dan_viewer_read_only_ditegakkan(): void
    {
        // auditor: boleh baca lintas modul, menulis diblokir, self-service tetap jalan
        $auditor = $this->makeUser('auditor');
        $this->actingAs($auditor)->withSession(['erp_context' => $this->ctx()]);
        $this->get('/finance/journals')->assertOk(); // punya finance.view
        $this->post('/sales/invoices', ['customer_id' => 1, 'date' => '2026-10-15', 'lines' => []])->assertForbidden();
        $this->put('/profile', ['name' => 'Auditor Baru'])->assertRedirect(); // whitelist self-service
        $this->assertSame('Auditor Baru', DB::table('users')->where('id', $auditor->id)->value('name'));

        // walau permission tulis diberikan ke role viewer, guard tetap menolak (defense-in-depth)
        $viewer = $this->makeUser('viewer');
        $rid = DB::table('roles')->where('name', 'viewer')->value('id');
        $pid = DB::table('permissions')->where('name', 'crm.create')->value('id');
        DB::table('permission_role')->updateOrInsert(['role_id' => $rid, 'permission_id' => $pid], []);
        $this->actingAs($viewer)->withSession(['erp_context' => $this->ctx()]);
        $this->get('/crm/customers')->assertOk();
        $this->post('/crm/customers', ['code' => 'RO-1', 'name' => 'Tidak Boleh'])->assertForbidden();
        $this->assertEquals(0, DB::table('customers')->where('code', 'RO-1')->count());

        // auditor + branch_manager (punya peran non-read-only) tetap boleh menulis
        $hybrid = $this->makeUser('auditor');
        DB::table('role_user')->insert(['role_id' => DB::table('roles')->where('name', 'branch_manager')->value('id'), 'user_id' => $hybrid->id]);
        $this->refreshApplication();
        $this->actingAs($hybrid->fresh())->withSession(['erp_context' => $this->ctx()]);
        $this->post('/crm/customers', ['code' => 'HY-1', 'name' => 'Hybrid Boleh'])->assertRedirect();
        $this->assertEquals(1, DB::table('customers')->where('code', 'HY-1')->count());
    }

    public function test_password_reset_mandiri_penuh(): void
    {
        $user = $this->makeUser('viewer');
        $user->forceFill(['email' => 'reset5@test.local'])->save();

        Notification::fake();
        // email tidak dikenal ditolak ramah
        $this->post('/forgot-password', ['email' => 'tidak-ada@test.local'])->assertSessionHasErrors();
        $this->post('/forgot-password', ['email' => 'reset5@test.local'])->assertRedirect();
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'reset5@test.local']);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->assertNotNull($token);

        // token salah ditolak
        $this->post('/reset-password', ['token' => 'token-salah', 'email' => 'reset5@test.local',
            'password' => 'baru123456', 'password_confirmation' => 'baru123456'])->assertSessionHasErrors();

        // token benar → password berubah
        $this->post('/reset-password', ['token' => $token, 'email' => 'reset5@test.local',
            'password' => 'baru123456', 'password_confirmation' => 'baru123456'])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('baru123456', $user->fresh()->password));
        $this->assertFalse(Hash::check('secret123', $user->fresh()->password));
    }

    public function test_backup_command_dump_terverifikasi(): void
    {
        exec('mysqldump --version 2>&1', $o, $code);
        if ($code !== 0) {
            $this->markTestSkipped('mysqldump tidak tersedia di PATH.');
        }
        $dir = 'storage/app/backups-test';
        $path = base_path($dir);
        foreach (glob($path.'/kazaerp-*.sql.gz') ?: [] as $f) {
            @unlink($f);
        }

        $this->artisan('kaza:backup', ['--dir' => $dir])->assertExitCode(0);

        $files = glob($path.'/kazaerp-*.sql.gz') ?: [];
        $this->assertNotEmpty($files, 'File backup tidak terbentuk.');
        $this->assertGreaterThan(1024, filesize($files[0]), 'Ukuran backup terlalu kecil (tidak valid).');
        // header gzip valid (magic bytes 1f 8b)
        $head = file_get_contents($files[0], false, null, 0, 2);
        $this->assertSame("\x1f\x8b", $head);

        // bersihkan artefak tes
        @unlink($files[0]);
        @rmdir($path);
    }

    public function test_queue_worker_memproses_job_jatuh_tempo(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        $inv = DB::table('invoices')->insertGetId(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid,
            'number' => 'INV-QW-1', 'date' => now()->subDays(10)->toDateString(), 'customer_id' => $cust->id,
            'due_date' => now()->subDays(3)->toDateString(), 'status' => 'posted', 'pay_status' => 'unpaid',
            'subtotal' => 50000, 'discount' => 0, 'tax' => 5500, 'grand_total' => 55500, 'paid_total' => 0,
            'created_at' => now(), 'updated_at' => now()]);

        // paksa antrean database (bukan sync) agar jalur worker nyata yang diuji
        config(['queue.default' => 'database']);
        SendOverdueInvoiceAlert::dispatch($inv);
        $this->assertGreaterThanOrEqual(1,
            DB::table('jobs')->where('payload', 'like', '%SendOverdueInvoiceAlert%')->count());

        $this->artisan('queue:work', ['--once' => true])->assertExitCode(0);

        $link = '/sales/invoices/'.$inv;
        $this->assertGreaterThanOrEqual(1, DB::table('notifications')->where('link', $link)->count());
        $this->assertEquals(0, DB::table('jobs')->where('payload', 'like', '%SendOverdueInvoiceAlert%')->count());
    }
}

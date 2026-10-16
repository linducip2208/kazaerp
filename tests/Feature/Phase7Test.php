<?php

namespace Tests\Feature;

use App\Jobs\SendBatchExpiryAlert;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Phase7Test extends TestCase
{
    public function test_admin_create_command(): void
    {
        $this->artisan('kaza:admin-create', ['--name' => 'Root Uji', '--email' => 'root7@test.local', '--password' => 'rahasia-kuat-123'])
            ->assertExitCode(0);
        $u = DB::table('users')->where('email', 'root7@test.local')->first();
        $this->assertNotNull($u);
        $this->assertTrue(Hash::check('rahasia-kuat-123', $u->password));
        $this->assertTrue(DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $u->id)->where('roles.name', 'super_admin')->exists());

        // idempoten per email: jalankan ulang → update, tidak duplikat
        $this->artisan('kaza:admin-create', ['--name' => 'Root Uji 2', '--email' => 'root7@test.local', '--password' => 'rahasia-baru-456'])
            ->assertExitCode(0);
        $this->assertEquals(1, DB::table('users')->where('email', 'root7@test.local')->count());
        $u2 = DB::table('users')->where('email', 'root7@test.local')->first();
        $this->assertEquals('Root Uji 2', $u2->name);
        $this->assertTrue(Hash::check('rahasia-baru-456', $u2->password));

        // validasi: password pendek & email salah ditolak tanpa membuat user
        $this->artisan('kaza:admin-create', ['--name' => 'X', '--email' => 'x7@test.local', '--password' => 'pendek'])
            ->expectsOutputToContain('minimal 12')->assertExitCode(1);
        $this->assertEquals(0, DB::table('users')->where('email', 'x7@test.local')->count());
        $this->artisan('kaza:admin-create', ['--name' => 'Y', '--email' => 'bukan-email', '--password' => 'rahasia-kuat-123'])
            ->assertExitCode(1);
    }

    public function test_expiry_alerts_dispatch_dedupe_dan_threshold(): void
    {
        $this->makeUser('super_admin');
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        $mk = function (string $lot, string $expiredAt) use ($prod) {
            return DB::table('batches')->insertGetId(['product_id' => $prod->id, 'lot_no' => $lot,
                'expired_at' => $expiredAt, 'qc_status' => 'released', 'created_at' => now(), 'updated_at' => now()]);
        };
        $move = function (int $batchId, float $qty) use ($prod) {
            StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'batch_id' => $batchId,
                'qty' => $qty, 'ref_type' => 'test', 'ref_id' => 1]);
        };
        $expired = $mk('LOT-EXP-1', now()->subDay()->toDateString());
        $move($expired, 5); // masuk gudang saat belum kedaluwarsa (guard hanya memblokir pengeluaran)
        $mk('LOT-EXP-2', now()->addDays(5)->toDateString()); // kedaluwarsa dekat tapi TANPA stok
        $drained = $mk('LOT-HABIS', now()->addDays(10)->toDateString());
        $move($drained, 2);
        $move($drained, -2); // stok habis
        $far = $mk('LOT-JAUH', now()->addDays(45)->toDateString());
        $move($far, 3);

        // 1) Jalankan seperti dev: --sync pada queue asli (SyncQueue) → notifikasi hanya untuk batch berstok
        $this->artisan('kaza:expiry-alerts', ['--sync' => true])->assertExitCode(0);
        $this->assertGreaterThanOrEqual(1, DB::table('notifications')->where('title', 'like', '%LOT-EXP-1%')->count());
        $this->assertEquals(0, DB::table('notifications')->where('title', 'like', '%LOT-EXP-2%')->count());
        $this->assertEquals(0, DB::table('notifications')->where('title', 'like', '%LOT-HABIS%')->count());
        $this->assertEquals(0, DB::table('notifications')->where('title', 'like', '%LOT-JAUH%')->count());

        // 2) idempoten 24 jam: run ulang tidak menambah notifikasi
        $before = DB::table('notifications')->count();
        $this->artisan('kaza:expiry-alerts', ['--sync' => true])->assertExitCode(0);
        $this->assertEquals($before, DB::table('notifications')->count());

        // 3) seleksi kandidat & threshold (dispatch ke queue; di bawah Queue::fake job direkam fake,
        //    jadi jalur worker diuji pada langkah --sync di atas)
        Queue::fake();
        $this->artisan('kaza:expiry-alerts')->assertExitCode(0);
        Queue::assertPushed(SendBatchExpiryAlert::class, 3); // 3 kandidat ≤ 30 hari
        Queue::assertNotPushed(SendBatchExpiryAlert::class, fn ($job) => $job->batchId === $far);

        $this->artisan('kaza:expiry-alerts', ['--days' => 60])->assertExitCode(0);
        Queue::assertPushed(SendBatchExpiryAlert::class, fn ($job) => $job->batchId === $far);
    }
}

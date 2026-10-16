<?php

namespace Tests\Feature;

use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase6Test extends TestCase
{
    public function test_pos_checkout_idempoten_client_ref(): void
    {
        $admin = $this->makeUser('super_admin');
        $admin->forceFill(['email' => 'api6a@test.local'])->save();
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'qty' => 10, 'ref_type' => 'test', 'ref_id' => 1]);

        $token = $this->postJson('/api/v1/login', ['email' => 'api6a@test.local', 'password' => 'secret123'])->json('token');
        $h = ['Authorization' => 'Bearer '.$token];
        $this->postJson('/api/v1/pos/shift/open', ['opening_cash' => 100000], $h)->assertStatus(201);

        $payload = ['lines' => [['product_id' => $prod->id, 'qty' => 2]], 'pay_method' => 'cash', 'client_ref' => 'MOB-REF-1'];
        $sale1 = $this->postJson('/api/v1/pos/checkout', $payload, $h)->assertStatus(201)->json('data');
        $this->assertFalse($sale1['replayed']);

        // retry dengan client_ref sama → 200, id & nomor sama, stok tidak dobel
        $sale2 = $this->postJson('/api/v1/pos/checkout', $payload, $h)->assertStatus(200)->json('data');
        $this->assertTrue($sale2['replayed']);
        $this->assertEquals($sale1['id'], $sale2['id']);
        $this->assertEquals($sale1['number'], $sale2['number']);
        $this->assertEquals(8.0, (float) DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'product_id' => $prod->id])->value('qty_on_hand'));
        $this->assertEquals(1, DB::table('pos_sales')->where('client_ref', 'MOB-REF-1')->count());

        // client_ref baru → penjualan baru
        $sale3 = $this->postJson('/api/v1/pos/checkout',
            ['lines' => [['product_id' => $prod->id, 'qty' => 1]], 'pay_method' => 'qris', 'client_ref' => 'MOB-REF-2'], $h)
            ->assertStatus(201)->json('data');
        $this->assertNotEquals($sale1['id'], $sale3['id']);
        $this->assertEquals(7.0, (float) DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'product_id' => $prod->id])->value('qty_on_hand'));

        // retry setelah shift ditutup tetap idempoten (tidak 422 "buka shift dulu")
        $this->postJson('/api/v1/pos/shift/close', ['closing_cash' => 100000], $h)->assertOk();
        $replay = $this->postJson('/api/v1/pos/checkout', $payload, $h)->assertStatus(200)->json('data');
        $this->assertEquals($sale1['id'], $replay['id']);
        $this->assertEquals(2, DB::table('pos_sales')->count());
    }

    public function test_audit_integrity_bersih_dan_terdeteksi(): void
    {
        $admin = $this->makeUser('super_admin');
        $prod = DB::table('products')->where('sku', 'T-001')->first();

        // kondisi bersih (setelah seed)
        $this->artisan('kaza:audit-integrity')->expectsOutputToContain('AUDIT OK')->assertExitCode(0);

        // data konsisten: +5 stok (balance & ledger sinkron) → tetap bersih
        StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'qty' => 5, 'ref_type' => 'test', 'ref_id' => 1]);
        $this->artisan('kaza:audit-integrity')->assertExitCode(0);

        // rusak 1: saldo stok digeser tanpa mutasi ledger
        DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'product_id' => $prod->id])->update(['qty_on_hand' => 8]);

        // rusak 2: jurnal tidak balance (debit 100, kredit 90)
        $coa = DB::table('coas')->where(['legal_entity_id' => $this->eid, 'code' => '1000'])->value('id');
        $jid = DB::table('journals')->insertGetId(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid,
            'date' => now()->toDateString(), 'number' => 'JN-RUSAK-1', 'description' => 'uji audit',
            'total_debit' => 100, 'total_credit' => 90, 'status' => 'posted', 'idempotency_key' => 'audit-rusak-1',
            'created_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('journal_lines')->insert([
            ['journal_id' => $jid, 'coa_id' => $coa, 'debit' => 100, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['journal_id' => $jid, 'coa_id' => $coa, 'debit' => 0, 'credit' => 90, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // rusak 3: invoice paid_total tanpa pembayaran
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        DB::table('invoices')->insert(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid,
            'number' => 'INV-RUSAK-1', 'date' => now()->toDateString(), 'customer_id' => $cust->id,
            'due_date' => now()->addDays(7)->toDateString(), 'status' => 'posted', 'pay_status' => 'unpaid',
            'subtotal' => 1000, 'discount' => 0, 'tax' => 0, 'grand_total' => 1000, 'paid_total' => 1000,
            'created_at' => now(), 'updated_at' => now()]);

        $this->artisan('kaza:audit-integrity')
            ->expectsOutputToContain('Saldo stok != sum ledger')
            ->expectsOutputToContain('Jurnal tidak balance')
            ->expectsOutputToContain('JN-RUSAK-1')
            ->expectsOutputToContain('INV-RUSAK-1')
            ->expectsOutputToContain('AUDIT GAGAL')
            ->assertExitCode(1);
    }

    public function test_api_throttle_60_per_menit(): void
    {
        $admin = $this->makeUser('super_admin');
        $admin->forceFill(['email' => 'api6t@test.local'])->save();
        $token = $this->postJson('/api/v1/login', ['email' => 'api6t@test.local', 'password' => 'secret123'])->json('token');
        $h = ['Authorization' => 'Bearer '.$token];

        for ($i = 1; $i <= 60; $i++) {
            $this->getJson('/api/v1/me', $h)->assertOk();
        }
        // permintaan ke-61 dalam 1 menit ditolak (rate limit per pengguna)
        $this->getJson('/api/v1/me', $h)->assertStatus(429);
    }
}

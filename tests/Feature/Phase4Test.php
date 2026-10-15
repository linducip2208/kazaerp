<?php

namespace Tests\Feature;

use App\Jobs\SendOverdueInvoiceAlert;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class Phase4Test extends TestCase
{
    protected function cloneProduct(array $over = []): int
    {
        return DB::table('products')->insertGetId(array_merge([
            'legal_entity_id' => $this->eid, 'sku' => 'P-'.uniqid(), 'name' => 'Produk Fase4',
            'kind' => 'goods', 'cost_price' => 0, 'sell_price' => 10000,
            'is_sellable' => true, 'is_purchasable' => true, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ], $over));
    }

    public function test_tier_pricing_invoice_and_api(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        DB::table('products')->where('id', $prod->id)->update(['wholesale_price' => 12000, 'reseller_price' => 11000]);
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        DB::table('customers')->where('id', $cust->id)->update(['tier' => 'wholesale']);
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'qty' => 20, 'ref_type' => 'test', 'ref_id' => 1]);

        // price kosong pada invoice → default harga tier wholesale (12000), bukan retail (15000)
        $this->post('/sales/invoices', ['customer_id' => $cust->id, 'date' => '2026-10-15',
            'lines' => [['product_id' => $prod->id, 'qty' => 1, 'price' => '']]])->assertRedirect();
        $inv = DB::table('invoices')->orderByDesc('id')->first();
        $line = DB::table('invoice_lines')->where('invoice_id', $inv->id)->first();
        $this->assertEquals(12000.0, (float) $line->price);
        // harga eksplisit tetap dihormati (override manual)
        $this->post('/sales/invoices', ['customer_id' => $cust->id, 'date' => '2026-10-15',
            'lines' => [['product_id' => $prod->id, 'qty' => 1, 'price' => 13500]]])->assertRedirect();
        $line2 = DB::table('invoice_lines')->where('invoice_id', DB::table('invoices')->orderByDesc('id')->value('id'))->first();
        $this->assertEquals(13500.0, (float) $line2->price);

        // halaman create menampilkan harga tier untuk pelanggan terpilih
        $this->get('/sales/invoices/create?customer_id='.$cust->id)->assertOk()->assertSee('12000', false);

        // API: harga mengikuti customer_id + endpoint price
        $apiUser = $this->makeUser('super_admin');
        $apiUser->forceFill(['email' => 'api4@test.local'])->save();
        $token = $this->postJson('/api/v1/login', ['email' => 'api4@test.local', 'password' => 'secret123'])->json('token');
        $h = ['Authorization' => 'Bearer '.$token];
        $list = $this->getJson('/api/v1/products?customer_id='.$cust->id, $h)->assertOk();
        $row = (object) collect($list->json('data'))->firstWhere('sku', 'T-001');
        $this->assertEquals(12000.0, (float) ($row->price ?? 0));
        $this->assertEquals('wholesale', $row->tier ?? null);
        $retail = $this->getJson('/api/v1/products', $h)->assertOk()->json('data');
        $rowRetail = (object) collect($retail)->firstWhere('sku', 'T-001');
        $this->assertEquals(15000.0, (float) ($rowRetail->price ?? 0));
        $this->getJson('/api/v1/products/'.$prod->id.'/price?customer_id='.$cust->id, $h)
            ->assertOk()->assertJsonPath('data.tier', 'wholesale');
        $pv = $this->getJson('/api/v1/products/'.$prod->id.'/price?customer_id='.$cust->id, $h)->json('data');
        $this->assertEquals(12000.0, (float) $pv['price']);
    }

    public function test_grn_batch_and_quarantine_guard(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();

        // GRN mengisi lot → batch dibuat otomatis
        $this->post('/inventory/receipts', ['warehouse_id' => $this->wh, 'date' => '2026-10-15',
            'lines' => [['product_id' => $prod->id, 'qty' => 5, 'cost' => 8000, 'lot' => 'LOT-A', 'expired' => '2027-01-01', 'qc' => 'released']]])->assertRedirect();
        $batch = DB::table('batches')->where('lot_no', 'LOT-A')->first();
        $this->assertNotNull($batch);
        $this->assertEquals('released', $batch->qc_status);
        $this->assertEquals(5.0, (float) DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'batch_id' => $batch->id])->value('qty_on_hand'));

        // karantina → keluar diblokir
        $this->post('/inventory/batches/'.$batch->id.'/qc', ['qc_status' => 'quarantine', 'note' => 'menunggu hasil lab'])->assertRedirect();
        $this->assertEquals('quarantine', DB::table('batches')->where('id', $batch->id)->value('qc_status'));
        try {
            \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'batch_id' => $batch->id, 'qty' => -1, 'ref_type' => 'test', 'ref_id' => 2]);
            $this->fail('Batch karantina seharusnya diblokir keluar.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('quarantine', $e->getMessage());
        }

        // release → keluar boleh
        $this->post('/inventory/batches/'.$batch->id.'/qc', ['qc_status' => 'released'])->assertRedirect();
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'batch_id' => $batch->id, 'qty' => -1, 'ref_type' => 'test', 'ref_id' => 3]);
        $this->assertEquals(4.0, (float) DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'batch_id' => $batch->id])->value('qty_on_hand'));

        // batch kedaluwarsa diblokir walau released
        $old = DB::table('batches')->insertGetId(['product_id' => $prod->id, 'lot_no' => 'LOT-OLD',
            'expired_at' => now()->subDay()->toDateString(), 'qc_status' => 'released', 'created_at' => now(), 'updated_at' => now()]);
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'batch_id' => $old, 'qty' => 1, 'ref_type' => 'test', 'ref_id' => 4]);
        try {
            \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'batch_id' => $old, 'qty' => -1, 'ref_type' => 'test', 'ref_id' => 5]);
            $this->fail('Batch kedaluwarsa seharusnya diblokir keluar.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('kedaluwarsa', $e->getMessage());
        }

        // audit QC + halaman
        $this->assertGreaterThanOrEqual(2, DB::table('quality_checks')->where('ref_type', 'batch')->count());
        $this->get('/inventory/batches')->assertOk()->assertSee('LOT-A');
    }

    public function test_work_order_issue_produce_variance(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $fg = $this->cloneProduct(['sku' => 'FG-001', 'name' => 'Kaos Jadi', 'sell_price' => 100000]);
        $mat = $this->cloneProduct(['sku' => 'MAT-001', 'name' => 'Kain', 'cost_price' => 5000]);
        $bom = DB::table('boms')->insertGetId(['legal_entity_id' => $this->eid, 'product_id' => $fg, 'version' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('bom_lines')->insert(['bom_id' => $bom, 'material_id' => $mat, 'qty' => 1, 'created_at' => now(), 'updated_at' => now()]);
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $mat, 'qty' => 10, 'ref_type' => 'test', 'ref_id' => 1, 'unit_cost' => 5000]);

        $this->post('/mfg/workorders', ['bom_id' => $bom, 'date' => '2026-10-15', 'qty_planned' => 2, 'warehouse_id' => $this->wh])->assertRedirect();
        $wo = DB::table('work_orders')->orderByDesc('id')->first();
        $this->assertEquals('released', $wo->status);
        $line = DB::table('work_order_lines')->where('work_order_id', $wo->id)->first();
        $this->assertEquals(2.0, (float) $line->qty_required);
        $this->assertEquals(5000.0, (float) $line->unit_cost); // standar di-snapshot

        // harga material naik sebelum issue → variance harga terdeteksi
        DB::table('products')->where('id', $mat)->update(['cost_price' => 6000]);
        $this->post('/mfg/workorders/'.$wo->id.'/issue')->assertRedirect();
        $this->assertEquals(8.0, (float) DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'product_id' => $mat])->value('qty_on_hand'));
        $wo = DB::table('work_orders')->where('id', $wo->id)->first();
        $this->assertEquals('in_progress', $wo->status);
        $this->assertEquals(-2000.0, (float) $wo->variance_amount); // std 10.000 − aktual 12.000

        // produce → barang jadi masuk + completed
        $this->post('/mfg/workorders/'.$wo->id.'/produce', ['qty_good' => 2, 'qty_reject' => 0, 'note' => 'lolos QC'])->assertRedirect();
        $this->assertEquals(2.0, (float) DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'product_id' => $fg])->value('qty_on_hand'));
        $wo = DB::table('work_orders')->where('id', $wo->id)->first();
        $this->assertEquals('completed', $wo->status);
        $this->assertEquals(2.0, (float) $wo->qty_good);

        // issue ulang pada WO selesai ditolak
        $this->post('/mfg/workorders/'.$wo->id.'/issue')->assertSessionHasErrors();
        $this->get('/mfg/workorders/'.$wo->id)->assertOk()->assertSee('completed');
    }

    public function test_commission_accrual_and_approve(): void
    {
        $admin = $this->makeUser('super_admin');
        $sales = $this->makeUser('sales_staff');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        DB::table('company_configs')->insert(['legal_entity_id' => $this->eid, 'key' => 'commission_rate_pct', 'value' => '5', 'created_at' => now(), 'updated_at' => now()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        DB::table('customers')->where('id', $cust->id)->update(['sales_owner_id' => $sales->id]);
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'qty' => 20, 'ref_type' => 'test', 'ref_id' => 1]);

        $this->post('/sales/invoices', ['customer_id' => $cust->id, 'date' => '2026-10-15',
            'lines' => [['product_id' => $prod->id, 'qty' => 2, 'price' => 15000]]])->assertRedirect();
        $inv = DB::table('invoices')->orderByDesc('id')->first();
        // belum lunas → belum ada komisi
        $this->assertEquals(0, DB::table('sales_commissions')->where('invoice_id', $inv->id)->count());
        $this->post('/sales/invoices/'.$inv->id.'/pay', ['amount' => (float) $inv->grand_total, 'method' => 'cash', 'date' => '2026-10-15'])->assertRedirect();
        $c = DB::table('sales_commissions')->where('invoice_id', $inv->id)->first();
        $this->assertNotNull($c);
        $this->assertEquals($sales->id, (int) $c->user_id);
        $this->assertEquals(1500.0, (float) $c->amount); // 5% × 30.000 (subtotal, tanpa pajak)
        $this->assertEquals('pending', $c->status);
        // approve oleh finance
        $this->post('/finance/commissions/'.$c->id.'/approve')->assertRedirect();
        $this->assertEquals('approved', DB::table('sales_commissions')->where('id', $c->id)->value('status'));
        $this->get('/finance/commissions')->assertOk()->assertSee('1.500');
    }

    public function test_api_pos_shift_checkout_and_gps(): void
    {
        $admin = $this->makeUser('super_admin');
        $admin->forceFill(['email' => 'api4b@test.local'])->save();
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        DB::table('products')->where('id', $prod->id)->update(['wholesale_price' => 12000]);
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        DB::table('customers')->where('id', $cust->id)->update(['tier' => 'wholesale']);
        \App\Services\StockService::move(['warehouse_id' => $this->wh, 'product_id' => $prod->id, 'qty' => 10, 'ref_type' => 'test', 'ref_id' => 1]);

        $token = $this->postJson('/api/v1/login', ['email' => 'api4b@test.local', 'password' => 'secret123'])->json('token');
        $h = ['Authorization' => 'Bearer '.$token];
        $this->postJson('/api/v1/pos/shift/open', ['opening_cash' => 100000], $h)->assertStatus(201);
        $this->postJson('/api/v1/pos/shift/open', ['opening_cash' => 100000], $h)->assertStatus(422); // dobel shift ditolak
        $sale = $this->postJson('/api/v1/pos/checkout', ['lines' => [['product_id' => $prod->id, 'qty' => 2]],
            'pay_method' => 'cash', 'customer_id' => $cust->id], $h)->assertStatus(201)->json('data');
        $this->assertEquals(24000.0, (float) $sale['subtotal']); // harga tier wholesale
        $this->assertEquals(26640.0, (float) $sale['grand_total']); // + PPN 11%
        $this->assertEquals(8.0, (float) DB::table('stock_balances')->where(['warehouse_id' => $this->wh, 'product_id' => $prod->id])->value('qty_on_hand'));
        $close = $this->postJson('/api/v1/pos/shift/close', ['closing_cash' => 100000 + 26640], $h)->assertOk()->json('data');
        $this->assertEquals(26640.0, (float) $close['system_total']);

        // GPS: driver push posisi trip, web tracking menampilkan
        $owner = DB::table('pet_owners')->insertGetId(['legal_entity_id' => $this->eid, 'name' => 'Pemilik Uji', 'created_at' => now(), 'updated_at' => now()]);
        $pet = DB::table('pets')->insertGetId(['pet_owner_id' => $owner, 'name' => 'Milo', 'species' => 'cat', 'created_at' => now(), 'updated_at' => now()]);
        $order = DB::table('transport_orders')->insertGetId(['legal_entity_id' => $this->eid, 'number' => 'TR-1', 'date' => '2026-10-15',
            'pet_id' => $pet, 'pickup_address' => 'A', 'delivery_address' => 'B', 'scheduled_at' => now(), 'price' => 50000, 'status' => 'ordered', 'created_at' => now(), 'updated_at' => now()]);
        $veh = DB::table('vehicles')->insertGetId(['legal_entity_id' => $this->eid, 'plate' => 'B 1234 XY', 'name' => 'Van', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $trip = DB::table('trips')->insertGetId(['transport_order_id' => $order, 'vehicle_id' => $veh, 'driver_id' => $admin->id,
            'status' => 'assigned', 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson('/api/v1/trips/'.$trip.'/positions', ['lat' => -6.2, 'lng' => 106.8167, 'speed_kmh' => 40], $h)->assertStatus(201);
        $pos = $this->getJson('/api/v1/trips/'.$trip.'/positions', $h)->assertOk()->json('data.positions');
        $this->assertCount(1, $pos);
        $this->assertEquals(-6.2, (float) $pos[0]['lat']);
        // entitas lain ditolak (user entitas kedua, role tanpa bypass lintas entitas)
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id' => $this->hid, 'code' => 'ENT-G', 'name' => 'Entitas G', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $other = $this->makeUser('module_operator', $eid2);
        $other->forceFill(['email' => 'api4c@test.local'])->save();
        $rid = DB::table('roles')->where('name', 'module_operator')->value('id');
        $pid = DB::table('permissions')->where('name', 'api.access')->value('id');
        if ($pid) {
            DB::table('permission_role')->updateOrInsert(['role_id' => $rid, 'permission_id' => $pid], []);
        }
        // guard auth di-cache per proses test → refresh aplikasi dulu agar token2 benar-benar dipakai
        $this->refreshApplication();
        $token2 = $this->postJson('/api/v1/login', ['email' => 'api4c@test.local', 'password' => 'secret123'])->json('token');
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$token2])->assertOk()->assertJsonPath('email', 'api4c@test.local');
        $this->getJson('/api/v1/trips/'.$trip.'/positions', ['Authorization' => 'Bearer '.$token2])->assertForbidden();

        // halaman web tracking
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $this->get('/pet/transport/tracking')->assertOk()->assertSee('TR-1');
        $this->get('/pet/transport/'.$order.'/track')->assertOk()->assertSee('B 1234 XY');
    }

    public function test_overdue_alert_job_and_smoke_pages(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $cust = DB::table('customers')->where('code', 'TC-001')->first();
        $inv = DB::table('invoices')->insertGetId(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid,
            'number' => 'INV-OD-1', 'date' => now()->subDays(10)->toDateString(), 'customer_id' => $cust->id,
            'due_date' => now()->subDays(3)->toDateString(), 'status' => 'posted', 'pay_status' => 'unpaid',
            'subtotal' => 100000, 'discount' => 0, 'tax' => 11000, 'grand_total' => 111000, 'paid_total' => 0,
            'created_at' => now(), 'updated_at' => now()]);

        Queue::fake();
        $this->artisan('kaza:overdue-alerts')->assertExitCode(0);
        Queue::assertPushed(SendOverdueInvoiceAlert::class);

        // jalankan job seperti worker
        (new SendOverdueInvoiceAlert($inv))->handle();
        $link = '/sales/invoices/'.$inv;
        $n = DB::table('notifications')->where('link', $link)->count();
        $this->assertGreaterThanOrEqual(1, $n);
        (new SendOverdueInvoiceAlert($inv))->handle(); // idempotent 24 jam
        $this->assertEquals($n, DB::table('notifications')->where('link', $link)->count());

        // invoice lunas → tidak ada notifikasi baru
        DB::table('notifications')->where('link', $link)->delete();
        DB::table('invoices')->where('id', $inv)->update(['pay_status' => 'paid']);
        (new SendOverdueInvoiceAlert($inv))->handle();
        $this->assertEquals(0, DB::table('notifications')->where('link', $link)->count());

        // smoke halaman baru
        $this->get('/inventory/batches')->assertOk();
        $this->get('/mfg/workorders')->assertOk();
        $this->get('/mfg/workorders/create')->assertOk();
        $this->get('/finance/commissions')->assertOk();
        $this->get('/pet/transport/tracking')->assertOk();
        $this->get('/sales/pos')->assertOk();
    }
}

<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Phase10 — paket demo gelombang 2 (MFG/WO, rental, thrift, studio, pet)
 * + guard regresi modul terkait. Semua idempoten & anti-bentrok.
 */
class Phase10Test extends TestCase {
    public function test_demo_gelombang2_idempotent(): void {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $this->seed(\Database\Seeders\DemoSeeder::class); // ulang: tidak boleh ganda
        $this->assertEquals(1, DB::table('work_orders')->where('number', 'DEMO-WO-001')->count());
        $this->assertEquals(1, DB::table('rental_reservations')->where('number', 'DEMO-RNT-001')->count());
        $this->assertEquals(1, DB::table('thrift_lots')->where('number', 'DEMO-LOT-001')->count());
        $this->assertEquals(2, DB::table('thrift_items')->where('code', 'like', 'DEMO-THR-%')->count());
        $this->assertEquals(1, DB::table('studio_projects')->where('title', 'Katalog Demo')->count());
        $this->assertEquals(1, DB::table('grooming_orders')->where('number', 'DEMO-GRM-ORD-001')->count());
        $this->assertEquals(1, DB::table('boarding_reservations')->where('number', 'DEMO-BRD-001')->count());
    }

    public function test_demo_wo_selesai_dan_variance_terhitung(): void {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $wo = DB::table('work_orders')->where('number', 'DEMO-WO-001')->first();
        $this->assertNotNull($wo);
        $this->assertEquals('completed', $wo->status);
        $this->assertEquals(10.0, (float) $wo->qty_good);
        // standar 10×5000=50000 vs aktual 10×6000=60000 → variance −10000
        $this->assertEquals(50000.0, (float) $wo->standard_cost);
        $this->assertEquals(60000.0, (float) $wo->actual_cost);
        $this->assertEquals(-10000.0, (float) $wo->variance_amount);
        $this->assertEquals(1, (int) $wo->bom_version_locked);
        // FG masuk stok 10
        $fg = DB::table('products')->where(['legal_entity_id' => $this->eid, 'sku' => 'DEMO-FG-001'])->first();
        $this->assertNotNull($fg);
        $this->assertGreaterThanOrEqual(10.0, (float) DB::table('stock_balances')
            ->where(['warehouse_id' => $this->wh, 'product_id' => $fg->id, 'batch_id' => null])->value('qty_on_hand'));
        // histori lengkap
        $this->assertGreaterThanOrEqual(3, DB::table('doc_status_histories')
            ->where(['doc_type' => 'work_orders', 'doc_id' => $wo->id])->count());
    }

    public function test_demo_rental_deposit_dan_anti_bentrok(): void {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $res = DB::table('rental_reservations')->where('number', 'DEMO-RNT-001')->first();
        $this->assertEquals('reserved', $res->status);
        $this->assertEquals(100000.0, (float) $res->deposit);
        // Periode tumpang tindih pada unit yang sama HARUS ditolak
        $cust = DB::table('customers')->where(['legal_entity_id' => $this->eid, 'code' => 'CUST-001'])->first();
        $r = $this->post('/rental/book', ['rental_unit_id' => $res->rental_unit_id, 'customer_id' => $cust->id,
            'start_date' => now()->addDays(8)->toDateString(), 'end_date' => now()->addDays(10)->toDateString(),
            'rate' => 250000]);
        $r->assertSessionHasErrors('msg');
        $this->assertEquals(1, DB::table('rental_reservations')->where('rental_unit_id', $res->rental_unit_id)->count());
    }

    public function test_demo_thrift_item_unik_dan_anti_double_sell(): void {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        // kode item unik global: duplikat harus gagal di DB (unique index)
        $lot = DB::table('thrift_lots')->where('number', 'DEMO-LOT-001')->first();
        try {
            DB::table('thrift_items')->insert(['thrift_lot_id' => $lot->id, 'code' => 'DEMO-THR-001',
                'name' => 'Duplikat', 'price' => 99999, 'status' => 'sorted',
                'created_at' => now(), 'updated_at' => now()]);
            $this->fail('Kode thrift duplikat seharusnya ditolak unique index.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('Duplicate', $e->getMessage());
        }
        // live order: item DEMO-THR-001 hanya boleh direservasi sekali
        $item = DB::table('thrift_items')->where('code', 'DEMO-THR-001')->first();
        $cust = DB::table('customers')->where(['legal_entity_id' => $this->eid, 'code' => 'CUST-001'])->first();
        $sess = DB::table('live_sessions')->insertGetId(['legal_entity_id' => $this->eid,
            'title' => 'Live P10', 'scheduled_at' => now(), 'status' => 'live',
            'created_at' => now(), 'updated_at' => now()]);
        $this->post('/thrift/live/reserve', ['live_session_id' => $sess, 'thrift_item_id' => $item->id,
            'customer_id' => $cust->id, 'price' => 150000])->assertRedirect();
        $this->assertEquals('reserved', DB::table('thrift_items')->where('id', $item->id)->value('status'));
        $r2 = $this->post('/thrift/live/reserve', ['live_session_id' => $sess, 'thrift_item_id' => $item->id,
            'customer_id' => $cust->id, 'price' => 150000]);
        $r2->assertSessionHasErrors('msg');
        $this->assertEquals(1, DB::table('live_orders')->where('thrift_item_id', $item->id)->count());
    }

    public function test_demo_studio_dan_pet_tersedia_terisolasi(): void {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $this->get('/studio/projects')->assertOk()->assertSee('Katalog Demo');
        $this->get('/pet/grooming')->assertOk();
        $this->get('/pet/boarding')->assertOk();
        // isolasi: entitas lain tidak melihat dokumen demo
        $other = DB::table('legal_entities')->where('id', '!=', $this->eid)->value('id');
        $this->assertNotNull($other);
        $this->assertFalse(DB::table('work_orders')->where('number', 'DEMO-WO-001')
            ->where('legal_entity_id', $other)->exists());
    }
}

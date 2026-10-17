<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Regresi FASE 1–2: memoization permission, beban query dashboard, idempotensi demo.
 */
class DemoPerfTest extends TestCase
{
    public function test_permission_memoized_tanpa_n_plus_1(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin);
        $perms = ['sales.view', 'inventory.view', 'finance.view', 'hr.view', 'reports.view', 'governance.view'];
        DB::flushQueryLog();
        DB::enableQueryLog();
        for ($i = 0; $i < 5; $i++) {
            foreach ($perms as $p) {
                $admin->hasPermission($p);
            }
            $admin->hasRole('super_admin', 'holding_owner');
        }
        $n = count(DB::getQueryLog());
        // 30x hasPermission + 5x hasRole harus <= 2 query (1 roles + 1 permission_role)
        $this->assertLessThanOrEqual(2, $n, "Permission tidak termemoize: {$n} query.");
        DB::disableQueryLog();
    }

    public function test_dashboard_200_dan_query_terbatas(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $r = $this->get('/');
        $r->assertStatus(200);
        // Baseline pra-perbaikan: 84 query (50 di antaranya roles/permission).
        $this->assertLessThan(45, $count, "Dashboard masih berat: {$count} query.");
    }

    public function test_demo_seeder_idempotent_dan_jurnal_balance(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $this->seed(\Database\Seeders\DemoSeeder::class); // ulang: tidak boleh ganda
        foreach (['DEMO-SO-001', 'DEMO-PO-001', 'DEMO-PO-002'] as $n) {
            $t = str_starts_with($n, 'DEMO-SO') ? 'sales_orders' : 'purchase_orders';
            $this->assertEquals(1, DB::table($t)->where('number', $n)->count(), "Duplikat {$n}");
        }
        $this->assertEquals(1, DB::table('invoices')->where('number', 'DEMO-INV-001')->count());
        $this->assertEquals(1, DB::table('vendor_invoices')->where('number', 'DEMO-VIN-001')->count());
        $js = DB::table('journals')->where('number', 'like', 'J-DEMO-%')->get();
        $this->assertGreaterThanOrEqual(3, $js->count(), 'Jurnal demo kurang.');
        foreach ($js as $j) {
            $this->assertEquals((float) $j->total_debit, (float) $j->total_credit, "Jurnal {$j->number} tidak balance.");
        }
        $this->assertEquals('paid', DB::table('invoices')->where('number', 'DEMO-INV-001')->value('pay_status'));
        $this->assertEquals('partial', DB::table('vendor_invoices')->where('number', 'DEMO-VIN-001')->value('status'));
        $this->assertEquals('matched', DB::table('vendor_invoices')->where('number', 'DEMO-VIN-001')->value('match_status'));
        $this->assertGreaterThanOrEqual(1, DB::table('approvals')->where('status', 'pending')->count(), 'Approval pending demo hilang.');
    }

    public function test_demo_login_dan_isolasi_entitas(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $admin = \App\Models\User::where('email', 'admin@kazaka.local')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasPermission('sales.view'));
        // Isolasi: dokumen demo milik MZA-HOLD, entitas lain tidak boleh melihat
        $eid = $this->eid;
        $other = DB::table('legal_entities')->where('id', '!=', $eid)->value('id');
        $this->assertNotNull($other);
        $leak = DB::table('invoices')->where('number', 'DEMO-INV-001')->where('legal_entity_id', $other)->exists();
        $this->assertFalse($leak, 'Dokumen demo bocor ke entitas lain.');
    }
}

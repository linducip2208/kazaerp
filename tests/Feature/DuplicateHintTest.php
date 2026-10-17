<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Regresi polish Paket 4: saran duplikat read-only pada master
 * (pola mapan: merge pelanggan CRM-05). Tidak menghapus/menggabung.
 */
class DuplicateHintTest extends TestCase
{
    public function test_supplier_duplikat_ditandai(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $a = DB::table('suppliers')->insertGetId([
            'legal_entity_id' => $this->eid, 'code' => 'DUP-A', 'name' => 'Supplier Ganda',
            'phone' => '08123456789', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('suppliers')->insert([
            'legal_entity_id' => $this->eid, 'code' => 'DUP-B', 'name' => 'Supplier Ganda Toko',
            'phone' => '08123456789', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->get("/crm/suppliers/{$a}")->assertStatus(200)->assertSee('Kemungkinan duplikat');
    }

    public function test_produk_duplikat_ditandai(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $p = DB::table('products')->insertGetId([
            'legal_entity_id' => $this->eid, 'sku' => 'DUP-P1', 'name' => 'Produk Kembar',
            'is_sellable' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('products')->insert([
            'legal_entity_id' => $this->eid, 'sku' => 'DUP-P2', 'name' => 'produk kembar',
            'is_sellable' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->get("/master/products/{$p}")->assertStatus(200)->assertSee('Kemungkinan duplikat');
    }

    public function test_tanpa_duplikat_tidak_ada_peringatan(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $s = DB::table('suppliers')->where('code', 'TS-001')->value('id');
        $this->get("/crm/suppliers/{$s}")->assertStatus(200)->assertDontSee('Kemungkinan duplikat');
    }
}

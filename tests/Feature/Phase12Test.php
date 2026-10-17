<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

// CRM-04 (penawaran berversi + masa berlaku), CRM-05 (gabung pelanggan),
// CRM-06 (opt-out promosi + daftar kampanye).
class Phase12Test extends TestCase
{
    public function test_quotation_validity_and_version_snapshot(): void
    {
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $prod = DB::table('products')->where('sku', 'T-001')->first();
        $cust = DB::table('customers')->where('code', 'TC-001')->first();

        // buat penawaran dengan masa berlaku 7 hari
        $until = now()->addDays(7)->toDateString();
        $this->post('/sales/quotes', ['customer_id' => $cust->id, 'date' => now()->toDateString(),
            'valid_until' => $until,
            'lines' => [['product_id' => $prod->id, 'qty' => 2, 'price' => 15000]]])->assertRedirect();
        $q = DB::table('quotations')->orderByDesc('id')->first();
        $this->assertEquals($until, substr((string) $q->valid_until, 0, 10));
        $this->assertEquals(1, (int) $q->version);

        // masa berlaku mundur dari tanggal → ditolak validasi
        $this->post('/sales/quotes', ['customer_id' => $cust->id, 'date' => now()->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
            'lines' => [['product_id' => $prod->id, 'qty' => 1, 'price' => 15000]]])->assertSessionHasErrors();

        // setujui selagi berlaku → snapshot versi tersimpan
        $this->post('/sales/quotes/' . $q->id . '/approve')->assertRedirect();
        $this->assertEquals('approved', DB::table('quotations')->where('id', $q->id)->value('status'));
        $this->assertDatabaseHas('quotation_versions', ['quotation_id' => $q->id, 'version' => 1]);
        $snap = json_decode(DB::table('quotation_versions')->where('quotation_id', $q->id)->value('snapshot'), true);
        $this->assertEquals((float) $q->grand_total, (float) $snap['grand_total']);
        $this->assertCount(1, $snap['lines']);
        $this->assertDatabaseHas('doc_status_histories', ['doc_type' => 'quotations', 'doc_id' => $q->id,
            'from_status' => 'draft', 'to_status' => 'approved']);

        // halaman show memuat arsip versi
        $this->get('/sales/quotes/' . $q->id)->assertOk()->assertSee('Arsip versi');

        // penawaran kedaluwarsa tidak bisa disetujui
        DB::table('quotations')->insert(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid,
            'number' => 'QT-EXP-1', 'date' => now()->subDays(10)->toDateString(),
            'customer_id' => $cust->id, 'valid_until' => now()->subDay()->toDateString(),
            'status' => 'draft', 'version' => 1, 'subtotal' => 1000, 'discount' => 0,
            'tax' => 110, 'grand_total' => 1110, 'created_by' => $op->id,
            'created_at' => now(), 'updated_at' => now()]);
        $exp = DB::table('quotations')->where('number', 'QT-EXP-1')->first();
        $this->post('/sales/quotes/' . $exp->id . '/approve')->assertSessionHasErrors();
        $this->assertEquals('draft', DB::table('quotations')->where('id', $exp->id)->value('status'));
        $this->assertDatabaseMissing('quotation_versions', ['quotation_id' => $exp->id]);
    }

    public function test_customer_merge_keeps_history(): void
    {
        $mgr = $this->makeUser('module_operator');
        $this->actingAs($mgr)->withSession(['erp_context' => $this->ctx()]);
        $dup = DB::table('customers')->insertGetId(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid,
            'code' => 'TC-DUP', 'name' => 'Pelanggan Uji', 'phone' => '0811', 'tier' => 'retail',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $induk = DB::table('customers')->where('code', 'TC-001')->first();

        $this->post('/crm/customers/' . $dup . '/merge', ['target_id' => $induk->id])->assertRedirect();
        $src = DB::table('customers')->where('id', $dup)->first();
        $this->assertEquals(0, (int) $src->is_active);
        $this->assertEquals($induk->id, (int) $src->merged_into_customer_id);
        // baris sumber TETAP ada (histori tidak dihapus/dipindah)
        $this->assertEquals('TC-DUP', $src->code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'merge', 'entity' => 'customers', 'record_id' => $dup]);

        // gabung ulang / ke diri sendiri ditolak
        $this->post('/crm/customers/' . $dup . '/merge', ['target_id' => $induk->id])->assertSessionHasErrors();
        $this->post('/crm/customers/' . $induk->id . '/merge', ['target_id' => $induk->id])->assertSessionHasErrors();

        // halaman show sumber menampilkan status gabungan
        $this->get('/crm/customers/' . $dup)->assertOk()->assertSee('sudah digabung');
    }

    public function test_customer_merge_guards_entity_and_permission(): void
    {
        $mgr = $this->makeUser('module_operator');
        $cashier = $this->makeUser('cashier');
        $this->actingAs($mgr)->withSession(['erp_context' => $this->ctx()]);
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id' => $this->hid, 'code' => 'ENT-MG',
            'name' => 'Entitas MG', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $alien = DB::table('customers')->insertGetId(['legal_entity_id' => $eid2, 'code' => 'TC-ALIEN',
            'name' => 'Asing', 'tier' => 'retail', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $induk = DB::table('customers')->where('code', 'TC-001')->first();

        // lintas entitas → 404 (IDOR), bukan error validasi
        $this->post('/crm/customers/' . $alien . '/merge', ['target_id' => $induk->id])->assertNotFound();

        // tanpa wewenang crm.convert → 403
        $this->actingAs($cashier)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/crm/customers/' . $induk->id . '/merge', ['target_id' => $induk->id])->assertForbidden();
    }

    public function test_optout_excluded_from_campaign_recipients(): void
    {
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $camp = DB::table('campaigns')->insertGetId(['legal_entity_id' => $this->eid, 'name' => 'Promo Uji', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now()]);
        $keluar = DB::table('customers')->insertGetId(['legal_entity_id' => $this->eid, 'branch_id' => $this->bid,
            'code' => 'TC-OPT', 'name' => 'Zzz Pelanggan Optout', 'tier' => 'retail', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now()]);

        // opt-out via form pelanggan
        $tc = DB::table('customers')->where('code', 'TC-001')->first();
        $this->put('/crm/customers/' . $tc->id, ['code' => 'TC-001', 'name' => $tc->name, 'marketing_opt_out' => '1'])
            ->assertRedirect();
        $this->assertEquals(1, (int) DB::table('customers')->where('id', $tc->id)->value('marketing_opt_out'));

        // gabungkan pelanggan opt-out tadi ke induk agar juga teruji sebagai "hasil gabungan"
        $this->post('/crm/customers/' . $keluar . '/merge', ['target_id' => $tc->id])->assertRedirect();

        // daftar kampanye: induk (opt-out) & hasil gabungan TIDAK tampil; pengecualian dihitung
        $res = $this->get('/marketing/campaigns/' . $camp)->assertOk();
        $res->assertSee('Penerima kampanye');
        $res->assertDontSee('Pelanggan Uji');
        $res->assertDontSee('Zzz Pelanggan Optout');

        // opt-out dicabut → kembali tampil
        $this->put('/crm/customers/' . $tc->id, ['code' => 'TC-001', 'name' => $tc->name, 'marketing_opt_out' => '0'])
            ->assertRedirect();
        $this->get('/marketing/campaigns/' . $camp)->assertOk()->assertSee('Pelanggan Uji');
    }
}

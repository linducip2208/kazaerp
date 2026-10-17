<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

// CRM-03 (follow-up) + RET-01/02/05 (tiket komplain): alur end-to-end + SoD + isolasi + pengingat.
class Phase11Test extends TestCase
{
    public function test_followup_schedule_complete_escalate(): void
    {
        $op = $this->makeUser('module_operator');
        $mgr = $this->makeUser('branch_manager');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $cust = DB::table('customers')->where('code', 'TC-001')->value('id');

        // jadwalkan follow-up untuk pelanggan
        $this->post('/crm/followups', ['customer_id' => $cust, 'due_at' => now()->addDay()->toDateString(),
            'note' => 'Hubungi untuk penawaran grooming.'])->assertRedirect();
        $f = DB::table('followups')->orderByDesc('id')->first();
        $this->assertEquals('open', $f->status);
        $this->assertEquals($this->eid, (int) $f->legal_entity_id);

        // tanpa induk → ditolak
        $this->post('/crm/followups', ['due_at' => now()->toDateString(), 'note' => 'x'])->assertSessionHasErrors();

        // halaman index + show 200 (empty/loading/error states nyata, tanpa tombol palsu)
        $this->get('/crm/followups')->assertOk();
        $this->get('/crm/followups/' . $f->id)->assertOk();

        // selesaikan + catat hasil
        $this->post('/crm/followups/' . $f->id . '/complete', ['result' => 'Deal, jadwal grooming Senin.'])->assertRedirect();
        $this->assertEquals('done', DB::table('followups')->where('id', $f->id)->value('status'));
        $this->assertNotNull(DB::table('followups')->where('id', $f->id)->value('done_at'));
        $this->assertStringContainsString('Deal', DB::table('followups')->where('id', $f->id)->value('note'));
        // histori transisi tercatat
        $this->assertDatabaseHas('doc_status_histories', ['doc_type' => 'followups', 'doc_id' => $f->id,
            'from_status' => 'open', 'to_status' => 'done']);

        // double-complete ditolak tegas (bukan ganda)
        $this->post('/crm/followups/' . $f->id . '/complete', ['result' => 'Lagi'])->assertSessionHasErrors();

        // follow-up baru untuk eskalasi
        $this->post('/crm/followups', ['customer_id' => $cust, 'due_at' => now()->addDay()->toDateString(), 'note' => 'Butuh atasan.'])->assertRedirect();
        $f2 = DB::table('followups')->orderByDesc('id')->first();
        $this->post('/crm/followups/' . $f2->id . '/escalate', ['assigned_to' => $mgr->id, 'reason' => 'Butuh otorisasi diskon.'])->assertRedirect();
        $this->assertEquals($mgr->id, (int) DB::table('followups')->where('id', $f2->id)->value('assigned_to'));
        $this->assertDatabaseHas('notifications', ['user_id' => $mgr->id, 'title' => 'Eskalasi follow-up #' . $f2->id]);
    }

    public function test_followup_isolation_and_overdue_reminder(): void
    {
        $op = $this->makeUser('module_operator');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id' => $this->hid, 'code' => 'ENT-FU',
            'name' => 'Entitas FU', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $alien = DB::table('followups')->insertGetId(['legal_entity_id' => $eid2, 'owner_type' => 'x',
            'owner_id' => 1, 'due_at' => now()->subDay(), 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        // IDOR lintas entitas → 404
        $this->get('/crm/followups/' . $alien)->assertNotFound();
        $this->post('/crm/followups/' . $alien . '/complete', ['result' => 'x'])->assertNotFound();

        // pengingat: follow-up terlambat milik sendiri → notifikasi, dedupe 24 jam
        $own = DB::table('followups')->insertGetId(['legal_entity_id' => $this->eid, 'owner_type' => 'x',
            'owner_id' => 1, 'assigned_to' => $op->id, 'created_by' => $op->id,
            'due_at' => now()->subHours(2), 'status' => 'open', 'note' => 't', 'created_at' => now(), 'updated_at' => now()]);
        $this->artisan('kaza:followup-reminders', ['--sync' => true])->assertOk();
        $this->assertDatabaseHas('notifications', ['user_id' => $op->id, 'title' => 'Follow-up TERLAMBAT #' . $own]);
        $n1 = DB::table('notifications')->where('user_id', $op->id)->where('title', 'Follow-up TERLAMBAT #' . $own)->count();
        $this->artisan('kaza:followup-reminders', ['--sync' => true])->assertOk();
        $this->assertEquals($n1, DB::table('notifications')->where('user_id', $op->id)->where('title', 'Follow-up TERLAMBAT #' . $own)->count());
    }

    public function test_complaint_ticket_sod_flow(): void
    {
        $reporter = $this->makeUser('branch_manager'); // punya crm.update, bisa lapor + review
        $solver = $this->makeUser('module_operator');  // penyelesai berbeda
        $this->actingAs($reporter)->withSession(['erp_context' => $this->ctx()]);
        $cust = DB::table('customers')->where('code', 'TC-001')->value('id');

        // buat tiket kritis tertaut invoice
        $this->post('/crm/complaints', ['customer_id' => $cust, 'subject' => 'Grooming tidak sesuai paket',
            'body' => 'Bulu tidak rapi, minta perbaikan.', 'urgency' => 'critical',
            'related_type' => 'invoices', 'related_id' => 7])->assertRedirect();
        $t = DB::table('complaints')->orderByDesc('id')->first();
        $this->assertEquals('open', $t->status);
        $this->assertEquals('critical', $t->urgency);
        $this->assertEquals($reporter->id, (int) $t->created_by);
        $this->get('/crm/complaints')->assertOk();
        $this->get('/crm/complaints/' . $t->id)->assertOk();

        // mulai tangani + tugaskan
        $this->post('/crm/complaints/' . $t->id . '/review', ['assignee_id' => $solver->id])->assertRedirect();
        $this->assertEquals('in_review', DB::table('complaints')->where('id', $t->id)->value('status'));

        // pelapor TIDAK boleh menyelesaikan tiket sendiri (SoD)
        $this->post('/crm/complaints/' . $t->id . '/resolve',
            ['decision' => 'fixed', 'resolution' => 'Diperbaiki.'])->assertSessionHasErrors();
        $this->assertEquals('in_review', DB::table('complaints')->where('id', $t->id)->value('status'));

        // penyelesai berbeda OK + histori
        $this->actingAs($solver)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/crm/complaints/' . $t->id . '/resolve',
            ['decision' => 'fixed', 'resolution' => 'Grooming ulang gratis, QC lolos.'])->assertRedirect();
        $done = DB::table('complaints')->where('id', $t->id)->first();
        $this->assertEquals('resolved', $done->status);
        $this->assertEquals('fixed', $done->decision);
        $this->assertEquals($solver->id, (int) $done->resolved_by);
        $this->assertNotNull($done->resolved_at);
        $this->assertDatabaseHas('doc_status_histories', ['doc_type' => 'complaints', 'doc_id' => $t->id,
            'from_status' => 'in_review', 'to_status' => 'resolved']);

        // resolve ganda ditolak; tiket final tak bisa diubah
        $this->post('/crm/complaints/' . $t->id . '/resolve', ['decision' => 'fixed', 'resolution' => 'x'])->assertSessionHasErrors();
        $this->put('/crm/complaints/' . $t->id, ['subject' => 'Ubah', 'body' => 'x', 'urgency' => 'low'])->assertSessionHasErrors();
    }

    public function test_complaint_reject_and_isolation(): void
    {
        $op = $this->makeUser('module_operator');
        $mgr = $this->makeUser('branch_manager');
        $this->actingAs($op)->withSession(['erp_context' => $this->ctx()]);
        $cust = DB::table('customers')->where('code', 'TC-001')->value('id');
        $this->post('/crm/complaints', ['customer_id' => $cust, 'subject' => 'Keluhan fiktif',
            'body' => 'Uji tolak.', 'urgency' => 'low'])->assertRedirect();
        $t = DB::table('complaints')->orderByDesc('id')->first();

        // penolakan oleh petugas lain beralasan
        $this->actingAs($mgr)->withSession(['erp_context' => $this->ctx()]);
        $this->post('/crm/complaints/' . $t->id . '/reject', ['resolution' => 'Tidak terbukti, ada bukti serah terima.'])->assertRedirect();
        $this->assertEquals('rejected', DB::table('complaints')->where('id', $t->id)->value('status'));

        // isolasi entitas: tiket entitas lain 404
        $eid2 = DB::table('legal_entities')->insertGetId(['holding_id' => $this->hid, 'code' => 'ENT-CP',
            'name' => 'Entitas CP', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $alien = DB::table('complaints')->insertGetId(['legal_entity_id' => $eid2, 'subject' => 'Rahasia',
            'status' => 'open', 'urgency' => 'normal', 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/crm/complaints/' . $alien)->assertNotFound();
    }
}

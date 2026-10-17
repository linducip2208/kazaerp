<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Regresi polish Paket 3:
 * - aksi sensitif yang tadinya tanpa jejak audit kini mencatat audit_logs,
 * - input email ber-CRLF dinetralkan sebelum validasi/pengiriman (mitigasi GHSA-5vg9
 *   pada laravel/framework 11.57; upgrade mayor v12 = backlog).
 */
class SecurityAuditTest extends TestCase
{
    public function test_approve_mencatat_audit_log(): void
    {
        $admin = $this->makeUser('super_admin');
        $this->actingAs($admin)->withSession(['erp_context' => $this->ctx()]);
        $cust = DB::table('customers')->where('code', 'TC-001')->value('id');

        $so = DB::table('sales_orders')->insertGetId([
            'legal_entity_id' => $this->eid, 'branch_id' => $this->bid, 'number' => 'SO-AUD-1',
            'date' => now()->toDateString(), 'customer_id' => $cust, 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $cn = DB::table('credit_notes')->insertGetId([
            'legal_entity_id' => $this->eid, 'branch_id' => $this->bid, 'number' => 'CN-AUD-1',
            'date' => now()->toDateString(), 'customer_id' => $cust, 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post("/sales/orders/{$so}/approve")->assertSessionHasNoErrors();
        $this->post("/sales/creditnotes/{$cn}/approve")->assertSessionHasNoErrors();

        $this->assertTrue(
            DB::table('audit_logs')->where(['action' => 'approve', 'entity' => 'sales_orders', 'record_id' => $so])->exists(),
            'Approve SO tidak mencatat audit.'
        );
        $this->assertTrue(
            DB::table('audit_logs')->where(['action' => 'approve', 'entity' => 'credit_notes', 'record_id' => $cn])->exists(),
            'Approve credit note tidak mencatat audit.'
        );
    }

    public function test_email_crlf_dinetralkan(): void
    {
        Mail::fake();
        // Login: CRLF tidak boleh lolos sebagai identitas
        $this->post('/login', ['email' => "admin@test.local\r\nBcc: evil@test.local", 'password' => 'x'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        // Lupa kata sandi: tidak boleh ada email terkirim dari input kotor
        $this->post('/forgot-password', ['email' => "a@test.local\nBcc: evil@test.local"])
            ->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }
}

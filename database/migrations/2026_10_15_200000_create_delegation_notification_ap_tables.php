<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_delegations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('delegator_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('delegate_id')->constrained('users')->cascadeOnDelete();
            $t->string('doc_type', 40)->nullable();
            $t->date('from_date');
            $t->date('to_date');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['delegate_id', 'is_active']);
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('title', 150);
            $t->string('link', 255)->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'read_at']);
        });
        Schema::create('ap_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique();
            $t->date('date');
            $t->foreignId('vendor_invoice_id')->constrained('vendor_invoices');
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->string('method', 30)->default('transfer');
            $t->decimal('amount', 18, 2);
            $t->string('reference', 80)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('payroll_journals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $t->foreignId('journal_id')->constrained('journals');
            $t->timestamps();
            $t->unique(['payroll_id', 'journal_id']);
        });
        // Akun kewajiban gaji untuk posting payroll (per entitas yang sudah ada)
        foreach (DB::table('legal_entities')->pluck('id') as $eid) {
            DB::table('coas')->updateOrInsert(
                ['legal_entity_id' => $eid, 'code' => '2300'],
                ['name' => 'Utang Gaji', 'type' => 'liability', 'normal_balance' => 'credit',
                    'is_postable' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
        // Permission API + auditor read
        foreach (['api.access' => 'api', 'users.deactivate' => 'users', 'roles.assign' => 'roles'] as $p => $g) {
            DB::table('permissions')->updateOrInsert(['name' => $p],
                ['group' => $g, 'label' => ucfirst(str_replace(['.', '_'], ' ', $p)), 'created_at' => now(), 'updated_at' => now()]);
        }
        // Attach untuk database yang sudah di-seed sebelumnya (idempotent)
        $superId = DB::table('roles')->where('name', 'super_admin')->value('id');
        if ($superId) {
            foreach (DB::table('permissions')->pluck('id') as $pid) {
                DB::table('permission_role')->updateOrInsert(['role_id' => $superId, 'permission_id' => $pid], []);
            }
        }
        $auditorId = DB::table('roles')->where('name', 'auditor')->value('id');
        if ($auditorId) {
            foreach (DB::table('permissions')->where(function ($q) {
                $q->where('name', 'like', '%.view')->orWhereIn('name', ['audit.view', 'reports.view', 'reports.export', 'api.access']);
            })->pluck('id') as $pid) {
                DB::table('permission_role')->updateOrInsert(['role_id' => $auditorId, 'permission_id' => $pid], []);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_journals');
        Schema::dropIfExists('ap_payments');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('approval_delegations');
    }
};

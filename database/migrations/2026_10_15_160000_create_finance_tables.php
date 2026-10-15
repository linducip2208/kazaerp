<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('coas', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 20); $t->string('name');
            $t->string('type', 20); $t->string('normal_balance', 10)->default('debit');
            $t->foreignId('parent_id')->nullable()->constrained('coas')->nullOnDelete();
            $t->boolean('is_postable')->default(true); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('journals', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->date('date'); $t->string('number', 60)->unique();
            $t->string('description', 255)->nullable();
            $t->string('source_type', 40)->nullable(); $t->unsignedBigInteger('source_id')->nullable();
            $t->decimal('total_debit', 18, 2); $t->decimal('total_credit', 18, 2);
            $t->string('status', 20)->default('draft');
            $t->string('idempotency_key', 120)->unique();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'date']);
        });
        Schema::create('journal_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $t->foreignId('coa_id')->constrained('coas');
            $t->decimal('debit', 18, 2)->default(0); $t->decimal('credit', 18, 2)->default(0);
            $t->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $t->string('note', 200)->nullable(); $t->timestamps();
        });
        Schema::create('cash_accounts', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('code', 20); $t->string('name'); $t->decimal('balance', 18, 2)->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('cash_receipts', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('cash_account_id')->constrained('cash_accounts');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->decimal('amount', 18, 2); $t->string('payer', 120)->nullable();
            $t->string('description', 255)->nullable(); $t->string('status', 20)->default('posted');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('cash_payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('cash_account_id')->constrained('cash_accounts');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->decimal('amount', 18, 2); $t->string('payee', 120)->nullable();
            $t->string('description', 255)->nullable(); $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('bank_transfers', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('from_bank_id')->constrained('bank_accounts');
            $t->foreignId('to_bank_id')->constrained('bank_accounts');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->decimal('amount', 18, 2); $t->string('status', 20)->default('draft'); $t->timestamps();
        });
        Schema::create('bank_reconciliations', function (Blueprint $t) {
            $t->id(); $t->foreignId('bank_account_id')->constrained('bank_accounts');
            $t->date('period'); $t->decimal('book_balance', 18, 2); $t->decimal('bank_balance', 18, 2);
            $t->decimal('difference', 18, 2)->default(0); $t->string('status', 20)->default('draft');
            $t->text('note')->nullable(); $t->timestamps();
        });
        Schema::create('expenses', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->string('title'); $t->decimal('amount', 18, 2);
            $t->string('status', 20)->default('draft');
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('petty_cash_txns', function (Blueprint $t) {
            $t->id(); $t->foreignId('cash_account_id')->constrained('cash_accounts');
            $t->date('date'); $t->string('kind', 20); $t->decimal('amount', 18, 2);
            $t->string('description', 255)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('budgets', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $t->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $t->string('name'); $t->string('status', 20)->default('draft'); $t->timestamps();
        });
        Schema::create('budget_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $t->foreignId('coa_id')->constrained('coas');
            $t->decimal('amount', 18, 2); $t->timestamps();
        });
        Schema::create('tax_rules', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name');
            $t->decimal('rate', 7, 4); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('journal_approvals', function (Blueprint $t) {
            $t->id(); $t->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('pending'); $t->text('note')->nullable(); $t->timestamps();
        });
        Schema::create('period_closings', function (Blueprint $t) {
            $t->id(); $t->foreignId('fiscal_period_id')->constrained('fiscal_periods');
            $t->string('status', 20)->default('open'); $t->text('note')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('intercompany_txns', function (Blueprint $t) {
            $t->id(); $t->foreignId('from_entity_id')->constrained('legal_entities');
            $t->foreignId('to_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->decimal('amount', 18, 2); $t->string('status', 20)->default('draft');
            $t->text('note')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('coas');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('cash_accounts');
        Schema::dropIfExists('cash_receipts');
        Schema::dropIfExists('cash_payments');
        Schema::dropIfExists('bank_transfers');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('petty_cash_txns');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('journal_approvals');
        Schema::dropIfExists('period_closings');
        Schema::dropIfExists('intercompany_txns');
    }
};

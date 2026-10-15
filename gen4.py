"""Generator 4: finance/hr/pet/business/governance migrations."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)
HDR = """<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;
return new class extends Migration {
    public function up(): void {
"""
FTR = """    }
    public function down(): void {
%s
    }
};
"""
def mig(up, tables):
    downs = "\n".join([f"        Schema::dropIfExists('{t}');" for t in reversed(tables)])
    return HDR + up + FTR % downs

up_fin = """
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
"""
w("database/migrations/2026_10_15_160000_create_finance_tables.php", mig(up_fin,
  ['intercompany_txns','period_closings','journal_approvals','tax_rules','budget_lines','budgets','petty_cash_txns','expenses','bank_reconciliations','bank_transfers','cash_payments','cash_receipts','cash_accounts','journal_lines','journals','coas']))

up_hr = """
        Schema::create('positions', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('code', 20); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('employees', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('nip', 30); $t->string('name'); $t->string('gender', 10)->nullable();
            $t->date('birth_date')->nullable(); $t->string('phone', 30)->nullable();
            $t->text('address')->nullable(); $t->date('join_date')->nullable();
            $t->string('status', 20)->default('active'); $t->timestamps(); $t->softDeletes();
            $t->unique(['legal_entity_id', 'nip']);
        });
        Schema::create('employment_histories', function (Blueprint $t) {
            $t->id(); $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('event', 40); $t->string('detail', 255)->nullable(); $t->date('date');
            $t->timestamps();
        });
        Schema::create('shifts', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 20); $t->string('name'); $t->time('start_time'); $t->time('end_time');
            $t->timestamps(); $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('attendances', function (Blueprint $t) {
            $t->id(); $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('date'); $t->time('check_in')->nullable(); $t->time('check_out')->nullable();
            $t->string('status', 20)->default('present'); $t->timestamps();
            $t->unique(['employee_id', 'date']);
        });
        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id(); $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('from_date'); $t->date('to_date'); $t->string('kind', 30)->default('annual');
            $t->text('reason')->nullable(); $t->string('status', 20)->default('pending');
            $t->timestamps();
        });
        Schema::create('leave_balances', function (Blueprint $t) {
            $t->id(); $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('year', 4); $t->integer('quota')->default(12); $t->integer('used')->default(0);
            $t->timestamps(); $t->unique(['employee_id', 'year']);
        });
        Schema::create('overtimes', function (Blueprint $t) {
            $t->id(); $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('date'); $t->decimal('hours', 5, 2); $t->decimal('amount', 18, 2)->default(0);
            $t->string('status', 20)->default('pending'); $t->timestamps();
        });
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('name', 30); $t->date('start_date'); $t->date('end_date');
            $t->string('status', 20)->default('open'); $t->timestamps();
        });
        Schema::create('payroll_components', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name'); $t->string('kind', 20);
            $t->string('formula', 255)->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('payrolls', function (Blueprint $t) {
            $t->id(); $t->foreignId('payroll_period_id')->constrained('payroll_periods');
            $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->string('status', 20)->default('draft');
            $t->decimal('grand_total', 18, 2)->default(0);
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('payroll_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees');
            $t->decimal('gross', 18, 2)->default(0); $t->decimal('deduction', 18, 2)->default(0);
            $t->decimal('net', 18, 2); $t->text('breakdown')->nullable(); $t->timestamps();
            $t->unique(['payroll_id', 'employee_id']);
        });
        Schema::create('payslips', function (Blueprint $t) {
            $t->id(); $t->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees');
            $t->string('number', 60)->unique(); $t->decimal('net', 18, 2); $t->timestamps();
        });
        Schema::create('employee_documents', function (Blueprint $t) {
            $t->id(); $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->string('doc_type', 40); $t->string('file_path', 255); $t->timestamps();
        });
"""
w("database/migrations/2026_10_15_170000_create_hr_tables.php", mig(up_hr,
  ['employee_documents','payslips','payroll_lines','payrolls','payroll_components','payroll_periods','overtimes','leave_balances','leave_requests','attendances','shifts','employment_histories','employees','positions']))

up_pet = """
        Schema::create('pet_owners', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('name'); $t->string('phone', 30)->nullable(); $t->string('email', 120)->nullable();
            $t->text('address')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('pets', function (Blueprint $t) {
            $t->id(); $t->foreignId('pet_owner_id')->constrained('pet_owners')->cascadeOnDelete();
            $t->string('name'); $t->string('species', 30)->default('cat'); $t->string('breed', 60)->nullable();
            $t->string('gender', 10)->nullable(); $t->date('birth_date')->nullable();
            $t->decimal('weight', 8, 2)->nullable(); $t->string('color', 40)->nullable();
            $t->text('notes')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('vet_appointments', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('pet_id')->constrained('pets');
            $t->dateTime('scheduled_at'); $t->foreignId('vet_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('scheduled'); $t->text('complaint')->nullable(); $t->timestamps();
        });
        Schema::create('consultations', function (Blueprint $t) {
            $t->id(); $t->foreignId('vet_appointment_id')->constrained('vet_appointments')->cascadeOnDelete();
            $t->foreignId('pet_id')->constrained('pets');
            $t->text('clinical_notes')->nullable(); $t->string('diagnosis', 255)->nullable();
            $t->text('treatment_plan')->nullable();
            $t->foreignId('vet_id')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('treatments', function (Blueprint $t) {
            $t->id(); $t->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $t->string('action', 150); $t->decimal('qty', 10, 2)->default(1);
            $t->decimal('price', 18, 2)->default(0); $t->timestamps();
        });
        Schema::create('grooming_services', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name'); $t->decimal('price', 18, 2);
            $t->integer('duration_min')->default(60); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('grooming_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('pet_id')->constrained('pets');
            $t->foreignId('grooming_service_id')->constrained('grooming_services');
            $t->foreignId('groomer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('queued'); $t->text('checklist')->nullable();
            $t->text('before_condition')->nullable(); $t->decimal('grand_total', 18, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('boarding_rooms', function (Blueprint $t) {
            $t->id(); $t->foreignId('branch_id')->constrained('branches');
            $t->string('code', 20); $t->string('name'); $t->integer('capacity')->default(1);
            $t->decimal('rate', 18, 2)->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['branch_id', 'code']);
        });
        Schema::create('boarding_reservations', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique();
            $t->foreignId('pet_id')->constrained('pets');
            $t->foreignId('boarding_room_id')->constrained('boarding_rooms');
            $t->date('check_in'); $t->date('check_out');
            $t->text('feeding')->nullable(); $t->text('medication_note')->nullable();
            $t->decimal('deposit', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->string('status', 20)->default('reserved'); $t->timestamps();
        });
        Schema::create('boarding_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('boarding_reservation_id')->constrained('boarding_reservations')->cascadeOnDelete();
            $t->date('date'); $t->text('activity'); $t->string('incident', 255)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('plate', 20)->unique(); $t->string('name'); $t->integer('capacity')->default(4);
            $t->string('status', 20)->default('active'); $t->timestamps();
        });
        Schema::create('transport_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('pet_id')->constrained('pets');
            $t->string('pickup_address', 255); $t->string('delivery_address', 255);
            $t->dateTime('scheduled_at'); $t->decimal('price', 18, 2)->default(0);
            $t->string('status', 20)->default('ordered'); $t->timestamps();
        });
        Schema::create('trips', function (Blueprint $t) {
            $t->id(); $t->foreignId('transport_order_id')->constrained('transport_orders')->cascadeOnDelete();
            $t->foreignId('vehicle_id')->constrained('vehicles');
            $t->foreignId('driver_id')->constrained('users');
            $t->dateTime('depart_at')->nullable(); $t->dateTime('arrive_at')->nullable();
            $t->string('status', 20)->default('assigned');
            $t->decimal('fuel_cost', 18, 2)->default(0); $t->decimal('toll_cost', 18, 2)->default(0);
            $t->text('handover_proof')->nullable(); $t->timestamps();
        });
"""
w("database/migrations/2026_10_15_180000_create_pet_transport_tables.php", mig(up_pet,
  ['trips','transport_orders','vehicles','boarding_logs','boarding_reservations','boarding_rooms','grooming_orders','grooming_services','treatments','consultations','vet_appointments','pets','pet_owners']))

up_biz = """
        Schema::create('collections', function (Blueprint $t) {
            $t->id(); $t->foreignId('brand_id')->constrained('brands');
            $t->string('code', 30); $t->string('name'); $t->string('season', 30)->nullable();
            $t->timestamps(); $t->unique(['brand_id', 'code']);
        });
        Schema::create('designs', function (Blueprint $t) {
            $t->id(); $t->foreignId('collection_id')->nullable()->constrained('collections')->nullOnDelete();
            $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name'); $t->string('status', 20)->default('draft');
            $t->timestamps(); $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('design_versions', function (Blueprint $t) {
            $t->id(); $t->foreignId('design_id')->constrained('designs')->cascadeOnDelete();
            $t->integer('version'); $t->string('file_path', 255)->nullable();
            $t->string('print_placement', 80)->nullable(); $t->string('print_size', 40)->nullable();
            $t->string('status', 20)->default('draft');
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
            $t->unique(['design_id', 'version']);
        });
        Schema::create('boms', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('product_id')->constrained('products');
            $t->integer('version')->default(1); $t->string('status', 20)->default('draft');
            $t->timestamps(); $t->unique(['product_id', 'version']);
        });
        Schema::create('bom_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('bom_id')->constrained('boms')->cascadeOnDelete();
            $t->foreignId('material_id')->constrained('products');
            $t->decimal('qty', 18, 4); $t->timestamps();
        });
        Schema::create('work_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('bom_id')->constrained('boms');
            $t->integer('bom_version_locked'); $t->decimal('qty_planned', 18, 4);
            $t->decimal('qty_good', 18, 4)->default(0); $t->decimal('qty_reject', 18, 4)->default(0);
            $t->string('status', 20)->default('released'); $t->timestamps();
        });
        Schema::create('work_order_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $t->foreignId('material_id')->constrained('products');
            $t->decimal('qty_required', 18, 4); $t->decimal('qty_issued', 18, 4)->default(0); $t->timestamps();
        });
        Schema::create('thrift_lots', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->decimal('acquisition_cost', 18, 2); $t->string('status', 20)->default('received');
            $t->timestamps();
        });
        Schema::create('thrift_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('thrift_lot_id')->constrained('thrift_lots')->cascadeOnDelete();
            $t->string('code', 40)->unique(); $t->string('name');
            $t->string('grade', 10)->default('B'); $t->string('condition_note', 255)->nullable();
            $t->decimal('cost_share', 18, 2)->default(0); $t->decimal('process_cost', 18, 2)->default(0);
            $t->decimal('price', 18, 2)->default(0);
            $t->string('status', 20)->default('sorted');
            $t->foreignId('rack_id')->nullable()->constrained('bins')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('live_sessions', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('title'); $t->dateTime('scheduled_at');
            $t->foreignId('host_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('scheduled'); $t->timestamps();
        });
        Schema::create('live_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('live_session_id')->constrained('live_sessions')->cascadeOnDelete();
            $t->foreignId('thrift_item_id')->constrained('thrift_items');
            $t->foreignId('customer_id')->constrained('customers');
            $t->decimal('price', 18, 2); $t->dateTime('pay_deadline')->nullable();
            $t->string('status', 20)->default('reserved'); $t->timestamps();
            $t->unique(['thrift_item_id']);
        });
        Schema::create('studio_projects', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('customer_id')->constrained('customers');
            $t->string('title'); $t->text('brief')->nullable();
            $t->string('status', 20)->default('brief');
            $t->decimal('price', 18, 2)->default(0); $t->decimal('cost', 18, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('studio_deliverables', function (Blueprint $t) {
            $t->id(); $t->foreignId('studio_project_id')->constrained('studio_projects')->cascadeOnDelete();
            $t->string('title'); $t->integer('version')->default(1);
            $t->string('file_path', 255)->nullable(); $t->string('status', 20)->default('draft');
            $t->timestamps();
        });
        Schema::create('studio_approvals', function (Blueprint $t) {
            $t->id(); $t->foreignId('studio_deliverable_id')->constrained('studio_deliverables')->cascadeOnDelete();
            $t->string('decision', 20); $t->text('note')->nullable();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('rental_catalogs', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name'); $t->decimal('rate', 18, 2);
            $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('rental_units', function (Blueprint $t) {
            $t->id(); $t->foreignId('rental_catalog_id')->constrained('rental_catalogs')->cascadeOnDelete();
            $t->string('code', 40)->unique(); $t->string('size', 20)->nullable();
            $t->string('condition_note', 255)->nullable(); $t->string('status', 20)->default('available');
            $t->timestamps();
        });
        Schema::create('rental_reservations', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique();
            $t->foreignId('rental_unit_id')->constrained('rental_units');
            $t->foreignId('customer_id')->constrained('customers');
            $t->date('start_date'); $t->date('end_date');
            $t->decimal('rate', 18, 2); $t->decimal('deposit', 18, 2)->default(0);
            $t->string('status', 20)->default('reserved'); $t->timestamps();
        });
        Schema::create('rental_txns', function (Blueprint $t) {
            $t->id(); $t->foreignId('rental_reservation_id')->constrained('rental_reservations')->cascadeOnDelete();
            $t->string('kind', 30); $t->decimal('amount', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->timestamps();
        });
        Schema::create('campaigns', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('name'); $t->date('start_date')->nullable(); $t->date('end_date')->nullable();
            $t->decimal('budget', 18, 2)->default(0); $t->string('status', 20)->default('draft');
            $t->timestamps();
        });
        Schema::create('vouchers', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 40)->unique(); $t->string('name');
            $t->decimal('value', 18, 2); $t->decimal('min_txn', 18, 2)->default(0);
            $t->date('valid_from')->nullable(); $t->date('valid_to')->nullable();
            $t->integer('quota')->default(0); $t->integer('used')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('loyalty_points', function (Blueprint $t) {
            $t->id(); $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->integer('points')->default(0); $t->string('ref_type', 40)->nullable();
            $t->unsignedBigInteger('ref_id')->nullable(); $t->timestamps();
        });
        Schema::create('memberships', function (Blueprint $t) {
            $t->id(); $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->string('tier', 20)->default('silver'); $t->date('joined_at')->nullable();
            $t->date('expires_at')->nullable(); $t->timestamps();
        });
        Schema::create('approval_matrices', function (Blueprint $t) {
            $t->id(); $t->string('doc_type', 40);
            $t->foreignId('legal_entity_id')->nullable()->constrained('legal_entities')->nullOnDelete();
            $t->integer('level')->default(1); $t->decimal('min_amount', 18, 2)->default(0);
            $t->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $t->boolean('allow_self_approve')->default(false); $t->timestamps();
            $t->unique(['doc_type', 'legal_entity_id', 'level'], 'appr_matrix_unique');
        });
        Schema::create('approvals', function (Blueprint $t) {
            $t->id(); $t->string('doc_type', 40); $t->unsignedBigInteger('doc_id');
            $t->integer('level')->default(1); $t->string('status', 20)->default('pending');
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('note')->nullable(); $t->timestamps();
            $t->index(['doc_type', 'doc_id']);
        });
        Schema::create('approval_histories', function (Blueprint $t) {
            $t->id(); $t->foreignId('approval_id')->constrained('approvals')->cascadeOnDelete();
            $t->string('action', 20); $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('note')->nullable(); $t->timestamps();
        });
"""
w("database/migrations/2026_10_15_190000_create_business_governance_tables.php", mig(up_biz,
  ['approval_histories','approvals','approval_matrices','memberships','loyalty_points','vouchers','campaigns','rental_txns','rental_reservations','rental_units','rental_catalogs','studio_approvals','studio_deliverables','studio_projects','live_orders','live_sessions','thrift_items','thrift_lots','work_order_lines','work_orders','bom_lines','boms','design_versions','designs','collections']))
print("migrations part C done")

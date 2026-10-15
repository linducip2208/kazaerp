<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

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
    }
    public function down(): void {
        Schema::dropIfExists('positions');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('employment_histories');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('overtimes');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('payroll_components');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('payroll_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('employee_documents');
    }
};

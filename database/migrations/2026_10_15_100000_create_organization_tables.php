<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('holdings', function (Blueprint $t) {
            $t->id(); $t->string('code', 20)->unique(); $t->string('name');
            $t->text('address')->nullable(); $t->string('phone', 30)->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('legal_entities', function (Blueprint $t) {
            $t->id(); $t->foreignId('holding_id')->constrained('holdings');
            $t->string('code', 20)->unique(); $t->string('name');
            $t->string('npwp', 30)->nullable(); $t->text('address')->nullable();
            $t->string('phone', 30)->nullable(); $t->string('email', 100)->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
            $t->index('holding_id');
        });
        Schema::create('entity_ownerships', function (Blueprint $t) {
            $t->id(); $t->foreignId('parent_id')->constrained('legal_entities');
            $t->foreignId('child_id')->constrained('legal_entities');
            $t->decimal('pct', 5, 2)->default(100); $t->timestamps();
            $t->unique(['parent_id', 'child_id']);
        });
        Schema::create('brands', function (Blueprint $t) {
            $t->id(); $t->foreignId('holding_id')->constrained('holdings');
            $t->string('code', 20)->unique(); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('business_units', function (Blueprint $t) {
            $t->id(); $t->foreignId('holding_id')->constrained('holdings');
            $t->foreignId('legal_entity_id')->nullable()->constrained('legal_entities')->nullOnDelete();
            $t->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $t->string('code', 20)->unique(); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $t->string('code', 20); $t->string('name'); $t->text('address')->nullable();
            $t->string('phone', 30)->nullable(); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->softDeletes();
            $t->unique(['legal_entity_id', 'code']); $t->index('brand_id');
        });
        Schema::create('locations', function (Blueprint $t) {
            $t->id(); $t->foreignId('branch_id')->constrained('branches');
            $t->string('code', 20); $t->string('name'); $t->string('kind', 20)->default('store');
            $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['branch_id', 'code']);
        });
        Schema::create('warehouses', function (Blueprint $t) {
            $t->id(); $t->foreignId('branch_id')->constrained('branches');
            $t->string('code', 20); $t->string('name'); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->unique(['branch_id', 'code']);
        });
        Schema::create('departments', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 20); $t->string('name'); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('cost_centers', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 20); $t->string('name'); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('profit_centers', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 20); $t->string('name'); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('fiscal_years', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('name', 20); $t->date('start_date'); $t->date('end_date');
            $t->boolean('is_closed')->default(false); $t->timestamps();
            $t->unique(['legal_entity_id', 'name']);
        });
        Schema::create('fiscal_periods', function (Blueprint $t) {
            $t->id(); $t->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('name', 20); $t->date('start_date'); $t->date('end_date');
            $t->boolean('is_closed')->default(false); $t->timestamps();
            $t->index(['legal_entity_id', 'start_date', 'end_date']);
        });
        Schema::create('bank_accounts', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('bank_name', 60); $t->string('account_no', 40); $t->string('holder', 100);
            $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'account_no']);
        });
        Schema::create('company_configs', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('key', 80); $t->text('value')->nullable(); $t->timestamps();
            $t->unique(['legal_entity_id', 'key']);
        });
        Schema::create('legal_documents', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('doc_type', 40); $t->string('number', 80); $t->date('issued_at')->nullable();
            $t->date('expires_at')->nullable(); $t->string('file_path', 255)->nullable();
            $t->timestamps();
        });

    }
    public function down(): void {
        Schema::dropIfExists('holdings');
        Schema::dropIfExists('legal_entities');
        Schema::dropIfExists('entity_ownerships');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('business_units');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('profit_centers');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('company_configs');
        Schema::dropIfExists('legal_documents');
    }
};

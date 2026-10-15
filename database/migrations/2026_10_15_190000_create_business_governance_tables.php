<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

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
    }
    public function down(): void {
        Schema::dropIfExists('collections');
        Schema::dropIfExists('designs');
        Schema::dropIfExists('design_versions');
        Schema::dropIfExists('boms');
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('work_order_lines');
        Schema::dropIfExists('thrift_lots');
        Schema::dropIfExists('thrift_items');
        Schema::dropIfExists('live_sessions');
        Schema::dropIfExists('live_orders');
        Schema::dropIfExists('studio_projects');
        Schema::dropIfExists('studio_deliverables');
        Schema::dropIfExists('studio_approvals');
        Schema::dropIfExists('rental_catalogs');
        Schema::dropIfExists('rental_units');
        Schema::dropIfExists('rental_reservations');
        Schema::dropIfExists('rental_txns');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('approval_matrices');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('approval_histories');
    }
};

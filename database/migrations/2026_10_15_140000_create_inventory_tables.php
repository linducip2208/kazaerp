<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('bins', function (Blueprint $t) {
            $t->id(); $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->string('code', 30); $t->string('name')->nullable(); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->unique(['warehouse_id', 'code']);
        });
        Schema::create('batches', function (Blueprint $t) {
            $t->id(); $t->foreignId('product_id')->constrained('products');
            $t->string('lot_no', 60); $t->date('produced_at')->nullable(); $t->date('expired_at')->nullable();
            $t->string('qc_status', 20)->default('pending'); $t->timestamps();
            $t->unique(['product_id', 'lot_no']);
        });
        Schema::create('stock_ledgers', function (Blueprint $t) {
            $t->id(); $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $t->decimal('qty', 18, 4); $t->decimal('unit_cost', 18, 2)->default(0);
            $t->string('ref_type', 40); $t->unsignedBigInteger('ref_id');
            $t->string('note', 200)->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['warehouse_id', 'product_id']); $t->index(['ref_type', 'ref_id']);
        });
        Schema::create('stock_balances', function (Blueprint $t) {
            $t->id(); $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $t->decimal('qty_on_hand', 18, 4)->default(0); $t->decimal('qty_reserved', 18, 4)->default(0);
            $t->timestamps(); $t->unique(['warehouse_id', 'product_id', 'variant_id', 'batch_id'], 'stock_bal_unique');
        });
        Schema::create('goods_receipts', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->string('ref_type', 40)->nullable(); $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('status', 20)->default('draft'); $t->text('note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('goods_receipt_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $t->decimal('qty_ordered', 18, 4)->default(0); $t->decimal('qty_received', 18, 4);
            $t->decimal('unit_cost', 18, 2)->default(0); $t->timestamps();
        });
        Schema::create('goods_issues', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->string('ref_type', 40)->nullable(); $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('status', 20)->default('draft'); $t->text('note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('goods_issue_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_issue_id')->constrained('goods_issues')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $t->decimal('qty', 18, 4); $t->timestamps();
        });
        Schema::create('stock_transfers', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('from_warehouse_id')->constrained('warehouses');
            $t->foreignId('to_warehouse_id')->constrained('warehouses');
            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('stock_transfer_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty_sent', 18, 4)->default(0); $t->decimal('qty_received', 18, 4)->default(0);
            $t->timestamps();
        });
        Schema::create('stock_reservations', function (Blueprint $t) {
            $t->id(); $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('product_id')->constrained('products');
            $t->string('ref_type', 40); $t->unsignedBigInteger('ref_id');
            $t->decimal('qty', 18, 4); $t->string('status', 20)->default('active');
            $t->timestamps(); $t->index(['ref_type', 'ref_id']);
        });
        Schema::create('stock_counts', function (Blueprint $t) {
            $t->id(); $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->date('date'); $t->string('status', 20)->default('draft');
            $t->timestamps();
        });
        Schema::create('stock_count_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty_system', 18, 4)->default(0); $t->decimal('qty_actual', 18, 4);
            $t->timestamps();
        });
        Schema::create('stock_adjustments', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('warehouse_id')->constrained('warehouses');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->string('kind', 20)->default('adjustment'); $t->string('status', 20)->default('draft');
            $t->text('reason')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('stock_adjustment_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty', 18, 4); $t->decimal('unit_cost', 18, 2)->default(0); $t->timestamps();
        });
        Schema::create('serials', function (Blueprint $t) {
            $t->id(); $t->foreignId('product_id')->constrained('products');
            $t->string('serial_no', 80)->unique(); $t->string('status', 20)->default('in_stock');
            $t->timestamps();
        });
        Schema::create('quality_checks', function (Blueprint $t) {
            $t->id(); $t->string('ref_type', 40); $t->unsignedBigInteger('ref_id');
            $t->string('result', 20)->default('pending'); $t->text('note')->nullable();
            $t->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
            $t->index(['ref_type', 'ref_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('bins');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('stock_ledgers');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_issues');
        Schema::dropIfExists('goods_issue_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('serials');
        Schema::dropIfExists('quality_checks');
    }
};

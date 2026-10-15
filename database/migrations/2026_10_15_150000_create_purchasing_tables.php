<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('purchase_requisitions', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->string('status', 20)->default('draft'); $t->boolean('is_emergency')->default(false);
            $t->text('emergency_reason')->nullable();
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('purchase_requisition_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty', 18, 4); $t->decimal('est_price', 18, 2)->default(0); $t->timestamps();
        });
        Schema::create('rfqs', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('purchase_requisition_id')->nullable()->constrained('purchase_requisitions')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date'); $t->date('deadline')->nullable();
            $t->string('status', 20)->default('open'); $t->timestamps();
        });
        Schema::create('rfq_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products'); $t->decimal('qty', 18, 4); $t->timestamps();
        });
        Schema::create('vendor_quotes', function (Blueprint $t) {
            $t->id(); $t->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->decimal('price', 18, 2); $t->integer('lead_days')->default(0);
            $t->boolean('is_winner')->default(false); $t->timestamps();
        });
        Schema::create('purchase_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->foreignId('requisition_id')->nullable()->constrained('purchase_requisitions')->nullOnDelete();
            $t->integer('version')->default(1); $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('tax', 18, 2)->default(0);
            $t->decimal('grand_total', 18, 2)->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('purchase_order_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty', 18, 4); $t->decimal('qty_received', 18, 4)->default(0);
            $t->decimal('price', 18, 2); $t->decimal('line_total', 18, 2); $t->timestamps();
        });
        Schema::create('po_versions', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $t->integer('version'); $t->text('snapshot'); $t->text('change_note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('vendor_invoices', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->string('number', 60)->unique(); $t->string('vendor_ref', 80)->nullable();
            $t->date('date'); $t->date('due_date')->nullable();
            $t->decimal('grand_total', 18, 2); $t->decimal('paid_total', 18, 2)->default(0);
            $t->string('status', 20)->default('draft'); $t->string('match_status', 20)->default('pending');
            $t->timestamps(); $t->unique(['supplier_id', 'vendor_ref'], 'vendor_inv_dup_guard');
        });
        Schema::create('vendor_invoice_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('vendor_invoice_id')->constrained('vendor_invoices')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty', 18, 4); $t->decimal('price', 18, 2); $t->decimal('line_total', 18, 2); $t->timestamps();
        });
        Schema::create('vendor_evaluations', function (Blueprint $t) {
            $t->id(); $t->foreignId('supplier_id')->constrained('suppliers');
            $t->integer('quality')->default(0); $t->integer('delivery')->default(0); $t->integer('price_score')->default(0);
            $t->text('note')->nullable(); $t->timestamps();
        });
        Schema::create('purchase_returns', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $t->foreignId('supplier_id')->constrained('suppliers');
            $t->string('number', 60)->unique(); $t->date('date'); $t->string('status', 20)->default('draft');
            $t->decimal('grand_total', 18, 2)->default(0); $t->timestamps();
        });
        Schema::create('purchase_return_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->decimal('qty', 18, 4); $t->decimal('price', 18, 2)->default(0); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchase_requisitions');
        Schema::dropIfExists('purchase_requisition_lines');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('rfq_lines');
        Schema::dropIfExists('vendor_quotes');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('po_versions');
        Schema::dropIfExists('vendor_invoices');
        Schema::dropIfExists('vendor_invoice_lines');
        Schema::dropIfExists('vendor_evaluations');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_return_lines');
    }
};

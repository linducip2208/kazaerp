<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('doc_sequences', function (Blueprint $t) {
            $t->id(); $t->string('seq_key', 160)->unique(); $t->string('doc_type', 30);
            $t->foreignId('legal_entity_id')->nullable()->constrained('legal_entities')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('year', 4); $t->string('month', 2); $t->unsignedBigInteger('last_seq')->default(0);
            $t->timestamps();
        });

        Schema::create('quotations', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('customer_id')->constrained('customers');
            $t->foreignId('channel_id')->nullable()->constrained('sales_channels')->nullOnDelete();
            $t->date('valid_until')->nullable(); $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('quotation_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2); $t->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date'); $t->foreignId('customer_id')->constrained('customers'); $t->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete(); $t->decimal('paid_total', 18, 2)->default(0);
            $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'status']);
        });
        Schema::create('sales_order_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2); $t->timestamps();
        });

        Schema::create('delivery_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date'); $t->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete(); $t->foreignId('customer_id')->constrained('customers'); $t->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'status']);
        });
        Schema::create('delivery_order_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2); $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date'); $t->foreignId('customer_id')->constrained('customers'); $t->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete(); $t->date('due_date')->nullable(); $t->decimal('paid_total', 18, 2)->default(0); $t->string('pay_status', 20)->default('unpaid');
            $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'status']);
        });
        Schema::create('invoice_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2); $t->timestamps();
        });

        Schema::create('credit_notes', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date'); $t->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete(); $t->foreignId('customer_id')->constrained('customers');
            $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'status']);
        });
        Schema::create('credit_note_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('credit_note_id')->constrained('credit_notes')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2); $t->timestamps();
        });

        Schema::create('sales_returns', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date'); $t->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete(); $t->foreignId('customer_id')->constrained('customers');
            $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'status']);
        });
        Schema::create('sales_return_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2); $t->timestamps();
        });

        Schema::create('customer_deposits', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('customer_id')->constrained('customers');
            $t->decimal('amount', 18, 2); $t->decimal('used_amount', 18, 2)->default(0);
            $t->string('status', 20)->default('active'); $t->timestamps();
        });
        Schema::create('sales_payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('invoice_id')->constrained('invoices');
            $t->foreignId('customer_id')->constrained('customers');
            $t->string('method', 30)->default('cash'); $t->decimal('amount', 18, 2);
            $t->string('reference', 80)->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('sales_commissions', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('sales_order_id')->constrained('sales_orders');
            $t->foreignId('user_id')->constrained('users');
            $t->decimal('amount', 18, 2)->default(0); $t->string('status', 20)->default('pending');
            $t->timestamps();
        });
        Schema::create('pos_shifts', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('cashier_id')->constrained('users');
            $t->dateTime('opened_at'); $t->dateTime('closed_at')->nullable();
            $t->decimal('opening_cash', 18, 2)->default(0); $t->decimal('closing_cash', 18, 2)->default(0);
            $t->decimal('system_total', 18, 2)->default(0); $t->decimal('difference', 18, 2)->default(0);
            $t->string('status', 20)->default('open'); $t->timestamps();
        });
        Schema::create('pos_sales', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('shift_id')->nullable()->constrained('pos_shifts')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('status', 20)->default('paid');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->string('pay_method', 30)->default('cash');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('pos_sale_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('pos_sale_id')->constrained('pos_sales')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('line_total', 18, 2); $t->timestamps();
        });
        Schema::create('cash_movements', function (Blueprint $t) {
            $t->id(); $t->foreignId('shift_id')->constrained('pos_shifts')->cascadeOnDelete();
            $t->string('kind', 20); $t->decimal('amount', 18, 2); $t->string('reason', 150)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('doc_status_histories', function (Blueprint $t) {
            $t->id(); $t->string('doc_type', 40); $t->unsignedBigInteger('doc_id');
            $t->string('from_status', 30)->nullable(); $t->string('to_status', 30);
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('note')->nullable(); $t->timestamps();
            $t->index(['doc_type', 'doc_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('doc_sequences');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('delivery_order_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('credit_note_lines');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('sales_return_lines');
        Schema::dropIfExists('customer_deposits');
        Schema::dropIfExists('sales_payments');
        Schema::dropIfExists('sales_commissions');
        Schema::dropIfExists('pos_shifts');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_sale_lines');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('doc_status_histories');
    }
};

"""Generator 3: sales/inventory/purchasing/finance/hr/pet/business migrations."""
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

HDR_DOC = """
        Schema::create('doc_sequences', function (Blueprint $t) {
            $t->id(); $t->string('seq_key', 160)->unique(); $t->string('doc_type', 30);
            $t->foreignId('legal_entity_id')->nullable()->constrained('legal_entities')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('year', 4); $t->string('month', 2); $t->unsignedBigInteger('last_seq')->default(0);
            $t->timestamps();
        });
"""
LINE = lambda h, l: f"""
        Schema::create('{h}', function (Blueprint $t) {{
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            %s
            $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'status']);
        }});
        Schema::create('{l}', function (Blueprint $t) {{
            $t->id(); $t->foreignId('%s')->constrained('{h}')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2);
            $t->timestamps();
        }});
"""

# ---------- sales ----------
up_sales = HDR_DOC
up_sales += """
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
"""
for h, l, fk, extra in [
    ("sales_orders", "sales_order_lines", "sales_order_id", "$t->foreignId('customer_id')->constrained('customers'); $t->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete(); $t->decimal('paid_total', 18, 2)->default(0);"),
    ("delivery_orders", "delivery_order_lines", "delivery_order_id", "$t->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete(); $t->foreignId('customer_id')->constrained('customers'); $t->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();"),
    ("invoices", "invoice_lines", "invoice_id", "$t->foreignId('customer_id')->constrained('customers'); $t->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete(); $t->date('due_date')->nullable(); $t->decimal('paid_total', 18, 2)->default(0); $t->string('pay_status', 20)->default('unpaid');"),
    ("credit_notes", "credit_note_lines", "credit_note_id", "$t->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete(); $t->foreignId('customer_id')->constrained('customers');"),
    ("sales_returns", "sales_return_lines", "sales_return_id", "$t->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete(); $t->foreignId('customer_id')->constrained('customers');"),
]:
    up_sales += f"""
        Schema::create('{h}', function (Blueprint $t) {{
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date'); {extra}
            $t->string('status', 20)->default('draft');
            $t->decimal('subtotal', 18, 2)->default(0); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->text('note')->nullable(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'status']);
        }});
        Schema::create('{l}', function (Blueprint $t) {{
            $t->id(); $t->foreignId('{fk}')->constrained('{h}')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('snapshot_name', 150); $t->decimal('qty', 18, 4);
            $t->decimal('price', 18, 2); $t->decimal('discount', 18, 2)->default(0);
            $t->decimal('tax_rate', 7, 4)->default(0); $t->decimal('line_total', 18, 2); $t->timestamps();
        }});
"""
up_sales += """
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
"""
w("database/migrations/2026_10_15_130000_create_sales_tables.php", mig(up_sales,
  ['doc_status_histories','cash_movements','pos_sale_lines','pos_sales','pos_shifts','sales_commissions','sales_payments','customer_deposits','sales_return_lines','sales_returns','credit_note_lines','credit_notes','invoice_lines','invoices','delivery_order_lines','delivery_orders','sales_order_lines','sales_orders','quotation_lines','quotations','doc_sequences']))

# ---------- inventory ----------
up_inv = """
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
"""
w("database/migrations/2026_10_15_140000_create_inventory_tables.php", mig(up_inv,
  ['quality_checks','serials','stock_adjustment_lines','stock_adjustments','stock_count_lines','stock_counts','stock_reservations','stock_transfer_lines','stock_transfers','goods_issue_lines','goods_issues','goods_receipt_lines','goods_receipts','stock_balances','stock_ledgers','batches','bins']))

# ---------- purchasing ----------
up_pur = """
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
"""
w("database/migrations/2026_10_15_150000_create_purchasing_tables.php", mig(up_pur,
  ['purchase_return_lines','purchase_returns','vendor_evaluations','vendor_invoice_lines','vendor_invoices','po_versions','purchase_order_lines','purchase_orders','vendor_quotes','rfq_lines','rfqs','purchase_requisition_lines','purchase_requisitions']))
print("migrations part B done")

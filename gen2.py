"""Generator 2: migrations."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

TMPL = """<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;
return new class extends Migration {
    public function up(): void {
%s
    }
    public function down(): void {
%s
    }
};
"""

def tbl(up_lines, tables):
    downs = "\n".join([f"        Schema::dropIfExists('{t}');" for t in reversed(tables)])
    return TMPL % (up_lines, downs)

# ---------- 2026_10_15_100000 organization ----------
up = """
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
"""
w("database/migrations/2026_10_15_100000_create_organization_tables.php",
  tbl(up, ['legal_documents','company_configs','bank_accounts','fiscal_periods','fiscal_years','profit_centers','cost_centers','departments','warehouses','locations','branches','business_units','brands','entity_ownerships','legal_entities','holdings']))

# ---------- 110000 identity ----------
up2 = """
        Schema::create('roles', function (Blueprint $t) {
            $t->id(); $t->string('name', 60)->unique(); $t->string('label', 100);
            $t->boolean('is_system')->default(false); $t->timestamps();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id(); $t->string('name', 100)->unique(); $t->string('group', 60);
            $t->string('label', 150); $t->timestamps();
        });
        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::create('role_user', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->primary(['role_id', 'user_id']);
        });
        Schema::create('user_scopes', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('scope_type', 30); $t->unsignedBigInteger('scope_id');
            $t->timestamps(); $t->unique(['user_id', 'scope_type', 'scope_id']);
            $t->index(['scope_type', 'scope_id']);
        });
        Schema::create('login_activities', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('email', 150)->nullable(); $t->string('ip', 45)->nullable();
            $t->string('agent', 255)->nullable(); $t->boolean('success')->default(false);
            $t->timestamp('created_at')->nullable(); $t->index(['user_id', 'created_at']);
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 40); $t->string('entity', 80); $t->unsignedBigInteger('record_id');
            $t->text('changes')->nullable(); $t->string('ip', 45)->nullable();
            $t->timestamps(); $t->index(['entity', 'record_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('password');
            $t->foreignId('holding_id')->nullable()->after('is_active')->constrained('holdings')->nullOnDelete();
            $t->foreignId('legal_entity_id')->nullable()->after('holding_id')->constrained('legal_entities')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->after('legal_entity_id')->constrained('branches')->nullOnDelete();
            $t->timestamp('last_login_at')->nullable();
        });
"""
down2 = """        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropConstrainedForeignId('legal_entity_id');
            $t->dropConstrainedForeignId('holding_id');
            $t->dropColumn(['is_active', 'last_login_at']);
        });
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('login_activities');
        Schema::dropIfExists('user_scopes');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
"""
w("database/migrations/2026_10_15_110000_create_identity_tables.php",
  "<?php\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\nreturn new class extends Migration {\n    public function up(): void {\n" + up2 + "    }\n    public function down(): void {\n" + down2 + "    }\n};\n")

# ---------- 120000 master + CRM ----------
up3 = """
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('kind', 20)->default('product'); $t->string('code', 30);
            $t->string('name'); $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
            $t->unique(['kind', 'code']);
        });
        Schema::create('uoms', function (Blueprint $t) {
            $t->id(); $t->string('code', 20)->unique(); $t->string('name'); $t->timestamps();
        });
        Schema::create('taxes', function (Blueprint $t) {
            $t->id(); $t->string('code', 20)->unique(); $t->string('name');
            $t->decimal('rate', 7, 4)->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('payment_terms', function (Blueprint $t) {
            $t->id(); $t->string('code', 20)->unique(); $t->string('name');
            $t->integer('days')->default(0); $t->timestamps();
        });
        Schema::create('sales_channels', function (Blueprint $t) {
            $t->id(); $t->string('code', 20)->unique(); $t->string('name'); $t->timestamps();
        });
        Schema::create('lead_sources', function (Blueprint $t) {
            $t->id(); $t->string('code', 20)->unique(); $t->string('name'); $t->timestamps();
        });
        Schema::create('tags', function (Blueprint $t) {
            $t->id(); $t->string('name', 60)->unique(); $t->timestamps();
        });
        Schema::create('taggables', function (Blueprint $t) {
            $t->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $t->string('taggable_type', 120); $t->unsignedBigInteger('taggable_id');
            $t->unique(['tag_id', 'taggable_type', 'taggable_id'], 'taggables_unique');
            $t->index(['taggable_type', 'taggable_id']);
        });
        Schema::create('addresses', function (Blueprint $t) {
            $t->id(); $t->string('owner_type', 80); $t->unsignedBigInteger('owner_id');
            $t->string('label', 40)->default('utama'); $t->text('street')->nullable();
            $t->string('city', 60)->nullable(); $t->string('province', 60)->nullable();
            $t->string('postal', 15)->nullable(); $t->string('phone', 30)->nullable();
            $t->timestamps(); $t->index(['owner_type', 'owner_id']);
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('code', 30); $t->string('name'); $t->string('kind', 20)->default('retail');
            $t->string('email', 120)->nullable(); $t->string('phone', 30)->nullable();
            $t->string('tier', 20)->default('retail'); $t->foreignId('sales_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
            $t->unique(['legal_entity_id', 'code']); $t->index('name');
        });
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name'); $t->string('email', 120)->nullable();
            $t->string('phone', 30)->nullable(); $t->string('bank_account', 60)->nullable();
            $t->integer('rating')->default(0); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->softDeletes(); $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('contacts', function (Blueprint $t) {
            $t->id(); $t->string('owner_type', 80); $t->unsignedBigInteger('owner_id');
            $t->string('name'); $t->string('role', 60)->nullable();
            $t->string('phone', 30)->nullable(); $t->string('email', 120)->nullable(); $t->timestamps();
            $t->index(['owner_type', 'owner_id']);
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $t->string('sku', 40); $t->string('barcode', 60)->nullable();
            $t->string('name'); $t->string('kind', 20)->default('goods');
            $t->decimal('cost_price', 18, 2)->default(0); $t->decimal('sell_price', 18, 2)->default(0);
            $t->decimal('wholesale_price', 18, 2)->default(0); $t->decimal('reseller_price', 18, 2)->default(0);
            $t->boolean('is_sellable')->default(true); $t->boolean('is_purchasable')->default(true);
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
            $t->unique(['legal_entity_id', 'sku']); $t->index('barcode'); $t->index('name');
        });
        Schema::create('product_variants', function (Blueprint $t) {
            $t->id(); $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $t->string('sku', 40); $t->string('option_name', 60)->nullable(); $t->string('option_value', 80)->nullable();
            $t->decimal('sell_price', 18, 2)->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['product_id', 'sku']);
        });
        Schema::create('price_lists', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name'); $t->date('valid_from')->nullable(); $t->date('valid_to')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('price_list_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products'); $t->decimal('price', 18, 2);
            $t->timestamps(); $t->unique(['price_list_id', 'product_id']);
        });
        Schema::create('notes', function (Blueprint $t) {
            $t->id(); $t->string('owner_type', 80); $t->unsignedBigInteger('owner_id');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('body'); $t->timestamps(); $t->index(['owner_type', 'owner_id']);
        });
        Schema::create('attachments', function (Blueprint $t) {
            $t->id(); $t->string('owner_type', 80); $t->unsignedBigInteger('owner_id');
            $t->string('file_path', 255); $t->string('file_name', 150); $t->string('mime', 80)->nullable();
            $t->unsignedBigInteger('size')->default(0); $t->integer('version')->default(1);
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['owner_type', 'owner_id']);
        });
        Schema::create('leads', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('name'); $t->string('phone', 30)->nullable(); $t->string('email', 120)->nullable();
            $t->foreignId('source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $t->string('stage', 30)->default('new'); $t->string('lost_reason', 150)->nullable();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->timestamps(); $t->index(['legal_entity_id', 'stage']);
        });
        Schema::create('opportunities', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('title'); $t->decimal('amount', 18, 2)->default(0);
            $t->string('stage', 30)->default('prospect'); $t->integer('probability')->default(10);
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('followups', function (Blueprint $t) {
            $t->id(); $t->string('owner_type', 80); $t->unsignedBigInteger('owner_id');
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('due_at')->nullable(); $t->string('status', 20)->default('open');
            $t->text('note')->nullable(); $t->dateTime('done_at')->nullable(); $t->timestamps();
            $t->index(['owner_type', 'owner_id']);
        });
        Schema::create('complaints', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('subject'); $t->text('body')->nullable(); $t->string('status', 20)->default('open');
            $t->text('resolution')->nullable(); $t->timestamps();
        });
"""
w("database/migrations/2026_10_15_120000_create_master_crm_tables.php",
  tbl(up3, ['complaints','followups','opportunities','leads','attachments','notes','price_list_lines','price_lists','product_variants','products','contacts','suppliers','customers','addresses','taggables','tags','lead_sources','sales_channels','payment_terms','taxes','uoms','categories']))
print("migrations part A done")

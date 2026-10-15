<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

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

    }
    public function down(): void {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('sales_channels');
        Schema::dropIfExists('lead_sources');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('price_list_lines');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('followups');
        Schema::dropIfExists('complaints');
    }
};

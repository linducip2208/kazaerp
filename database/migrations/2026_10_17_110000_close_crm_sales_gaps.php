<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CRM-04 (penawaran berversi + masa berlaku), CRM-05 (gabung pelanggan),
// CRM-06 (opt-out promosi). SEMUA aditif — tanpa ubah kolom/hapus data lama.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            $t->unsignedInteger('version')->default(1)->after('status');
        });
        Schema::create('quotation_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->json('snapshot'); // lines + subtotal/diskon/pajak/grand_total + valid_until
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('reason', 190)->nullable();
            $t->timestamps();
            $t->unique(['quotation_id', 'version']);
        });
        Schema::table('customers', function (Blueprint $t) {
            $t->boolean('marketing_opt_out')->default(false)->after('is_active');
            $t->foreignId('merged_into_customer_id')->nullable()->after('marketing_opt_out')
                ->constrained('customers')->nullOnDelete();
            $t->index('merged_into_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->dropForeign(['merged_into_customer_id']);
            $t->dropColumn(['marketing_opt_out', 'merged_into_customer_id']);
        });
        Schema::dropIfExists('quotation_versions');
        Schema::table('quotations', function (Blueprint $t) {
            $t->dropColumn('version');
        });
    }
};

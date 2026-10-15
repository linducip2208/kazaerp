<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_invoices', function (Blueprint $t) {
            $t->foreignId('branch_id')->nullable()->after('legal_entity_id')->constrained('branches')->nullOnDelete();
        });
        Schema::table('purchase_returns', function (Blueprint $t) {
            $t->foreignId('branch_id')->nullable()->after('legal_entity_id')->constrained('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_invoices', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
        });
        Schema::table('purchase_returns', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
        });
    }
};

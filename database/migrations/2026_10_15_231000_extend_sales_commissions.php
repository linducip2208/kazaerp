<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_commissions', function (Blueprint $t) {
            // invoice tanpa SO juga boleh punya komisi
            $t->foreignId('sales_order_id')->nullable()->change();
            $t->foreignId('invoice_id')->nullable()->after('sales_order_id')->constrained('invoices')->nullOnDelete();
            $t->decimal('basis_amount', 18, 2)->default(0)->after('amount');
            $t->decimal('rate_pct', 6, 2)->default(0)->after('basis_amount');
            $t->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $t->dateTime('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('sales_commissions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('invoice_id');
            $t->dropConstrainedForeignId('approved_by');
            $t->dropColumn(['basis_amount', 'rate_pct', 'approved_at']);
            $t->foreignId('sales_order_id')->nullable(false)->change();
        });
    }
};

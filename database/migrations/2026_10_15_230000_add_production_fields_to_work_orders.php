<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $t) {
            $t->foreignId('warehouse_id')->nullable()->after('bom_version_locked')->constrained('warehouses')->nullOnDelete();
            $t->decimal('standard_cost', 18, 2)->default(0)->after('qty_reject');
            $t->decimal('actual_cost', 18, 2)->default(0)->after('standard_cost');
            $t->decimal('variance_amount', 18, 2)->default(0)->after('actual_cost');
            $t->dateTime('started_at')->nullable()->after('variance_amount');
            $t->dateTime('completed_at')->nullable()->after('started_at');
        });
        Schema::table('work_order_lines', function (Blueprint $t) {
            $t->decimal('unit_cost', 18, 2)->default(0)->after('qty_issued');       // biaya standar (snapshot saat WO dibuat)
            $t->decimal('issued_unit_cost', 18, 2)->default(0)->after('unit_cost'); // biaya aktual saat issue
        });
    }

    public function down(): void
    {
        Schema::table('work_order_lines', function (Blueprint $t) {
            $t->dropColumn(['unit_cost', 'issued_unit_cost']);
        });
        Schema::table('work_orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('warehouse_id');
            $t->dropColumn(['standard_cost', 'actual_cost', 'variance_amount', 'started_at', 'completed_at']);
        });
    }
};

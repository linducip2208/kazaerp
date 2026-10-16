<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $t) {
            // Referensi idempoten dari klien (POS mobile/offline retry) — unik per entitas.
            $t->string('client_ref', 80)->nullable()->after('number');
            $t->unique(['legal_entity_id', 'client_ref'], 'pos_sales_client_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $t) {
            $t->dropUnique('pos_sales_client_ref_unique');
            $t->dropColumn('client_ref');
        });
    }
};

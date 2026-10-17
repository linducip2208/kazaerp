<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci idempotensi pembayaran anti double-submit (R1 FINALIZATION_AUDIT).
 * Aditif murni: kolom nullable + unique, tanpa mengubah kolom yang ada.
 * Baris lama (tanpa kunci) tetap valid; NULL tidak melanggar unique di MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_payments', function (Blueprint $t) {
            $t->string('idempotency_key', 64)->nullable()->after('reference');
            $t->unique('idempotency_key', 'sales_payments_idem_unique');
        });
        Schema::table('ap_payments', function (Blueprint $t) {
            $t->string('idempotency_key', 64)->nullable()->after('reference');
            $t->unique('idempotency_key', 'ap_payments_idem_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ap_payments', function (Blueprint $t) {
            $t->dropUnique('ap_payments_idem_unique');
            $t->dropColumn('idempotency_key');
        });
        Schema::table('sales_payments', function (Blueprint $t) {
            $t->dropUnique('sales_payments_idem_unique');
            $t->dropColumn('idempotency_key');
        });
    }
};

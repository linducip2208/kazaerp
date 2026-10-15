<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Akrual komisi sales saat invoice lunas.
 * Rate dibaca dari company_configs key `commission_rate_pct` per entitas (0 = nonaktif).
 * Basis = subtotal − diskon (di luar pajak). PENDING_BUSINESS_DECISION: rate resmi & skema per-SO.
 */
class CommissionService
{
    public static function accrue(int $invoiceId): ?int
    {
        $inv = DB::table('invoices')->where('id', $invoiceId)->first();
        if (!$inv) {
            return null;
        }
        $rate = (float) DB::table('company_configs')
            ->where(['legal_entity_id' => $inv->legal_entity_id, 'key' => 'commission_rate_pct'])
            ->value('value');
        if ($rate <= 0) {
            return null;
        }
        if (DB::table('sales_commissions')->where('invoice_id', $invoiceId)->exists()) {
            return null; // idempotent
        }
        $cust = DB::table('customers')->where('id', $inv->customer_id)->first();
        $userId = ($cust->sales_owner_id ?? null) ?: $inv->created_by;
        if (!$userId) {
            return null;
        }
        $basis = (float) $inv->subtotal - (float) $inv->discount;
        $amount = round($basis * $rate / 100, 2);
        if ($amount <= 0) {
            return null;
        }

        return (int) DB::table('sales_commissions')->insertGetId([
            'legal_entity_id' => $inv->legal_entity_id,
            'sales_order_id' => $inv->sales_order_id,
            'invoice_id' => $inv->id,
            'user_id' => $userId,
            'amount' => $amount,
            'basis_amount' => $basis,
            'rate_pct' => $rate,
            'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

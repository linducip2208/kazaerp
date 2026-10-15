<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VoucherService
{
    /** Validasi voucher → nominal diskon. Throw bila tidak eligible. */
    public static function validate(string $code, int $entityId, float $amount): array
    {
        $v = DB::table('vouchers')->where('code', $code)->first();
        if (! $v) {
            throw new \RuntimeException('Voucher tidak ditemukan.');
        }
        if (! $v->is_active) {
            throw new \RuntimeException('Voucher nonaktif.');
        }
        if ((int) $v->legal_entity_id !== $entityId) {
            throw new \RuntimeException('Voucher beda entitas.');
        }
        $now = now()->toDateString();
        if (($v->valid_from && $v->valid_from > $now) || ($v->valid_to && $v->valid_to < $now)) {
            throw new \RuntimeException('Voucher di luar periode aktif.');
        }
        if ((int) $v->quota > 0 && (int) $v->used >= (int) $v->quota) {
            throw new \RuntimeException('Kuota voucher habis.');
        }
        if ($amount < (float) $v->min_txn) {
            throw new \RuntimeException('Minimum transaksi '.number_format((float) $v->min_txn, 0).' untuk voucher ini.');
        }

        return ['id' => $v->id, 'discount' => min((float) $v->value, $amount)];
    }

    public static function consume(int $voucherId): void
    {
        DB::table('vouchers')->where('id', $voucherId)->update(['used' => DB::raw('used + 1')]);
    }

    /** Poin loyalty saat invoice lunas: 1 poin per kelipatan tarif (config promo.loyalty_rate, default 10000). */
    public static function accrueOnPaid(int $invoiceId): void
    {
        $inv = DB::table('invoices')->where('id', $invoiceId)->first();
        if (! $inv || $inv->pay_status !== 'paid') {
            return;
        }
        if (DB::table('loyalty_points')->where(['ref_type' => 'invoice', 'ref_id' => $invoiceId])->exists()) {
            return; // sekali saja
        }
        $rate = (float) (DB::table('company_configs')->where(['legal_entity_id' => $inv->legal_entity_id, 'key' => 'promo.loyalty_rate'])->value('value') ?: 10000);
        $points = (int) floor((float) $inv->grand_total / $rate);
        if ($points > 0) {
            DB::table('loyalty_points')->insert(['customer_id' => $inv->customer_id, 'points' => $points,
                'ref_type' => 'invoice', 'ref_id' => $invoiceId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}

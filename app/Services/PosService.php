<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PosService
{
    /** Buka shift kasir; satu user hanya boleh punya satu shift terbuka. */
    public static function openShift(int $eid, ?int $bid, int $userId, float $openingCash): int
    {
        if (DB::table('pos_shifts')->where(['cashier_id' => $userId, 'status' => 'open'])->exists()) {
            throw new \RuntimeException('Masih ada shift terbuka.');
        }
        if ($openingCash < 0) {
            throw new \RuntimeException('Kas awal tidak valid.');
        }

        return (int) DB::table('pos_shifts')->insertGetId([
            'legal_entity_id' => $eid, 'branch_id' => $bid, 'cashier_id' => $userId,
            'opened_at' => now(), 'opening_cash' => $openingCash, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public static function closeShift(int $userId, float $closingCash): array
    {
        $shift = DB::table('pos_shifts')->where(['cashier_id' => $userId, 'status' => 'open'])->first();
        if (!$shift) {
            throw new \RuntimeException('Tidak ada shift terbuka untuk ditutup.');
        }
        $sys = (float) DB::table('pos_sales')->where('shift_id', $shift->id)->sum('grand_total');
        $moves = (float) DB::table('cash_movements')->where('shift_id', $shift->id)->sum(DB::raw("CASE WHEN kind='in' THEN amount ELSE -amount END"));
        $expected = (float) $shift->opening_cash + $sys + $moves;
        $difference = $closingCash - $expected;
        DB::table('pos_shifts')->where('id', $shift->id)->update([
            'closed_at' => now(), 'closing_cash' => $closingCash, 'system_total' => $sys,
            'difference' => $difference, 'status' => 'closed', 'updated_at' => now(),
        ]);

        return ['shift_id' => $shift->id, 'system_total' => $sys, 'expected_cash' => $expected, 'difference' => $difference];
    }

    /** Checkout POS: harga mengikuti tier pelanggan (bila dipilih), stok keluar via ledger. */
    public static function checkout(int $eid, ?int $bid, int $userId, array $lines, string $payMethod, ?int $customerId = null): array
    {
        if (!in_array($payMethod, ['cash', 'transfer', 'qris'], true)) {
            throw new \RuntimeException('Metode bayar tidak dikenal.');
        }
        $shift = DB::table('pos_shifts')->where(['cashier_id' => $userId, 'status' => 'open'])->first();
        if (!$shift) {
            throw new \RuntimeException('Buka shift kasir terlebih dahulu.');
        }
        $tier = null;
        if ($customerId) {
            $cust = DB::table('customers')->where('id', $customerId)->where('legal_entity_id', $eid)->first();
            if (!$cust) {
                throw new \RuntimeException('Pelanggan tidak valid untuk entitas ini.');
            }
            $tier = $cust->tier;
        }

        return DB::transaction(function () use ($eid, $bid, $userId, $lines, $payMethod, $customerId, $tier, $shift) {
            $ent = \App\Models\Org\LegalEntity::find($eid);
            $br = \App\Models\Org\Branch::find($bid);
            $number = DocNumberService::next('POS', $eid, $bid, $ent?->code, $br?->code);
            $sub = 0; $lns = [];
            foreach ($lines as $l) {
                $p = DB::table('products')->where('id', $l['product_id'])->where('legal_entity_id', $eid)->first();
                if (!$p) {
                    throw new \RuntimeException('Produk tidak valid untuk entitas ini.');
                }
                if (!(bool) $p->is_sellable) {
                    throw new \RuntimeException('Produk tidak dijual: ' . $p->sku);
                }
                $qty = (float) $l['qty'];
                if ($qty <= 0) {
                    throw new \RuntimeException('Qty tidak valid untuk ' . $p->sku . '.');
                }
                $price = PriceService::forProduct(new \App\Models\Master\Product([
                    'sell_price' => $p->sell_price, 'wholesale_price' => $p->wholesale_price, 'reseller_price' => $p->reseller_price,
                ]), $tier);
                $lt = $qty * $price; $sub += $lt;
                $lns[] = ['product_id' => $p->id, 'snapshot_name' => $p->name, 'qty' => $qty, 'price' => $price, 'line_total' => $lt, 'created_at' => now(), 'updated_at' => now()];
            }
            $tax = $sub * 0.11; $grand = $sub + $tax;
            $saleId = (int) DB::table('pos_sales')->insertGetId([
                'legal_entity_id' => $eid, 'branch_id' => $bid, 'number' => $number, 'date' => now()->toDateString(),
                'shift_id' => $shift->id, 'customer_id' => $customerId, 'status' => 'paid',
                'subtotal' => $sub, 'discount' => 0, 'tax' => $tax, 'grand_total' => $grand,
                'pay_method' => $payMethod, 'created_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($lns as &$ln) {
                $ln['pos_sale_id'] = $saleId;
            }
            unset($ln);
            DB::table('pos_sale_lines')->insert($lns);
            $wh = DB::table('warehouses')->where('branch_id', $bid)->value('id') ?? DB::table('warehouses')->value('id');
            foreach ($lns as $ln) {
                if ($wh) {
                    StockService::move(['warehouse_id' => $wh, 'product_id' => $ln['product_id'], 'qty' => -1 * (float) $ln['qty'], 'ref_type' => 'pos_sale', 'ref_id' => $saleId]);
                }
            }

            return ['id' => $saleId, 'number' => $number, 'subtotal' => $sub, 'tax' => $tax, 'grand_total' => $grand];
        });
    }

    public static function currentShift(int $userId): ?object
    {
        return DB::table('pos_shifts')->where(['cashier_id' => $userId, 'status' => 'open'])->first();
    }
}

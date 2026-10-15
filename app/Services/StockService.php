<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class StockService {
    public static function move(array $p): void {
        // $p: warehouse_id, product_id, variant_id?, batch_id?, qty (+masuk/-keluar), ref_type, ref_id, unit_cost?, note?
        DB::transaction(function () use ($p) {
            $wh = (int) $p['warehouse_id']; $prod = (int) $p['product_id'];
            $var = $p['variant_id'] ?? null; $batch = $p['batch_id'] ?? null;
            $qty = (float) $p['qty'];
            if ($qty < 0) { // locking untuk pengeluaran agar cegah oversell
                $bal = DB::table('stock_balances')->where([
                    'warehouse_id' => $wh, 'product_id' => $prod,
                    'variant_id' => $var, 'batch_id' => $batch,
                ])->lockForUpdate()->first();
                $avail = $bal ? ((float) $bal->qty_on_hand - (float) $bal->qty_reserved) : 0;
                if ($avail + $qty < -0.0001) {
                    throw new \RuntimeException('Stok tidak mencukupi (tersedia: ' . $avail . ')');
                }
            }
            DB::table('stock_ledgers')->insert([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
                'qty' => $qty, 'unit_cost' => $p['unit_cost'] ?? 0, 'ref_type' => $p['ref_type'], 'ref_id' => $p['ref_id'],
                'note' => $p['note'] ?? null, 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $row = DB::table('stock_balances')->where([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
            ])->first();
            if ($row) {
                DB::table('stock_balances')->where('id', $row->id)->update([
                    'qty_on_hand' => (float) $row->qty_on_hand + $qty, 'updated_at' => now(),
                ]);
            } else {
                DB::table('stock_balances')->insert([
                    'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
                    'qty_on_hand' => $qty, 'qty_reserved' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }
    public static function reserve(int $wh, int $prod, float $qty, $var = null, $batch = null): void {
        DB::transaction(function () use ($wh, $prod, $qty, $var, $batch) {
            $row = DB::table('stock_balances')->where([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
            ])->lockForUpdate()->first();
            $avail = $row ? ((float) $row->qty_on_hand - (float) $row->qty_reserved) : 0;
            if ($avail < $qty) throw new \RuntimeException('Stok tersedia tidak cukup untuk reservasi.');
            if ($row) DB::table('stock_balances')->where('id', $row->id)->update(['qty_reserved' => (float) $row->qty_reserved + $qty]);
            else DB::table('stock_balances')->insert(['warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch, 'qty_on_hand' => 0, 'qty_reserved' => $qty, 'created_at' => now(), 'updated_at' => now()]);
        });
    }
    public static function release(int $wh, int $prod, float $qty, $var = null, $batch = null): void {
        DB::transaction(function () use ($wh, $prod, $qty, $var, $batch) {
            $row = DB::table('stock_balances')->where([
                'warehouse_id' => $wh, 'product_id' => $prod, 'variant_id' => $var, 'batch_id' => $batch,
            ])->lockForUpdate()->first();
            if ($row) DB::table('stock_balances')->where('id', $row->id)->update(['qty_reserved' => max(0, (float) $row->qty_reserved - $qty)]);
        });
    }
}

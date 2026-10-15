<?php

namespace App\Services;

use App\Models\Master\Customer;
use App\Models\Master\Product;

/**
 * Resolusi harga jual berdasarkan tier pelanggan.
 * Kolom produk: sell_price (retail), wholesale_price, reseller_price.
 * Fallback: reseller -> wholesale -> retail; wholesale -> retail.
 */
class PriceService
{
    public const TIERS = ['retail' => 'Retail', 'wholesale' => 'Wholesale', 'reseller' => 'Reseller'];

    public static function forProduct(Product $p, ?string $tier): float
    {
        $tier = strtolower(trim((string) $tier));
        $sell = (float) $p->sell_price;
        $whole = (float) ($p->wholesale_price ?? 0);
        $resel = (float) ($p->reseller_price ?? 0);

        return match ($tier) {
            'reseller' => $resel > 0 ? $resel : ($whole > 0 ? $whole : $sell),
            'wholesale' => $whole > 0 ? $whole : $sell,
            default => $sell,
        };
    }

    public static function forCustomer(Product $p, ?Customer $customer): float
    {
        return self::forProduct($p, $customer?->tier);
    }

    /** Label tier yang dipakai (untuk badge di form). */
    public static function effectiveTier(?string $tier): string
    {
        $tier = strtolower(trim((string) $tier));

        return array_key_exists($tier, self::TIERS) ? $tier : 'retail';
    }

    /** Daftar produk siap-render untuk komponen doc-lines-form dengan harga per tier. */
    public static function productOptions(int $entityId, ?string $tier): \Illuminate\Support\Collection
    {
        return Product::where('legal_entity_id', $entityId)->where('is_sellable', true)->orderBy('name')->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sell_price' => self::forProduct($p, $tier),
            ]);
    }
}

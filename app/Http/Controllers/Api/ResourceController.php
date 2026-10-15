<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResourceController extends Controller
{
    protected function eid(Request $r): ?int
    {
        return $r->get('legal_entity_id', $r->user()->legal_entity_id);
    }

    protected function guardEntity(Request $r, ?int $eid): void
    {
        $u = $r->user();
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor') && (int) $eid !== (int) $u->legal_entity_id) {
            abort(403, 'Di luar cakupan entitas.');
        }
    }

    public function invoices(Request $r)
    {
        if (! $r->user()->hasPermission('sales.view')) {
            abort(403);
        }
        $eid = $this->eid($r);
        $this->guardEntity($r, $eid);
        $rows = DB::table('invoices')->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.legal_entity_id', $eid)
            ->when($r->from, fn ($q) => $q->where('invoices.date', '>=', $r->from))
            ->when($r->to, fn ($q) => $q->where('invoices.date', '<=', $r->to))
            ->select('invoices.id', 'invoices.number', 'invoices.date', 'customers.name as customer', 'invoices.grand_total', 'invoices.paid_total', 'invoices.pay_status', 'invoices.status')
            ->orderByDesc('invoices.id')->limit(100)->get();

        return response()->json(['data' => $rows]);
    }

    public function stock(Request $r)
    {
        if (! $r->user()->hasPermission('inventory.view')) {
            abort(403);
        }
        $eid = $this->eid($r);
        $this->guardEntity($r, $eid);
        $rows = DB::table('stock_balances')->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->where('products.legal_entity_id', $eid)
            ->select('warehouses.code as warehouse', 'products.sku', 'products.name', 'stock_balances.qty_on_hand', 'stock_balances.qty_reserved')
            ->orderByDesc('stock_balances.id')->limit(200)->get();

        return response()->json(['data' => $rows]);
    }

    public function products(Request $r)
    {
        if (! $r->user()->hasPermission('crm.view')) {
            abort(403);
        }
        $eid = $this->eid($r);
        $this->guardEntity($r, $eid);
        $tier = null;
        if ($r->filled('customer_id')) {
            $cust = DB::table('customers')->where('id', (int) $r->customer_id)->where('legal_entity_id', $eid)->first();
            if (! $cust) {
                abort(404, 'Pelanggan tidak ditemukan pada entitas ini.');
            }
            $tier = $cust->tier;
        }
        $rows = DB::table('products')->where('legal_entity_id', $eid)->where('is_active', true)
            ->when($r->q, fn ($q) => $q->where(fn ($w) => $w->where('sku', 'like', "%{$r->q}%")->orWhere('name', 'like', "%{$r->q}%")))
            ->select('id', 'sku', 'barcode', 'name', 'sell_price', 'wholesale_price', 'reseller_price')
            ->orderBy('name')->limit(100)->get()
            ->map(function ($p) use ($tier) {
                $p->tier = \App\Services\PriceService::effectiveTier($tier);
                $p->price = \App\Services\PriceService::forProduct(
                    new \App\Models\Master\Product(['sell_price' => $p->sell_price, 'wholesale_price' => $p->wholesale_price, 'reseller_price' => $p->reseller_price]),
                    $tier
                );

                return $p;
            });

        return response()->json(['data' => $rows]);
    }

    public function customers(Request $r)
    {
        if (! $r->user()->hasPermission('crm.view')) {
            abort(403);
        }
        $eid = $this->eid($r);
        $this->guardEntity($r, $eid);
        $rows = DB::table('customers')->where('legal_entity_id', $eid)->where('is_active', true)
            ->when($r->q, fn ($q) => $q->where(fn ($w) => $w->where('code', 'like', "%{$r->q}%")->orWhere('name', 'like', "%{$r->q}%")))
            ->select('id', 'code', 'name', 'tier', 'phone')
            ->orderBy('name')->limit(100)->get();

        return response()->json(['data' => $rows]);
    }

    /** Resolusi harga satu produk untuk tier pelanggan (default retail). */
    public function productPrice(Request $r, int $id)
    {
        if (! $r->user()->hasPermission('crm.view')) {
            abort(403);
        }
        $eid = $this->eid($r);
        $this->guardEntity($r, $eid);
        $p = DB::table('products')->where('id', $id)->where('legal_entity_id', $eid)->first();
        if (! $p) {
            abort(404);
        }
        $tier = null;
        if ($r->filled('customer_id')) {
            $tier = DB::table('customers')->where('id', (int) $r->customer_id)->where('legal_entity_id', $eid)->value('tier');
        }

        return response()->json([
            'data' => [
                'product_id' => $p->id,
                'sku' => $p->sku,
                'price' => \App\Services\PriceService::forProduct(
                    new \App\Models\Master\Product(['sell_price' => $p->sell_price, 'wholesale_price' => $p->wholesale_price, 'reseller_price' => $p->reseller_price]),
                    $tier
                ),
                'tier' => \App\Services\PriceService::effectiveTier($tier),
            ],
        ]);
    }
}

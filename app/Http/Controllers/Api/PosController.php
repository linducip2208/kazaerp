<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PosService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected function eid(Request $r): int
    {
        return (int) $r->user()->legal_entity_id;
    }

    protected function bid(Request $r): ?int
    {
        return $r->user()->branch_id ? (int) $r->user()->branch_id : null;
    }

    public function shift(Request $r)
    {
        if (! $r->user()->hasPermission('pos.operate')) {
            abort(403);
        }

        return response()->json(['data' => PosService::currentShift((int) $r->user()->id)]);
    }

    public function openShift(Request $r)
    {
        if (! $r->user()->hasPermission('pos.shift')) {
            abort(403);
        }
        $r->validate(['opening_cash' => 'required|numeric|min:0']);
        try {
            $id = PosService::openShift($this->eid($r), $this->bid($r), (int) $r->user()->id, (float) $r->opening_cash);
        } catch (\Throwable $e) {
            abort(422, $e->getMessage());
        }

        return response()->json(['data' => PosService::currentShift((int) $r->user()->id)], 201);
    }

    public function closeShift(Request $r)
    {
        if (! $r->user()->hasPermission('pos.shift')) {
            abort(403);
        }
        $r->validate(['closing_cash' => 'required|numeric|min:0']);
        try {
            $summary = PosService::closeShift((int) $r->user()->id, (float) $r->closing_cash);
        } catch (\Throwable $e) {
            abort(422, $e->getMessage());
        }

        return response()->json(['data' => $summary]);
    }

    public function checkout(Request $r)
    {
        if (! $r->user()->hasPermission('pos.operate')) {
            abort(403);
        }
        $r->validate([
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer',
            'lines.*.qty' => 'required|numeric|min:0.0001',
            'pay_method' => 'required|in:cash,transfer,qris',
            'customer_id' => 'nullable|integer',
        ]);
        try {
            $sale = PosService::checkout($this->eid($r), $this->bid($r), (int) $r->user()->id, $r->lines, $r->pay_method, $r->customer_id ? (int) $r->customer_id : null);
        } catch (\Throwable $e) {
            abort(422, $e->getMessage());
        }

        return response()->json(['data' => $sale], 201);
    }
}

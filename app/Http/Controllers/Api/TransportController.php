<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportController extends Controller
{
    /** Driver/mobile mengirim titik GPS untuk sebuah trip. */
    public function store(Request $r, int $tripId)
    {
        $u = $r->user();
        if (! $u->hasPermission('pet.transport')) {
            abort(403);
        }
        $r->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'speed_kmh' => 'nullable|numeric|min:0|max:300',
            'recorded_at' => 'nullable|date',
        ]);
        $trip = DB::table('trips')
            ->join('transport_orders', 'transport_orders.id', '=', 'trips.transport_order_id')
            ->where('trips.id', $tripId)
            ->select('trips.*', 'transport_orders.legal_entity_id')
            ->first();
        if (! $trip) {
            abort(404, 'Trip tidak ditemukan.');
        }
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor') && (int) $trip->legal_entity_id !== (int) $u->legal_entity_id) {
            abort(403, 'Trip di luar cakupan entitas.');
        }
        if (! in_array($trip->status, ['assigned', 'ongoing', 'in_progress'], true)) {
            abort(422, 'Trip berstatus ' . $trip->status . ' — posisi tidak diterima.');
        }
        $id = (int) DB::table('transport_positions')->insertGetId([
            'trip_id' => $trip->id, 'lat' => $r->lat, 'lng' => $r->lng,
            'speed_kmh' => $r->speed_kmh, 'recorded_at' => $r->recorded_at ?? now(),
            'created_by' => $u->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $id, 'trip_id' => $trip->id]], 201);
    }

    /** Riwayat posisi trip (terbaru lebih dulu). */
    public function index(Request $r, int $tripId)
    {
        $u = $r->user();
        if (! $u->hasPermission('pet.transport')) {
            abort(403);
        }
        $trip = DB::table('trips')
            ->join('transport_orders', 'transport_orders.id', '=', 'trips.transport_order_id')
            ->where('trips.id', $tripId)
            ->select('trips.*', 'transport_orders.legal_entity_id', 'transport_orders.number as order_number')
            ->first();
        if (! $trip) {
            abort(404, 'Trip tidak ditemukan.');
        }
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor') && (int) $trip->legal_entity_id !== (int) $u->legal_entity_id) {
            abort(403, 'Trip di luar cakupan entitas.');
        }
        $rows = DB::table('transport_positions')->where('trip_id', $tripId)
            ->orderByDesc('recorded_at')->orderByDesc('id')->limit(min(200, max(1, (int) $r->get('limit', 100))))->get();

        return response()->json(['data' => [
            'trip_id' => $trip->id, 'order_number' => $trip->order_number, 'status' => $trip->status,
            'positions' => $rows,
        ]]);
    }
}

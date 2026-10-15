<?php

namespace App\Http\Controllers\Pet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportTrackingController extends Controller
{
    public function index(Request $r)
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = $this->baseTripQuery($eid)
            ->orderByDesc('trips.id')->paginate(20)->withQueryString();

        return view('modules.pet.transport.tracking', compact('rows'));
    }

    public function show($id)
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $order = DB::table('transport_orders')->where('id', $id)->where('legal_entity_id', $eid)->firstOrFail();
        $trips = $this->baseTripQuery($eid)->where('trips.transport_order_id', $id)->orderBy('trips.id')->get();
        $tripIds = $trips->pluck('id')->all();
        $positions = $tripIds
            ? DB::table('transport_positions')->whereIn('trip_id', $tripIds)->orderByDesc('recorded_at')->orderByDesc('id')->limit(50)->get()
            : collect();

        return view('modules.pet.transport.track-show', compact('order', 'trips', 'positions'));
    }

    protected function baseTripQuery(int $eid)
    {
        return DB::table('trips')
            ->join('transport_orders', 'transport_orders.id', '=', 'trips.transport_order_id')
            ->join('vehicles', 'vehicles.id', '=', 'trips.vehicle_id')
            ->join('users', 'users.id', '=', 'trips.driver_id')
            ->leftJoinSub(
                DB::table('transport_positions')->selectRaw('trip_id, MAX(id) as last_id')->groupBy('trip_id'),
                'lp', 'lp.trip_id', '=', 'trips.id'
            )
            ->leftJoin('transport_positions as p', 'p.id', '=', 'lp.last_id')
            ->where('transport_orders.legal_entity_id', $eid)
            ->select('trips.*', 'transport_orders.number as order_number', 'transport_orders.pickup_address',
                'transport_orders.delivery_address', 'vehicles.plate', 'users.name as driver',
                'p.lat', 'p.lng', 'p.speed_kmh', 'p.recorded_at as position_at');
    }
}

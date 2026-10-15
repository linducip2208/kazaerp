<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class Trip extends Model {
    protected $table = 'trips';
    protected $fillable = ['transport_order_id', 'vehicle_id', 'driver_id', 'depart_at', 'arrive_at', 'status', 'fuel_cost', 'toll_cost', 'handover_proof'];
    protected $casts = [];
}

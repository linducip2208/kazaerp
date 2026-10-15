<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class TransportOrder extends Model {
    protected $table = 'transport_orders';
    protected $fillable = ['legal_entity_id', 'number', 'date', 'pet_id', 'pickup_address', 'delivery_address', 'scheduled_at', 'price', 'status'];
    protected $casts = [];
}

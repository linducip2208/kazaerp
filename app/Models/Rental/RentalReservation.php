<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
class RentalReservation extends Model {
    protected $table = 'rental_reservations';
    protected $fillable = ['legal_entity_id', 'number', 'rental_unit_id', 'customer_id', 'start_date', 'end_date', 'rate', 'deposit', 'status'];
    protected $casts = [];

    public function unit() { return $this->belongsTo(\App\Models\Rental\RentalUnit::class, 'rental_unit_id'); }
    public function customer() { return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id'); }
}

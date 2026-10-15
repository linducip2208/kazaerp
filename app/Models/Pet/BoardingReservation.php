<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class BoardingReservation extends Model {
    protected $table = 'boarding_reservations';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'pet_id', 'boarding_room_id', 'check_in', 'check_out', 'feeding', 'medication_note', 'deposit', 'grand_total', 'status'];
    protected $casts = [];

    public function pet() { return $this->belongsTo(\App\Models\Pet\Pet::class, 'pet_id'); }
    public function room() { return $this->belongsTo(\App\Models\Pet\BoardingRoom::class, 'boarding_room_id'); }
}

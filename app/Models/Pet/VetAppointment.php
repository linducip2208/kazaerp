<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class VetAppointment extends Model {
    protected $table = 'vet_appointments';
    protected $fillable = ['legal_entity_id', 'branch_id', 'pet_id', 'scheduled_at', 'vet_id', 'status', 'complaint'];
    protected $casts = [];
}

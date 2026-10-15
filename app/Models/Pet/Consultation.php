<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class Consultation extends Model {
    protected $table = 'consultations';
    protected $fillable = ['vet_appointment_id', 'pet_id', 'clinical_notes', 'diagnosis', 'treatment_plan', 'vet_id'];
    protected $casts = [];
}

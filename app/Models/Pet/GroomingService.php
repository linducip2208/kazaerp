<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class GroomingService extends Model {
    protected $table = 'grooming_services';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'price', 'duration_min', 'is_active'];
    protected $casts = [];
}

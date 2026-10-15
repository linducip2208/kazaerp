<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class Vehicle extends Model {
    protected $table = 'vehicles';
    protected $fillable = ['legal_entity_id', 'plate', 'name', 'capacity', 'status'];
    protected $casts = [];
}

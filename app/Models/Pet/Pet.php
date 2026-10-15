<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class Pet extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'pets';
    protected $fillable = ['pet_owner_id', 'name', 'species', 'breed', 'gender', 'birth_date', 'weight', 'color', 'notes'];
    protected $casts = [];

    public function owner() { return $this->belongsTo(\App\Models\Pet\PetOwner::class, 'pet_owner_id'); }
}

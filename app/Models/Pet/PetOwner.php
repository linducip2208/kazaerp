<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class PetOwner extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'pet_owners';
    protected $fillable = ['legal_entity_id', 'customer_id', 'name', 'phone', 'email', 'address'];
    protected $casts = [];
}

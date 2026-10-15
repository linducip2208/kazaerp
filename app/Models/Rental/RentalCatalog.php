<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
class RentalCatalog extends Model {
    protected $table = 'rental_catalogs';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'rate', 'is_active'];
    protected $casts = [];
}

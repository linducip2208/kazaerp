<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
class RentalUnit extends Model {
    protected $table = 'rental_units';
    protected $fillable = ['rental_catalog_id', 'code', 'size', 'condition_note', 'status'];
    protected $casts = [];
}

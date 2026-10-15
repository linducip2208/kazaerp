<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class BoardingRoom extends Model {
    protected $table = 'boarding_rooms';
    protected $fillable = ['branch_id', 'code', 'name', 'capacity', 'rate', 'is_active'];
    protected $casts = [];
}

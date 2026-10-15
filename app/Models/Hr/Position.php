<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class Position extends Model {
    protected $table = 'positions';
    protected $fillable = ['legal_entity_id', 'department_id', 'code', 'name', 'is_active'];
    protected $casts = [];
}

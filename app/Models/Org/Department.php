<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class Department extends Model {
    protected $table = 'departments';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'is_active'];
    protected $casts = [];
}

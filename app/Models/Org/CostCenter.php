<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class CostCenter extends Model {
    protected $table = 'cost_centers';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'is_active'];
    protected $casts = [];
}

<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class ProfitCenter extends Model {
    protected $table = 'profit_centers';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'is_active'];
    protected $casts = [];
}

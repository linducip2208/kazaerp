<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class BusinessUnit extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'business_units';
    protected $fillable = ['holding_id', 'legal_entity_id', 'brand_id', 'code', 'name', 'is_active'];
    protected $casts = [];
}

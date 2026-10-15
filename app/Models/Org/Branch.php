<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class Branch extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'branches';
    protected $fillable = ['legal_entity_id', 'brand_id', 'code', 'name', 'address', 'phone', 'is_active'];
    protected $casts = [];
}

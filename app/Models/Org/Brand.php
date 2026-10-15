<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class Brand extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'brands';
    protected $fillable = ['holding_id', 'code', 'name', 'is_active'];
    protected $casts = [];
}

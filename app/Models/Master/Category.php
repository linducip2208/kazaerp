<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Category extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'categories';
    protected $fillable = ['kind', 'code', 'name', 'parent_id', 'is_active'];
    protected $casts = [];
}

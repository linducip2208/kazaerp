<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class Holding extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'holdings';
    protected $fillable = ['code', 'name', 'address', 'phone', 'is_active'];
    protected $casts = [];
}

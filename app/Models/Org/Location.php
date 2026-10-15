<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class Location extends Model {
    protected $table = 'locations';
    protected $fillable = ['branch_id', 'code', 'name', 'kind', 'is_active'];
    protected $casts = [];
}

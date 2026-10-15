<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class Warehouse extends Model {
    protected $table = 'warehouses';
    protected $fillable = ['branch_id', 'code', 'name', 'is_active'];
    protected $casts = [];
}

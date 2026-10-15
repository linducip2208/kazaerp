<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'customers';
    protected $fillable = ['legal_entity_id', 'branch_id', 'code', 'name', 'kind', 'email', 'phone', 'tier', 'sales_owner_id', 'is_active'];
    protected $casts = [];
}

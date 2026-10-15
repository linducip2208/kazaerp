<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'suppliers';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'email', 'phone', 'bank_account', 'rating', 'is_active'];
    protected $casts = ['rating'=>'integer'];
}

<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class PriceList extends Model {
    protected $table = 'price_lists';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'valid_from', 'valid_to', 'is_active'];
    protected $casts = [];
}

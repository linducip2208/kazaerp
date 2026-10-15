<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'products';
    protected $fillable = ['legal_entity_id', 'brand_id', 'category_id', 'uom_id', 'sku', 'barcode', 'name', 'kind', 'cost_price', 'sell_price', 'wholesale_price', 'reseller_price', 'is_sellable', 'is_purchasable', 'is_active'];
    protected $casts = ['cost_price'=>'decimal:2','sell_price'=>'decimal:2'];
}

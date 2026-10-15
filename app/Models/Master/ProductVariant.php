<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class ProductVariant extends Model {
    protected $table = 'product_variants';
    protected $fillable = ['product_id', 'sku', 'option_name', 'option_value', 'sell_price', 'is_active'];
    protected $casts = ['sell_price'=>'decimal:2'];
}

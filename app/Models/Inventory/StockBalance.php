<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockBalance extends Model {
    protected $table = 'stock_balances';
    protected $fillable = ['warehouse_id', 'product_id', 'variant_id', 'batch_id', 'qty_on_hand', 'qty_reserved'];
    protected $casts = ['qty_on_hand'=>'decimal:4'];
}

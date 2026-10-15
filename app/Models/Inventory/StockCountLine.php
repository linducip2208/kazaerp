<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockCountLine extends Model {
    protected $table = 'stock_count_lines';
    protected $fillable = ['stock_count_id', 'product_id', 'qty_system', 'qty_actual'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

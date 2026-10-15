<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockAdjustmentLine extends Model {
    protected $table = 'stock_adjustment_lines';
    protected $fillable = ['stock_adjustment_id', 'product_id', 'qty', 'unit_cost'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockTransferLine extends Model {
    protected $table = 'stock_transfer_lines';
    protected $fillable = ['stock_transfer_id', 'product_id', 'qty_sent', 'qty_received'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

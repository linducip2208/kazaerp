<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class GoodsReceiptLine extends Model {
    protected $table = 'goods_receipt_lines';
    protected $fillable = ['goods_receipt_id', 'product_id', 'batch_id', 'qty_ordered', 'qty_received', 'unit_cost'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

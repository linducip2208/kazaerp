<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrderLine extends Model {
    protected $table = 'purchase_order_lines';
    protected $fillable = ['purchase_order_id', 'product_id', 'qty', 'qty_received', 'price', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

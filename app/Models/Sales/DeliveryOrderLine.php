<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class DeliveryOrderLine extends Model {
    protected $table = 'delivery_order_lines';
    protected $fillable = ['delivery_order_id', 'product_id', 'variant_id', 'snapshot_name', 'qty', 'price', 'discount', 'tax_rate', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

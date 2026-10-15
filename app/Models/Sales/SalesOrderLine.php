<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class SalesOrderLine extends Model {
    protected $table = 'sales_order_lines';
    protected $fillable = ['sales_order_id', 'product_id', 'variant_id', 'snapshot_name', 'qty', 'price', 'discount', 'tax_rate', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

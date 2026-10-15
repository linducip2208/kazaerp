<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class SalesReturnLine extends Model {
    protected $table = 'sales_return_lines';
    protected $fillable = ['sales_return_id', 'product_id', 'variant_id', 'snapshot_name', 'qty', 'price', 'discount', 'tax_rate', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

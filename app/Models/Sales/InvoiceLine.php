<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class InvoiceLine extends Model {
    protected $table = 'invoice_lines';
    protected $fillable = ['invoice_id', 'product_id', 'variant_id', 'snapshot_name', 'qty', 'price', 'discount', 'tax_rate', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

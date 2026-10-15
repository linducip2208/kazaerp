<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class QuotationLine extends Model {
    protected $table = 'quotation_lines';
    protected $fillable = ['quotation_id', 'product_id', 'snapshot_name', 'qty', 'price', 'discount', 'tax_rate', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

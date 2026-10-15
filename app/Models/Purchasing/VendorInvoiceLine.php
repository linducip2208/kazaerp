<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class VendorInvoiceLine extends Model {
    protected $table = 'vendor_invoice_lines';
    protected $fillable = ['vendor_invoice_id', 'product_id', 'qty', 'price', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

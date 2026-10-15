<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class CreditNoteLine extends Model {
    protected $table = 'credit_note_lines';
    protected $fillable = ['credit_note_id', 'product_id', 'variant_id', 'snapshot_name', 'qty', 'price', 'discount', 'tax_rate', 'line_total'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

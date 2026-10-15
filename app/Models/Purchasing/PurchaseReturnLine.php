<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class PurchaseReturnLine extends Model {
    protected $table = 'purchase_return_lines';
    protected $fillable = ['purchase_return_id', 'product_id', 'qty', 'price'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

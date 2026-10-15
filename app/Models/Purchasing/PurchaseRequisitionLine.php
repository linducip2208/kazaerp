<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequisitionLine extends Model {
    protected $table = 'purchase_requisition_lines';
    protected $fillable = ['purchase_requisition_id', 'product_id', 'qty', 'est_price'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

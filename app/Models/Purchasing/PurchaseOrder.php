<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrder extends Model {
    protected $table = 'purchase_orders';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'supplier_id', 'requisition_id', 'version', 'status', 'subtotal', 'tax', 'grand_total', 'created_by'];
    protected $casts = ['grand_total'=>'decimal:2'];

    public function lines() { return $this->hasMany(\App\Models\Purchasing\PurchaseOrderLine::class, 'purchase_order_id'); }
    public function supplier() { return $this->belongsTo(\App\Models\Master\Supplier::class, 'supplier_id'); }
}

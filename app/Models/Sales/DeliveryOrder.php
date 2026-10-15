<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class DeliveryOrder extends Model {
    protected $table = 'delivery_orders';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'sales_order_id', 'customer_id', 'warehouse_id', 'status', 'subtotal', 'discount', 'tax', 'grand_total', 'note', 'created_by'];
    protected $casts = [];

    public function customer() { return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\App\Models\Sales\DeliveryOrderLine::class, 'delivery_order_id'); }
}

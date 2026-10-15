<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class SalesOrder extends Model {
    protected $table = 'sales_orders';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'customer_id', 'quotation_id', 'paid_total', 'status', 'subtotal', 'discount', 'tax', 'grand_total', 'note', 'created_by'];
    protected $casts = ['grand_total'=>'decimal:2'];

    public function customer() { return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\App\Models\Sales\SalesOrderLine::class, 'sales_order_id'); }
}

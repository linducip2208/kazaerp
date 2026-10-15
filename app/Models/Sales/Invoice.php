<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model {
    protected $table = 'invoices';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'customer_id', 'sales_order_id', 'due_date', 'paid_total', 'pay_status', 'status', 'subtotal', 'discount', 'tax', 'grand_total', 'note', 'created_by'];
    protected $casts = ['grand_total'=>'decimal:2'];

    public function customer() { return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\App\Models\Sales\InvoiceLine::class, 'invoice_id'); }
    public function payments() { return $this->hasMany(\App\Models\Sales\SalesPayment::class, 'invoice_id'); }
}

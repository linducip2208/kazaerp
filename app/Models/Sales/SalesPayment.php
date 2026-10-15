<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class SalesPayment extends Model {
    protected $table = 'sales_payments';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'invoice_id', 'customer_id', 'method', 'amount', 'reference', 'created_by'];
    protected $casts = ['amount'=>'decimal:2'];
}

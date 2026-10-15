<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class VendorInvoice extends Model {
    protected $table = 'vendor_invoices';
    protected $fillable = ['legal_entity_id', 'purchase_order_id', 'supplier_id', 'number', 'vendor_ref', 'date', 'due_date', 'grand_total', 'paid_total', 'status', 'match_status'];
    protected $casts = ['grand_total'=>'decimal:2'];
}

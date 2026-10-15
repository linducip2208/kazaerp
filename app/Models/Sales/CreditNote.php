<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class CreditNote extends Model {
    protected $table = 'credit_notes';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'invoice_id', 'customer_id', 'status', 'subtotal', 'discount', 'tax', 'grand_total', 'note', 'created_by'];
    protected $casts = [];

    public function customer() { return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id'); }
}

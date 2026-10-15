<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Model;

class ApPayment extends Model
{
    protected $table = 'ap_payments';

    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'vendor_invoice_id', 'supplier_id', 'method', 'amount', 'reference', 'created_by'];

    protected $casts = ['amount' => 'decimal:2'];
}

<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class CashReceipt extends Model {
    protected $table = 'cash_receipts';
    protected $fillable = ['legal_entity_id', 'cash_account_id', 'number', 'date', 'amount', 'payer', 'description', 'status'];
    protected $casts = [];
}

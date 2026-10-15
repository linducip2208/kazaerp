<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class CashPayment extends Model {
    protected $table = 'cash_payments';
    protected $fillable = ['legal_entity_id', 'cash_account_id', 'number', 'date', 'amount', 'payee', 'description', 'status'];
    protected $casts = [];
}

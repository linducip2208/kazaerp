<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class BankTransfer extends Model {
    protected $table = 'bank_transfers';
    protected $fillable = ['legal_entity_id', 'from_bank_id', 'to_bank_id', 'number', 'date', 'amount', 'status'];
    protected $casts = [];
}

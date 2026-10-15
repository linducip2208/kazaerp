<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class CashAccount extends Model {
    protected $table = 'cash_accounts';
    protected $fillable = ['legal_entity_id', 'branch_id', 'code', 'name', 'balance', 'is_active'];
    protected $casts = ['balance'=>'decimal:2'];
}

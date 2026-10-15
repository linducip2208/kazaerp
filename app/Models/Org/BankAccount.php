<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class BankAccount extends Model {
    protected $table = 'bank_accounts';
    protected $fillable = ['legal_entity_id', 'bank_name', 'account_no', 'holder', 'is_active'];
    protected $casts = [];
}

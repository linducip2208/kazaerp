<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class CustomerDeposit extends Model {
    protected $table = 'customer_deposits';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'customer_id', 'amount', 'used_amount', 'status'];
    protected $casts = ['amount'=>'decimal:2'];
}

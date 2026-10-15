<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class PosSale extends Model {
    protected $table = 'pos_sales';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'shift_id', 'customer_id', 'status', 'subtotal', 'discount', 'tax', 'grand_total', 'pay_method', 'created_by'];
    protected $casts = [];
}

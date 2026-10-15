<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class PurchaseReturn extends Model {
    protected $table = 'purchase_returns';
    protected $fillable = ['legal_entity_id', 'purchase_order_id', 'supplier_id', 'number', 'date', 'status', 'grand_total'];
    protected $casts = [];
}

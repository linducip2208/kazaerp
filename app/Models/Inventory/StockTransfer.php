<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockTransfer extends Model {
    protected $table = 'stock_transfers';
    protected $fillable = ['legal_entity_id', 'number', 'date', 'from_warehouse_id', 'to_warehouse_id', 'status', 'created_by'];
    protected $casts = [];
}

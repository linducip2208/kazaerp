<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockAdjustment extends Model {
    protected $table = 'stock_adjustments';
    protected $fillable = ['legal_entity_id', 'warehouse_id', 'number', 'date', 'kind', 'status', 'reason', 'created_by'];
    protected $casts = [];
}

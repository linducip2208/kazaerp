<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockCount extends Model {
    protected $table = 'stock_counts';
    protected $fillable = ['warehouse_id', 'legal_entity_id', 'number', 'date', 'status'];
    protected $casts = [];
}

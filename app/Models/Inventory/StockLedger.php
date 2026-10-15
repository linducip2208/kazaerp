<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class StockLedger extends Model {
    protected $table = 'stock_ledgers';
    protected $fillable = ['warehouse_id', 'product_id', 'variant_id', 'batch_id', 'qty', 'unit_cost', 'ref_type', 'ref_id', 'note', 'created_by'];
    protected $casts = ['qty'=>'decimal:4'];
}

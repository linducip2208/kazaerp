<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class GoodsReceipt extends Model {
    protected $table = 'goods_receipts';
    protected $fillable = ['legal_entity_id', 'branch_id', 'warehouse_id', 'number', 'date', 'ref_type', 'ref_id', 'status', 'note', 'created_by'];
    protected $casts = [];
}

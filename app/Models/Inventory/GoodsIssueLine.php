<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class GoodsIssueLine extends Model {
    protected $table = 'goods_issue_lines';
    protected $fillable = ['goods_issue_id', 'product_id', 'batch_id', 'qty'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

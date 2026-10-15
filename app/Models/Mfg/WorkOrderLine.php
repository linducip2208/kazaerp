<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class WorkOrderLine extends Model {
    protected $table = 'work_order_lines';
    protected $fillable = ['work_order_id', 'material_id', 'qty_required', 'qty_issued', 'unit_cost'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'material_id'); }
}

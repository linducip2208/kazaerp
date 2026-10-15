<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class WorkOrder extends Model {
    protected $table = 'work_orders';
    protected $fillable = ['legal_entity_id', 'number', 'date', 'bom_id', 'bom_version_locked', 'qty_planned', 'qty_good', 'qty_reject', 'status', 'warehouse_id', 'standard_cost', 'actual_cost', 'variance_amount', 'started_at', 'completed_at'];
    protected $casts = ['qty_planned'=>'decimal:4','qty_good'=>'decimal:4','qty_reject'=>'decimal:4','standard_cost'=>'decimal:2','actual_cost'=>'decimal:2','variance_amount'=>'decimal:2'];
    public function bom() { return $this->belongsTo(Bom::class, 'bom_id'); }
    public function lines() { return $this->hasMany(WorkOrderLine::class, 'work_order_id'); }
}

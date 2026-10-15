<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class WorkOrder extends Model {
    protected $table = 'work_orders';
    protected $fillable = ['legal_entity_id', 'number', 'date', 'bom_id', 'bom_version_locked', 'qty_planned', 'qty_good', 'qty_reject', 'status'];
    protected $casts = [];
}

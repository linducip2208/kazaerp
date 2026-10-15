<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequisition extends Model {
    protected $table = 'purchase_requisitions';
    protected $fillable = ['legal_entity_id', 'branch_id', 'cost_center_id', 'number', 'date', 'status', 'is_emergency', 'emergency_reason', 'requested_by'];
    protected $casts = ['is_emergency'=>'boolean'];
}

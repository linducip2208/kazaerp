<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class Rfq extends Model {
    protected $table = 'rfqs';
    protected $fillable = ['legal_entity_id', 'purchase_requisition_id', 'number', 'date', 'deadline', 'status'];
    protected $casts = [];
}

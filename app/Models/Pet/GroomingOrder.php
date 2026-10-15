<?php
namespace App\Models\Pet;
use Illuminate\Database\Eloquent\Model;
class GroomingOrder extends Model {
    protected $table = 'grooming_orders';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'pet_id', 'grooming_service_id', 'groomer_id', 'status', 'checklist', 'before_condition', 'grand_total'];
    protected $casts = [];

    public function pet() { return $this->belongsTo(\App\Models\Pet\Pet::class, 'pet_id'); }
}

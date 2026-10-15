<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Lead extends Model {
    protected $table = 'leads';
    protected $fillable = ['legal_entity_id', 'name', 'phone', 'email', 'source_id', 'stage', 'lost_reason', 'owner_id', 'converted_customer_id'];
    protected $casts = [];
}

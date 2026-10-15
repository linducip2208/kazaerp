<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Opportunity extends Model {
    protected $table = 'opportunities';
    protected $fillable = ['legal_entity_id', 'lead_id', 'customer_id', 'title', 'amount', 'stage', 'probability', 'owner_id'];
    protected $casts = ['amount'=>'decimal:2'];
}

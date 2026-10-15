<?php
namespace App\Models\Studio;
use Illuminate\Database\Eloquent\Model;
class StudioProject extends Model {
    protected $table = 'studio_projects';
    protected $fillable = ['legal_entity_id', 'customer_id', 'title', 'brief', 'status', 'price', 'cost'];
    protected $casts = [];
}

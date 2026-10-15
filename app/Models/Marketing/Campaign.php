<?php
namespace App\Models\Marketing;
use Illuminate\Database\Eloquent\Model;
class Campaign extends Model {
    protected $table = 'campaigns';
    protected $fillable = ['legal_entity_id', 'name', 'start_date', 'end_date', 'budget', 'status'];
    protected $casts = [];
}

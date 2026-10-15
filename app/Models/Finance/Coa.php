<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class Coa extends Model {
    protected $table = 'coas';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'type', 'normal_balance', 'parent_id', 'is_postable', 'is_active'];
    protected $casts = [];
}

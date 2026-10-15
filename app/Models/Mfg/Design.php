<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class Design extends Model {
    protected $table = 'designs';
    protected $fillable = ['collection_id', 'legal_entity_id', 'code', 'name', 'status'];
    protected $casts = [];
}

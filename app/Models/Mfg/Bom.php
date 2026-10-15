<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class Bom extends Model {
    protected $table = 'boms';
    protected $fillable = ['legal_entity_id', 'product_id', 'version', 'status'];
    protected $casts = [];
}

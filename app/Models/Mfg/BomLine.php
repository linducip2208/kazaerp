<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class BomLine extends Model {
    protected $table = 'bom_lines';
    protected $fillable = ['bom_id', 'material_id', 'qty'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

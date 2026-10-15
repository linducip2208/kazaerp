<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Uom extends Model {
    protected $table = 'uoms';
    protected $fillable = ['code', 'name'];
    protected $casts = [];
}

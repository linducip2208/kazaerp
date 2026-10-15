<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class Collection extends Model {
    protected $table = 'collections';
    protected $fillable = ['brand_id', 'code', 'name', 'season'];
    protected $casts = [];
}

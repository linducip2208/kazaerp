<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Tax extends Model {
    protected $table = 'taxes';
    protected $fillable = ['code', 'name', 'rate', 'is_active'];
    protected $casts = ['rate'=>'decimal:4'];
}

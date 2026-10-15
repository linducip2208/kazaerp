<?php
namespace App\Models\Identity;
use Illuminate\Database\Eloquent\Model;
class Role extends Model {
    protected $table = 'roles';
    protected $fillable = ['name', 'label', 'is_system'];
    protected $casts = ['is_system'=>'boolean'];
}

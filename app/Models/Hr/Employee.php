<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'employees';
    protected $fillable = ['legal_entity_id', 'branch_id', 'department_id', 'position_id', 'user_id', 'nip', 'name', 'gender', 'birth_date', 'phone', 'address', 'join_date', 'status'];
    protected $casts = [];
}

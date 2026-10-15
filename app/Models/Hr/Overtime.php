<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class Overtime extends Model {
    protected $table = 'overtimes';
    protected $fillable = ['employee_id', 'date', 'hours', 'amount', 'status'];
    protected $casts = [];
}

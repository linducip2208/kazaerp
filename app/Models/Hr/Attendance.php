<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class Attendance extends Model {
    protected $table = 'attendances';
    protected $fillable = ['employee_id', 'date', 'check_in', 'check_out', 'status'];
    protected $casts = [];
}

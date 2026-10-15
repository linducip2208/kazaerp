<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class LeaveRequest extends Model {
    protected $table = 'leave_requests';
    protected $fillable = ['employee_id', 'from_date', 'to_date', 'kind', 'reason', 'status'];
    protected $casts = [];
}

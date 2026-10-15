<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class Payslip extends Model {
    protected $table = 'payslips';
    protected $fillable = ['payroll_id', 'employee_id', 'number', 'net'];
    protected $casts = [];
}

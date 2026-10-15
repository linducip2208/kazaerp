<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class PayrollPeriod extends Model {
    protected $table = 'payroll_periods';
    protected $fillable = ['legal_entity_id', 'name', 'start_date', 'end_date', 'status'];
    protected $casts = [];
}

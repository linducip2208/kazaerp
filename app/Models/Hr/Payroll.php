<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class Payroll extends Model {
    protected $table = 'payrolls';
    protected $fillable = ['payroll_period_id', 'legal_entity_id', 'number', 'status', 'grand_total', 'approved_by'];
    protected $casts = [];

    public function lines() { return $this->hasMany(\App\Models\Hr\PayrollLine::class, 'payroll_id'); }
}

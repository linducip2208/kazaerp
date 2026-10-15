<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class PayrollLine extends Model {
    protected $table = 'payroll_lines';
    protected $fillable = ['payroll_id', 'employee_id', 'gross', 'deduction', 'net', 'breakdown'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

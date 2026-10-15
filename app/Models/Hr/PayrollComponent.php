<?php
namespace App\Models\Hr;
use Illuminate\Database\Eloquent\Model;
class PayrollComponent extends Model {
    protected $table = 'payroll_components';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'kind', 'formula', 'is_active'];
    protected $casts = [];
}

<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class FiscalPeriod extends Model {
    protected $table = 'fiscal_periods';
    protected $fillable = ['fiscal_year_id', 'legal_entity_id', 'name', 'start_date', 'end_date', 'is_closed'];
    protected $casts = ['is_closed'=>'boolean'];
}

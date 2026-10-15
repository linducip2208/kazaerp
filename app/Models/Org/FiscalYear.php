<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class FiscalYear extends Model {
    protected $table = 'fiscal_years';
    protected $fillable = ['legal_entity_id', 'name', 'start_date', 'end_date', 'is_closed'];
    protected $casts = ['is_closed'=>'boolean'];
}

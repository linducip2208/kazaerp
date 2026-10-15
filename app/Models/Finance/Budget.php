<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class Budget extends Model {
    protected $table = 'budgets';
    protected $fillable = ['legal_entity_id', 'fiscal_year_id', 'cost_center_id', 'name', 'status'];
    protected $casts = [];
}

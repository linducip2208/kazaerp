<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class Expense extends Model {
    protected $table = 'expenses';
    protected $fillable = ['legal_entity_id', 'branch_id', 'cost_center_id', 'number', 'date', 'title', 'amount', 'status', 'requested_by'];
    protected $casts = [];
}

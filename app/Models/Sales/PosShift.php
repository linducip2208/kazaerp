<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class PosShift extends Model {
    protected $table = 'pos_shifts';
    protected $fillable = ['legal_entity_id', 'branch_id', 'cashier_id', 'opened_at', 'closed_at', 'opening_cash', 'closing_cash', 'system_total', 'difference', 'status'];
    protected $casts = [];
}

<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class BudgetLine extends Model {
    protected $table = 'budget_lines';
    protected $fillable = ['budget_id', 'coa_id', 'amount'];
    public function product() { return $this->belongsTo(\App\Models\Master\Product::class, 'product_id'); }
}

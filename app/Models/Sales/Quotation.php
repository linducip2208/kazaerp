<?php
namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class Quotation extends Model {
    protected $table = 'quotations';
    protected $fillable = ['legal_entity_id', 'branch_id', 'number', 'date', 'customer_id', 'channel_id', 'valid_until', 'status', 'version', 'subtotal', 'discount', 'tax', 'grand_total', 'note', 'created_by'];
    protected $casts = ['grand_total'=>'decimal:2'];

    public function customer() { return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(\App\Models\Sales\QuotationLine::class, 'quotation_id'); }
}

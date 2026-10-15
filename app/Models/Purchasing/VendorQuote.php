<?php
namespace App\Models\Purchasing;
use Illuminate\Database\Eloquent\Model;
class VendorQuote extends Model {
    protected $table = 'vendor_quotes';
    protected $fillable = ['rfq_id', 'supplier_id', 'price', 'lead_days', 'is_winner'];
    protected $casts = ['price'=>'decimal:2'];
}

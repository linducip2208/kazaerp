<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class PaymentTerm extends Model {
    protected $table = 'payment_terms';
    protected $fillable = ['code', 'name', 'days'];
    protected $casts = ['days'=>'integer'];
}

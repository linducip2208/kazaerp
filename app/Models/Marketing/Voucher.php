<?php
namespace App\Models\Marketing;
use Illuminate\Database\Eloquent\Model;
class Voucher extends Model {
    protected $table = 'vouchers';
    protected $fillable = ['legal_entity_id', 'code', 'name', 'value', 'min_txn', 'valid_from', 'valid_to', 'quota', 'used', 'is_active'];
    protected $casts = [];
}

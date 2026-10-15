<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class Batch extends Model {
    protected $table = 'batches';
    protected $fillable = ['product_id', 'lot_no', 'produced_at', 'expired_at', 'qc_status'];
    protected $casts = [];
}

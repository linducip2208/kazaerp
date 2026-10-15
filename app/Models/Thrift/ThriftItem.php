<?php
namespace App\Models\Thrift;
use Illuminate\Database\Eloquent\Model;
class ThriftItem extends Model {
    protected $table = 'thrift_items';
    protected $fillable = ['thrift_lot_id', 'code', 'name', 'grade', 'condition_note', 'cost_share', 'process_cost', 'price', 'status', 'rack_id'];
    protected $casts = [];
}

<?php
namespace App\Models\Thrift;
use Illuminate\Database\Eloquent\Model;
class LiveOrder extends Model {
    protected $table = 'live_orders';
    protected $fillable = ['live_session_id', 'thrift_item_id', 'customer_id', 'price', 'pay_deadline', 'status'];
    protected $casts = [];
}

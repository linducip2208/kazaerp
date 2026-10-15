<?php
namespace App\Models\Thrift;
use Illuminate\Database\Eloquent\Model;
class ThriftLot extends Model {
    protected $table = 'thrift_lots';
    protected $fillable = ['legal_entity_id', 'number', 'date', 'acquisition_cost', 'status'];
    protected $casts = [];
}

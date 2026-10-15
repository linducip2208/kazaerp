<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class IntercompanyTxn extends Model {
    protected $table = 'intercompany_txns';
    protected $fillable = ['from_entity_id', 'to_entity_id', 'number', 'date', 'amount', 'status', 'note'];
    protected $casts = [];
}

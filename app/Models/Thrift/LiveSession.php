<?php
namespace App\Models\Thrift;
use Illuminate\Database\Eloquent\Model;
class LiveSession extends Model {
    protected $table = 'live_sessions';
    protected $fillable = ['legal_entity_id', 'title', 'scheduled_at', 'host_id', 'status'];
    protected $casts = [];
}

<?php
namespace App\Models\Identity;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model {
    protected $table = 'audit_logs';
    protected $fillable = ['actor_id', 'action', 'entity', 'record_id', 'changes', 'ip'];
    protected $casts = [];
}

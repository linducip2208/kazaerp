<?php
namespace App\Models\Crm;
use Illuminate\Database\Eloquent\Model;
class Followup extends Model {
    protected $table = 'followups';
    protected $fillable = ['owner_type', 'owner_id', 'assigned_to', 'due_at', 'status', 'note', 'done_at'];
    protected $casts = [];
}

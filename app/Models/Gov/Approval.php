<?php
namespace App\Models\Gov;
use Illuminate\Database\Eloquent\Model;
class Approval extends Model {
    protected $table = 'approvals';
    protected $fillable = ['doc_type', 'doc_id', 'level', 'status', 'requested_by', 'decided_by', 'note'];
    protected $casts = [];
}

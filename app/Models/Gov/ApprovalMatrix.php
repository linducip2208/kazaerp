<?php
namespace App\Models\Gov;
use Illuminate\Database\Eloquent\Model;
class ApprovalMatrix extends Model {
    protected $table = 'approval_matrices';
    protected $fillable = ['doc_type', 'legal_entity_id', 'level', 'min_amount', 'role_id', 'allow_self_approve'];
    protected $casts = [];
}

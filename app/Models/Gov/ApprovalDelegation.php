<?php

namespace App\Models\Gov;

use Illuminate\Database\Eloquent\Model;

class ApprovalDelegation extends Model
{
    protected $table = 'approval_delegations';

    protected $fillable = ['delegator_id', 'delegate_id', 'doc_type', 'from_date', 'to_date', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}

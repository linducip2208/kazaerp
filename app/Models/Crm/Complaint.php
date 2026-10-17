<?php
namespace App\Models\Crm;
use Illuminate\Database\Eloquent\Model;
class Complaint extends Model {
    protected $table = 'complaints';
    protected $fillable = ['legal_entity_id', 'customer_id', 'subject', 'body', 'status', 'urgency',
        'decision', 'related_type', 'related_id', 'assignee_id', 'created_by', 'resolution', 'resolved_by', 'resolved_at'];
    protected $casts = [];
}

<?php
namespace App\Models\Crm;
use Illuminate\Database\Eloquent\Model;
class Complaint extends Model {
    protected $table = 'complaints';
    protected $fillable = ['legal_entity_id', 'customer_id', 'subject', 'body', 'status', 'resolution'];
    protected $casts = [];
}

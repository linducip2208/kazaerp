<?php
namespace App\Models\Marketing;
use Illuminate\Database\Eloquent\Model;
class Membership extends Model {
    protected $table = 'memberships';
    protected $fillable = ['customer_id', 'tier', 'joined_at', 'expires_at'];
    protected $casts = [];
}

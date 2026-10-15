<?php
namespace App\Models\Identity;
use Illuminate\Database\Eloquent\Model;
class LoginActivity extends Model {
    protected $table = 'login_activities';
    protected $fillable = ['user_id', 'email', 'ip', 'agent', 'success'];
    protected $casts = ['success'=>'boolean'];
}

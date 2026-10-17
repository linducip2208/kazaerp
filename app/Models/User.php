<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = ['name','email','password','is_active','holding_id','legal_entity_id','branch_id','last_login_at'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array {
        return ['email_verified_at' => 'datetime','password' => 'hashed','is_active'=>'boolean','last_login_at'=>'datetime'];
    }
    public function roles() { return $this->belongsToMany(\App\Models\Identity\Role::class, 'role_user'); }
    /** Request-scoped memoization: sidebar + dashboard memanggil hasPermission puluhan kali per halaman. */
    private ?array $memoRoleNames = null;
    private ?array $memoPerms = null;
    private function memoRoleNames(): array {
        if ($this->memoRoleNames === null) {
            $this->memoRoleNames = $this->relationLoaded('roles')
                ? $this->roles->pluck('name')->all()
                : $this->roles()->pluck('name')->all();
        }
        return $this->memoRoleNames;
    }
    public function flushAuthCache(): void {
        $this->memoRoleNames = null;
        $this->memoPerms = null;
        $this->unsetRelation('roles');
    }
    public function hasRole(string ...$names): bool {
        $roles = $this->memoRoleNames();
        foreach ($names as $n) if (in_array($n, $roles, true)) return true;
        return false;
    }
    public function hasPermission(string $perm): bool {
        if (!$this->is_active) return false;
        if ($this->hasRole('super_admin')) return true;
        if ($this->memoPerms === null) {
            $this->memoPerms = DB::table('permission_role')
                ->join('role_user','role_user.role_id','=','permission_role.role_id')
                ->join('permissions','permissions.id','=','permission_role.permission_id')
                ->where('role_user.user_id',$this->id)->pluck('permissions.name')->all();
        }
        return in_array($perm, $this->memoPerms, true);
    }
    public function inScope(string $type, int $id): bool {
        if ($this->hasRole('super_admin','holding_owner','executive','auditor')) return true;
        if ($type==='legal_entity' && (int)$this->legal_entity_id===$id) return true;
        if ($type==='branch' && (int)$this->branch_id===$id) return true;
        return DB::table('user_scopes')->where('user_id',$this->id)->where('scope_type',$type)->where('scope_id',$id)->exists();
    }
    public function scopeIds(string $type): array {
        return DB::table('user_scopes')->where('user_id',$this->id)->where('scope_type',$type)->pluck('scope_id')->all();
    }
}

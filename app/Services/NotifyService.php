<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NotifyService
{
    public static function send(int $userId, string $title, ?string $link = null): void
    {
        try {
            DB::table('notifications')->insert([
                'user_id' => $userId, 'title' => $title, 'link' => $link,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function sendToPerm(string $permission, string $title, ?string $link = null, int $limit = 20): void
    {
        try {
            $ids = DB::table('users')
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('permission_role', 'permission_role.role_id', '=', 'role_user.role_id')
                ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
                ->where('permissions.name', $permission)->where('users.is_active', true)
                ->distinct()->limit($limit)->pluck('users.id');
            foreach ($ids as $uid) {
                self::send((int) $uid, $title, $link);
            }
        } catch (\Throwable $e) {
        }
    }
}

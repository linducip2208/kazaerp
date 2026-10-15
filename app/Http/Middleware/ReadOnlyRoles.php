<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Menegakkan read-only untuk pengguna yang HANYA memiliki peran auditor/viewer.
 * Defense-in-depth: walaupun permission tulis salah dikonfigurasi, operasi tulis (non-GET)
 * tetap ditolak 403. Aksi self-service (logout, konteks, profil, 2FA, tandai notifikasi) dikecualikan.
 * Pengguna dengan peran lain di sampingnya (mis. auditor + sales_staff) bukan read-only.
 */
class ReadOnlyRoles
{
    public const READ_ONLY_ROLES = ['auditor', 'viewer'];

    /** Nama route yang tetap boleh non-GET bagi akun read-only (self-service). */
    protected array $allowedRoutes = [
        'logout', 'context.switch', 'profile.update',
        '2fa.verify', '2fa.confirm', '2fa.disable',
        'notifications.readAll',
        'login.attempt', 'password.email', 'password.update',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }
        $u = $request->user();
        if (! $u) {
            return $next($request);
        }
        $roles = $u->roles()->pluck('name');
        if ($roles->isEmpty() || $roles->diff(self::READ_ONLY_ROLES)->isNotEmpty()) {
            return $next($request);
        }
        $name = optional($request->route())->getName();
        if ($name && in_array($name, $this->allowedRoutes, true)) {
            return $next($request);
        }

        abort(403, 'Akun read-only (auditor/viewer): operasi tulis tidak diizinkan.');
    }
}

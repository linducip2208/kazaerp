<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class CheckPermission {
    public function handle(Request $request, Closure $next, string $permission) {
        $u = $request->user();
        if (!$u) abort(401);
        if (!$u->hasPermission($permission)) abort(403, 'Akses ditolak: ' . $permission);
        return $next($request);
    }
}

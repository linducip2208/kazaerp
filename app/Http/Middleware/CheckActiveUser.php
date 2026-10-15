<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mencabut sesi seketika bila akun dinonaktifkan (tanpa menunggu login ulang).
 */
class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && ! $u->fresh()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Akun dinonaktifkan. Hubungi administrator.']);
        }

        return $next($request);
    }
}

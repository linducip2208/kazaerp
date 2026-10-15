<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $res = $next($request);
        $res->headers->set('X-Content-Type-Options', 'nosniff');
        $res->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $res->headers->set('Referrer-Policy', 'same-origin');
        if (app()->environment('production')) {
            $res->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $res->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:");
        }

        return $res;
    }
}

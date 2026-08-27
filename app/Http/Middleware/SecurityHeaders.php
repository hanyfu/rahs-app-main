<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        // Never serve stale HTML/CSRF pages from cache. A cached login page
        // carries an expired token and makes sign-in appear broken.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; ".
            // Alpine evaluates x-* expressions at runtime. Until the app is
            // migrated to Alpine's CSP-specific build, unsafe-eval is required
            // for those directives to function.
            // TODO: Migrate to Alpine.js CSP-compatible build to remove unsafe-eval.
            // See https://alpinejs.dev/essentials/csp
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ".
            "style-src 'self' 'unsafe-inline'; ".
            "img-src 'self' data: blob:; ".
            "font-src 'self' data:; ".
            "connect-src 'self'; ".
            "frame-ancestors 'none'; ".
            "base-uri 'self'; ".
            "form-action 'self'; ".
            "object-src 'none'; ".
            "worker-src 'self'; ".
            "manifest-src 'self'"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}

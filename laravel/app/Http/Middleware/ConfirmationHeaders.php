<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ConfirmationHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        foreach (['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow', 'X-Content-Type-Options' => 'nosniff'] as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}

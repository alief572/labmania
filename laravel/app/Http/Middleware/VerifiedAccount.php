<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifiedAccount
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $factor = $user?->password;
        if (! $user?->active() || ! $factor
            || (int) $request->session()->get('auth.verified_id') !== $user->id_user
            || ! hash_equals(hash('sha256', $factor), (string) $request->session()->get('auth.factor'))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login');
        }

        return $next($request);
    }
}

<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    public function boot(): void
    {
        RateLimiter::for('confirmation', fn (Request $request) => Limit::perHour(12)->by('confirmation|'.$request->ip()));
        Gate::define('menu', fn (User $user, string $menu, string $action = 'View') => Permissions::allows($user, $menu, $action));
        RateLimiter::for('login', function (Request $request) {
            $username = hash('sha256', mb_strtolower((string) $request->input('username')));

            return [
                Limit::perMinute(5)->by('pair|'.$username.'|'.$request->ip()),
                Limit::perMinute(20)->by('ip|'.$request->ip()),
                Limit::perHour(30)->by('user|'.$username),
            ];
        });
    }
}

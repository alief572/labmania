<?php

namespace App\Http\Middleware;

use App\Support\Navigation;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        $map = null;
        $permissions = function () use ($request, &$map) {
            return $map ??= $request->user() ? Permissions::map($request->user()) : [];
        };

        return [...parent::share($request),
            'auth' => fn () => $request->user()?->only(['id_user', 'username', 'nm_lengkap']),
            'permissions' => $permissions,
            'navigation' => fn () => $request->user() ? Navigation::items($permissions()) : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}

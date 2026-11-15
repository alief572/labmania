<?php

namespace App\Http\Middleware;

use App\Support\MenuCounts;
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
            'navigation' => fn () => collect(config('navigation'))->filter(fn ($item) => ! $item['menu'] || in_array('View', $permissions()[$item['menu']] ?? [], true))->map(function ($item) {
                if (($count = MenuCounts::for($item['menu'])) !== null) {
                    $item['count'] = $count;
                }

                return $item;
            })->values(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'enroll_token' => fn () => $request->session()->get('enroll_token'),
            ],
        ];
    }
}

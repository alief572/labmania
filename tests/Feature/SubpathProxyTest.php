<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SubpathProxyTest extends TestCase
{
    public function test_url_mengikuti_base_path_dan_skema_dari_proxy_tepercaya(): void
    {
        // Produksi: Apache host meneruskan /labmania/... dan header X-Forwarded-*.
        TrustProxies::at('*');
        Route::middleware(TrustProxies::class)->get('/probe', fn () => response()->json([
            'url' => url('/login'),
            'basePath' => request()->getBasePath(),
        ]));

        $response = $this->call('GET', '/labmania/probe', [], [], [], [
            'SCRIPT_NAME' => '/labmania/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
            'PHP_SELF' => '/labmania/index.php',
            'REQUEST_URI' => '/labmania/probe',
            'HTTP_HOST' => 'apps.example.invalid',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'apps.example.invalid',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $response->assertOk()->assertJson([
            'url' => 'https://apps.example.invalid/labmania/login',
            'basePath' => '/labmania',
        ]);
        URL::forceRootUrl(null);
    }
}

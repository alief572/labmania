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

    /** @return array<string, string> */
    private function proxyServer(string $uri): array
    {
        return [
            'SCRIPT_NAME' => '/labmania/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
            'PHP_SELF' => '/labmania/index.php',
            'REQUEST_URI' => $uri,
            'HTTP_HOST' => 'apps.example.invalid',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'apps.example.invalid',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
            'REMOTE_ADDR' => '172.18.0.1',
        ];
    }

    public function test_link_konfirmasi_lama_tetap_menuju_halaman_berprefix(): void
    {
        TrustProxies::at('*');
        $token = str_repeat('a1', 16);
        $uri = '/labmania/konfirmasi_kehadiran/'.$token;

        $this->call('GET', $uri, [], [], [], $this->proxyServer($uri))
            ->assertRedirect('https://apps.example.invalid/labmania/konfirmasi/'.$token);
    }

    public function test_ip_klien_untuk_rate_limit_berasal_dari_header_proxy(): void
    {
        TrustProxies::at('*');
        Route::middleware(TrustProxies::class)->get('/ip', fn () => response()->json(['ip' => request()->ip()]));

        // Throttle login/konfirmasi memakai request()->ip(): harus IP klien, bukan gateway Docker.
        $this->call('GET', '/labmania/ip', [], [], [], $this->proxyServer('/labmania/ip'))
            ->assertJson(['ip' => '203.0.113.7']);
    }
}

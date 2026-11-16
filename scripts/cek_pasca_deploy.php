<?php

/**
 * Pemeriksa pasca-deploy (hanya baca). Jalankan dari root repository di server setelah build:
 *   php scripts/cek_pasca_deploy.php [--url=https://host] [--lock-sha256=<hash composer.lock yang diuji>]
 * Tidak membuka database dan tidak mengubah apa pun. Exit 0 = semua lulus, 1 = ada yang gagal.
 */
$root = dirname(__DIR__);
$args = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z0-9-]+)=(.*)$/', $a, $m)) {
        $args[$m[1]] = $m[2];
    }
}
$fail = 0;
$check = function (string $name, bool $ok, string $detail = '') use (&$fail) {
    $fail += $ok ? 0 : 1;
    echo ($ok ? '[LULUS] ' : '[GAGAL] ').$name.($detail !== '' ? ' — '.$detail : '')."\n";
};

$check('PHP >= 8.5', PHP_VERSION_ID >= 80500, PHP_VERSION);
foreach (['bcmath', 'curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'xmlwriter', 'zip'] as $ext) {
    $check("ekstensi $ext", extension_loaded($ext));
}

$env = [];
$file = $root.'/.env';
if (is_file($file)) {
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/', $line, $m)) {
            $env[$m[1]] = trim($m[2], "\"'");
        }
    }
}
$check('.env ada', $env !== []);
$check('APP_ENV=production', ($env['APP_ENV'] ?? '') === 'production', $env['APP_ENV'] ?? 'kosong');
$check('APP_DEBUG=false', strtolower($env['APP_DEBUG'] ?? '') === 'false', $env['APP_DEBUG'] ?? 'kosong');
$check('APP_KEY terisi', str_starts_with($env['APP_KEY'] ?? '', 'base64:') && strlen($env['APP_KEY']) > 20);
$check('SESSION_SECURE_COOKIE=true', strtolower($env['SESSION_SECURE_COOKIE'] ?? '') === 'true', $env['SESSION_SECURE_COOKIE'] ?? 'kosong');
$check('APP_URL memakai https', str_starts_with($env['APP_URL'] ?? '', 'https://'), $env['APP_URL'] ?? 'kosong');
$check('DB_DATABASE bukan CI3 (db_labmania)', ($env['DB_DATABASE'] ?? '') !== '' && ($env['DB_DATABASE'] ?? '') !== 'db_labmania', $env['DB_DATABASE'] ?? 'kosong');
$check('DB_USERNAME bukan root', ($env['DB_USERNAME'] ?? '') !== '' && ($env['DB_USERNAME'] ?? '') !== 'root', $env['DB_USERNAME'] ?? 'kosong');

$check('vendor/autoload.php ada', is_file($root.'/vendor/autoload.php'), 'jalankan composer install --no-dev');
$check('tanpa paket dev (phpunit)', ! is_dir($root.'/vendor/phpunit'), 'pasang dengan --no-dev');
$check('public/build/manifest.json ada', is_file($root.'/public/build/manifest.json'), 'jalankan npm ci && npm run build');
foreach (['storage', 'storage/logs', 'storage/framework/cache', 'bootstrap/cache'] as $dir) {
    $check("$dir dapat ditulis", is_dir("$root/$dir") && is_writable("$root/$dir"));
}
if (isset($args['lock-sha256'])) {
    $sha = is_file($root.'/composer.lock') ? hash_file('sha256', $root.'/composer.lock') : '';
    $check('composer.lock sama dengan yang diuji', hash_equals(strtolower($args['lock-sha256']), $sha), $sha);
}

if (isset($args['url'])) {
    $base = rtrim($args['url'], '/');
    $status = function (string $path) use ($base): int {
        $c = curl_init($base.$path);
        curl_setopt_array($c, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10]);
        curl_exec($c);
        $code = (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);

        return $code;
    };
    $check('GET /up = 200', ($c = $status('/up')) === 200, "status $c");
    foreach (['/.env', '/.git/config', '/composer.json', '/composer.lock', '/vendor/autoload.php', '/storage/logs/laravel.log', '/database/schema/rehearsal.mysql.sql', '/artisan', '/app/Support/Audit.php'] as $path) {
        $code = $status($path);
        $check("tertutup $path", in_array($code, [403, 404], true), "status $code");
    }
}

echo "\n".($fail === 0 ? 'SEMUA LULUS' : "$fail PEMERIKSAAN GAGAL")."\n";
exit($fail === 0 ? 0 : 1);

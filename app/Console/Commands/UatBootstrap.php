<?php

namespace App\Console\Commands;

use App\Support\LocalUat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class UatBootstrap extends Command
{
    protected $signature = 'labmania:uat {--check : Periksa bootstrap dan koneksi DB tanpa menulis}';

    protected $description = 'Bootstrap data sintetis, hanya Docker UAT lokal terisolasi';

    public function handle(): int
    {
        try {
            LocalUat::guard();
            if ($this->option('check')) {
                LocalUat::ready();
                $this->info('DB UAT terhubung dan bootstrap lengkap.');

                return self::SUCCESS;
            }

            return Cache::lock('local-uat.bootstrap', 120)->block(5, function () {
                $credentials = LocalUat::bootstrap();
                if ($credentials === []) {
                    $this->info('UAT sudah diinisialisasi; data, password dan autentikator dipertahankan.');
                }
                foreach ($credentials as $username => $password) {
                    $this->line($username.' | password: '.$password);
                }
                $this->info('Data sintetis UAT. Login memakai username/password.');

                return self::SUCCESS;
            });
        } catch (Throwable $e) {
            $this->error($e::class === \RuntimeException::class ? $e->getMessage() : 'Bootstrap gagal; periksa konfigurasi/skema UAT secara privat.');

            return self::FAILURE;
        }
    }
}

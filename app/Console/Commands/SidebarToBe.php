<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\SidebarToBe as Sidebar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class SidebarToBe extends Command
{
    protected $signature = 'labmania:sidebar-to-be {--terapkan : Tulis pada salinan lokal} {--aktor= : ID Superadmin untuk audit} {--semua-modul : Susun seluruh modul sesuai mockup}';

    protected $description = 'Sesuaikan menu Master Data dengan mockup tiga level; default simulasi';

    public function handle(): int
    {
        $previous = Auth::user();
        try {
            Sidebar::guard();
            if ($this->option('terapkan')) {
                $actor = (string) $this->option('aktor');
                if (! ctype_digit($actor) || ! ($user = User::find((int) $actor))) {
                    throw new RuntimeException('Isi --aktor dengan ID Superadmin aktif.');
                }
                Auth::setUser($user);
            }
            $changes = $this->option('semua-modul') ? Sidebar::syncAll((bool) $this->option('terapkan')) : Sidebar::sync((bool) $this->option('terapkan'));
            $this->line(json_encode(['mode' => $this->option('terapkan') ? 'terapkan' : 'simulasi', 'changes' => $changes], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e instanceof RuntimeException ? $e->getMessage() : 'Penyesuaian gagal; transaksi dibatalkan. Periksa konfigurasi/skema secara privat.');

            return self::FAILURE;
        } finally {
            $previous ? Auth::setUser($previous) : Auth::forgetUser();
        }
    }
}

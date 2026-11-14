<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Memetakan hak role untuk menu baru fase 2 dengan menyalin dari menu setara (sama dengan niat SQL CI3 20261112_*.sql),
 * HANYA pada salinan labmania_laravel. Simulasi secara default; --terapkan menulis. Tidak menyentuh override user dan tidak
 * memberi hak ke role yang tidak punya hak pada menu sumbernya. Aman dijalankan ulang (insert-if-missing).
 */
class PetakanHakMenu extends Command
{
    protected $signature = 'labmania:petakan-hak-menu {--terapkan : Tulis ke database (tanpa ini hanya simulasi)}';

    protected $description = 'Salin hak role dari menu setara ke menu baru fase 2 (simulasi dulu)';

    /** [menu tujuan, aksi tujuan, menu sumber, aksi sumber] */
    private const ATURAN = [
        ['master_data.varian_pelaksanaan', 'lihat', 'master_data.jenis_bidang', 'lihat'],
        ['master_data.varian_pelaksanaan', 'tambah', 'master_data.jenis_bidang', 'tambah'],
        ['master_data.varian_pelaksanaan', 'ubah', 'master_data.jenis_bidang', 'ubah'],
        ['master_data.varian_pelaksanaan', 'aktif', 'master_data.jenis_bidang', 'aktif'],
        ['master_data.katalog', 'ubah', 'master_data.paket_produk', 'ubah'],
        ['master_data.katalog', 'cetak', 'master_data.paket_produk', 'ubah'],
    ];

    public function handle(): int
    {
        $db = DB::connection()->getDatabaseName();
        if ($db === 'db_labmania' || str_ends_with($db, '_test')) {
            $this->error("Tolak: database '$db' bukan salinan Laravel.");

            return self::FAILURE;
        }
        $apply = (bool) $this->option('terapkan');
        $menu = DB::table('ms_menu')->pluck('id', 'kode');
        $rows = [];
        DB::transaction(function () use ($menu, $apply, &$rows) {
            foreach (self::ATURAN as [$to, $toAction, $from, $fromAction]) {
                if (! isset($menu[$to], $menu[$from])) {
                    $this->warn("Lewati: menu $to atau $from tidak ada.");

                    continue;
                }
                $available = DB::table('ms_menu_aksi')->where('id_menu', $menu[$to])->where('aksi', $toAction)->exists();
                if (! $available) {
                    DB::table('ms_menu_aksi')->when($apply, fn ($q) => $q->insertOrIgnore(['id_menu' => $menu[$to], 'aksi' => $toAction]));
                }
                $groups = DB::table('ms_group_akses')->where('id_menu', $menu[$from])->where('aksi', $fromAction)->pluck('id_group');
                foreach ($groups as $group) {
                    $exists = DB::table('ms_group_akses')->where(['id_group' => $group, 'id_menu' => $menu[$to], 'aksi' => $toAction])->exists();
                    $rows[] = [$group, $to, $toAction, $exists ? 'sudah ada' : ($apply ? 'DITAMBAH' : 'akan ditambah')];
                    if (! $exists && $apply) {
                        DB::table('ms_group_akses')->insert(['id_group' => $group, 'id_menu' => $menu[$to], 'aksi' => $toAction]);
                    }
                }
            }
        });
        $this->info($apply ? 'Mode TERAPKAN pada '.$db : 'Mode SIMULASI (tidak ada yang ditulis) pada '.$db);
        $this->table(['Role', 'Menu', 'Aksi', 'Status'], $rows);

        return self::SUCCESS;
    }
}

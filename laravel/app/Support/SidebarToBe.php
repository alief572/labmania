<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SidebarToBe
{
    public const GROUPS = [
        1 => [
            'master_data.jenis_produk' => 'Jenis Produk', 'master_data.jenis_sertifikasi' => 'Jenis Sertifikat',
            'master_data.jenis_bidang' => 'Jenis Bidang', 'master_data.varian_pelaksanaan' => 'Varian Pelaksanaan',
            'master_data.paket_produk' => 'Product', 'master_data.katalog' => 'Katalog Produk', 'master_data.rate' => 'Master Rate',
        ],
        2 => ['master_data.users' => 'Master User', 'master_data.role' => 'Master Role', 'permission_group' => 'Role & Permission'],
        3 => ['master_data.peserta' => 'Master Peserta', 'master_data.company' => 'Master Company', 'master_trainer' => 'Master Trainer'],
        4 => ['master_data.template_keterangan' => 'Template Keterangan Quotation', 'master_data.template_konfirmasi' => 'Template Formulir Konfirmasi'],
    ];

    public static function guard(): void
    {
        $c = config('database.connections.'.config('database.default'));
        if (app()->environment('testing') && (($c['driver'] === 'sqlite' && $c['database'] === ':memory:')
            || ($c['driver'] === 'mysql' && str_ends_with($c['database'], '_test')))) {
            return;
        }
        if (! app()->environment('local', 'rehearsal') || $c['driver'] !== 'mysql' || $c['database'] !== 'labmania_laravel'
            || ! in_array($c['host'], ['rehearsal-db', 'uat-db', '127.0.0.1', 'localhost'], true) || ! empty($c['url'])) {
            throw new RuntimeException('Ditolak: hanya salinan Laravel lokal atau database pengujian terisolasi.');
        }
    }

    public static function sync(bool $apply = false): array
    {
        self::guard();
        if ($apply && (! Auth::user() || ! Permissions::superadmin(Auth::user()))) {
            throw new RuntimeException('Penerapan memerlukan aktor Superadmin aktif.');
        }

        return DB::transaction(function () use ($apply) {
            $menus = DB::table('ms_menu')->when($apply, fn ($q) => $q->lockForUpdate())->get()->keyBy('kode');
            $parent = $menus['m_master_data'] ?? null;
            if (! $parent || (int) $parent->parent_id !== 0 || $parent->link !== '#') {
                throw new RuntimeException('Modul m_master_data tidak lengkap atau bukan modul tingkat atas.');
            }
            $role = $menus['master_data.role'] ?? null;
            foreach (self::GROUPS as $members) {
                foreach ($members as $code => $title) {
                    if ($code !== 'master_data.role' && ! isset($menus[$code])) {
                        throw new RuntimeException('Menu sumber tidak ditemukan: '.$code);
                    }
                }
            }
            if ($menus->count() !== DB::table('ms_menu')->count()) {
                throw new RuntimeException('Kode menu duplikat; perbaiki sebelum penerapan.');
            }
            if (($role && ! in_array($role->link, ['master/role', '/master/role'], true))
                || DB::table('ms_menu')->whereIn('link', ['master/role', '/master/role'])->where('kode', '!=', 'master_data.role')->exists()) {
                throw new RuntimeException('Kode atau link Master Role berbenturan dengan menu lain.');
            }
            $source = $menus['permission_group'];
            $actions = DB::table('ms_menu_aksi')->where('id_menu', $source->id)
                ->whereIn('aksi', ['lihat', 'tambah', 'ubah', 'aktif', 'hapus'])->pluck('aksi')->all();
            if (! in_array('lihat', $actions, true)) {
                throw new RuntimeException('Katalog aksi permission_group belum lengkap.');
            }
            $changes = [];
            foreach (self::GROUPS as $group => $members) {
                $order = 0;
                foreach ($members as $code => $title) {
                    $old = isset($menus[$code]) ? (array) $menus[$code] : null;
                    $data = ['parent_id' => $parent->id, 'kelompok' => $group, 'urutan' => ++$order * 10, 'judul' => $title];
                    if ($old && ! array_diff_assoc($data, $old)) {
                        continue;
                    }
                    $changes[] = ['kode' => $code, 'old' => $old ? array_intersect_key($old, $data) : null, 'new' => $data];
                    if (! $apply) {
                        continue;
                    }
                    if ($old) {
                        DB::table('ms_menu')->where('id', $old['id'])->update($data + ['updated_by' => Auth::id(), 'updated_date' => now()]);
                        write_log('Menu Sidebar', 'Edit', 'Sesuaikan sidebar '.$title, ['old' => $old, 'new' => array_merge($old, $data)], 'ms_menu', $old['id'], $code);
                    } else {
                        $data += ['kode' => $code, 'link' => 'master/role', 'aktif' => $source->aktif, 'sistem' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
                        $id = DB::table('ms_menu')->insertGetId($data);
                        foreach ($actions as $action) {
                            DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $action]);
                        }
                        $counts = [];
                        foreach (['ms_group_akses', 'ms_user_akses'] as $table) {
                            $grants = DB::table($table)->where('id_menu', $source->id)->whereIn('aksi', $actions)->get();
                            foreach ($grants as $grant) {
                                DB::table($table)->insert(array_replace((array) $grant, ['id_menu' => $id]));
                            }
                            $counts[$table] = $grants->count();
                        }
                        write_log('Menu Sidebar', 'Tambah', 'Tambah Master Role, hak awal dari Role & Permission', $data + ['salinan_hak' => $counts], 'ms_menu', $id, $code);
                    }
                }
            }

            return $changes;
        });
    }
}

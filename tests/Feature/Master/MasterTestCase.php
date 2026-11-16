<?php

namespace Tests\Feature\Master;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Dasar tes Master Data: SQLite in-memory, fixture foundation + master, data sintetis. */
abstract class MasterTestCase extends TestCase
{
    /** kode menu => id */
    protected const MENUS = [
        'activity_log' => 1, 'permission_group' => 2, 'master_program' => 31, 'master_data.users' => 22, 'master_data.role' => 24,
        'master_data.jenis_produk' => 11, 'master_data.jenis_sertifikasi' => 12, 'master_data.jenis_bidang' => 13, 'master_data.paket_produk' => 14,
        'master_data.katalog' => 15, 'master_data.peserta' => 16, 'master_data.company' => 17, 'master_trainer' => 18,
        'master_data.template_keterangan' => 19, 'master_data.template_konfirmasi' => 20, 'master_data.rate' => 21, 'master_data.varian_pelaksanaan' => 23,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (! app()->environment('testing') || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Test hanya boleh SQLite :memory:.');
        }
        $this->withoutVite();
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/foundation.sql'));
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/master.sql'));
        DB::table('groups')->insert([['id_group' => 1, 'nm_group' => 'Superadmin', 'ket' => ''], ['id_group' => 2, 'nm_group' => 'Staf', 'ket' => '']]);
        foreach (self::MENUS as $code => $id) {
            DB::table('ms_menu')->insert(['id' => $id, 'kode' => $code, 'judul' => $code, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => $id, 'aktif' => 1, 'sistem' => 0]);
            foreach (['lihat', 'tambah', 'ubah', 'aktif', 'hapus'] as $aksi) {
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $aksi]);
            }
        }
    }

    protected function account(string $name = 'staf', array $attributes = []): User
    {
        return User::forceCreate($attributes + ['username' => $name, 'nm_lengkap' => $name, 'password' => password_hash('known-password', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
    }

    protected function login(User $user): static
    {
        return $this->actingAs($user)->withSession(['auth.verified_id' => $user->id_user, 'auth.factor' => hash('sha256', $user->password)]);
    }

    protected function admin(): User
    {
        $user = $this->account('root');
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 1]);

        return $user;
    }

    /** Beri hak langsung (override) pada satu atau lebih menu; $actions memakai nama legacy. */
    protected function allow(User $user, string $menu, string ...$actions): void
    {
        foreach ($actions as $aksi) {
            DB::table('ms_user_akses')->insert(['id_user' => $user->id_user, 'id_menu' => self::MENUS[$menu], 'aksi' => $aksi]);
        }
    }

    protected function seedAttributes(): array
    {
        $now = now();
        $ids = [];
        foreach (['ms_jenis_produk' => 'Training', 'ms_jenis_bidang' => 'K3', 'ms_varian_pelaksanaan' => 'Workshop', 'ms_jenis_sertifikasi' => 'BNSP'] as $table => $nama) {
            $ids[$table] = DB::table($table)->insertGetId(['kode' => 'X-'.$table, 'nama' => $nama, 'aktif' => 1, 'created_date' => $now]);
        }

        return $ids;
    }
}

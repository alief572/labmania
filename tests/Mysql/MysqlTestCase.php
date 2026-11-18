<?php

namespace Tests\Mysql;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Dasar tes integrasi MySQL. Menolak jalan selain di database berakhiran _test (guard sebelum DDL apa pun).
 * Skema dibangun dari DB salinan (SHOW CREATE TABLE, tanpa baris data) lalu dikosongkan per kelas tes;
 * data uji sintetis. Tidak menyentuh db_labmania maupun labmania_laravel.
 */
abstract class MysqlTestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $database = DB::connection()->getDatabaseName();
        if (config('database.default') !== 'mysql' || ! str_ends_with((string) $database, '_test') || $database === 'db_labmania') {
            throw new \RuntimeException("Tes MySQL hanya boleh pada database *_test, bukan '$database'.");
        }
        $this->withoutVite();
        $this->resetSchema();
    }

    /** Salin struktur dari labmania_laravel (tanpa data) ke database _test, lalu kosongkan. */
    private function resetSchema(): void
    {
        $source = 'labmania_laravel';
        $pdo = DB::connection()->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"') as $row) {
            $pdo->exec('DROP TABLE `'.array_values((array) $row)[0].'`');
        }
        foreach (DB::select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = "BASE TABLE"', [$source]) as $row) {
            $table = $row->TABLE_NAME;
            $ddl = (array) DB::selectOne("SHOW CREATE TABLE `$source`.`$table`");
            $pdo->exec(array_values($ddl)[1]);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        DB::table('groups')->insert([['id_group' => 1, 'nm_group' => 'Superadmin', 'ket' => ''], ['id_group' => 2, 'nm_group' => 'Staf', 'ket' => '']]);
        $this->applyLaterMigrations();
    }

    /**
     * Salinan labmania_laravel belum tentu memuat migrasi terbaru. Migrasi yang kode terbarunya butuhkan diterapkan ke database _test
     * (idempoten, hanya database _test); salinan itu sendiri tidak diubah.
     */
    private function applyLaterMigrations(): void
    {
        foreach (['2026_11_19_090000_penjualan_batal_dampak', '2026_11_19_100000_reschedule_deal_ulang', '2026_11_19_110000_penjualan_multi_master'] as $migration) {
            (require base_path('database/migrations/'.$migration.'.php'))->up();
        }
    }

    /** Salin hanya konfigurasi non-pribadi (definisi menu, satuan, satu akun COA) dari salinan ke database _test. */
    protected function seedConfiguration(): string
    {
        foreach (['ms_menu', 'ms_menu_aksi', 'ms_satuan'] as $table) {
            DB::statement("INSERT INTO `$table` SELECT * FROM `labmania_laravel`.`$table`");
        }
        // Salinan skema development dapat mendahului pemisahan menu Master Role di sidebar lokal.
        if (! DB::table('ms_menu')->where('kode', 'master_data.role')->exists()) {
            $id = DB::table('ms_menu')->insertGetId(['kode' => 'master_data.role', 'judul' => 'Master Role',
                'link' => '/master/role', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 1, 'aktif' => 1, 'sistem' => 0]);
            foreach (['lihat', 'tambah', 'ubah', 'hapus'] as $action) {
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $action]);
            }
        }
        DB::statement("INSERT INTO coa_master (no_perkiraan, nama, level) SELECT no_perkiraan, nama, level FROM labmania_laravel.coa_master WHERE level = '5' LIMIT 1");

        return (string) DB::table('coa_master')->value('no_perkiraan');
    }

    protected function superadmin(): User
    {
        $user = User::forceCreate(['username' => 'root_uji', 'nm_lengkap' => 'Root Uji', 'password' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 1]);

        return $user;
    }

    protected function loginAs(User $user): static
    {
        return $this->actingAs($user)->withSession(['auth.verified_id' => $user->id_user, 'auth.factor' => hash('sha256', $user->password)]);
    }

    protected function attributes(): array
    {
        $ids = [];
        foreach (['ms_jenis_produk' => 'Training', 'ms_jenis_bidang' => 'K3', 'ms_varian_pelaksanaan' => 'Workshop', 'ms_jenis_sertifikasi' => 'BNSP'] as $table => $nama) {
            $ids[$table] = DB::table($table)->insertGetId(['kode' => 'X-'.substr(md5($table), 0, 6), 'nama' => $nama, 'aktif' => 1, 'created_date' => now()]);
        }

        return $ids;
    }
}

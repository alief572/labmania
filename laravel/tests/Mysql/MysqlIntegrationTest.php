<?php

namespace Tests\Mysql;

use App\Models\User;
use App\Support\CodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Hal-hal yang tidak dapat dibuktikan SQLite: unique index generated column, kolasi, panjang kolom, lock, dan ENUM. */
class MysqlIntegrationTest extends MysqlTestCase
{
    public function test_activity_log_hanya_untuk_role_manajemen_dan_superadmin_dengan_data_role_nyata(): void
    {
        $this->seedConfiguration();
        DB::table('groups')->insert([['id_group' => 3, 'nm_group' => 'Manajemen', 'ket' => ''], ['id_group' => 4, 'nm_group' => 'Admin Developer', 'ket' => '']]);
        $log = DB::table('ms_menu')->where('kode', 'activity_log')->value('id');
        config(['labmania.activity_log_role_ids' => [3]]);
        $akun = [];
        foreach ([2 => 'staf', 3 => 'manajemen', 4 => 'developer'] as $role => $nama) {
            $u = User::forceCreate(['username' => $nama, 'nm_lengkap' => $nama, 'password' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
            DB::table('user_groups')->insert(['id_user' => $u->id_user, 'id_group' => $role]);
            DB::table('ms_group_akses')->insert(['id_group' => $role, 'id_menu' => $log, 'aksi' => 'lihat']);
            $akun[$role] = $u;
        }
        $this->loginAs($akun[3])->get('/activity-log')->assertOk();
        $this->loginAs($akun[4])->get('/activity-log')->assertForbidden();
        $this->loginAs($akun[2])->get('/activity-log')->assertForbidden();
        $this->loginAs($this->superadmin())->get('/activity-log')->assertOk();
    }

    private function paket(array $ids, string $nomor, int $aktif, ?int $varian = -1): array
    {
        return ['nomor' => $nomor, 'judul' => $nomor, 'id_jenis_produk' => $ids['ms_jenis_produk'], 'id_jenis_bidang' => $ids['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $ids['ms_jenis_sertifikasi'],
            'id_varian_pelaksanaan' => $varian === -1 ? $ids['ms_varian_pelaksanaan'] : $varian, 'aktif' => $aktif];
    }

    public function test_unique_kombinasi_aktif_ditegakkan_mysql_dan_nonaktif_boleh_ganda(): void
    {
        $ids = $this->attributes();
        DB::table('paket_produk')->insert($this->paket($ids, 'PC-1', 1));
        DB::table('paket_produk')->insert($this->paket($ids, 'PC-2', 0));
        DB::table('paket_produk')->insert($this->paket($ids, 'PC-3', 0));
        $this->assertSame(3, DB::table('paket_produk')->count());
        try {
            DB::table('paket_produk')->insert($this->paket($ids, 'PC-4', 1));
            $this->fail('Duplikat aktif lolos');
        } catch (QueryException $e) {
            $this->assertStringContainsString('uk_paket_kombinasi_aktif', $e->getMessage());
        }
        $this->assertNull(DB::table('paket_produk')->where('nomor', 'PC-2')->value('kombinasi_aktif'));
        $this->assertSame($ids['ms_jenis_produk'].'-'.$ids['ms_jenis_sertifikasi'].'-'.$ids['ms_jenis_bidang'].'-'.$ids['ms_varian_pelaksanaan'], DB::table('paket_produk')->where('nomor', 'PC-1')->value('kombinasi_aktif'));
    }

    public function test_paket_lama_dengan_varian_null_tetap_unik_di_antara_aktif(): void
    {
        $ids = $this->attributes();
        DB::table('paket_produk')->insert($this->paket($ids, 'PC-1', 1, null));
        $this->expectException(QueryException::class);
        DB::table('paket_produk')->insert($this->paket($ids, 'PC-2', 1, null));
    }

    public function test_nama_atribut_unik_di_database(): void
    {
        $this->attributes();
        $this->expectException(QueryException::class);
        DB::table('ms_jenis_produk')->insert(['kode' => 'JP-9', 'nama' => 'Training', 'aktif' => 1]);
    }

    public function test_kolasi_unique_nama_tidak_peka_huruf_besar_kecil(): void
    {
        $this->attributes();
        $this->expectException(QueryException::class);
        DB::table('ms_jenis_produk')->insert(['kode' => 'JP-9', 'nama' => 'TRAINING', 'aktif' => 1]);
    }

    public function test_kolom_peserta_dilonggarkan_untuk_peserta_dari_deal(): void
    {
        DB::table('ms_peserta')->insert(['kode' => 'PS-001', 'nama' => 'Dari Deal']);
        $row = DB::table('ms_peserta')->first();
        $this->assertNull($row->gender);
        $this->assertNull($row->email);
        $this->assertNull($row->nik);
        $this->expectException(QueryException::class);
        DB::table('ms_peserta')->insert(['kode' => 'PS-002', 'nama' => 'X', 'gender' => 'Z']);
    }

    public function test_akun_trainer_unik_tetapi_banyak_null_boleh(): void
    {
        DB::table('ms_trainer')->insert([['kode' => 'TR-1', 'nama' => 'A', 'aktif' => 1], ['kode' => 'TR-2', 'nama' => 'B', 'aktif' => 1]]);
        DB::table('ms_trainer')->insert(['kode' => 'TR-3', 'nama' => 'C', 'aktif' => 1, 'id_user' => 5]);
        $this->expectException(QueryException::class);
        DB::table('ms_trainer')->insert(['kode' => 'TR-4', 'nama' => 'D', 'aktif' => 1, 'id_user' => 5]);
    }

    public function test_jp_nullable_untuk_data_lama_dan_unik_product_per_program(): void
    {
        DB::table('master_program')->insert(['nomor' => 'MP-2601-001', 'durasi_hari' => 1, 'target_peserta' => 10, 'harga_jual_peserta' => 1, 'status' => 'draft']);
        $this->assertNull(DB::table('master_program')->value('jam_pembelajaran'));
        DB::table('master_program_produk')->insert(['id_master_program' => 1, 'id_paket_produk' => 5, 'urutan' => 1]);
        $this->expectException(QueryException::class);
        DB::table('master_program_produk')->insert(['id_master_program' => 1, 'id_paket_produk' => 5, 'urutan' => 2]);
    }

    public function test_kode_otomatis_berurutan_dan_unique_menjaga_bentrok(): void
    {
        DB::transaction(function () {
            for ($i = 0; $i < 3; $i++) {
                $kode = CodeGenerator::next('ms_jenis_produk', 'JP');
                DB::table('ms_jenis_produk')->insert(['kode' => $kode, 'nama' => 'N'.$i, 'aktif' => 1]);
            }
        });
        $this->assertSame(['JP-001', 'JP-002', 'JP-003'], DB::table('ms_jenis_produk')->orderBy('id')->pluck('kode')->all());
        $this->expectException(QueryException::class);
        DB::table('ms_jenis_produk')->insert(['kode' => 'JP-003', 'nama' => 'Bentrok', 'aktif' => 1]);
    }

    public function test_lock_for_update_membuat_simpan_paralel_menunggu_dua_koneksi(): void
    {
        DB::table('ms_jenis_produk')->insert(['kode' => 'JP-001', 'nama' => 'A', 'aktif' => 1]);
        DB::beginTransaction();
        DB::table('ms_jenis_produk')->where('kode', 'like', 'JP-%')->lockForUpdate()->get();

        $env = array_map(fn ($k) => config("database.connections.mysql.$k"), ['host', 'port', 'database', 'username', 'password']);
        $second = new \PDO("mysql:host={$env[0]};port={$env[1]};dbname={$env[2]}", $env[3], $env[4], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $second->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $second->beginTransaction();
        try {
            $second->query("SELECT kode FROM ms_jenis_produk WHERE kode LIKE 'JP-%' FOR UPDATE")->fetchAll();
            $this->fail('Koneksi kedua seharusnya menunggu lock');
        } catch (\PDOException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            $second->rollBack();
            DB::rollBack();
        }
    }

    public function test_enum_gender_dan_panjang_kolom_ditolak_mode_ketat(): void
    {
        $this->attributes();
        $this->expectException(QueryException::class);
        DB::table('ms_jenis_produk')->insert(['kode' => 'JP-9', 'nama' => str_repeat('x', 101), 'aktif' => 1]);
    }
}

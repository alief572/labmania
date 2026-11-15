<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait ServerDataTableScenarios
{
    private const TABLES = [
        'jenis-produk' => 'master_data.jenis_produk', 'jenis-sertifikat' => 'master_data.jenis_sertifikasi',
        'jenis-bidang' => 'master_data.jenis_bidang', 'varian-pelaksanaan' => 'master_data.varian_pelaksanaan',
        'product' => 'master_data.paket_produk', 'rate/fixed' => 'master_data.rate', 'rate/variable' => 'master_data.rate',
        'user' => 'master_data.users', 'role' => 'master_data.role', 'peserta' => 'master_data.peserta',
        'company' => 'master_data.company', 'trainer' => 'master_trainer',
        'template-keterangan' => 'master_data.template_keterangan', 'template-konfirmasi' => 'master_data.template_konfirmasi',
    ];

    private function tableData(string $path, array $input = [])
    {
        return $this->getJson('/master/'.$path.'/data?'.http_build_query($input + ['draw' => 3, 'start' => 0, 'length' => 10]));
    }

    public function test_seluruh_tabel_mengikuti_protokol_dan_tidak_menulis_data(): void
    {
        $before = DB::table('logs')->count();
        foreach (array_keys(self::TABLES) as $path) {
            $response = $this->tableData($path)->assertOk()->assertJsonPath('draw', 3)
                ->assertJsonStructure(['recordsTotal', 'recordsFiltered', 'data']);
            $this->assertIsInt($response->json('recordsTotal'));
            $this->assertSame($response->json('recordsTotal'), $response->json('recordsFiltered'));
            $this->assertLessThanOrEqual(10, count($response->json('data')));
            $this->tableData($path, ['search' => ['value' => 'tidak-ada-hasil', 'regex' => 'false']])
                ->assertOk()->assertJsonPath('recordsFiltered', 0)->assertJsonCount(0, 'data');
        }
        $this->assertSame($before, DB::table('logs')->count());
    }

    public function test_pagination_pencarian_nol_status_dan_urutan_tetap(): void
    {
        for ($i = 1; $i <= 31; $i++) {
            DB::table('ms_jenis_produk')->insert(['kode' => sprintf('JP-%03d', $i), 'nama' => sprintf('Produk %02d', $i), 'aktif' => $i % 2]);
        }
        $this->tableData('jenis-produk', ['start' => 10, 'order' => [['column' => 2, 'dir' => 'desc']]])
            ->assertOk()->assertJsonPath('recordsTotal', 31)->assertJsonPath('recordsFiltered', 31)
            ->assertJsonCount(10, 'data')->assertJsonPath('data.0.nama', 'Produk 21')->assertJsonPath('data.9.nama', 'Produk 12');
        $this->tableData('jenis-produk', ['status' => 'off', 'search' => ['value' => '0', 'regex' => 'false']])
            ->assertOk()->assertJsonPath('recordsTotal', 31)->assertJsonPath('recordsFiltered', 15)->assertJsonCount(10, 'data');
        $first = $this->tableData('jenis-produk', ['order' => [['column' => 3, 'dir' => 'asc']]])->assertOk()->json('data');
        $second = $this->tableData('jenis-produk', ['start' => 10, 'order' => [['column' => 3, 'dir' => 'asc']]])->assertOk()->json('data');
        $this->assertSame('JP-002', $first[0]['kode']);
        $this->assertSame('JP-022', $second[0]['kode']);
        $this->assertSame([], array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->tableData('jenis-produk', ['start' => 100])->assertOk()->assertJsonPath('recordsFiltered', 31)->assertJsonCount(0, 'data');
        $this->tableData('jenis-produk', ['search' => ['value' => "' OR 1=1 --"]])->assertOk()->assertJsonPath('recordsFiltered', 0);
    }

    public function test_input_berbahaya_ditolak_dan_nama_kolom_klien_diabaikan(): void
    {
        foreach ([['draw' => '<script>'], ['start' => -1], ['length' => -1], ['length' => 10000],
            ['search' => ['regex' => 'true']], ['search' => ['value' => str_repeat('a', 101)]], ['q' => ['bukan-string']],
            ['order' => [['column' => 999, 'dir' => 'asc']]], ['order' => [['column' => 1, 'dir' => 'desc; DROP TABLE users']]],
            ['columns' => [['search' => ['value' => 'tidak-didukung']]]],
        ] as $input) {
            $this->tableData('jenis-produk', $input)->assertUnprocessable();
        }
        DB::table('ms_jenis_produk')->insert(['kode' => 'JP-001', 'nama' => '<img src=x onerror=alert(1)>']);
        $this->tableData('jenis-produk', ['columns' => [['data' => 'password', 'name' => 'users.password']], 'order' => [['column' => 1, 'dir' => 'desc']]])
            ->assertOk()->assertJsonPath('data.0.nama', '<img src=x onerror=alert(1)>');
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('users'));
    }

    public function test_join_filter_role_dan_field_akun_tetap_aman(): void
    {
        $this->post('/master/user', ['nama' => 'Pegawai Uji', 'id_group' => 2, 'akun' => true, 'username' => 'pegawai.uji',
            'password' => 'sangat-rahasia', 'password_confirmation' => 'sangat-rahasia'])->assertSessionHasNoErrors();
        $user = DB::table('users')->where('username', 'pegawai.uji')->value('id_user');
        $menu = DB::table('ms_menu')->where('kode', 'master_data.users')->value('id');
        foreach (['lihat', 'ubah'] as $action) {
            DB::table('ms_user_akses')->insert(['id_user' => $user, 'id_menu' => $menu, 'aksi' => $action]);
        }
        $response = $this->tableData('user', ['role' => 2, 'status' => 'on', 'search' => ['value' => 'pegawai.uji']])
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.n_override', 2)->assertJsonPath('data.0.nm_group', 'Staf');
        foreach (['password', 'ga_secret', 'remember_token', 'created_by'] as $field) {
            $this->assertArrayNotHasKey($field, $response->json('data.0'));
        }
        $this->assertStringNotContainsString('sangat-rahasia', $response->getContent());
        $this->tableData('user', ['role' => 999])->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 0);
        $this->tableData('role', ['order' => [['column' => 2, 'dir' => 'desc']]])->assertOk()->assertJsonPath('data.0.n_user', 1);
    }

    public function test_biaya_diurutkan_numerik_dan_total_terpisah_per_tab(): void
    {
        foreach (['fixed', 'variable'] as $type) {
            $unit = DB::table('ms_satuan')->insertGetId(['category' => 'rate_'.$type, 'code' => 'uji_'.$type, 'nama' => 'Satuan Uji']);
            foreach ([9, 100, 20] as $cost) {
                DB::table('ms_rate')->insert(['kode' => $type.'-'.$cost, 'tipe' => $type, 'item' => 'Biaya '.$cost,
                    'id_satuan' => $unit, 'default_cost' => $cost, 'aktif' => $cost === 9 ? 0 : 1]);
            }
        }
        $response = $this->tableData('rate/fixed', ['status' => 'on', 'order' => [['column' => 4, 'dir' => 'desc']]])
            ->assertOk()->assertJsonPath('recordsTotal', 3)->assertJsonPath('recordsFiltered', 2);
        $this->assertSame([100, 20], array_map('intval', array_column($response->json('data'), 'default_cost')));
        $this->tableData('rate/variable', ['status' => 'off'])->assertOk()->assertJsonPath('recordsTotal', 3)
            ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.kode', 'variable-9');
    }

    public function test_product_dapat_dicari_dari_nama_relasi_dan_template_tetap_array(): void
    {
        $ids = [];
        foreach (['ms_jenis_produk', 'ms_jenis_bidang', 'ms_jenis_sertifikasi', 'ms_varian_pelaksanaan'] as $table) {
            $ids[] = DB::table($table)->insertGetId(['kode' => 'UJI', 'nama' => 'Nama '.$table, 'aktif' => 1]);
        }
        $this->post('/master/product', ['judul' => 'Produk Uji', 'rows' => [
            ['produk' => $ids[0], 'bidang' => $ids[1], 'sertifikasi' => $ids[2], 'varian' => $ids[3]],
        ]])->assertSessionHasNoErrors();
        $this->tableData('product', ['search' => ['value' => 'Nama ms_jenis_bidang'], 'status' => 'on'])
            ->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.nama_varian', 'Nama ms_varian_pelaksanaan');
        $this->post('/master/template-konfirmasi', ['nama' => 'Form Uji', 'label_kegiatan' => 'Pelatihan',
            'id_jenis_produk' => $ids[0], 'isian' => ['nama', 'email'], 'pernyataan' => 'Benar.'])->assertSessionHasNoErrors();
        $this->tableData('template-konfirmasi', ['search' => ['value' => 'Nama ms_jenis_produk']])
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.isian', ['nama', 'email']);
    }
}

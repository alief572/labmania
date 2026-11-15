<?php

namespace Tests\Feature\Master;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProductTest extends MasterTestCase
{
    private function row(array $ids, array $over = []): array
    {
        return $over + ['produk' => $ids['ms_jenis_produk'], 'bidang' => $ids['ms_jenis_bidang'], 'varian' => $ids['ms_varian_pelaksanaan'], 'sertifikasi' => $ids['ms_jenis_sertifikasi']];
    }

    public function test_atribut_kode_otomatis_nama_unik_dan_log(): void
    {
        $this->login($this->admin());
        $this->post('/master/jenis-produk', ['nama' => 'Training'])->assertRedirect();
        $this->post('/master/jenis-produk', ['nama' => 'Training'])->assertSessionHasErrors('nama');
        $this->post('/master/jenis-produk', ['nama' => 'Workshop'])->assertRedirect();
        $this->assertSame(['JP-001', 'JP-002'], DB::table('ms_jenis_produk')->orderBy('id')->pluck('kode')->all());
        $id = DB::table('ms_jenis_produk')->value('id');
        $this->put("/master/jenis-produk/$id", ['nama' => 'Workshop'])->assertSessionHasErrors('nama');
        $this->put("/master/jenis-produk/$id", ['nama' => 'Pelatihan'])->assertRedirect();
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertSame('Training', $log['old']['nama']);
        $this->assertSame('Pelatihan', $log['new']['nama']);
        $this->post("/master/jenis-produk/$id/toggle")->assertRedirect();
        $this->assertSame(0, (int) DB::table('ms_jenis_produk')->where('id', $id)->value('aktif'));
        $this->assertDatabaseHas('logs', ['action' => 'Nonaktifkan', 'ref_no' => 'JP-001']);
        $this->get('/master/tidak-ada')->assertNotFound();
    }

    public function test_tanpa_hak_ditolak_langsung(): void
    {
        $user = $this->account();
        $this->login($user)->get('/master/jenis-produk')->assertForbidden();
        $this->post('/master/jenis-produk', ['nama' => 'X'])->assertForbidden();
        $this->allow($user, 'master_data.jenis_produk', 'lihat');
        $this->get('/master/jenis-produk')->assertOk();
        $this->post('/master/jenis-produk', ['nama' => 'X'])->assertForbidden();
        $this->post('/master/jenis-produk/1/toggle')->assertForbidden();
    }

    public function test_n_baris_menjadi_n_product_atau_gagal_semua(): void
    {
        $ids = $this->seedAttributes();
        $second = DB::table('ms_jenis_bidang')->insertGetId(['kode' => 'B-2', 'nama' => 'Lingkungan', 'aktif' => 1]);
        $this->login($this->admin());
        $this->post('/master/product', ['judul' => 'Paket A', 'rows' => [$this->row($ids), $this->row($ids, ['bidang' => $second])]])->assertRedirect();
        $this->assertSame(['PC-001', 'PC-002'], DB::table('paket_produk')->orderBy('id')->pluck('nomor')->all());

        $this->post('/master/product', ['judul' => 'Paket B', 'rows' => [$this->row($ids, ['bidang' => $second]), $this->row($ids)]])->assertSessionHasErrors();
        $this->assertSame(2, DB::table('paket_produk')->count());

        $this->post('/master/product', ['judul' => 'Paket C', 'rows' => [$this->row($ids), $this->row($ids)]])->assertSessionHasErrors();
        $this->assertSame(2, DB::table('paket_produk')->count());
    }

    public function test_unik_hanya_di_antara_product_aktif_dan_reaktivasi_ditolak(): void
    {
        $ids = $this->seedAttributes();
        $this->login($this->admin());
        $this->post('/master/product', ['judul' => 'Lama', 'rows' => [$this->row($ids)]]);
        $lama = DB::table('paket_produk')->value('id');
        $this->post("/master/product/$lama/toggle")->assertRedirect();
        $this->post('/master/product', ['judul' => 'Baru', 'rows' => [$this->row($ids)]])->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('paket_produk')->count());
        $this->post("/master/product/$lama/toggle")->assertSessionHasErrors('status');
        $this->assertSame(0, (int) DB::table('paket_produk')->where('id', $lama)->value('aktif'));
        $baru = DB::table('paket_produk')->where('id', '!=', $lama)->value('id');
        $this->post("/master/product/$baru/toggle");
        $this->post("/master/product/$lama/toggle")->assertSessionHasNoErrors();
        $this->assertSame(1, (int) DB::table('paket_produk')->where('id', $lama)->value('aktif'));
    }

    public function test_database_menolak_duplikat_aktif_walau_cek_aplikasi_dilewati(): void
    {
        $ids = $this->seedAttributes();
        $row = ['judul' => 'x', 'id_jenis_produk' => $ids['ms_jenis_produk'], 'id_jenis_bidang' => $ids['ms_jenis_bidang'], 'id_varian_pelaksanaan' => $ids['ms_varian_pelaksanaan'], 'id_jenis_sertifikasi' => $ids['ms_jenis_sertifikasi'], 'aktif' => 1];
        DB::table('paket_produk')->insert($row + ['nomor' => 'PC-1']);
        $this->expectException(QueryException::class);
        DB::table('paket_produk')->insert($row + ['nomor' => 'PC-2']);
    }

    public function test_edit_mencatat_old_new_dan_menolak_bentrok(): void
    {
        $ids = $this->seedAttributes();
        $second = DB::table('ms_jenis_bidang')->insertGetId(['kode' => 'B-2', 'nama' => 'Lingkungan', 'aktif' => 1]);
        $this->login($this->admin());
        $this->post('/master/product', ['judul' => 'A', 'rows' => [$this->row($ids), $this->row($ids, ['bidang' => $second])]]);
        [$a, $b] = DB::table('paket_produk')->orderBy('id')->pluck('id')->all();
        $payload = ['judul' => 'A2', 'produk' => $ids['ms_jenis_produk'], 'bidang' => $second, 'varian' => $ids['ms_varian_pelaksanaan'], 'sertifikasi' => $ids['ms_jenis_sertifikasi']];
        $this->put("/master/product/$a", $payload)->assertSessionHasErrors('kombinasi');
        $this->put("/master/product/$b", ['judul' => 'B2'] + $payload)->assertSessionHasNoErrors();
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertSame('A', $log['old']['judul'] ?? 'A');
        $this->assertArrayHasKey('new', $log);
    }

    public function test_atribut_nonaktif_tidak_boleh_dipilih_untuk_product_baru(): void
    {
        $ids = $this->seedAttributes();
        DB::table('ms_jenis_bidang')->where('id', $ids['ms_jenis_bidang'])->update(['aktif' => 0]);
        $this->login($this->admin());
        $this->post('/master/product', ['judul' => 'X', 'rows' => [$this->row($ids)]])->assertSessionHasErrors();
        $this->assertSame(0, DB::table('paket_produk')->count());
    }

    public function test_lingkup_batas_duplikat_dan_log(): void
    {
        $ids = $this->seedAttributes();
        $admin = $this->admin();
        $this->login($admin)->post('/master/product', ['judul' => 'A', 'rows' => [$this->row($ids)]]);
        $id = DB::table('paket_produk')->value('id');
        $this->put("/master/product/$id/lingkup", ['items' => ['K3 Umum', '', 'k3 umum']])->assertSessionHasErrors('lingkup');
        $this->put("/master/product/$id/lingkup", ['items' => [str_repeat('a', 201)]])->assertSessionHasErrors('lingkup');
        $this->put("/master/product/$id/lingkup", ['items' => array_map(fn ($i) => "Item $i", range(1, 21))])->assertSessionHasErrors('lingkup');
        $this->assertSame(0, DB::table('paket_produk_lingkup')->count());
        $this->put("/master/product/$id/lingkup", ['items' => ['K3 Umum', '', 'Ahli K3']])->assertSessionHasNoErrors();
        $this->assertSame(['K3 Umum', 'Ahli K3'], DB::table('paket_produk_lingkup')->orderBy('urutan')->pluck('lingkup')->all());
        $log = json_decode(DB::table('logs')->where('description', 'like', 'Update Lingkup%')->value('data'), true);
        $this->assertStringContainsString('1. K3 Umum', $log['new']['lingkup']);
        $this->put("/master/product/$id/lingkup", ['items' => ['K3 Umum', 'Ahli K3']])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('logs')->where('description', 'like', 'Update Lingkup%')->count());
    }

    public function test_katalog_hanya_product_aktif_dan_pdf_kosong_ditolak(): void
    {
        $ids = $this->seedAttributes();
        $this->login($this->admin())->post('/master/product', ['judul' => 'Katalog A', 'rows' => [$this->row($ids)]]);
        $this->get('/master/katalog')->assertOk()->assertInertia(fn ($page) => $page->where('groups.0.items.0.judul', 'Katalog A'));
        $this->get('/master/katalog?bidang=999')->assertInertia(fn ($page) => $page->where('groups', []));
        $this->get('/master/katalog/pdf?bidang=999')->assertSessionHasErrors('pdf');
        $this->assertDatabaseMissing('logs', ['action' => 'Cetak']);
        $response = $this->get('/master/katalog/pdf');
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertDatabaseHas('logs', ['action' => 'Cetak', 'ref_no' => 'Katalog Produk']);
    }
}

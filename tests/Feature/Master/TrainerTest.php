<?php

namespace Tests\Feature\Master;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TrainerTest extends MasterTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/trainer-'.bin2hex(random_bytes(6)));
        mkdir($this->root, 0777, true);
        config(['labmania.document_root' => $this->root, 'labmania.legacy_document_root' => null]);
        $this->login($this->admin());
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->root);
        parent::tearDown();
    }

    private function rmrf(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item !== '.' && $item !== '..') {
                // @: di Windows berkas yang baru dihapus bisa tertunda selama handle UploadedFile palsu masih dipegang exception.
                is_dir("$dir/$item") ? $this->rmrf("$dir/$item") : @unlink("$dir/$item");
            }
        }
        @rmdir($dir);
    }

    private function data(array $over = []): array
    {
        return $over + ['nama' => 'Ivan Uji', 'tempat_lahir' => 'Bandung', 'tgl_lahir' => '1985-05-01', 'bidang_keahlian' => "K3 Umum;\nAhli K3", 'pelatihan' => [['judul' => 'Seminar K3', 'penyelenggara' => 'Kemnaker', 'waktu' => '2024'], ['judul' => '', 'penyelenggara' => '', 'waktu' => '']]];
    }

    public function test_tambah_trainer_kode_riwayat_dan_keahlian_dinormalkan(): void
    {
        $this->post('/master/trainer', $this->data())->assertSessionHasNoErrors();
        $t = DB::table('ms_trainer')->first();
        $this->assertSame('TR-001', $t->kode);
        $this->assertSame("K3 Umum\nAhli K3", $t->bidang_keahlian);
        $this->assertSame(1, DB::table('ms_trainer_pelatihan')->count());
        $this->assertDatabaseHas('logs', ['action' => 'Tambah', 'ref_no' => 'TR-001']);
        $this->post('/master/trainer', $this->data(['nama' => 'Dua']));
        $this->assertSame('TR-002', DB::table('ms_trainer')->orderByDesc('id')->value('kode'));
    }

    public function test_validasi_nama_tanggal_dan_baris_riwayat(): void
    {
        $this->post('/master/trainer', $this->data(['nama' => '']))->assertSessionHasErrors('nama');
        $this->post('/master/trainer', $this->data(['tgl_lahir' => '2999-01-01']))->assertSessionHasErrors('tgl_lahir');
        $this->post('/master/trainer', $this->data(['tgl_lahir' => '31-12-2000']))->assertSessionHasErrors('tgl_lahir');
        $this->post('/master/trainer', $this->data(['pelatihan' => [['judul' => '', 'penyelenggara' => 'X', 'waktu' => '']]]))->assertSessionHasErrors('pelatihan.0.judul');
        $this->assertSame(0, DB::table('ms_trainer')->count());
    }

    public function test_akun_login_opsional_satu_akun_satu_trainer_dan_harus_aktif(): void
    {
        $akun = $this->account('trainer1');
        $this->post('/master/trainer', $this->data(['id_user' => $akun->id_user]))->assertSessionHasNoErrors();
        $this->post('/master/trainer', $this->data(['nama' => 'Lain', 'id_user' => $akun->id_user]))->assertSessionHasErrors('id_user');
        $this->post('/master/trainer', $this->data(['nama' => 'Lepas']))->assertSessionHasNoErrors();
        $this->post('/master/trainer', $this->data(['nama' => 'Lepas 2']))->assertSessionHasNoErrors();
        $this->assertSame(3, DB::table('ms_trainer')->count());
        $nonaktif = $this->account('mati', ['st_aktif' => 0]);
        $this->post('/master/trainer', $this->data(['nama' => 'X', 'id_user' => $nonaktif->id_user]))->assertSessionHasErrors('id_user');
        $this->post('/master/trainer', $this->data(['nama' => 'Y', 'id_user' => 9999]))->assertSessionHasErrors('id_user');
    }

    public function test_edit_mengganti_riwayat_mencatat_old_new_dan_toggle_tanpa_hapus(): void
    {
        $this->post('/master/trainer', $this->data());
        $id = DB::table('ms_trainer')->value('id');
        $this->post("/master/trainer/$id", $this->data(['nama' => 'Ivan Baru', 'pelatihan' => []]))->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('ms_trainer_pelatihan')->count());
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertSame('Ivan Uji', $log['old']['nama']);
        $this->assertSame('Ivan Baru', $log['new']['nama']);
        $this->post("/master/trainer/$id/toggle")->assertRedirect();
        $this->assertSame(0, (int) DB::table('ms_trainer')->where('id', $id)->value('aktif'));
        $this->assertSame(404, $this->delete("/master/trainer/$id")->status() === 405 ? 404 : 0);
        $this->getJson('/master/trainer/data')->assertOk()->assertJsonPath('data.0.n_penjualan', 0);
    }

    public function test_lampiran_tersimpan_privat_diunduh_berizin_dan_nama_klien_tidak_dipercaya(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('sertifikat.pdf', '%PDF-1.4 uji');
        $this->post('/master/trainer', $this->data(['lampiran' => [['dokumen' => 'Sertifikat K3', 'tahun' => '2024', 'file' => $pdf]]]), ['Accept' => 'text/html'])->assertSessionHasNoErrors();
        $row = DB::table('ms_trainer_lampiran')->first();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.pdf$/', $row->file_path);
        $this->assertFileExists($this->root.'/uploads/trainers/'.$row->file_path);
        $this->get("/documents/trainer/{$row->id}")->assertOk()->assertDownload('Sertifikat K3.pdf');
        $this->assertDatabaseHas('logs', ['action' => 'Unduh', 'ref_table' => 'ms_trainer_lampiran']);

        $viewer = $this->account('viewer');
        $this->login($viewer)->get("/documents/trainer/{$row->id}")->assertForbidden();
        $this->allow($viewer, 'master_trainer', 'lihat');
        $this->get("/documents/trainer/{$row->id}")->assertOk();

        DB::table('ms_trainer_lampiran')->where('id', $row->id)->update(['file_path' => '../../x.pdf']);
        $this->get("/documents/trainer/{$row->id}")->assertNotFound();
    }

    public function test_lampiran_tipe_dan_ukuran_ditolak_dan_berkas_lama_dibuang_saat_diganti(): void
    {
        $this->post('/master/trainer', $this->data(['lampiran' => [['dokumen' => 'Bad', 'file' => UploadedFile::fake()->create('x.exe', 10)]]]))->assertSessionHasErrors('lampiran.0.file');
        $this->post('/master/trainer', $this->data(['lampiran' => [['dokumen' => 'Besar', 'file' => UploadedFile::fake()->create('x.pdf', 6000)]]]))->assertSessionHasErrors('lampiran.0.file');
        $this->assertSame(0, DB::table('ms_trainer')->count());

        $this->post('/master/trainer', $this->data(['lampiran' => [['dokumen' => 'A', 'file' => UploadedFile::fake()->createWithContent('a.pdf', '%PDF a')]]]));
        $id = DB::table('ms_trainer')->value('id');
        $old = DB::table('ms_trainer_lampiran')->value('file_path');
        $lid = DB::table('ms_trainer_lampiran')->value('id');
        $this->post("/master/trainer/$id", $this->data(['lampiran' => [['dokumen' => 'A', 'file_id' => $lid]]]));
        $this->assertSame($old, DB::table('ms_trainer_lampiran')->value('file_path'));
        $this->assertFileExists($this->root.'/uploads/trainers/'.$old);
        $this->post("/master/trainer/$id", $this->data(['lampiran' => [['dokumen' => 'A', 'file_id' => 99999]]]));
        $this->assertNull(DB::table('ms_trainer_lampiran')->value('file_path'));
        $this->assertFileDoesNotExist($this->root.'/uploads/trainers/'.$old);
    }

    public function test_gagal_simpan_membuang_berkas_baru(): void
    {
        DB::unprepared("CREATE TRIGGER tolak_log BEFORE INSERT ON logs BEGIN SELECT RAISE(ABORT, 'uji'); END;");
        $this->withoutExceptionHandling();
        try {
            $this->post('/master/trainer', $this->data(['lampiran' => [['dokumen' => 'A', 'file' => UploadedFile::fake()->createWithContent('a.pdf', '%PDF a')]]]));
            $this->fail('Harus gagal');
        } catch (\Throwable) {
        }
        $this->assertSame(0, DB::table('ms_trainer')->count());
        $this->assertSame([], glob($this->root.'/uploads/trainers/*') ?: []);
    }

    public function test_hak_diperiksa_di_server(): void
    {
        $viewer = $this->account('viewer');
        $this->allow($viewer, 'master_trainer', 'lihat');
        $this->login($viewer)->get('/master/trainer')->assertOk();
        $this->post('/master/trainer', $this->data())->assertForbidden();
        $this->post('/master/trainer/1', $this->data())->assertForbidden();
        $this->post('/master/trainer/1/toggle')->assertForbidden();
    }
}

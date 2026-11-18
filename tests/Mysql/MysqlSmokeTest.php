<?php

namespace Tests\Mysql;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Alur utama fase 2 lewat HTTP pada MySQL asli (database _test): membuktikan SQL yang ditulis untuk MySQL
 * (kata cadangan, GROUP BY ketat, subquery, ORDER BY CASE, generated column) benar, bukan hanya di SQLite.
 */
class MysqlSmokeTest extends MysqlTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/mysql-'.bin2hex(random_bytes(5)));
        mkdir($this->root, 0777, true);
        config(['labmania.document_root' => $this->root, 'labmania.legacy_document_root' => null]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/uploads/trainers/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->root.'/uploads/trainers');
        @rmdir($this->root.'/uploads');
        @rmdir($this->root);
        parent::tearDown();
    }

    public function test_seluruh_alur_master_data_dan_program_berjalan_di_mysql(): void
    {
        $this->seedConfiguration();
        $coa = 'SMOKE-5';
        DB::table('coa_master')->insert(['no_perkiraan' => $coa, 'nama' => 'COA fixture smoke test', 'level' => '5']);
        $root = $this->superadmin();
        $this->loginAs($root);
        $sat = DB::table('ms_satuan')->whereIn('code', ['event', 'day', 'peserta_event', 'peserta_day'])->pluck('id', 'code');
        $this->assertCount(4, $sat, 'Salinan harus memuat empat satuan rate');

        // Atribut, Product, Lingkup, Katalog
        foreach (['jenis-produk' => 'Training', 'jenis-sertifikat' => 'BNSP', 'jenis-bidang' => 'K3', 'varian-pelaksanaan' => 'Workshop'] as $slug => $nama) {
            $this->post("/master/$slug", ['nama' => $nama])->assertSessionHasNoErrors();
        }
        $row = ['produk' => DB::table('ms_jenis_produk')->value('id'), 'bidang' => DB::table('ms_jenis_bidang')->value('id'), 'varian' => DB::table('ms_varian_pelaksanaan')->value('id'), 'sertifikasi' => DB::table('ms_jenis_sertifikasi')->value('id')];
        $this->post('/master/product', ['judul' => 'K3 Umum', 'rows' => [$row]])->assertSessionHasNoErrors();
        $this->post('/master/product', ['judul' => 'Duplikat', 'rows' => [$row]])->assertSessionHasErrors();
        $paket = (int) DB::table('paket_produk')->value('id');
        $this->put("/master/product/$paket/lingkup", ['items' => ['K3 Umum', 'Ahli K3']])->assertSessionHasNoErrors();
        $this->get('/master/katalog')->assertOk()->assertInertia(fn ($p) => $p->where('groups.0.items.0.lingkup.1', 'Ahli K3'));
        $pdf = $this->get('/master/katalog/pdf');
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->post("/master/product/$paket/toggle")->assertRedirect();
        $this->post('/master/product', ['judul' => 'Setelah nonaktif', 'rows' => [$row]])->assertSessionHasNoErrors();
        $this->post("/master/product/$paket/toggle")->assertSessionHasErrors('status');

        // Rate dan template
        $this->post('/master/rate/fixed', ['item' => 'Sewa Ruang', 'id_satuan' => $sat['day'], 'default_cost' => '1000000', 'coa' => $coa])->assertSessionHasNoErrors();
        $this->post('/master/rate/variable', ['item' => 'Snack', 'id_satuan' => $sat['peserta_day'], 'default_cost' => '50000', 'coa' => $coa])->assertSessionHasNoErrors();
        $this->post('/master/rate/fixed', ['item' => 'Salah', 'id_satuan' => $sat['peserta_day'], 'default_cost' => '1', 'coa' => $coa])->assertSessionHasErrors('id_satuan');
        $this->post('/master/template-keterangan', ['kategori' => 'syarat', 'teks' => 'Syarat A', 'berlaku_untuk' => 'semua'])->assertSessionHasNoErrors();
        $this->post('/master/template-konfirmasi', ['nama' => 'Form', 'label_kegiatan' => 'Workshop', 'pernyataan' => 'Benar.', 'isian' => ['nama', 'email']])->assertSessionHasNoErrors();
        $kf = DB::table('ms_template_konfirmasi')->value('id');
        $this->get("/master/template-konfirmasi/$kf/contoh")->assertOk();

        // Company, Peserta
        $company = ['nama' => 'PT Uji', 'jenis' => 'Swasta', 'alamat' => 'Jl. Uji', 'id_prov' => '31', 'provinsi' => 'DKI JAKARTA', 'id_kab' => '3171', 'kabupaten' => 'JAKARTA SELATAN', 'telp' => '0211234567', 'email' => 'k@uji.test', 'picNama' => 'Budi', 'picJabatan' => 'HRD', 'picTelp' => '081234567890', 'perlakuan_ppn' => 'PKP', 'picEmail' => 'b@uji.test', 'npwp' => '012345678901000'];
        $this->post('/master/company', $company)->assertSessionHasNoErrors();
        $this->post('/master/company', array_merge($company, ['npwp' => '']))->assertSessionHasErrors('npwp');
        $this->post('/master/peserta', ['nama' => 'Siti', 'gender' => 'P', 'instansi' => 'PT Uji', 'bidang' => $row['bidang'], 'id_prov' => '31', 'id_kab' => '3171', 'no_telp' => '0812-3456-7890', 'email' => 's@uji.test', 'id_customer' => 'CO-001'])->assertSessionHasNoErrors();
        DB::table('ms_peserta')->insert(['kode' => 'PS-900', 'nama' => 'Dari Deal']);
        $this->getJson('/master/peserta/data?kelengkapan=belum')->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->delete('/master/company/CO-001')->assertSessionHasErrors('hapus');

        // Trainer, User, Role
        $this->post('/master/trainer', ['nama' => 'Ivan', 'pelatihan' => [['judul' => 'Seminar', 'penyelenggara' => '', 'waktu' => '']], 'lampiran' => [['dokumen' => 'Sertifikat', 'tahun' => '2024', 'file' => UploadedFile::fake()->createWithContent('a.pdf', '%PDF uji')]]])->assertSessionHasNoErrors();
        $lampiran = DB::table('ms_trainer_lampiran')->first();
        $this->get("/documents/trainer/{$lampiran->id}")->assertOk();
        $this->post('/master/role', ['nama' => 'Sales Uji'])->assertSessionHasNoErrors();
        $role = DB::table('groups')->where('nm_group', 'Sales Uji')->value('id_group');
        $this->post('/master/user', ['nama' => 'Budi', 'id_group' => $role, 'akun' => true, 'username' => 'budi.uji', 'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123'])->assertSessionHasNoErrors();
        $this->assertTrue(password_verify('rahasia-123', DB::table('users')->where('username', 'budi.uji')->value('password')));
        $this->delete("/master/role/$role")->assertSessionHasErrors('hapus');

        // Master Program: draft, simpan, finalkan
        $paketAktif = (int) DB::table('paket_produk')->where('aktif', 1)->value('id');
        $biaya = DB::table('ms_rate')->orderBy('id')->get()->map(fn ($r) => ['id_rate' => $r->id, 'tipe' => $r->tipe, 'item' => $r->item, 'id_satuan' => $r->id_satuan, 'harga' => (string) (int) $r->default_cost, 'is_manual' => false])->all();
        $this->post('/program', ['paket' => [$paketAktif], 'durasi_hari' => '3', 'jam_pembelajaran' => '', 'target_peserta' => '10', 'harga_jual_peserta' => '2500000', 'biaya' => $biaya])->assertSessionHasErrors('jam_pembelajaran');
        $this->post('/program', ['paket' => [$paketAktif], 'durasi_hari' => '3', 'jam_pembelajaran' => '16', 'target_peserta' => '10', 'harga_jual_peserta' => '2500000', 'biaya' => $biaya])->assertSessionHasNoErrors();
        $mp = DB::table('master_program')->first();
        $this->assertSame('MP-'.now()->format('ym').'-001', $mp->nomor);
        $this->get("/program/{$mp->id}/ringkasan")->assertOk()->assertJsonPath('hpp', 3000000 + 50000 * 3 * 10);
        $this->post("/program/{$mp->id}/finalkan")->assertSessionHasNoErrors();
        $this->put("/program/{$mp->id}", ['paket' => [$paketAktif], 'durasi_hari' => '1', 'jam_pembelajaran' => '1', 'target_peserta' => '1', 'harga_jual_peserta' => '1', 'biaya' => $biaya])->assertSessionHasErrors('program');

        // Setiap halaman daftar dan form dapat dibuka (SQL daftar benar di MySQL)
        foreach (['/master/jenis-produk', '/master/jenis-sertifikat', '/master/jenis-bidang', '/master/varian-pelaksanaan', '/master/product', '/master/katalog', '/master/rate/fixed', '/master/rate/variable',
            '/master/template-keterangan', '/master/template-konfirmasi', '/master/peserta', '/master/company', '/master/trainer', '/master/user', '/master/role', '/program', '/program/create', "/program/{$mp->id}",
            '/permissions', '/activity-log'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/master/peserta?q=Siti&kelengkapan=lengkap')->assertOk();
        $this->get('/master/user?q=budi&status=on')->assertOk();
        $this->get('/program?q=nonaktif&status=final')->assertOk()->assertInertia(fn ($p) => $p->where('rows.total', 1)->where('rows.data.0.judul', 'Setelah nonaktif'));
        $this->get('/program?q=K3&status=final')->assertOk()->assertInertia(fn ($p) => $p->where('rows.total', 0));
        $this->assertGreaterThan(20, DB::table('logs')->count());
        $this->assertSame(0, DB::table('logs')->where('data', 'like', '%rahasia-123%')->count());
    }
}

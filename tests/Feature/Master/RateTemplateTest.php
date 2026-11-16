<?php

namespace Tests\Feature\Master;

use Illuminate\Support\Facades\DB;

class RateTemplateTest extends MasterTestCase
{
    private array $sat = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([['rate_fixed', 'event', 'Event'], ['rate_fixed', 'day', 'Day'], ['rate_variable', 'peserta_event', 'Peserta/Event'], ['rate_variable', 'peserta_day', 'Peserta/Day'], ['rate_fixed', 'bulan', 'Bulan']] as [$cat, $code, $nama]) {
            $this->sat[$code] = DB::table('ms_satuan')->insertGetId(['category' => $cat, 'code' => $code, 'nama' => $nama, 'deleted' => 0]);
        }
        DB::table('coa_master')->insert(['no_perkiraan' => '5000-01-01', 'nama' => 'Biaya Uji', 'level' => '5']);
        $this->login($this->admin());
    }

    private function rate(array $over = []): array
    {
        return $over + ['item' => 'Sewa Ruang', 'id_satuan' => $this->sat['event'], 'default_cost' => '1500000', 'coa' => '5000-01-01', 'keterangan' => ''];
    }

    public function test_rate_kode_per_tab_dan_log(): void
    {
        $this->post('/master/rate/fixed', $this->rate())->assertSessionHasNoErrors();
        $this->post('/master/rate/variable', $this->rate(['item' => 'Snack', 'id_satuan' => $this->sat['peserta_event']]))->assertSessionHasNoErrors();
        $this->assertSame(['FC-001', 'VC-001'], DB::table('ms_rate')->orderBy('id')->pluck('kode')->all());
        $log = json_decode(DB::table('logs')->where('ref_no', 'FC-001')->value('data'), true);
        $this->assertSame('Sewa Ruang', $log['item']);
    }

    public function test_halaman_rate_memuat_jumlah_per_tab(): void
    {
        $this->post('/master/rate/fixed', $this->rate())->assertSessionHasNoErrors();
        $this->post('/master/rate/fixed', $this->rate(['item' => 'Konsumsi']))->assertSessionHasNoErrors();
        $this->get('/master/rate/variable')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Master/Rate')->where('counts.fixed', 2)->where('counts.variable', 0)->where('tipe', 'variable'));
    }

    public function test_satuan_lintas_tab_dan_di_luar_daftar_ditolak_server(): void
    {
        $this->post('/master/rate/fixed', $this->rate(['id_satuan' => $this->sat['peserta_event']]))->assertSessionHasErrors('id_satuan');
        $this->post('/master/rate/variable', $this->rate(['id_satuan' => $this->sat['event']]))->assertSessionHasErrors('id_satuan');
        $this->post('/master/rate/fixed', $this->rate(['id_satuan' => $this->sat['bulan']]))->assertSessionHasErrors('id_satuan');
        $this->assertSame(0, DB::table('ms_rate')->count());
    }

    public function test_validasi_biaya_coa_dan_edit_tidak_mengubah_tipe(): void
    {
        foreach (['-5', '12.5', '1e3', '', 'abc'] as $bad) {
            $this->post('/master/rate/fixed', $this->rate(['default_cost' => $bad]))->assertSessionHasErrors('default_cost');
        }
        $this->post('/master/rate/fixed', $this->rate(['coa' => '9999']))->assertSessionHasErrors('coa');
        $this->post('/master/rate/fixed', $this->rate(['default_cost' => '0']))->assertSessionHasNoErrors();
        $id = DB::table('ms_rate')->value('id');
        $this->put("/master/rate/variable/$id", $this->rate())->assertNotFound();
        $this->put("/master/rate/fixed/$id", $this->rate(['id_satuan' => $this->sat['day'], 'default_cost' => '2000000']))->assertSessionHasNoErrors();
        $this->assertSame('fixed', DB::table('ms_rate')->where('id', $id)->value('tipe'));
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertEquals(0, $log['old']['default_cost']);
        $this->assertEquals(2000000, $log['new']['default_cost']);
        $this->assertEquals([$this->sat['event'], $this->sat['day']], [$log['old']['id_satuan'], $log['new']['id_satuan']]);
        $this->assertArrayNotHasKey('item', $log['new']);
        $this->post("/master/rate/fixed/$id/toggle")->assertRedirect();
        $this->assertSame(0, (int) DB::table('ms_rate')->where('id', $id)->value('aktif'));
    }

    public function test_rate_tanpa_hak_manage_tidak_bisa_ubah(): void
    {
        $user = $this->account('viewer');
        $this->allow($user, 'master_data.rate', 'lihat');
        $this->login($user)->get('/master/rate/fixed')->assertOk();
        $this->post('/master/rate/fixed', $this->rate())->assertForbidden();
        $this->get('/master/rate/lainnya')->assertNotFound();
    }

    public function test_template_keterangan_validasi_kode_dan_urutan_kategori(): void
    {
        $this->post('/master/template-keterangan', ['kategori' => 'asing', 'teks' => 'x', 'berlaku_untuk' => 'semua'])->assertSessionHasErrors('kategori');
        $this->post('/master/template-keterangan', ['kategori' => 'syarat', 'teks' => str_repeat('a', 501), 'berlaku_untuk' => 'semua'])->assertSessionHasErrors('teks');
        $this->post('/master/template-keterangan', ['kategori' => 'klausul', 'teks' => 'Klausul A', 'berlaku_untuk' => 'b2b', 'is_default' => true])->assertSessionHasNoErrors();
        $this->post('/master/template-keterangan', ['kategori' => 'syarat', 'teks' => 'Syarat A', 'berlaku_untuk' => 'semua'])->assertSessionHasNoErrors();
        $this->assertSame(['TK-001', 'TK-002'], DB::table('ms_template_keterangan')->orderBy('id')->pluck('kode')->all());
        $this->getJson('/master/template-keterangan/data')->assertOk()->assertJsonPath('data.0.kategori', 'syarat')->assertJsonPath('data.1.kategori', 'klausul');
    }

    public function test_template_konfirmasi_wajib_isian_dan_contoh_tanpa_kolom_lama(): void
    {
        $base = ['nama' => 'Form A', 'label_kegiatan' => 'Workshop', 'pernyataan' => 'Saya menyatakan benar.', 'catatan' => "Satu\n\n Dua "];
        $this->post('/master/template-konfirmasi', $base)->assertSessionHasErrors('isian');
        $this->post('/master/template-konfirmasi', $base + ['isian' => ['asing']])->assertSessionHasErrors('isian.0');
        $this->post('/master/template-konfirmasi', $base + ['isian' => ['email', 'nama']])->assertSessionHasNoErrors();
        $row = DB::table('ms_template_konfirmasi')->first();
        $this->assertSame(['nama', 'email'], json_decode($row->isian, true));
        $this->assertSame("Satu\nDua", $row->catatan);
        $this->assertSame('KF-001', $row->kode);
        $this->get("/master/template-konfirmasi/{$row->id}/contoh")->assertOk()->assertJsonPath('template.nama', 'Form A')->assertJsonPath('program', null);
        $this->get('/master/template-konfirmasi')->assertOk();
    }

    public function test_daftar_template_konfirmasi_menyaring_status_dan_mencari_kode(): void
    {
        $base = ['nama' => 'Form A', 'label_kegiatan' => 'Workshop', 'pernyataan' => 'Saya menyatakan benar.', 'isian' => ['nama']];
        $this->post('/master/template-konfirmasi', $base)->assertSessionHasNoErrors();
        $this->post('/master/template-konfirmasi', ['nama' => 'Form B'] + $base)->assertSessionHasNoErrors();
        $off = DB::table('ms_template_konfirmasi')->where('nama', 'Form B')->value('id');
        $this->post("/master/template-konfirmasi/$off/toggle")->assertRedirect();

        $url = '/master/template-konfirmasi/data?draw=1&start=0&length=25';
        $this->getJson($url)->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson($url.'&status=on')->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nama', 'Form A');
        $this->getJson($url.'&status=off')->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nama', 'Form B');
        $this->getJson($url.'&search[value]=KF-002')->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->getJson($url.'&status=salah')->assertUnprocessable();
    }
}

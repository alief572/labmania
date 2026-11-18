<?php

namespace Tests\Feature\Master;

use Illuminate\Support\Facades\DB;

/** Harga "start from" per Paket Produk (FR-69, [MMB-5]). */
class HargaMulaiTest extends MasterTestCase
{
    private int $paket;

    protected function setUp(): void
    {
        parent::setUp();
        (require base_path('database/migrations/2026_11_18_090000_paket_produk_harga_mulai.php'))->up();
        $ids = $this->seedAttributes();
        $this->login($this->admin())->post('/master/product', ['judul' => 'Paket Harga', 'rows' => [[
            'produk' => $ids['ms_jenis_produk'], 'bidang' => $ids['ms_jenis_bidang'], 'varian' => $ids['ms_varian_pelaksanaan'], 'sertifikasi' => $ids['ms_jenis_sertifikasi']]]]);
        $this->paket = (int) DB::table('paket_produk')->value('id');
    }

    private function save(array $in)
    {
        return $this->put('/master/product/'.$this->paket.'/lingkup', $in + ['items' => []]);
    }

    public function test_harga_mulai_is_optional_saved_logged_and_shown_in_catalog(): void
    {
        $this->assertNull(DB::table('paket_produk')->value('harga_mulai'));
        $this->get('/master/katalog')->assertOk()->assertInertia(fn ($p) => $p->where('groups.0.items.0.harga_mulai', null));

        $this->save(['harga_mulai' => '1.500.000'])->assertSessionHasNoErrors();
        $this->assertSame('1500000', (string) (int) DB::table('paket_produk')->value('harga_mulai'));
        $this->get('/master/katalog')->assertInertia(fn ($p) => $p->where('groups.0.items.0.harga_mulai', fn ($v) => (int) $v === 1500000));

        $log = json_decode(DB::table('logs')->where('description', 'like', 'Update Harga start from%')->value('data'), true);
        $this->assertNull($log['old']['harga_mulai']);
        $this->assertSame('1500000', $log['new']['harga_mulai']);

        // Tanpa perubahan tidak menulis log baru.
        $this->save(['harga_mulai' => '1500000'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('logs')->where('description', 'like', 'Update Harga start from%')->count());
    }

    public function test_klien_lama_yang_hanya_mengirim_lingkup_tidak_menghapus_harga(): void
    {
        $this->save(['harga_mulai' => '2000000']);
        $this->put('/master/product/'.$this->paket.'/lingkup', ['items' => ['K3 Umum']])->assertSessionHasNoErrors();
        $this->assertSame(2000000, (int) DB::table('paket_produk')->value('harga_mulai'));
        $this->assertSame(['K3 Umum'], DB::table('paket_produk_lingkup')->pluck('lingkup')->all());
    }

    public function test_kosong_menghapus_harga_dan_input_tidak_valid_ditolak(): void
    {
        $this->save(['harga_mulai' => '2000000']);
        $this->save(['harga_mulai' => ''])->assertSessionHasNoErrors();
        $this->assertNull(DB::table('paket_produk')->value('harga_mulai'));
        $this->save(['harga_mulai' => 'abc'])->assertSessionHasErrors('harga_mulai');
        $this->save(['harga_mulai' => '-5'])->assertSessionHasErrors('harga_mulai');
        $this->save(['harga_mulai' => '12,5'])->assertSessionHasErrors('harga_mulai');
        $this->save(['harga_mulai' => '99999999999999'])->assertSessionHasErrors('harga_mulai');
        $this->assertNull(DB::table('paket_produk')->value('harga_mulai'));
    }

    public function test_hak_manage_diperlukan_dan_pdf_katalog_memuat_harga(): void
    {
        $staf = $this->account('staf-harga');
        $this->login($staf)->put('/master/product/'.$this->paket.'/lingkup', ['items' => [], 'harga_mulai' => '1'])->assertForbidden();
        $this->login($this->admin());
        $this->save(['harga_mulai' => '750000']);
        $pdf = $this->get('/master/katalog/pdf');
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }
}

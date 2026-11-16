<?php

namespace Tests\Feature\Master;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PesertaCompanyTest extends MasterTestCase
{
    private int $bidang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bidang = DB::table('ms_jenis_bidang')->insertGetId(['kode' => 'JB-001', 'nama' => 'K3', 'aktif' => 1]);
        $this->login($this->admin());
    }

    private function company(array $over = []): array
    {
        return $over + ['nama' => 'PT Uji', 'jenis' => 'Swasta', 'alamat' => 'Jl. Uji 1', 'id_prov' => '31', 'provinsi' => 'DKI JAKARTA', 'id_kab' => '3171', 'kabupaten' => 'JAKARTA SELATAN',
            'telp' => '0211234567', 'email' => 'kantor@uji.test', 'picNama' => 'Budi', 'picJabatan' => 'HRD', 'picTelp' => '081234567890', 'perlakuan_ppn' => 'PKP', 'picEmail' => 'budi@uji.test', 'npwp' => '01.234.567.8-901.000'];
    }

    private function peserta(array $over = []): array
    {
        return $over + ['nama' => 'Siti', 'gender' => 'P', 'instansi' => 'PT Uji', 'bidang' => $this->bidang, 'id_prov' => '31', 'provinsi' => 'DKI JAKARTA', 'id_kab' => '3171', 'kabupaten' => 'JAKARTA SELATAN',
            'no_telp' => '0812-3456-7890', 'email' => 'siti@uji.test', 'nik' => '', 'npwp_instansi' => '', 'id_customer' => ''];
    }

    public function test_company_npwp_wajib_dan_format(): void
    {
        $this->post('/master/company', $this->company(['npwp' => '']))->assertSessionHasErrors('npwp');
        $this->post('/master/company', $this->company(['npwp' => '12345']))->assertSessionHasErrors('npwp');
        $this->post('/master/company', $this->company(['npwp' => 'abcdefghijklmno']))->assertSessionHasErrors('npwp');
        $this->assertSame(0, DB::table('master_customer')->count());
        $this->post('/master/company', $this->company())->assertSessionHasNoErrors();
        $row = DB::table('master_customer')->first();
        $this->assertSame('CO-001', $row->id_customer);
        $this->assertSame('012345678901000', $row->npwp);
        $this->assertSame('aktif', $row->activation);
    }

    public function test_company_validasi_wajib_dan_jenis(): void
    {
        foreach (['nama', 'alamat', 'picNama', 'picJabatan', 'picEmail', 'telp', 'id_prov', 'id_kab'] as $field) {
            $this->post('/master/company', $this->company([$field => '']))->assertSessionHasErrors($field);
        }
        $this->post('/master/company', $this->company(['jenis' => 'Asing']))->assertSessionHasErrors('jenis');
        $this->post('/master/company', $this->company(['telp' => '123']))->assertSessionHasErrors('telp');
        $this->post('/master/company', $this->company(['email' => 'salah']))->assertSessionHasErrors('email');
        $this->assertSame(0, DB::table('master_customer')->count());
    }

    public function test_company_lama_tanpa_npwp_tidak_diubah_dan_edit_menuntut_npwp(): void
    {
        DB::table('master_customer')->insert(['id_customer' => 'CO-009', 'name_customer' => 'Lama', 'activation' => null, 'npwp' => null]);
        $this->getJson('/master/company/data')->assertOk()->assertJsonPath('data.0.aktif', 1)->assertJsonPath('data.0.npwp', null);
        $this->assertNull(DB::table('master_customer')->where('id_customer', 'CO-009')->value('npwp'));
        $this->put('/master/company/CO-009', $this->company(['npwp' => '']))->assertSessionHasErrors('npwp');
        $this->put('/master/company/CO-009', $this->company())->assertSessionHasNoErrors();
        $this->assertSame('012345678901000', DB::table('master_customer')->where('id_customer', 'CO-009')->value('npwp'));
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertSame('Lama', $log['old']['name_customer']);
        $this->assertSame('PT Uji', $log['new']['name_customer']);
    }

    public function test_status_company_nilai_lama_dibaca_benar_dan_toggle_menulis_nilai_kanonik(): void
    {
        foreach (['inactive' => 'CO-001', 'nonaktif' => 'CO-002', 'active' => 'CO-003', 'aktif' => 'CO-004'] as $value => $id) {
            DB::table('master_customer')->insert(['id_customer' => $id, 'name_customer' => $id, 'activation' => $value]);
        }
        $this->getJson('/master/company/data')->assertOk()->assertJsonPath('data.0.aktif', 0)->assertJsonPath('data.1.aktif', 0)->assertJsonPath('data.2.aktif', 1)->assertJsonPath('data.3.aktif', 1);
        $this->post('/master/company/CO-001/toggle');
        $this->post('/master/company/CO-003/toggle');
        $this->assertSame('aktif', DB::table('master_customer')->where('id_customer', 'CO-001')->value('activation'));
        $this->assertSame('nonaktif', DB::table('master_customer')->where('id_customer', 'CO-003')->value('activation'));
    }

    public function test_hapus_company_diblokir_bila_dipakai_penjualan_quotation_atau_peserta(): void
    {
        foreach (['CO-001', 'CO-002', 'CO-003', 'CO-004'] as $id) {
            DB::table('master_customer')->insert(['id_customer' => $id, 'name_customer' => $id, 'activation' => 'aktif']);
        }
        DB::table('penjualan_program')->insert(['nomor' => 'PJ-1', 'id_customer' => 'CO-001', 'id_master_program' => 1, 'jenis' => 'b2b']);
        DB::table('quotation')->insert(['nomor' => 'Q-1', 'id_customer' => 'CO-002', 'id_penjualan' => 1, 'tanggal' => '2026-11-13']);
        DB::table('ms_peserta')->insert(['kode' => 'PS-001', 'nama' => 'X', 'id_customer' => 'CO-003']);
        foreach (['CO-001', 'CO-002', 'CO-003'] as $id) {
            $this->delete("/master/company/$id")->assertSessionHasErrors('hapus');
            $this->assertNull(DB::table('master_customer')->where('id_customer', $id)->value('deleted_date'));
        }
        $this->delete('/master/company/CO-004')->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('master_customer')->where('id_customer', 'CO-004')->value('deleted_date'));
        $this->assertDatabaseHas('logs', ['action' => 'Hapus', 'ref_no' => 'CO-004']);
        $this->getJson('/master/company/data')->assertOk()->assertJsonPath('recordsFiltered', 3);
    }

    public function test_peserta_validasi_dan_kode(): void
    {
        $this->post('/master/peserta', $this->peserta(['gender' => 'X']))->assertSessionHasErrors('gender');
        $this->post('/master/peserta', $this->peserta(['no_telp' => '123']))->assertSessionHasErrors('no_telp');
        $this->post('/master/peserta', $this->peserta(['email' => 'salah']))->assertSessionHasErrors('email');
        $this->post('/master/peserta', $this->peserta(['bidang' => 999]))->assertSessionHasErrors('bidang');
        $this->post('/master/peserta', $this->peserta(['instansi' => '']))->assertSessionHasErrors('instansi');
        $this->assertSame(0, DB::table('ms_peserta')->count());
        $this->post('/master/peserta', $this->peserta())->assertSessionHasNoErrors();
        $row = DB::table('ms_peserta')->first();
        $this->assertSame('PS-001', $row->kode);
        $this->assertSame('081234567890', $row->no_telp);
        $this->assertNull($row->nik);
    }

    public function test_peserta_dengan_company_mengikuti_nama_dan_npwp_company_dari_server(): void
    {
        DB::table('master_customer')->insert(['id_customer' => 'CO-001', 'name_customer' => 'PT Asli', 'npwp' => '012345678901000', 'activation' => 'aktif']);
        $this->post('/master/peserta', $this->peserta(['id_customer' => 'CO-001', 'instansi' => 'Dipalsukan', 'npwp_instansi' => '999']))->assertSessionHasNoErrors();
        $row = DB::table('ms_peserta')->first();
        $this->assertSame('PT Asli', $row->instansi);
        $this->assertSame('012345678901000', $row->npwp_instansi);
        $this->post('/master/peserta', $this->peserta(['id_customer' => 'CO-404']))->assertSessionHasErrors('id_customer');
    }

    public function test_peserta_belum_lengkap_dari_deal_ditandai_dan_difilter(): void
    {
        DB::table('ms_peserta')->insert(['kode' => 'PS-001', 'nama' => 'Dari Deal']);
        $this->post('/master/peserta', $this->peserta());
        $this->getJson('/master/peserta/data?kelengkapan=belum')->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nama', 'Dari Deal')->assertJsonPath('data.0.kurang.0', 'Gender');
        $this->getJson('/master/peserta/data?kelengkapan=lengkap')->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nama', 'Siti');
        $id = DB::table('ms_peserta')->where('kode', 'PS-001')->value('id');
        $this->put("/master/peserta/$id", $this->peserta(['nama' => 'Dilengkapi']))->assertSessionHasNoErrors();
        $this->getJson('/master/peserta/data?kelengkapan=belum')->assertOk()->assertJsonPath('recordsFiltered', 0);
    }

    public function test_hapus_peserta_soft_delete_dan_diblokir_bila_sudah_deal(): void
    {
        $this->post('/master/peserta', $this->peserta());
        $this->post('/master/peserta', $this->peserta(['nama' => 'Budi']));
        [$bebas, $deal] = DB::table('ms_peserta')->orderBy('id')->pluck('id')->all();
        DB::table('quotation')->insert(['id' => 7, 'nomor' => 'Q-7', 'id_penjualan' => 1, 'tanggal' => '2026-11-13']);
        DB::table('quotation_peserta')->insert(['id_quotation' => 7, 'id_peserta' => $deal]);
        $this->delete("/master/peserta/$deal")->assertSessionHasErrors('hapus');
        $this->assertNull(DB::table('ms_peserta')->where('id', $deal)->value('deleted_date'));
        $this->delete("/master/peserta/$bebas")->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('ms_peserta')->where('id', $bebas)->value('deleted_date'));
        $this->assertSame(1, DB::table('ms_peserta')->whereNull('deleted_date')->count());
        $this->delete("/master/peserta/$bebas")->assertNotFound();
    }

    public function test_hak_diperiksa_di_server(): void
    {
        $user = $this->account('viewer');
        $this->allow($user, 'master_data.peserta', 'lihat', 'tambah');
        $this->allow($user, 'master_data.company', 'lihat');
        $this->login($user)->post('/master/peserta', $this->peserta())->assertSessionHasNoErrors();
        $id = DB::table('ms_peserta')->value('id');
        $this->put("/master/peserta/$id", $this->peserta())->assertForbidden();
        $this->delete("/master/peserta/$id")->assertForbidden();
        $this->post('/master/company', $this->company())->assertForbidden();
        $this->post('/master/company/CO-001/toggle')->assertForbidden();
    }

    public function test_wilayah_memakai_cache_dan_fallback_tanpa_jaringan(): void
    {
        Http::fake(['wilayah.id/api/provinces.json' => Http::response(['data' => [['code' => '11', 'name' => 'ACEH']]]), 'wilayah.id/api/regencies/*' => Http::response('', 500)]);
        $this->get('/master/wilayah/provinces')->assertJsonPath('data.0.name', 'ACEH');
        $this->get('/master/wilayah/regencies/11')->assertJsonPath('data', []);
        $this->get('/master/wilayah/regencies/abc')->assertNotFound();
        Http::assertSentCount(2);
        $this->get('/master/wilayah/provinces');
        Http::assertSentCount(2);
        Cache::flush();
        Http::fake(fn () => throw new ConnectionException('offline'));
        $this->get('/master/wilayah/provinces')->assertJsonPath('data.0.name', 'DKI JAKARTA');
    }
}

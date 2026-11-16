<?php

namespace Tests\Mysql;

use Illuminate\Support\Facades\DB;

/** Paritas fitur di MySQL nyata: Dashboard (agregat Final), CRUD Menu (menu sistem asli), CV PDF Trainer. */
class Phase8MysqlTest extends MysqlTestCase
{
    private int $master;

    private int $trainer;

    private int $employee;

    private int $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfiguration();
        $ids = $this->attributes();
        $product = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-UJI', 'judul' => 'Program Uji', 'id_jenis_produk' => $ids['ms_jenis_produk'], 'id_jenis_bidang' => $ids['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $ids['ms_jenis_sertifikasi'], 'id_varian_pelaksanaan' => $ids['ms_varian_pelaksanaan'], 'aktif' => 1]);
        $this->master = DB::table('master_program')->insertGetId(['nomor' => 'MP-UJI', 'status' => 'final', 'durasi_hari' => 2, 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => 1000000]);
        DB::table('master_program_produk')->insert(['id_master_program' => $this->master, 'id_paket_produk' => $product, 'urutan' => 1]);
        $this->unit = (int) (DB::table('ms_satuan')->where('category', 'rate_fixed')->where('code', 'event')->value('id')
            ?: DB::table('ms_satuan')->insertGetId(['category' => 'rate_fixed', 'code' => 'event', 'nama' => 'Event', 'deleted' => '0']));
        $this->trainer = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-UJI', 'nama' => 'Trainer Uji', 'aktif' => 1]);
        $this->employee = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-UJI', 'nama' => 'Asisten Uji', 'aktif' => 1]);
        DB::table('master_customer')->insert(['id_customer' => 'CO-001', 'name_customer' => 'Company Uji', 'activation' => 'aktif', 'pic_name' => 'PIC', 'address_office' => 'Alamat', 'kota' => 'Kota']);
    }

    private function program(bool $b2b = false): void
    {
        $this->post('/penjualan-program', ['jenis' => $b2b ? 'b2b' : 'public', 'id_master_program' => $this->master, 'id_customer' => $b2b ? 'CO-001' : null, 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee,
            'tgl_mulai' => '2030-12-20', 'tgl_selesai' => '2030-12-21', 'waktu' => '08.00', 'lokasi' => 'Lokasi uji', 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => '1000000', 'target_profit' => 0,
            'biaya' => [['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Biaya uji', 'id_satuan' => $this->unit, 'harga' => 100000, 'is_manual' => true]]])->assertSessionHasNoErrors();
        $this->post('/penjualan-program/'.DB::table('penjualan_program')->orderByDesc('id')->value('id').'/finalkan')->assertSessionHasNoErrors();
    }

    public function test_dashboard_kpi_adds_up_with_detail_on_mysql(): void
    {
        $this->loginAs($this->superadmin());
        $this->program();
        $this->program(true);
        $kpi = $this->get('/')->assertOk()->viewData('page')['props']['kpi'];
        $this->assertSame(2, $kpi['final']);
        $this->assertSame(1, $kpi['public']);
        $finals = $this->getJson('/dashboard/kpi/prof')->assertOk()->json('finals');
        $this->assertCount(2, $finals);
        $this->assertEquals($kpi['pendapatan'], array_sum(array_column($finals, 'pendapatan')));
        $this->assertEquals($kpi['profit'], array_sum(array_column($finals, 'profit')));
        $this->getJson('/dashboard/kpi/xyz')->assertNotFound();
    }

    public function test_menu_system_rules_hold_on_real_menu_table(): void
    {
        $this->loginAs($this->superadmin());
        $sys = DB::table('ms_menu')->where('kode', 'menu_labmania')->first();
        $this->assertSame(1, (int) $sys->sistem);
        $this->post('/pengaturan/menu/'.$sys->id.'/toggle')->assertSessionHasErrors('aktif');
        $modul = DB::table('ms_menu')->where('id', $sys->parent_id)->first();
        $this->assertSame('#', $modul->link);
        $this->post('/pengaturan/menu/'.$modul->id.'/toggle')->assertSessionHasErrors('aktif');
        $this->assertSame(1, (int) DB::table('ms_menu')->find($modul->id)->aktif);

        $before = DB::table('ms_menu')->count();
        $this->post('/pengaturan/menu', ['tipe' => 'menu', 'judul' => 'Laporan Uji', 'link' => 'laporan/uji', 'parent_id' => $modul->id, 'kelompok' => 1, 'aksi' => ['tambah', 'cetak']])->assertSessionHasNoErrors();
        $this->assertSame($before + 1, DB::table('ms_menu')->count());
        $new = DB::table('ms_menu')->where('link', 'laporan/uji')->first();
        $this->assertSame('laporan.uji', $new->kode);
        $this->assertEqualsCanonicalizing(['lihat', 'tambah', 'cetak'], DB::table('ms_menu_aksi')->where('id_menu', $new->id)->pluck('aksi')->all());
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => $new->id, 'aksi' => 'cetak']);
        $this->put('/pengaturan/menu/'.$new->id, ['judul' => 'Laporan Uji', 'link' => 'laporan/uji', 'parent_id' => $modul->id, 'kelompok' => 1, 'aksi' => ['tambah']])->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('ms_group_akses')->where('id_menu', $new->id)->count());
        $this->post('/pengaturan/menu/'.$new->id.'/pindah/up')->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('logs')->where('module', 'Menu Sidebar')->where('action', 'Tambah')->count());
    }

    public function test_trainer_cv_pdf_is_valid_on_mysql(): void
    {
        $this->loginAs($this->superadmin());
        $r = $this->get('/master/trainer/'.$this->trainer.'/cv.pdf')->assertOk();
        $this->assertStringStartsWith('%PDF', $r->getContent());
        $this->assertStringContainsString('CV-TR-UJI-', $r->headers->get('Content-Disposition'));
    }
}

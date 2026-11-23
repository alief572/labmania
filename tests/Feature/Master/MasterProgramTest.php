<?php

namespace Tests\Feature\Master;

use App\Support\Costing;
use Illuminate\Support\Facades\DB;

class MasterProgramTest extends MasterTestCase
{
    private array $sat = [];

    private array $ids = [];

    private int $paket;

    private int $rateFixed;

    private int $rateVar;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([['rate_fixed', 'event', 'Event'], ['rate_fixed', 'day', 'Day'], ['rate_variable', 'peserta_event', 'Peserta/Event'], ['rate_variable', 'peserta_day', 'Peserta/Day']] as [$cat, $code, $nama]) {
            $this->sat[$code] = DB::table('ms_satuan')->insertGetId(['category' => $cat, 'code' => $code, 'nama' => $nama, 'deleted' => 0]);
        }
        $this->ids = $this->seedAttributes();
        $this->paket = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-001', 'judul' => 'K3 Umum', 'id_jenis_produk' => $this->ids['ms_jenis_produk'], 'id_jenis_bidang' => $this->ids['ms_jenis_bidang'], 'id_varian_pelaksanaan' => $this->ids['ms_varian_pelaksanaan'], 'id_jenis_sertifikasi' => $this->ids['ms_jenis_sertifikasi'], 'aktif' => 1]);
        $this->rateFixed = DB::table('ms_rate')->insertGetId(['kode' => 'FC-001', 'tipe' => 'fixed', 'item' => 'Sewa Ruang', 'id_satuan' => $this->sat['day'], 'default_cost' => 1000000, 'coa' => 'x', 'aktif' => 1]);
        $this->rateVar = DB::table('ms_rate')->insertGetId(['kode' => 'VC-001', 'tipe' => 'variable', 'item' => 'Snack', 'id_satuan' => $this->sat['peserta_day'], 'default_cost' => 50000, 'coa' => 'x', 'aktif' => 1]);
        $this->login($this->admin());
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'paket' => $this->paket, 'durasi_hari' => '3', 'jam_pembelajaran' => '16', 'target_peserta' => '10', 'harga_jual_peserta' => '2.500.000', 'target_profit' => '',
            'biaya' => [
                ['id_rate' => $this->rateFixed, 'tipe' => 'fixed', 'item' => 'Sewa Ruang', 'id_satuan' => $this->sat['day'], 'harga' => '1000000', 'is_manual' => false],
                ['id_rate' => $this->rateVar, 'tipe' => 'variable', 'item' => 'Snack', 'id_satuan' => $this->sat['peserta_day'], 'harga' => '50000', 'is_manual' => false],
            ],
        ];
    }

    private function draft(array $over = []): int
    {
        $this->post('/program', $this->payload($over))->assertSessionHasNoErrors();

        return (int) DB::table('master_program')->orderByDesc('id')->value('id');
    }

    public function test_simpan_draft_nomor_bulanan_costing_server_dan_log(): void
    {
        $id = $this->draft();
        $mp = DB::table('master_program')->where('id', $id)->first();
        $this->assertSame('MP-'.now()->format('ym').'-001', $mp->nomor);
        $this->assertSame('draft', $mp->status);
        $this->assertSame(16, (int) $mp->jam_pembelajaran);
        $this->assertEquals(2500000, $mp->harga_jual_peserta);
        $this->assertSame(2, DB::table('master_program_biaya')->where('id_master_program', $id)->count());
        $this->assertSame($this->paket, (int) $mp->id_paket_produk);
        $this->assertSame('MP-'.now()->format('ym').'-002', DB::table('master_program')->where('id', $this->draft())->value('nomor'));
        $this->get("/program/$id/ringkasan")->assertJsonPath('hpp', 3000000 + 50000 * 3 * 10);
        $this->assertDatabaseHas('logs', ['action' => 'Tambah', 'ref_no' => $mp->nomor]);
    }

    public function test_jp_wajib_bilangan_bulat_positif_dan_validasi_inti(): void
    {
        foreach ([['jam_pembelajaran' => ''], ['jam_pembelajaran' => '0'], ['jam_pembelajaran' => '-3'], ['jam_pembelajaran' => '1.5'], ['jam_pembelajaran' => 'abc']] as $bad) {
            $this->post('/program', $this->payload($bad))->assertSessionHasErrors('jam_pembelajaran');
        }
        $this->post('/program', $this->payload(['durasi_hari' => '0']))->assertSessionHasErrors('durasi_hari');
        $this->post('/program', $this->payload(['target_peserta' => '0']))->assertSessionHasErrors('target_peserta');
        $this->post('/program', $this->payload(['harga_jual_peserta' => '']))->assertSessionHasErrors('harga_jual_peserta');
        $this->post('/program', $this->payload(['paket' => '']))->assertSessionHasErrors('paket');
        $this->post('/program', $this->payload(['paket' => [$this->paket, $this->paket]]))->assertSessionHasErrors('paket');
        $this->post('/program', $this->payload(['paket' => 9999]))->assertSessionHasErrors('paket');
        $this->assertSame(0, DB::table('master_program')->count());
    }

    public function test_satu_product_per_program_dan_product_nonaktif_ditolak_untuk_baru(): void
    {
        $kedua = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-002', 'judul' => 'Lingkungan', 'id_jenis_produk' => 1, 'id_jenis_bidang' => 2, 'id_varian_pelaksanaan' => 1, 'id_jenis_sertifikasi' => 1, 'aktif' => 1]);
        $id = $this->draft(['paket' => $kedua]);
        $this->assertSame($kedua, (int) DB::table('master_program')->where('id', $id)->value('id_paket_produk'));
        $this->assertSame(2, DB::table('master_program_biaya')->where('id_master_program', $id)->count());
        $this->get('/program')->assertInertia(fn ($p) => $p->where('rows.data.0.judul', 'Lingkungan'));

        DB::table('paket_produk')->where('id', $kedua)->update(['aktif' => 0]);
        $this->put("/program/$id", $this->payload(['paket' => $kedua]))->assertSessionHasNoErrors();
        $this->post('/program', $this->payload(['paket' => $kedua]))->assertSessionHasErrors('paket');
    }

    public function test_biaya_satuan_rate_ganda_nonaktif_dan_nominal_nol(): void
    {
        $biaya = fn (array $row) => $this->payload(['biaya' => [$row + ['id_rate' => $this->rateFixed, 'tipe' => 'fixed', 'item' => 'Sewa Ruang', 'id_satuan' => $this->sat['day'], 'harga' => '1000', 'is_manual' => false]]]);
        $this->post('/program', $biaya(['id_satuan' => $this->sat['peserta_day']]))->assertSessionHasErrors('biaya.0');
        $this->post('/program', $biaya(['tipe' => 'variable']))->assertSessionHasErrors('biaya.0');
        $this->post('/program', $biaya(['harga' => '-5']))->assertSessionHasErrors('biaya.0');
        $this->post('/program', $biaya(['harga' => '']))->assertSessionHasErrors('biaya.0');
        $this->post('/program', $biaya(['is_manual' => true, 'item' => '']))->assertSessionHasErrors('biaya.0');
        $ganda = $this->payload();
        $ganda['biaya'][] = $ganda['biaya'][0];
        $this->post('/program', $ganda)->assertSessionHasErrors('biaya.2');
        DB::table('ms_rate')->where('id', $this->rateFixed)->update(['aktif' => 0]);
        $this->post('/program', $this->payload())->assertSessionHasErrors('biaya.0');
        DB::table('ms_rate')->where('id', $this->rateFixed)->update(['aktif' => 1]);

        $nol = $this->payload();
        $nol['biaya'][1]['harga'] = '0';
        $nol['biaya'][] = ['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Biaya lain', 'id_satuan' => $this->sat['event'], 'harga' => '250000', 'is_manual' => true];
        $this->post('/program', $nol)->assertSessionHasNoErrors()->assertSessionHas('success', fn ($m) => str_contains($m, '1 item bernilai 0 dibuang'));
        $id = DB::table('master_program')->value('id');
        $this->assertSame(['Biaya lain', 'Sewa Ruang'], DB::table('master_program_biaya')->where('id_master_program', $id)->orderBy('item')->pluck('item')->all());
        $this->assertSame(1, (int) DB::table('master_program_biaya')->where('item', 'Biaya lain')->value('is_manual'));
    }

    public function test_edit_draft_mencatat_old_new_dan_detail_changes(): void
    {
        $id = $this->draft();
        $ubah = $this->payload(['harga_jual_peserta' => '3000000']);
        $ubah['biaya'][0]['harga'] = '1200000';
        unset($ubah['biaya'][1]);
        $this->put("/program/$id", $ubah)->assertSessionHasNoErrors();
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertEquals(2500000, $log['old']['harga_jual_peserta']);
        $this->assertEquals(3000000, $log['new']['harga_jual_peserta']);
        $this->assertEquals(['old' => 1000000, 'new' => 1200000], $log['detail_changes']['Item [Sewa Ruang] - Harga']);
        $this->assertEquals(['old' => 50000, 'new' => null], $log['detail_changes']['Item [Snack] - Dihapus']);
        $this->assertArrayNotHasKey('product', $log['new']);

        $kedua = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-002', 'judul' => 'Lingkungan', 'id_jenis_produk' => 1, 'id_jenis_bidang' => 2, 'id_varian_pelaksanaan' => 1, 'id_jenis_sertifikasi' => 1, 'aktif' => 1]);
        $this->put("/program/$id", array_merge($ubah, ['paket' => [$this->paket, $kedua]]))->assertSessionHasNoErrors();
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->orderByDesc('id')->value('data'), true);
        $this->assertSame(['PC-001', 'PC-001 + PC-002'], [$log['old']['product'], $log['new']['product']]);
    }

    public function test_finalkan_butuh_approve_manage_tidak_cukup(): void
    {
        $id = $this->draft();
        $user = $this->account('manager');
        $this->allow($user, 'master_program', 'lihat', 'ubah', 'aktif', 'tambah');
        $this->login($user);
        $this->post("/program/$id/finalkan")->assertForbidden();
        $this->assertSame('draft', DB::table('master_program')->where('id', $id)->value('status'));
        $this->allow($user, 'master_program', 'approve');
        $this->post("/program/$id/finalkan")->assertSessionHasNoErrors();
        $mp = DB::table('master_program')->where('id', $id)->first();
        $this->assertSame('final', $mp->status);
        $this->assertSame($user->id_user, (int) $mp->confirmed_by);
        $this->assertDatabaseHas('logs', ['action' => 'Finalkan', 'ref_no' => $mp->nomor]);
    }

    public function test_penjualan_program_dibuka_dari_master_final_memilih_master_itu(): void
    {
        $menu = DB::table('ms_menu')->insertGetId(['kode' => 'penjualan_program', 'judul' => 'Penjualan Program', 'link' => 'penjualan-program', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 32, 'aktif' => 1, 'sistem' => 0]);
        foreach (['lihat', 'tambah', 'ubah', 'aktif', 'hapus', 'approve'] as $aksi) {
            DB::table('ms_menu_aksi')->insert(['id_menu' => $menu, 'aksi' => $aksi]);
        }
        $final = $this->draft();
        $draft = $this->draft();
        $this->post("/program/$final/finalkan")->assertSessionHasNoErrors();

        $this->get("/penjualan-program/create?master=$final")->assertOk()->assertInertia(fn ($page) => $page
            ->component('Sales/Form')->where('program.id_master_program', $final));
        // Master yang belum Final, tidak ada, atau bukan angka tidak dipilih otomatis.
        foreach ([$draft, 999999, 'abc'] as $nilai) {
            $this->get("/penjualan-program/create?master=$nilai")->assertOk()->assertInertia(fn ($page) => $page->where('program.id_master_program', ''));
        }
    }

    public function test_halaman_mulai_penjualan_hanya_memuat_master_final_dan_jenis_terkunci(): void
    {
        $menu = DB::table('ms_menu')->insertGetId(['kode' => 'penjualan_program', 'judul' => 'Penjualan Program', 'link' => 'penjualan-program', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 32, 'aktif' => 1, 'sistem' => 0]);
        foreach (['lihat', 'tambah', 'ubah', 'aktif', 'hapus', 'approve'] as $aksi) {
            DB::table('ms_menu_aksi')->insert(['id_menu' => $menu, 'aksi' => $aksi]);
        }
        $final = $this->draft();
        $draft = $this->draft();
        $this->post("/program/$final/finalkan")->assertSessionHasNoErrors();

        $this->get('/penjualan-program/mulai')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Sales/Start')->has('masters', 1)->where('masters.0.id', $final)->where('preset', null)->where('minB2b', 10));
        $this->get("/penjualan-program/mulai?master=$final")->assertInertia(fn ($page) => $page->where('preset', $final));
        $this->get("/penjualan-program/mulai?master=$draft")->assertInertia(fn ($page) => $page->where('preset', null));

        $this->get("/penjualan-program/create?master=$final&jenis=b2b")->assertOk()->assertInertia(fn ($page) => $page
            ->component('Sales/Form')->where('program.jenis', 'b2b')->where('jenisLocked', true));
        $this->get("/penjualan-program/create?master=$final&jenis=xxx")->assertInertia(fn ($page) => $page->where('program.jenis', 'public')->where('jenisLocked', false));

        $tanpaHak = $this->account('viewer');
        DB::table('ms_user_akses')->insert(['id_user' => $tanpaHak->id_user, 'id_menu' => $menu, 'aksi' => 'lihat']);
        $this->login($tanpaHak);
        $this->get('/penjualan-program/mulai')->assertForbidden();
    }

    public function test_daftar_master_program_memuat_varian_dan_sertifikat_produk_pertama(): void
    {
        $this->post('/program', $this->payload())->assertSessionHasNoErrors();
        $row = $this->getJson('/program/data?draw=1&start=0&length=25')->assertOk()->json('data.0');
        $this->assertSame('Workshop', $row['nama_varian']);
        $this->assertSame('BNSP', $row['nama_sertifikasi']);
    }

    public function test_final_dan_batal_terkunci_dan_tidak_bisa_ganda(): void
    {
        $a = $this->draft();
        $b = $this->draft();
        $this->post("/program/$a/finalkan")->assertSessionHasNoErrors();
        $this->post("/program/$a/finalkan")->assertSessionHasErrors('program');
        $this->post("/program/$a/batalkan")->assertSessionHasErrors('program');
        $this->put("/program/$a", $this->payload(['harga_jual_peserta' => '1']))->assertSessionHasErrors('program');
        $this->assertEquals(2500000, DB::table('master_program')->where('id', $a)->value('harga_jual_peserta'));
        $this->post("/program/$b/batalkan")->assertSessionHasNoErrors();
        $this->post("/program/$b/finalkan")->assertSessionHasErrors('program');
        $this->post("/program/$b/batalkan")->assertSessionHasErrors('program');
        $this->assertSame(1, DB::table('logs')->where('action', 'Finalkan')->count());
        $this->assertSame(1, DB::table('logs')->where('action', 'Batalkan')->count());
        $this->get("/program/$a")->assertInertia(fn ($p) => $p->where('program.status', 'final')->where('can.edit', true));
    }

    public function test_finalkan_ditolak_bila_isian_wajib_kosong_tetapi_peringatan_costing_tidak_memblokir(): void
    {
        $lama = DB::table('master_program')->insertGetId(['nomor' => 'MP-2601-001', 'id_paket_produk' => $this->paket, 'durasi_hari' => 2, 'target_peserta' => 10, 'harga_jual_peserta' => 100000, 'status' => 'draft', 'jam_pembelajaran' => null]);
        DB::table('master_program_biaya')->insert(['id_master_program' => $lama, 'tipe' => 'fixed', 'item' => 'X', 'id_satuan' => $this->sat['event'], 'harga' => 9000000, 'is_manual' => 1]);
        $this->post("/program/$lama/finalkan")->assertSessionHasErrors('program');
        $this->assertSame('draft', DB::table('master_program')->where('id', $lama)->value('status'));
        DB::table('master_program')->where('id', $lama)->update(['jam_pembelajaran' => 8]);
        $this->post("/program/$lama/finalkan")->assertSessionHasNoErrors()->assertSessionHas('success', fn ($m) => str_contains($m, 'Profit minus'));
        $this->assertSame('final', DB::table('master_program')->where('id', $lama)->value('status'));
    }

    public function test_batalkan_butuh_manage_dan_view_saja_tidak_cukup(): void
    {
        $id = $this->draft();
        $viewer = $this->account('viewer');
        $this->allow($viewer, 'master_program', 'lihat');
        $this->login($viewer)->get('/program')->assertOk();
        $this->post("/program/$id/batalkan")->assertForbidden();
        $this->put("/program/$id", $this->payload())->assertForbidden();
        $this->post('/program', $this->payload())->assertForbidden();
        $this->get('/program/create')->assertForbidden();
        $this->get("/program/$id")->assertOk()->assertInertia(fn ($p) => $p->where('can.edit', false));
    }

    public function test_form_baru_memuat_fixed_cost_aktif_dengan_default_cost(): void
    {
        $this->get('/program/create')->assertOk()->assertInertia(fn ($p) => $p->where('program.biaya.0.item', 'Sewa Ruang')->where('program.biaya.0.harga', 1000000)->where('program.target_peserta', 10)->where('program.durasi_hari', 1));
    }

    public function test_costing_server_sama_dengan_rumus_dan_filter_daftar(): void
    {
        $id = $this->draft();
        $biaya = DB::table('master_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')->where('b.id_master_program', $id)->get(['b.*', 's.nama as nama_satuan'])->map(fn ($b) => (array) $b)->all();
        $this->assertEquals(Costing::calculate(3, 10, 2500000, $biaya)['total_hpp'], 3000000 + 1500000);
        $this->get('/program?status=final')->assertInertia(fn ($p) => $p->where('rows.total', 0));
        $this->get('/program?status=draft&q=K3')->assertInertia(fn ($p) => $p->where('rows.total', 1));
        $this->get('/program?q=tidak-ada')->assertInertia(fn ($p) => $p->where('rows.total', 0));
    }
}

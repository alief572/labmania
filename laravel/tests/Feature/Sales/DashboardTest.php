<?php

namespace Tests\Feature\Sales;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Master\MasterTestCase;

/** Dashboard (FR-40..42): angka KPI, jadwal, perlu tindakan, rincian, dan hak menu 'dashboard'. */
class DashboardTest extends MasterTestCase
{
    private int $master;

    private int $trainer;

    private int $unit;

    private int $employee;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotation_peserta', 'quotation', 'penjualan_program'] as $t) {
            DB::statement('DROP TABLE '.$t);
        }
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/sales_base.sql'));
        (require base_path('database/migrations/2026_11_13_130000_penjualan_quotation_to_be.php'))->up();
        $this->travelTo(now()->setDate(2026, 11, 13)->startOfDay());
        foreach (['dashboard' => 50, 'penjualan_program' => 41] as $code => $id) {
            DB::table('ms_menu')->insert(['id' => $id, 'kode' => $code, 'judul' => $code, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => $id, 'aktif' => 1, 'sistem' => 0]);
            foreach (['lihat', 'tambah', 'ubah', 'aktif', 'hapus', 'approve'] as $aksi) {
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $aksi]);
            }
        }
        $ids = $this->seedAttributes();
        $product = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-UJI', 'judul' => 'Program Uji', 'id_jenis_produk' => $ids['ms_jenis_produk'], 'id_jenis_bidang' => $ids['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $ids['ms_jenis_sertifikasi'], 'id_varian_pelaksanaan' => $ids['ms_varian_pelaksanaan'], 'aktif' => 1]);
        $this->master = DB::table('master_program')->insertGetId(['nomor' => 'MP-UJI', 'status' => 'final', 'durasi_hari' => 2, 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => 1000000]);
        DB::table('master_program_produk')->insert(['id_master_program' => $this->master, 'id_paket_produk' => $product, 'urutan' => 1]);
        $this->unit = (int) DB::table('ms_satuan')->insertGetId(['category' => 'rate_fixed', 'code' => 'event', 'nama' => 'Event', 'deleted' => '0']);
        $this->trainer = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-UJI', 'nama' => 'Trainer', 'aktif' => 1]);
        $this->employee = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-UJI', 'nama' => 'Asisten Uji', 'aktif' => 1]);
        DB::table('master_customer')->insert(['id_customer' => 'CO-001', 'name_customer' => 'Company Uji', 'activation' => 'aktif', 'pic_name' => 'PIC', 'address_office' => 'Alamat', 'kota' => 'Kota']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    /** Buat Penjualan Program lewat alur resmi; $harga per peserta, $biaya fixed total. */
    private function sale(string $mulai, string $selesai, bool $b2b, int $harga, int $biaya, bool $final = true): int
    {
        $this->login($this->admin())->post('/penjualan-program', [
            'jenis' => $b2b ? 'b2b' : 'public', 'id_master_program' => $this->master, 'id_customer' => $b2b ? 'CO-001' : null, 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee,
            'tgl_mulai' => $mulai, 'tgl_selesai' => $selesai, 'waktu' => '08.00–16.00', 'lokasi' => 'Lokasi uji', 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => (string) $harga, 'target_profit' => 0,
            'biaya' => [['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Biaya uji', 'id_satuan' => $this->unit, 'harga' => $biaya, 'is_manual' => true]],
        ])->assertSessionHasNoErrors();
        $id = (int) DB::table('penjualan_program')->orderByDesc('id')->value('id');
        if ($final) {
            $this->post('/penjualan-program/'.$id.'/finalkan')->assertSessionHasNoErrors();
        }

        return $id;
    }

    public function test_guest_redirected_and_menu_permission_enforced(): void
    {
        $this->get('/')->assertRedirect('/login');
        $staff = $this->account();
        $this->login($staff)->get('/')->assertOk()->assertInertia(fn ($p) => $p->component('Home')->missing('kpi'));
        $this->get('/dashboard/kpi/final')->assertForbidden();
        DB::table('ms_user_akses')->insert(['id_user' => $staff->id_user, 'id_menu' => 50, 'aksi' => 'lihat']);
        $this->get('/')->assertOk()->assertInertia(fn ($p) => $p->component('Dashboard/Index')->where('can.sales', false));
    }

    public function test_empty_database_shows_zero_kpi(): void
    {
        $this->login($this->admin())->get('/')->assertInertia(fn ($p) => $p->where('kpi.final', 0)->where('kpi.pendapatan', 0)->where('kpi.margin', 0)->where('kpi.draft', 0)->where('jadwal', [])->where('todoTotal', 0));
    }

    public function test_kpi_schedule_and_todo_follow_final_sales_only(): void
    {
        $this->sale('2026-12-20', '2026-12-21', false, 1000000, 100000);
        $b2bFinal = $this->sale('2026-12-01', '2026-12-02', true, 2000000, 500000);
        $loss = $this->sale('2026-12-10', '2026-12-11', false, 100000, 5000000);
        $this->sale('2026-12-25', '2026-12-26', false, 1000000, 100000, false);
        $past = $this->sale('2026-12-05', '2026-12-06', false, 1000000, 100000);
        DB::table('penjualan_program')->where('id', $past)->update(['tgl_mulai' => '2026-11-01', 'tgl_selesai' => '2026-11-02']);
        DB::table('master_program')->insert(['nomor' => 'MP-DRAFT', 'status' => 'draft', 'durasi_hari' => 1, 'jam_pembelajaran' => 8, 'target_peserta' => 5, 'harga_jual_peserta' => 0]);

        $this->get('/')->assertOk()->assertInertia(function ($p) use ($b2bFinal, $loss) {
            $p->where('kpi.final', 4)->where('kpi.public', 3)->where('kpi.b2b', 1)->where('kpi.peserta', 40)
                ->where('kpi.pendapatan', 10000000 + 20000000 + 1000000 + 10000000)->where('kpi.draft', 2)
                ->has('jadwal', 3)->where('jadwal.0.id', $b2bFinal)->where('jadwal.0.company', 'Company Uji');
            $todo = $p->toArray()['props']['todo'];
            $this->assertContains($loss, array_column(array_filter($todo, fn ($t) => $t['warn']), 'id'));
        });
        $this->assertNotContains($past, array_column($this->get('/')->viewData('page')['props']['jadwal'], 'id'));
    }

    public function test_detail_returns_rows_that_add_up_to_the_kpi_and_rejects_unknown_key(): void
    {
        $this->sale('2026-12-20', '2026-12-21', false, 1000000, 100000);
        $this->sale('2026-12-01', '2026-12-02', true, 2000000, 500000);
        $this->sale('2026-12-25', '2026-12-26', false, 1000000, 100000, false);
        $kpi = $this->get('/')->viewData('page')['props']['kpi'];

        $finals = $this->getJson('/dashboard/kpi/prof')->assertOk()->json('finals');
        $this->assertCount(2, $finals);
        $this->assertEquals($kpi['pendapatan'], array_sum(array_column($finals, 'pendapatan')));
        $this->assertEquals($kpi['profit'], array_sum(array_column($finals, 'profit')));
        foreach ($finals as $f) {
            $this->assertEquals($f['pendapatan'] - $f['fixed'] - $f['variable'], $f['profit']);
        }
        $draft = $this->getJson('/dashboard/kpi/draft')->assertOk()->json();
        $this->assertCount(1, $draft['penjualan']);
        $this->assertCount(0, $draft['master']);
        $this->getJson('/dashboard/kpi/lain')->assertNotFound();
    }
}

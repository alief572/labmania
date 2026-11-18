<?php

namespace Tests\Feature\Sales;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Master\MasterTestCase;

class Phase3Test extends MasterTestCase
{
    use Phase3Scenarios;
    use TopQuotationScenarios;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotation_peserta', 'quotation', 'penjualan_program'] as $table) {
            DB::statement('DROP TABLE '.$table);
        }
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/sales_base.sql'));
        (require base_path('database/migrations/2026_11_13_130000_penjualan_quotation_to_be.php'))->up();
        foreach (['penjualan_program' => 41, 'quotation_program' => 42] as $code => $id) {
            DB::table('ms_menu')->insert(['id' => $id, 'kode' => $code, 'judul' => $code, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => $id, 'aktif' => 1, 'sistem' => 0]);
            foreach (['lihat', 'tambah', 'ubah', 'aktif', 'hapus', 'approve'] as $aksi) {
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $aksi]);
            }
        }
        $this->seedPhase3();
        (require base_path('database/migrations/2026_11_14_090000_sales_order_invoice.php'))->up();
        (require base_path('database/migrations/2026_11_14_100000_reschedule_refund.php'))->up();
        (require base_path('database/migrations/2026_11_17_090000_quotation_top.php'))->up();
        (require base_path('database/migrations/2026_11_18_100000_refund_approval.php'))->up();
    }

    public function test_halaman_quotation_memuat_jumlah_tab_dan_ringkasan_batch(): void
    {
        $this->login($this->admin());
        $program = $this->program();
        $this->quote($program);

        $this->get('/quotation')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Quotation/Index')->where('tabCounts.ongoing', 1)->where('tabCounts.selesai', 0)->has('tabCounts.data'));
        $this->get("/quotation/batch/$program?data=1")->assertOk()->assertInertia(fn ($page) => $page
            ->component('Quotation/Batch')->where('summary.quotation', 1)->where('summary.total', 0)->where('summary.aktif', 0)->has('summary.harga'));
    }

    public function test_daftar_sales_order_memuat_tahun_acara_untuk_filter(): void
    {
        $this->login($this->admin());
        $this->program();

        $this->get('/sales-order')->assertOk()->assertInertia(fn ($page) => $page
            ->component('SalesOrder/Index')->has('years', 1)->where('years.0', 2026));
    }
}

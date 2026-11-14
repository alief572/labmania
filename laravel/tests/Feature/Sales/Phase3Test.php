<?php

namespace Tests\Feature\Sales;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Master\MasterTestCase;

class Phase3Test extends MasterTestCase
{
    use Phase3Scenarios;

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
    }
}

<?php

namespace Tests\Feature\Sales;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Master\MasterTestCase;

class Phase4Test extends MasterTestCase
{
    use Phase4Scenarios;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotation_peserta', 'quotation', 'penjualan_program'] as $t) {
            DB::statement('DROP TABLE '.$t);
        }
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/sales_base.sql'));
        (require base_path('database/migrations/2026_11_13_130000_penjualan_quotation_to_be.php'))->up();
        (require base_path('database/migrations/2026_11_14_090000_sales_order_invoice.php'))->up();
        (require base_path('database/migrations/2026_11_14_100000_reschedule_refund.php'))->up();
        $this->seedPhase4();
    }
}

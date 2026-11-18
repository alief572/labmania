<?php

namespace Tests\Feature\Sales;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Master\MasterTestCase;

class Phase6Test extends MasterTestCase
{
    use OperationalDataTableScenarios;
    use Phase6Scenarios;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotation_peserta', 'quotation', 'penjualan_program'] as $t) {
            DB::statement('DROP TABLE '.$t);
        }
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/sales_base.sql'));
        foreach (['2026_11_13_130000_penjualan_quotation_to_be.php', '2026_11_14_090000_sales_order_invoice.php', '2026_11_14_100000_reschedule_refund.php', '2026_11_14_110000_operasional_biaya.php', '2026_11_17_090000_quotation_top.php'] as $f) {
            (require base_path('database/migrations/'.$f))->up();
        }
        $this->seedPhase6();
    }
}

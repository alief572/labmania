<?php

namespace Tests\Feature\Master;

use App\Support\Costing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Port Costing harus identik dengan Master_program_model::calculate_costing (CI3).
 * Fungsi CI3 diekstrak dari sumbernya (tanpa CodeIgniter/DB) lalu dijalankan pada vektor yang sama.
 */
class CostingParityTest extends TestCase
{
    private function legacy(): object
    {
        $source = str_replace("\r\n", "\n", file_get_contents(__DIR__.'/../../../../application/modules/master_program/models/Master_program_model.php'));
        preg_match('/public function calculate_costing\(.*?\n    \}\n/s', $source, $m);
        $this->assertNotEmpty($m, 'Fungsi CI3 tidak ditemukan');

        return eval('return new class { '.$m[0].' };');
    }

    public static function vectors(): array
    {
        $b = fn ($tipe, $satuan, $harga) => ['tipe' => $tipe, 'nama_satuan' => $satuan, 'harga' => $harga];

        return [
            'kosong' => [1, 10, 0, []],
            'tipikal' => [3, 12, 2500000, [$b('fixed', 'Event', 5000000), $b('fixed', 'Day', 750000), $b('variable', 'Peserta/Event', 100000), $b('variable', 'Peserta/Day', 50000)]],
            'rugi' => [2, 5, 100000, [$b('fixed', 'Event', 9000000), $b('variable', 'Peserta/Event', 20000)]],
            'bep tak tercapai' => [1, 10, 100000, [$b('fixed', 'Event', 1000000), $b('variable', 'Peserta/Event', 150000)]],
            'bep minimal 1' => [1, 10, 500000, [$b('fixed', 'Event', 1), $b('variable', 'Peserta/Event', 1000)]],
            'bep pecahan' => [2, 7, 333333, [$b('fixed', 'Event', 1234567), $b('fixed', 'Day', 89123), $b('variable', 'Peserta/Day', 7777)]],
            'durasi nol' => [0, 0, 1000, [$b('fixed', 'Day', 100)]],
            'target nol' => [4, 0, 1000, [$b('fixed', 'Day', 100), $b('variable', 'Peserta/Day', 10)]],
            'harga persis impas' => [1, 4, 100, [$b('fixed', 'Event', 300), $b('variable', 'Peserta/Event', 25)]],
        ];
    }

    #[DataProvider('vectors')]
    public function test_sama_dengan_ci3(int $durasi, int $target, int $harga, array $biaya): void
    {
        $this->assertEquals($this->legacy()->calculate_costing($durasi, $target, $harga, $biaya), Costing::calculate($durasi, $target, $harga, $biaya));
    }

    public function test_peringatan_finalkan(): void
    {
        $rugi = Costing::calculate(2, 5, 100000, [['tipe' => 'fixed', 'nama_satuan' => 'Event', 'harga' => 9000000]]);
        $this->assertSame(['Profit minus.', 'BEP 90 peserta di atas target 5 peserta.'], Costing::warnings($rugi, 5));
        $tidak = Costing::calculate(1, 10, 100000, [['tipe' => 'variable', 'nama_satuan' => 'Peserta/Event', 'harga' => 150000]]);
        $this->assertContains('BEP tidak tercapai: harga jual ≤ biaya variable / peserta.', Costing::warnings($tidak, 10));
        $this->assertSame([], Costing::warnings(Costing::calculate(1, 10, 500000, [['tipe' => 'fixed', 'nama_satuan' => 'Event', 'harga' => 1000000]]), 10));
    }
}

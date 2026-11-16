<?php

namespace Tests\Unit;

use App\Support\Invoice;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    public function test_rupiah_rounding_tax_matrix_and_allin_extra(): void
    {
        $q = (object) ['skema_harga' => 'peserta', 'harga_peserta' => '101', 'diskon' => '0', 'biaya_lain' => '3', 'is_ppn' => 1];
        foreach (['PKP' => '115', 'WAPU' => '104', 'KB' => '104'] as $treatment => $receivable) {
            $h = Invoice::calculate($q, 1, $treatment);
            $this->assertSame('93', $h['dpp']);
            $this->assertSame('11', $h['ppn']);
            $this->assertSame($receivable, $h['piutang']);
            Invoice::balance(Invoice::journal($h, $treatment));
        }
        $q->is_ppn = 0;
        $h = Invoice::calculate($q, 1, 'WAPU');
        $this->assertSame('0', $h['dpp']);
        $this->assertCount(2, Invoice::journal($h, 'WAPU'));
        $q = (object) ['skema_harga' => 'allin', 'harga_allin' => '20000000', 'paket_maks_peserta' => 25, 'tambahan_per_peserta' => 300000, 'diskon' => 500000, 'biaya_lain' => 10000, 'is_ppn' => 1];
        $h = Invoice::calculate($q, 27, 'PKP');
        $this->assertSame('20100000', $h['penjualan']);
        $this->assertSame('18425000', $h['dpp']);
        $this->assertSame('2211000', $h['ppn']);
        $this->assertSame('22321000', $h['piutang']);
    }

    public function test_unbalanced_journal_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        Invoice::balance([['piutang', '1', '0']]);
    }

    public function test_exact_rupiah_and_terbilang_without_float_precision_loss(): void
    {
        $this->assertSame('Rp 999.999.999.999.999.999', Invoice::rupiah('999999999999999999'));
        $this->assertSame('Rp 101', Invoice::rupiah('100.50'));
        $this->assertSame('dua juta seratus sembilan belas ribu', Invoice::terbilang('2119000'));
        $this->assertSame('sebelas', Invoice::terbilang('11'));
        $this->assertSame('seratus dua belas', Invoice::terbilang('112'));
        $this->assertSame('seribu satu', Invoice::terbilang('1001'));
        $this->assertSame('satu kuadriliun satu', Invoice::terbilang('1000000000000001'));
    }
}

<?php

namespace Tests\Feature\Sales;

use App\Support\Invoice;
use App\Support\Quotation;
use App\Support\SalesOrder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Cara pembayaran TOP (QTN-FR-44, SO-FR-24). Memakai seed Phase4: quotation personal, 2 peserta Deal,
 * harga 1.000.000/peserta, diskon 50.000, PPN: penjualan 1.900.000, DPP 1.741.667, PPN 209.000, total 2.119.000.
 */
trait TopScenarios
{
    private function useTop(array $persen = [40, 60]): int
    {
        $q = DB::table('quotation')->value('id');
        DB::table('quotation')->where('id', $q)->update(['cara_bayar' => 'top']);
        foreach ($persen as $i => $p) {
            DB::table('quotation_termin')->insert(['id_quotation' => $q, 'urutan' => $i + 1, 'persen' => $p, 'keterangan' => 'Termin '.($i + 1)]);
        }
        SalesOrder::ready($this->so);

        return $q;
    }

    public function test_top_bills_one_invoice_per_termin_and_last_termin_takes_rounding(): void
    {
        $this->useTop([33.33, 33.33, 33.34]);
        $totals = [];
        foreach ([1, 2, 3] as $ke) {
            $source = Invoice::source($this->so);
            $this->assertSame($ke, $source['termin']['ke']);
            $this->assertSame(3, $source['termin']['dari']);
            $id = Invoice::save($this->so, $this->invoiceInput(['termin_ke' => $ke]));
            $totals[$ke] = DB::table('invoice')->where('id', $id)->first();
        }
        $this->assertSame([1, 2, 3], array_keys($totals));
        // Penjualan 1.900.000 dibagi menurut persen; termin terakhir menampung selisih sehingga jumlahnya tepat.
        $this->assertSame('633270', (string) $totals[1]->penjualan);
        $this->assertSame('633270', (string) $totals[2]->penjualan);
        $this->assertSame('1900000', (string) array_sum(array_map(fn ($i) => (int) $i->penjualan, $totals)));
        $this->assertSame(3, DB::table('invoice')->where('status', 'invoiced')->count());
        $this->assertSame('Invoiced', SalesOrder::detail(SalesOrder::row($this->so))['so']['status_tampil']);
    }

    public function test_top_so_stays_uninvoiced_until_every_termin_is_billed(): void
    {
        $this->useTop([40, 60]);
        $this->assertSame(1, Invoice::uninvoiced()->count());
        Invoice::save($this->so, $this->invoiceInput(['termin_ke' => 1]));
        $this->assertSame(1, Invoice::uninvoiced()->count(), 'SO TOP tetap Uninvoiced setelah termin 1.');
        $this->assertNotSame('Invoiced', SalesOrder::detail(SalesOrder::row($this->so))['so']['status_tampil']);
        $this->assertSame(2, Invoice::source($this->so)['termin']['ke']);
        Invoice::save($this->so, $this->invoiceInput(['termin_ke' => 2]));
        $this->assertSame(0, Invoice::uninvoiced()->count());
    }

    public function test_top_termin_is_idempotent_and_rejects_skipping_ahead(): void
    {
        $this->useTop([40, 60]);
        $first = Invoice::save($this->so, $this->invoiceInput(['termin_ke' => 1]));
        $this->assertSame($first, Invoice::save($this->so, $this->invoiceInput(['termin_ke' => 1])), 'Klik ganda tidak boleh menagih termin berikutnya.');
        $this->assertSame(1, DB::table('invoice')->count());
        $this->post('/invoicing', $this->invoiceInput(['termin_ke' => 2, 'tanggal' => '2026-11-14']))->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('invoice')->count());
        $this->post('/invoicing', $this->invoiceInput(['termin_ke' => 3]))->assertSessionHasErrors('termin_ke');
    }

    public function test_top_requires_termin_ke_and_blocks_skipping(): void
    {
        $this->useTop([50, 50]);
        $this->post('/invoicing', $this->invoiceInput())->assertSessionHasErrors('termin_ke');
        $this->post('/invoicing', $this->invoiceInput(['termin_ke' => 2]))->assertSessionHasErrors('termin_ke');
        $this->assertSame(0, DB::table('invoice')->count());
    }

    public function test_unique_active_invoice_is_per_termin_and_cancelled_termin_can_be_rebilled(): void
    {
        $this->useTop([50, 50]);
        $id = Invoice::save($this->so, $this->invoiceInput(['termin_ke' => 1]));
        $copy = (array) Invoice::row($id);
        unset($copy['id'], $copy['so_aktif']);
        $copy['nomor'] = 'LMI/INV/11/26-998';
        try {
            DB::table('invoice')->insert($copy);
            $this->fail('Invoice aktif ganda untuk termin yang sama lolos.');
        } catch (QueryException $e) {
            $this->assertContains($e->errorInfo[1], [19, 1062]);
        }
        $copy['termin_ke'] = 2;
        DB::table('invoice')->insert($copy);
        $this->assertSame(2, DB::table('invoice')->whereNotNull('so_aktif')->count());
        DB::table('invoice')->where('id', $id)->update(['status' => 'batal']);
        $this->assertSame(1, Invoice::source($this->so)['termin']['ke']);
    }

    public function test_full_payment_is_unchanged_when_quotation_has_no_termin(): void
    {
        SalesOrder::ready($this->so);
        $this->assertNull(Invoice::source($this->so)['termin']);
        $id = Invoice::save($this->so, $this->invoiceInput());
        $this->assertNull(DB::table('invoice')->where('id', $id)->value('termin_ke'));
        $this->assertSame('1900000', (string) DB::table('invoice')->where('id', $id)->value('penjualan'));
    }

    public function test_termin_share_helper_math(): void
    {
        $t = [(object) ['persen' => '50.00'], (object) ['persen' => '50.00']];
        $this->assertSame('950001', Quotation::terminShare($t, 1, '1900001'));
        $this->assertSame('950000', Quotation::terminShare($t, 2, '1900001', '950001'));
    }

    public function test_invoice_print_and_sales_order_show_the_termin(): void
    {
        $this->useTop([40, 60]);
        $first = Invoice::save($this->so, $this->invoiceInput(['termin_ke' => 1]));
        $invoice = Invoice::row($first);
        $snapshot = json_decode($invoice->snapshot, true);
        $this->assertSame([1, 2, 40.0, 'Termin 1'], [$snapshot['termin']['ke'], $snapshot['termin']['dari'], (float) $snapshot['termin']['persen'], $snapshot['termin']['keterangan']]);

        // Cetak invoice mencantumkan nomor termin dari snapshot historis (SO-FR-24), tidak dari master terbaru.
        $print = ['bank' => 'BANK UJI', 'rekening' => '1', 'atas_nama' => 'PT', 'penanda_tangan' => 'TTD', 'jabatan' => 'Finance', 'kota' => 'Bekasi', 'kode_dokumen' => 'FR'];
        $html = view('pdf.invoice', ['invoice' => $invoice, 'snapshot' => $snapshot, 'issuer' => $print])->render();
        $this->assertStringContainsString('Pembayaran: Termin 1 dari 2', $html);
        DB::table('quotation_termin')->where('urutan', 1)->update(['keterangan' => 'Master berubah']);
        $this->assertStringContainsString('(Termin 1)', view('pdf.invoice', ['invoice' => $invoice, 'snapshot' => $snapshot, 'issuer' => $print])->render());

        // Invoice Full payment tidak menulis baris termin.
        $fullHtml = view('pdf.invoice', ['invoice' => (object) (array_replace((array) $invoice, ['termin_ke' => null])), 'snapshot' => array_diff_key($snapshot, ['termin' => 1]), 'issuer' => $print])->render();
        $this->assertStringNotContainsString('Pembayaran: Termin', $fullHtml);

        // Detail SO membawa semua termin dan invoice aktif per termin; status SO baru "Invoiced" setelah semua termin terbit.
        $detail = SalesOrder::detail(SalesOrder::row($this->so));
        $this->assertCount(2, $detail['termin']);
        $this->assertSame([1], $detail['invoices']->pluck('termin_ke')->map(fn ($k) => (int) $k)->all());
        $this->assertNotSame('Invoiced', $detail['so']['status_tampil']);
        Invoice::save($this->so, $this->invoiceInput(['termin_ke' => 2]));
        $detail = SalesOrder::detail(SalesOrder::row($this->so));
        $this->assertSame([1, 2], $detail['invoices']->pluck('termin_ke')->map(fn ($k) => (int) $k)->all());
        $this->assertSame('Invoiced', $detail['so']['status_tampil']);
    }
}

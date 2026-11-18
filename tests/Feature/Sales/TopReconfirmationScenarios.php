<?php

namespace Tests\Feature\Sales;

use App\Support\Invoice;
use App\Support\Quotation;
use App\Support\Refund;
use Illuminate\Support\Facades\DB;

/** Quotation TOP dan konfirmasi ulang ([JMR-7]): refund dari termin yang sudah dibayar, termin belum dibayar dibatalkan. Memakai seed Phase5. */
trait TopReconfirmationScenarios
{
    private function topQuotation(array $persen = [40, 60]): void
    {
        DB::table('quotation')->where('id', $this->ids5['q'])->update(['cara_bayar' => 'top']);
        foreach ($persen as $i => $p) {
            DB::table('quotation_termin')->insert(['id_quotation' => $this->ids5['q'], 'urutan' => $i + 1, 'persen' => $p, 'keterangan' => 'Termin '.($i + 1)]);
        }
    }

    private function billTermin(int $ke, bool $paid): object
    {
        $id = Invoice::save($this->ids5['so'], ['tanggal' => '2026-11-14', 'no_faktur' => 'FAKTUR5-'.$ke, 'coa_penjualan' => 902, 'termin_ke' => $ke]);
        if ($paid) {
            Invoice::record($id, ['tanggal' => '2026-11-14', 'keterangan' => 'Lunas termin '.$ke]);
        }

        return Invoice::row($id);
    }

    public function test_top_refund_comes_from_paid_termin_and_cancels_unpaid_termins(): void
    {
        $this->topQuotation([40, 60]);
        $paid = $this->billTermin(1, true);
        $unpaid = $this->billTermin(2, false);
        $this->runReschedule5();
        [$a, $b] = $this->ids5['people'];

        $this->post('/quotation/peserta/'.$a.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Bentrok jadwal'])->assertSessionHasNoErrors();

        $refund = DB::table('sales_order_refund')->first();
        $weights = DB::table('invoice_peserta')->where('id_invoice', $paid->id)->pluck('nominal', 'id_quotation_peserta')->all();
        $expected = Refund::allocate((string) $paid->piutang, $weights)[$a];
        $this->assertSame((int) $expected, (int) $refund->nominal_tagihan, 'Refund = bagian peserta dari termin yang sudah dibayar, bukan seluruh nilai.');
        $this->assertSame($paid->id, (int) $refund->id_invoice, 'Rujukan refund adalah invoice termin yang dibayar.');
        $snapshot = json_decode($refund->snapshot, true);
        $this->assertSame([1], array_column($snapshot['termin'], 'termin_ke'));
        $this->assertSame('menunggu', $refund->status);

        $this->assertSame('invoiced', Invoice::row($paid->id)->status, 'Invoice yang sudah dibayar tidak dibatalkan.');
        $cancelled = Invoice::row($unpaid->id);
        $this->assertSame('batal', $cancelled->status, 'Termin yang belum dibayar dibatalkan.');
        $this->assertNotNull($cancelled->batal_alasan);
        $this->assertSame('keluar', DB::table('quotation_peserta')->where('id', $a)->value('status'));

        // Replay: jawaban yang sama tidak menggandakan refund atau pembatalan.
        $this->post('/quotation/peserta/'.$a.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Bentrok jadwal'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('sales_order_refund')->count());
        $this->assertSame(1, DB::table('logs')->where('action', 'Batal')->where('ref_no', $unpaid->nomor)->count());

        // Peserta yang tetap ikut: termin 2 kembali menjadi termin berikutnya, dan nilainya = sisa nilai Deal peserta yang tersisa
        // dikurangi termin 1 yang sudah dibayar, tanpa menghitung bagian peserta yang sudah keluar.
        $this->post('/quotation/peserta/'.$b.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'pindah_jadwal'])->assertSessionHasNoErrors();
        $source = Invoice::source($this->ids5['so']);
        $this->assertSame(2, $source['termin']['ke'], 'Termin 2 kembali menjadi termin berikutnya.');
        $this->assertSame(1, $source['peserta']->count(), 'Hanya peserta yang masih Deal yang ditagih.');
        $dealValue = (int) Invoice::calculate($source['quotation'], 1, $source['perlakuan_ppn'])['penjualan'];
        $paidShare = (int) DB::table('invoice_peserta')->where('id_invoice', $paid->id)->where('id_quotation_peserta', $b)->value('nominal');
        $this->assertSame($dealValue - $paidShare, (int) $source['amounts']['penjualan'], 'Termin terakhir menagih sisa nilai Deal peserta yang tersisa.');
    }

    public function test_top_refund_sums_every_paid_termin(): void
    {
        $this->topQuotation([50, 50]);
        $first = $this->billTermin(1, true);
        $second = $this->billTermin(2, true);
        $this->runReschedule5();
        $a = $this->ids5['people'][0];

        $this->post('/quotation/peserta/'.$a.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Tidak ikut'])->assertSessionHasNoErrors();

        $expected = 0;
        foreach ([$first, $second] as $invoice) {
            $weights = DB::table('invoice_peserta')->where('id_invoice', $invoice->id)->pluck('nominal', 'id_quotation_peserta')->all();
            $expected += (int) Refund::allocate((string) $invoice->piutang, $weights)[$a];
        }
        $refund = DB::table('sales_order_refund')->first();
        $this->assertSame($expected, (int) $refund->nominal_tagihan, 'Semua termin lunas dijumlahkan.');
        $this->assertSame([1, 2], array_column(json_decode($refund->snapshot, true)['termin'], 'termin_ke'));
        $this->assertSame(1, DB::table('sales_order_refund')->count());
        $this->assertSame(2, DB::table('invoice')->where('status', 'invoiced')->count(), 'Tidak ada termin belum dibayar yang perlu dibatalkan.');
    }

    public function test_top_resubmit_ceiling_is_the_sum_of_paid_termin_funds(): void
    {
        $this->topQuotation([40, 60]);
        $paid = $this->billTermin(1, true);
        $this->billTermin(2, false);
        $this->runReschedule5();
        $this->post('/quotation/peserta/'.$this->ids5['people'][0].'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Bentrok'])->assertSessionHasNoErrors();
        $refund = DB::table('sales_order_refund')->first();

        $this->assertSame((string) (int) $paid->piutang, (string) (int) Refund::ceiling($refund), 'Batas = dana termin yang dibayar, bukan nilai seluruh invoice.');
        $this->post('/refund/'.$refund->id.'/keputusan', ['keputusan' => 'tolak', 'catatan' => 'Perbaiki nominal'])->assertSessionHasNoErrors();
        $over = (string) ((int) $paid->piutang + 1);
        $this->post('/refund/'.$refund->id.'/ajukan-ulang', ['alasan' => 'Bentrok', 'nominal' => $over, 'catatan' => 'x'])->assertSessionHasErrors('nominal');
        $this->post('/refund/'.$refund->id.'/ajukan-ulang', ['alasan' => 'Bentrok', 'nominal' => (string) (int) $paid->piutang, 'catatan' => 'Sudah benar'])->assertSessionHasNoErrors();
        $this->assertSame('menunggu', DB::table('sales_order_refund')->value('status'));
    }

    public function test_top_batal_ikut_cancels_every_unpaid_termin_and_pindah_jadwal_keeps_them(): void
    {
        $this->topQuotation([40, 60]);
        $one = $this->billTermin(1, false);
        $two = $this->billTermin(2, false);
        $this->runReschedule5();
        [$a, $b] = $this->ids5['people'];

        $this->post('/quotation/peserta/'.$b.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'pindah_jadwal'])->assertSessionHasNoErrors();
        $this->assertSame(['invoiced', 'invoiced'], [Invoice::row($one->id)->status, Invoice::row($two->id)->status], 'Pindah jadwal tidak menyentuh invoice.');

        $this->post('/quotation/peserta/'.$a.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'batal', 'alasan' => 'Tidak bisa ikut'])->assertSessionHasNoErrors();
        $this->assertSame(['batal', 'batal'], [Invoice::row($one->id)->status, Invoice::row($two->id)->status], 'Semua termin belum dibayar dibatalkan.');
        $this->assertSame(0, DB::table('sales_order_refund')->count(), 'Batal ikut tanpa pembayaran tidak membentuk refund.');
    }

    public function test_full_payment_refund_is_unchanged_by_top_support(): void
    {
        $this->assertSame([], Quotation::termin(Quotation::row($this->ids5['q'])));
        $invoice = $this->invoice5(true);
        $this->runReschedule5();
        $this->post('/quotation/peserta/'.$this->ids5['people'][0].'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Bentrok jadwal'])->assertSessionHasNoErrors();
        $refund = DB::table('sales_order_refund')->first();
        $this->assertSame('1059501', (string) (int) $refund->nominal_tagihan);
        $this->assertNull(json_decode($refund->snapshot, true)['termin']);
        $this->assertSame((string) (int) $invoice->piutang, (string) (int) Refund::ceiling($refund));
    }
}

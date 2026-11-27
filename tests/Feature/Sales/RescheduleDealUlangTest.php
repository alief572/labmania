<?php

namespace Tests\Feature\Sales;

use App\Support\Invoice;
use App\Support\Quotation;
use App\Support\Sales;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Master\MasterTestCase;

/** Reschedule setelah Deal (SO-FR-16/21, QTN-FR-42, PJ-FR-27 [RDU]): SO Batal, quotation kembali Terkirim, Deal ulang menerbitkan SO -rev. */
class RescheduleDealUlangTest extends MasterTestCase
{
    use Phase5Scenarios;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotation_peserta', 'quotation', 'penjualan_program'] as $t) {
            DB::statement('DROP TABLE '.$t);
        }
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/sales_base.sql'));
        foreach (['2026_11_13_130000_penjualan_quotation_to_be.php', '2026_11_14_090000_sales_order_invoice.php', '2026_11_14_100000_reschedule_refund.php', '2026_11_14_110000_operasional_biaya.php',
            '2026_11_17_090000_quotation_top.php', '2026_11_18_100000_refund_approval.php', '2026_11_19_090000_penjualan_batal_dampak.php', '2026_11_19_100000_reschedule_deal_ulang.php', '2026_11_19_110000_penjualan_multi_master.php'] as $f) {
            (require base_path('database/migrations/'.$f))->up();
        }
        $this->seedPhase5();
        DB::table('quotation_dokumen')->insert(['id_quotation' => $this->ids5['q'], 'nama_file' => 'po.pdf', 'path_file' => 'uploads/x/po.pdf', 'tipe_file' => 'pdf', 'ukuran_byte' => 10]);
    }

    private function quotation(): object
    {
        return Quotation::row($this->ids5['q']);
    }

    private function redeal(array $people)
    {
        return $this->post('/quotation/'.$this->ids5['q'].'/deal', ['peserta' => $people]);
    }

    private function answer(int $person, string $decision = 'pindah_jadwal'): void
    {
        $this->post('/quotation/peserta/'.$person.'/konfirmasi-ulang', ['revisi' => $this->quotation()->revisi, 'keputusan' => $decision] + ($decision === 'pindah_jadwal' ? [] : ['alasan' => 'Tidak bisa ikut']))->assertSessionHasNoErrors();
    }

    public function test_reschedule_quotation_deal_kembali_terkirim_dan_so_masuk_so_batal(): void
    {
        $invoice = $this->invoice5(false);
        $this->runReschedule5();

        $q = $this->quotation();
        $this->assertSame('terkirim', $q->status);
        $this->assertSame(1, (int) $q->revisi);
        $this->assertSame(1, (int) $q->deal_ulang_revisi);
        $this->assertTrue(Quotation::needsRedeal($q));

        $so = DB::table('sales_order')->where('id', $this->ids5['so'])->first();
        $this->assertSame('batal', $so->status);
        $this->assertSame('reschedule', $so->batal_sebab);
        $this->assertSame(1, (int) $so->batal_revisi);
        $this->assertSame(2, (int) $so->batal_peserta);
        $this->assertSame('invoiced', Invoice::row($invoice->id)->status, 'Invoice tidak dibatalkan');
        $this->assertSame(1, (int) Invoice::row($invoice->id)->resched_revisi);
        $this->assertNotNull(Invoice::row($invoice->id)->resched_waktu);
        $this->assertSame('PJP-2611-001', Sales::program($this->ids5['p'])->nomor);
        $this->assertDatabaseHas('logs', ['action' => 'Batal', 'ref_no' => 'LMI/SO/11/26-001']);
        $this->getJson('/sales-order/data?tab=batal')->assertOk()->assertJsonPath('recordsFiltered', 1);
    }

    public function test_tab_so_batal_menerima_request_tabel_browser_dan_pencarian_judul(): void
    {
        $this->invoice5(false);
        $this->runReschedule5();
        $judul = DB::table('master_program as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')
            ->where('m.id', Sales::program($this->ids5['p'])->id_master_program)->value('k.judul');
        // Delapan kolom seperti tabel di SalesOrder/Index.vue.
        $get = fn (string $cari) => $this->getJson('/sales-order/data?'.http_build_query(['tab' => 'batal', 'draw' => 1, 'start' => 0, 'length' => 25,
            'columns' => array_fill(0, 8, ['search' => ['value' => '', 'regex' => 'false']]), 'order' => [['column' => 4, 'dir' => 'desc']], 'search' => ['value' => $cari, 'regex' => 'false']]))->assertOk();

        $get('')->assertJsonPath('recordsFiltered', 1);
        $get($judul)->assertJsonPath('recordsFiltered', 1);
        $get('LMI/SO/11/26-001')->assertJsonPath('recordsFiltered', 1);
        $get('tidak-ada-yang-cocok')->assertJsonPath('recordsFiltered', 0);
    }

    public function test_so_batal_bisa_dilihat_dan_tidak_membuat_daftar_so_program_gagal(): void
    {
        $this->invoice5(false);
        $this->runReschedule5();

        $this->getJson('/sales-order/program/'.$this->ids5['p'].'/data')->assertOk()->assertJsonPath('recordsTotal', 0)->assertJsonPath('data', []);
        $this->get('/sales-order/'.$this->ids5['so'])->assertOk()->assertInertia(fn ($page) => $page->where('so.status_tampil', 'Batal'));
        $this->post('/sales-order/'.$this->ids5['so'].'/siap-invoice')->assertSessionHasErrors('so');
        $this->assertSame('batal', DB::table('sales_order')->where('id', $this->ids5['so'])->value('status'));
    }

    public function test_reschedule_hanya_ganti_asisten_tidak_mengubah_deal_atau_so(): void
    {
        $this->invoice5(false);
        $p = Sales::program($this->ids5['p']);
        $other = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-9', 'nama' => 'Asisten Lain', 'aktif' => 1]);
        $this->post('/penjualan-program/'.$this->ids5['p'].'/reschedule', $this->reschedule5(['tgl_mulai' => $p->tgl_mulai, 'id_asisten' => $other]))->assertSessionHasNoErrors();

        $this->assertSame('deal', $this->quotation()->status);
        $this->assertSame('siap', DB::table('sales_order')->where('id', $this->ids5['so'])->value('status'));
    }

    public function test_deal_ulang_menerbitkan_so_rev_nomor_sama_dan_invoice_dipindah(): void
    {
        $invoice = $this->invoice5(true);
        $this->runReschedule5();
        [$a, $b] = $this->ids5['people'];
        $this->answer($a);
        $this->answer($b);

        $this->redeal([$a, $b])->assertSessionHasNoErrors();

        $q = $this->quotation();
        $this->assertSame('deal', $q->status);
        $this->assertNull($q->deal_ulang_revisi);
        $this->assertSame(1, (int) $q->deal_ulang_selesai_revisi);
        $old = DB::table('sales_order')->where('id', $this->ids5['so'])->first();
        $new = DB::table('sales_order')->where('id_quotation', $this->ids5['q'])->where('status', '!=', 'batal')->first();
        $this->assertNotNull($new);
        $this->assertSame($old->nomor, $new->nomor, 'Nomor dasar sama dengan SO lama');
        $this->assertSame(1, (int) $new->revisi);
        $this->assertSame('open', $new->status);
        $this->assertSame((int) $new->id, (int) $old->digantikan_oleh);
        $this->assertSame((int) $new->id, (int) Invoice::row($invoice->id)->id_sales_order, 'Invoice lama dipakai SO baru');
        $this->assertSame('invoiced', Invoice::row($invoice->id)->status);
        $this->assertSame(0, DB::table('sales_order_refund')->count());
        $this->assertDatabaseHas('logs', ['action' => 'Deal Ulang', 'ref_no' => $q->nomor]);
        $this->assertSame(1, DB::table('sales_order')->where('id_quotation', $this->ids5['q'])->where('status', '!=', 'batal')->count());
    }

    public function test_deal_ulang_menolak_peserta_yang_tidak_pindah_jadwal_dan_menunggu_jawaban(): void
    {
        $this->invoice5(false);
        $this->runReschedule5();
        [$a, $b] = $this->ids5['people'];

        $this->redeal([$a, $b])->assertSessionHasErrors('peserta');
        $this->answer($a);
        $this->answer($b, 'batal');
        $this->redeal([$a, $b])->assertSessionHasErrors('peserta');
        $this->assertSame('terkirim', $this->quotation()->status);
        $this->assertSame(1, DB::table('sales_order')->where('id_quotation', $this->ids5['q'])->count(), 'Tidak ada SO baru yang bocor');
    }

    public function test_deal_ulang_memproses_otomatis_peserta_lama_yang_tidak_ikut_dengan_refund(): void
    {
        $invoice = $this->invoice5(true);
        $this->runReschedule5();
        [$a, $b] = $this->ids5['people'];
        $this->answer($a);
        $this->answer($b, 'refund');

        $this->redeal([$a])->assertSessionHasNoErrors();

        $this->assertSame('deal', DB::table('quotation_peserta')->where('id', $a)->value('status'));
        $this->assertSame('keluar', DB::table('quotation_peserta')->where('id', $b)->value('status'));
        $refund = DB::table('sales_order_refund')->first();
        $this->assertNotNull($refund);
        $this->assertSame('menunggu', $refund->status);
        $this->assertSame((int) $b, (int) $refund->id_quotation_peserta);
        $this->assertSame('invoiced', Invoice::row($invoice->id)->status);
    }

    public function test_deal_ulang_ditolak_setelah_tanggal_mulai_dan_tanpa_dokumen_lama_wajib_dokumen(): void
    {
        $this->invoice5(false);
        $this->runReschedule5();
        [$a] = $this->ids5['people'];
        $this->answer($a);
        $this->travelTo(now()->setDate(2027, 1, 10)->startOfDay());
        $this->redeal([$a])->assertSessionHasErrors('quotation');
        $this->travelTo(now()->setDate(2026, 11, 14)->startOfDay());

        DB::table('quotation_dokumen')->delete();
        $this->redeal([$a])->assertSessionHasErrors();
        $this->assertSame('terkirim', $this->quotation()->status);
    }
}

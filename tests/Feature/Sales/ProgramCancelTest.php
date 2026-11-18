<?php

namespace Tests\Feature\Sales;

use App\Support\Invoice;
use App\Support\ProgramCancel;
use App\Support\Sales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Master\MasterTestCase;

/** Pembatalan Penjualan Program (mockup pjBatal): dampak ke quotation, SO, invoice, refund, Surat Tugas, pengajuan biaya. */
class ProgramCancelTest extends MasterTestCase
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
        foreach (['penjualan_program'] as $code) {
            DB::table('ms_user_akses')->where('id_menu', DB::table('ms_menu')->where('kode', $code)->value('id'))->delete();
        }
    }

    private function program(): object
    {
        return Sales::program($this->ids5['p']);
    }

    private function cancel(string $reason = 'Kuota tidak terpenuhi')
    {
        return $this->post('/penjualan-program/'.$this->ids5['p'].'/batalkan', ['alasan' => $reason]);
    }

    private function pengajuan(string $status = 'menunggu'): int
    {
        return DB::table('pengajuan_biaya')->insertGetId(['nomor' => 'PB-'.uniqid(), 'id_penjualan' => $this->ids5['p'], 'tipe_biaya' => 'fixed', 'jenis' => 'kasbon', 'bank' => 'BCA', 'no_rekening' => '1', 'nama_rekening' => 'A',
            'tanggal' => '2026-11-14', 'deskripsi' => 'Uji', 'total' => 1000, 'status' => $status, 'request_by' => 1]);
    }

    public function test_migrasi_menambah_status_batal_dan_refund_tanpa_konfirmasi_ulang(): void
    {
        $this->assertTrue(Schema::hasColumn('penjualan_program', 'alasan_batal'));
        DB::table('sales_order')->where('id', $this->ids5['so'])->update(['status' => 'batal']);
        DB::table('surat_kerja_trainer')->update(['status' => 'batal']);
        $this->assertSame('batal', DB::table('sales_order')->where('id', $this->ids5['so'])->value('status'));
        (require base_path('database/migrations/2026_11_19_090000_penjualan_batal_dampak.php'))->up(); // idempoten
        $this->assertSame('batal', DB::table('surat_kerja_trainer')->value('status'));
    }

    public function test_alasan_wajib_dan_hak_manage_dibutuhkan(): void
    {
        $this->post('/penjualan-program/'.$this->ids5['p'].'/batalkan', [])->assertSessionHasErrors('alasan');
        $this->assertSame('final', $this->program()->status);

        $user = $this->account('tanpa_manage');
        DB::table('ms_user_akses')->insert(['id_user' => $user->id_user, 'id_menu' => DB::table('ms_menu')->where('kode', 'penjualan_program')->value('id'), 'aksi' => 'hapus']);
        $this->login($user);
        $this->cancel()->assertForbidden();
        $this->assertSame('final', $this->program()->status);
    }

    public function test_final_dengan_invoice_belum_dibayar_membatalkan_semua_turunan(): void
    {
        $invoice = $this->invoice5(false);
        $pengajuan = $this->pengajuan();
        $this->cancel('Kuota tidak terpenuhi')->assertSessionHasNoErrors();

        $p = $this->program();
        $this->assertSame('batal', $p->status);
        $this->assertSame('Kuota tidak terpenuhi', $p->alasan_batal);
        $this->assertNotNull($p->closed_date);
        $this->assertSame('batal', DB::table('quotation')->where('id', $this->ids5['q'])->value('status'));
        $this->assertSame(['batal'], DB::table('quotation_peserta')->where('id_quotation', $this->ids5['q'])->pluck('status')->unique()->all());
        $this->assertSame('batal', DB::table('sales_order')->where('id', $this->ids5['so'])->value('status'));
        $this->assertSame('batal', DB::table('invoice')->where('id', $invoice->id)->value('status'));
        $this->assertSame('batal', DB::table('surat_kerja_trainer')->value('status'));
        $this->assertSame('batal', DB::table('pengajuan_biaya')->where('id', $pengajuan)->value('status'));
        $this->assertSame(0, DB::table('sales_order_refund')->count());
        $this->assertDatabaseHas('logs', ['action' => 'Batal', 'ref_no' => $p->nomor]);
    }

    public function test_invoice_sudah_dibayar_tetap_dan_membuat_refund_menunggu_approval(): void
    {
        $invoice = $this->invoice5(true);
        $this->cancel('Program dibatalkan klien')->assertSessionHasNoErrors();

        $this->assertSame('invoiced', DB::table('invoice')->where('id', $invoice->id)->value('status'));
        $refund = DB::table('sales_order_refund')->first();
        $this->assertNotNull($refund);
        $this->assertSame('menunggu', $refund->status);
        $this->assertSame('batal', $refund->sebab);
        $this->assertNull($refund->id_konfirmasi_ulang);
        $this->assertSame($this->ids5['so'], (int) $refund->id_sales_order);
        $this->assertStringContainsString('Program dibatalkan klien', $refund->alasan);
        $this->assertSame('batal', DB::table('sales_order')->where('id', $this->ids5['so'])->value('status'));
    }

    public function test_batal_dua_kali_dan_program_closed_ditolak(): void
    {
        $this->cancel()->assertSessionHasNoErrors();
        $this->cancel('lagi')->assertSessionHasErrors('program');
        $this->assertSame(1, DB::table('logs')->where('action', 'Batal')->where('ref_no', $this->program()->nomor)->count());

        DB::table('penjualan_program')->where('id', $this->ids5['p'])->update(['status' => 'final', 'closed_date' => now()]);
        $this->cancel()->assertSessionHasErrors('program');
        $this->assertFalse(ProgramCancel::allowed($this->program()));
    }

    public function test_draft_tanpa_turunan_dibatalkan_dan_dampak_dapat_dibaca(): void
    {
        $impact = $this->getJson('/penjualan-program/'.$this->ids5['p'].'/dampak-batal')->assertOk()->json();
        $this->assertTrue($impact['allowed']);
        $this->assertSame(1, $impact['quotation']);
        $this->assertSame(1, $impact['sales_order']);
        $this->assertSame(1, $impact['surat_tugas']);

        DB::table('penjualan_program')->where('id', $this->ids5['p'])->update(['status' => 'draft']);
        foreach (['surat_kerja_trainer', 'sales_order', 'quotation_peserta', 'quotation'] as $t) {
            DB::table($t)->delete();
        }
        $this->cancel()->assertSessionHasNoErrors();
        $this->assertSame('batal', $this->program()->status);
    }

    public function test_tab_so_batal_dan_halaman_so_aktif_memisahkan_so_batal(): void
    {
        $this->cancel()->assertSessionHasNoErrors();
        $this->getJson('/sales-order/data?tab=so')->assertOk()->assertJsonPath('recordsFiltered', 0)->assertJsonPath('meta.counts.batal', 1);
        $this->getJson('/sales-order/data?tab=batal')->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.sebab', 'Program batal');
        $this->assertSame(0, Invoice::uninvoiced()->count());
    }
}

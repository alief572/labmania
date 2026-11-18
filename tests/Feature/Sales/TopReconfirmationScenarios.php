<?php

namespace Tests\Feature\Sales;

use App\Support\Quotation;
use Illuminate\Support\Facades\DB;

/** Quotation TOP dan konfirmasi ulang: batal/refund per termin belum dibangun ([JMR-7]), jadi ditolak jelas. Memakai seed Phase5. */
trait TopReconfirmationScenarios
{
    public function test_top_quotation_blocks_batal_and_refund_but_allows_pindah_jadwal(): void
    {
        DB::table('quotation')->where('id', $this->ids5['q'])->update(['cara_bayar' => 'top']);
        foreach ([[1, 50], [2, 50]] as [$urutan, $persen]) {
            DB::table('quotation_termin')->insert(['id_quotation' => $this->ids5['q'], 'urutan' => $urutan, 'persen' => $persen, 'keterangan' => 'Termin '.$urutan]);
        }
        $this->runReschedule5();
        $q = Quotation::row($this->ids5['q']);
        [$a, $b] = $this->ids5['people'];
        $url = '/konfirmasi/'.$q->link_token.'/ulang';

        $this->post($url, ['revisi' => 1, 'id_peserta' => $a, 'keputusan' => 'batal', 'setuju' => 1, 'catatan' => 'Tidak bisa ikut'])->assertSessionHasErrors('keputusan');
        $this->post('/quotation/peserta/'.$a.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'batal', 'alasan' => 'Tidak bisa ikut'])->assertSessionHasErrors('keputusan');
        $this->assertNotSame('keluar', DB::table('quotation_peserta')->where('id', $a)->value('status'), 'Peserta TOP tidak boleh keluar lewat jalur yang belum mengalokasikan refund per termin.');

        $this->post('/quotation/peserta/'.$b.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'pindah_jadwal'])->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('quotation_peserta_konfirmasi_ulang')->where('id_quotation_peserta', $b)->value('diproses_date'));
    }
}

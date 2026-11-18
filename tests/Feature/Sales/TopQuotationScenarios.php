<?php

namespace Tests\Feature\Sales;

use Illuminate\Support\Facades\DB;

/** Validasi termin TOP pada form quotation (QTN-FR-44). Memakai helper quotePayload() milik Phase3Scenarios. */
trait TopQuotationScenarios
{
    public function test_quotation_form_validates_termin_rules(): void
    {
        $this->login($this->admin());
        $p = $this->program();
        $base = $this->quotePayload($p, ['kuota_peserta' => 2]);
        $top = fn (array $extra) => $this->post('/quotation', $extra + $base);
        $before = DB::table('quotation')->count();

        // Satu termin ditolak: aturan minimal 2 termin (kunci 'termin'), dan 100% ditolak per baris (kunci 'termin.0.persen').
        $top(['cara_bayar' => 'top', 'termin' => [['persen' => 99.99, 'keterangan' => 'Lunas']]])->assertSessionHasErrors('termin');
        $top(['cara_bayar' => 'top', 'termin' => [['persen' => 100, 'keterangan' => 'Lunas']]])->assertSessionHasErrors('termin.0.persen');
        $top(['cara_bayar' => 'top', 'termin' => [['persen' => 40, 'keterangan' => 'DP'], ['persen' => 40, 'keterangan' => 'Lunas']]])->assertSessionHasErrors('termin');
        $top(['cara_bayar' => 'top', 'termin' => [['persen' => 50, 'keterangan' => ''], ['persen' => 50, 'keterangan' => 'Lunas']]])->assertSessionHasErrors('termin.0.keterangan');
        $this->assertSame($before, DB::table('quotation')->count(), 'Input TOP yang salah tidak boleh menyimpan quotation.');

        // Total 100,03% masih dalam toleransi pembulatan 0,05% (QTN-FR-44).
        $top(['cara_bayar' => 'top', 'termin' => [['persen' => 50, 'keterangan' => 'DP'], ['persen' => 50.03, 'keterangan' => 'Lunas']]])->assertSessionHasNoErrors();
        $new = DB::table('quotation')->orderByDesc('id')->first();
        $this->assertSame('top', $new->cara_bayar);
        $this->assertSame(2, DB::table('quotation_termin')->where('id_quotation', $new->id)->count());

        // Tanpa cara_bayar = Full payment, dan quotation tanpa termin tidak menyimpan baris termin.
        $top([])->assertSessionHasNoErrors();
        $full = DB::table('quotation')->orderByDesc('id')->first();
        $this->assertSame('full', $full->cara_bayar);
        $this->assertSame(0, DB::table('quotation_termin')->where('id_quotation', $full->id)->count());
    }
}

<?php

namespace Tests\Mysql;

use App\Models\User;
use App\Support\Invoice;
use App\Support\Sales;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Sales\Phase5Scenarios;

/**
 * Pembatalan Penjualan Program pada MySQL: enum SO dan Surat Tugas (kolom generated program_berlaku), refund tanpa konfirmasi ulang
 * dan transaksi atomik. Hanya database *_test (guard di MysqlTestCase).
 */
class ProgramCancelMysqlTest extends MysqlTestCase
{
    use Phase5Scenarios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfiguration();
        $this->seedPhase5();
    }

    protected function seedAttributes(): array
    {
        return $this->attributes();
    }

    protected function login(User $user): static
    {
        return $this->loginAs($user);
    }

    protected function admin(): User
    {
        return $this->superadmin();
    }

    protected function account(string $name): User
    {
        return User::forceCreate(['username' => $name, 'nm_lengkap' => $name, 'password' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
    }

    public function test_migrasi_idempoten_dan_kolom_berubah_pada_mysql(): void
    {
        (require base_path('database/migrations/2026_11_19_090000_penjualan_batal_dampak.php'))->up();
        $type = fn (string $table, string $column) => DB::selectOne('SELECT COLUMN_TYPE t, IS_NULLABLE n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);
        $this->assertSame("enum('open','siap','batal')", $type('sales_order', 'status')->t);
        $this->assertSame("enum('berlaku','diganti','batal')", $type('surat_kerja_trainer', 'status')->t);
        $this->assertSame('YES', $type('sales_order_refund', 'id_konfirmasi_ulang')->n);
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) c FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_refund' AND COLUMN_NAME = 'id_konfirmasi_ulang' AND REFERENCED_TABLE_NAME IS NOT NULL")->c, 'FK ke konfirmasi ulang tetap ada');
    }

    public function test_so_aktif_mengizinkan_so_batal_dan_so_rev_tetapi_menolak_dua_so_aktif(): void
    {
        $so = DB::table('sales_order')->where('id', $this->ids5['so'])->first();
        $row = ['id_quotation' => $so->id_quotation, 'id_penjualan' => $so->id_penjualan, 'tanggal' => '2026-11-14', 'created_by' => 1, 'created_date' => now()];
        DB::table('sales_order')->where('id', $so->id)->update(['status' => 'batal']);
        $this->assertNull(DB::table('sales_order')->where('id', $so->id)->value('so_aktif'), 'SO batal melepas slot aktif');

        DB::table('sales_order')->insert($row + ['nomor' => $so->nomor, 'revisi' => 1, 'status' => 'open']); // SO -rev: nomor dasar sama, revisi berbeda
        try {
            DB::table('sales_order')->insert($row + ['nomor' => $so->nomor.'-X', 'revisi' => 2, 'status' => 'open']);
            $this->fail('Dua SO aktif untuk satu quotation diterima.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame(1062, $e->errorInfo[1]);
        }
        try {
            DB::table('sales_order')->insert($row + ['nomor' => $so->nomor, 'revisi' => 1, 'status' => 'batal']);
            $this->fail('Nomor dan revisi yang sama diterima dua kali.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame(1062, $e->errorInfo[1]);
        }
    }

    public function test_batal_final_dengan_invoice_dibayar_membuat_refund_dan_membebaskan_surat_tugas(): void
    {
        $invoice = Invoice::row(Invoice::save($this->ids5['so'], ['tanggal' => '2026-11-14', 'no_faktur' => 'F', 'coa_penjualan' => DB::table('coa_master')->value('id')]));
        Invoice::record($invoice->id, ['tanggal' => '2026-11-14', 'keterangan' => 'Lunas']);
        $this->post('/penjualan-program/'.$this->ids5['p'].'/batalkan', ['alasan' => 'Klien membatalkan'])->assertSessionHasNoErrors();

        $this->assertSame('batal', Sales::program($this->ids5['p'])->status);
        $this->assertSame('batal', DB::table('sales_order')->where('id', $this->ids5['so'])->value('status'));
        $this->assertSame('batal', DB::table('surat_kerja_trainer')->value('status'));
        $this->assertNull(DB::table('surat_kerja_trainer')->value('program_berlaku'), 'Surat Batal tidak lagi memegang slot berlaku');
        $refund = DB::table('sales_order_refund')->first();
        $this->assertSame(['menunggu', 'batal', null], [$refund->status, $refund->sebab, $refund->id_konfirmasi_ulang]);
        $this->assertSame('invoiced', DB::table('invoice')->where('id', $invoice->id)->value('status'));
    }

    public function test_kegagalan_di_tengah_pembatalan_membatalkan_seluruh_transaksi(): void
    {
        $this->post('/penjualan-program/'.$this->ids5['p'].'/batalkan', ['alasan' => ''])->assertSessionHasErrors('alasan');
        $fail = true;
        DB::connection()->beforeExecuting(function ($sql) use (&$fail) {
            if ($fail && str_contains($sql, 'update `surat_kerja_trainer` set')) {
                $fail = false;
                throw new \RuntimeException('Kegagalan tulis disimulasikan.');
            }
        });
        try {
            $this->withoutExceptionHandling()->post('/penjualan-program/'.$this->ids5['p'].'/batalkan', ['alasan' => 'uji atomik']);
            $this->fail('Kegagalan harus merambat.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('disimulasikan', $e->getMessage());
        }
        $this->assertSame('final', Sales::program($this->ids5['p'])->status, 'Program tidak berubah');
        $this->assertSame('deal', DB::table('quotation')->where('id', $this->ids5['q'])->value('status'));
        $this->assertSame('siap', DB::table('sales_order')->where('id', $this->ids5['so'])->value('status'));
    }
}

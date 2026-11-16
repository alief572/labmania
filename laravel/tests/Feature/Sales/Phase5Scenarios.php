<?php

namespace Tests\Feature\Sales;

use App\Support\Invoice;
use App\Support\Quotation;
use App\Support\QuotationFiles;
use App\Support\Reconfirmation;
use App\Support\Reschedule;
use App\Support\Sales;
use App\Support\SalesOrder;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait Phase5Scenarios
{
    private array $ids5;

    private string $root5;

    protected function seedPhase5(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 14)->startOfDay());
        $this->login($this->admin());
        foreach (['penjualan_program', 'quotation_program'] as $code) {
            if (! DB::table('ms_menu')->where('kode', $code)->exists()) {
                $menu = DB::table('ms_menu')->insertGetId(['kode' => $code, 'judul' => $code, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 40, 'aktif' => 1, 'sistem' => 0]);
                foreach (['lihat', 'ubah', 'tambah', 'approve'] as $action) {
                    DB::table('ms_menu_aksi')->insert(['id_menu' => $menu, 'aksi' => $action]);
                }
            }
        }
        $this->root5 = storage_path('framework/testing/phase5-'.Str::uuid());
        File::ensureDirectoryExists($this->root5);
        config(['labmania.document_root' => $this->root5, 'labmania.legacy_document_root' => null]);
        $a = $this->seedAttributes();
        $k = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-5', 'judul' => 'Program Lima', 'id_jenis_produk' => $a['ms_jenis_produk'], 'id_jenis_bidang' => $a['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $a['ms_jenis_sertifikasi'], 'id_varian_pelaksanaan' => $a['ms_varian_pelaksanaan'], 'aktif' => 1]);
        $m = DB::table('master_program')->insertGetId(['nomor' => 'MP-5', 'status' => 'final', 'durasi_hari' => 2, 'target_peserta' => 10, 'harga_jual_peserta' => 1000000]);
        DB::table('master_program_produk')->insert(['id_master_program' => $m, 'id_paket_produk' => $k, 'urutan' => 1]);
        $tr = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-5', 'nama' => 'Trainer Lima', 'aktif' => 1]);
        $as = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-5', 'nama' => 'Asisten Lima', 'aktif' => 1]);
        $p = DB::table('penjualan_program')->insertGetId(['nomor' => 'PJP-2611-001', 'jenis' => 'public', 'id_master_program' => $m, 'id_trainer' => $tr, 'id_asisten' => $as, 'tgl_mulai' => '2026-12-20', 'tgl_selesai' => '2026-12-21', 'durasi_hari' => 2, 'jam_pembelajaran' => 8, 'waktu' => '08.00', 'lokasi' => 'Bekasi', 'status' => 'final']);
        $q = DB::table('quotation')->insertGetId(['nomor' => 'LMI/PW/11/26-001', 'tanggal' => '2026-11-14', 'berlaku_sampai' => '2026-12-31', 'id_penjualan' => $p, 'id_sales' => $as, 'tujuan' => 'personal', 'kepada_nama' => 'Peserta', 'alamat' => 'Alamat Uji', 'status' => 'deal', 'kuota_peserta' => 2, 'skema_harga' => 'peserta', 'harga_peserta' => 1000000, 'diskon' => 50000, 'biaya_lain' => 10001, 'is_ppn' => 1, 'link_token' => str_repeat('a', 32)]);
        $people = [];
        foreach (['Satu', 'Dua'] as $n) {
            $people[] = DB::table('quotation_peserta')->insertGetId(['id_quotation' => $q, 'nama' => 'Peserta '.$n, 'status' => 'deal', 'sumber' => 'link']);
        }
        $so = DB::table('sales_order')->insertGetId(['nomor' => 'LMI/SO/11/26-001', 'id_quotation' => $q, 'id_penjualan' => $p, 'tanggal' => '2026-11-14', 'status' => 'siap']);
        DB::table('surat_kerja_trainer')->insert(['nomor' => 'SKT-2611-001', 'id_penjualan' => $p, 'id_trainer' => $tr, 'id_asisten' => $as, 'tanggal' => '2026-11-14', 'status' => 'berlaku', 'snapshot' => json_encode(SalesOrder::programSnapshot(Sales::program($p)))]);
        foreach (['1102-01-01', '2104-01-07', '4101-01-03', '4101-01-02', 'WAPU-UM', 'WAPU-HT'] as $i => $num) {
            DB::table('coa_master')->insert(['id' => 900 + $i, 'no_perkiraan' => $num, 'nama' => 'Akun Uji '.$num, 'level' => '5']);
        }
        $this->ids5 = compact('p', 'q', 'so', 'tr', 'as', 'people');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        if (isset($this->root5)) {
            File::deleteDirectory($this->root5);
        }
        parent::tearDown();
    }

    private function reschedule5(array $over = []): array
    {
        $p = Sales::program($this->ids5['p']);

        return $over + ['request_id' => (string) Str::uuid(), 'version' => Reschedule::version($p), 'tgl_mulai' => '2027-01-10', 'id_trainer' => $p->id_trainer, 'id_asisten' => $p->id_asisten, 'alasan' => 'Customer mengubah jadwal', 'evidence' => [UploadedFile::fake()->image('evidence.png')]];
    }

    private function runReschedule5(): void
    {
        $this->post('/penjualan-program/'.$this->ids5['p'].'/reschedule', $this->reschedule5())->assertRedirect()->assertSessionHasNoErrors();
    }

    private function invoice5(bool $paid = false): object
    {
        $id = Invoice::save($this->ids5['so'], ['tanggal' => '2026-11-14', 'no_faktur' => 'FAKTUR5', 'coa_penjualan' => 902]);
        if ($paid) {
            Invoice::record($id, ['tanggal' => '2026-11-14', 'keterangan' => 'Lunas']);
        }

        return Invoice::row($id);
    }

    public function test_reschedule_chain_replay_snapshot_and_stale_request(): void
    {
        $invoice = $this->invoice5(true);
        $in = $this->reschedule5();
        $url = '/penjualan-program/'.$this->ids5['p'].'/reschedule';
        $this->post($url, $in)->assertSessionHasNoErrors();
        $this->assertSame(1, Sales::program($this->ids5['p'])->revisi);
        $this->assertSame('PJP-2611-001', Sales::program($this->ids5['p'])->nomor);
        $this->assertSame(1, Quotation::row($this->ids5['q'])->revisi);
        $this->assertSame(2, DB::table('quotation_peserta_konfirmasi_ulang')->count());
        $this->assertSame(1, DB::table('surat_kerja_trainer')->where('status', 'berlaku')->count());
        $this->assertSame(1, DB::table('surat_kerja_trainer')->where('status', 'diganti')->count());
        $this->assertSame($invoice->snapshot, Invoice::row($invoice->id)->snapshot);
        $this->get('/konfirmasi/'.str_repeat('a', 32))->assertStatus(410)->assertSee('Link ini sudah diganti', false);
        $this->get('/quotation/'.$this->ids5['q'].'/revisi/0/quotation')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->post($url, $in)->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('penjualan_program_reschedule')->count());
        $in['request_id'] = (string) Str::uuid();
        $this->post($url, $in)->assertSessionHasErrors('reschedule');
        $this->post($url, $this->reschedule5(['tgl_mulai' => '2027-02-10']))->assertSessionHasNoErrors();
        $this->assertSame(2, Quotation::row($this->ids5['q'])->revisi);
        $this->assertSame(4, DB::table('quotation_peserta_konfirmasi_ulang')->count());
        DB::table('ms_trainer')->where('id', $this->ids5['tr'])->update(['nama' => 'Nama master berubah', 'aktif' => 0]);
        $this->get($url)->assertInertia(fn ($page) => $page->where('history.0.team.trainer_lama', 'Trainer Lima'));
    }

    public function test_public_answers_block_billing_cancel_unpaid_and_reinvoice_remaining(): void
    {
        $old = $this->invoice5();
        $this->runReschedule5();
        $q = Quotation::row($this->ids5['q']);
        $a = $this->ids5['people'][0];
        $b = $this->ids5['people'][1];
        $this->get('/konfirmasi/'.$q->link_token.'/ulang')->assertOk();
        $this->post('/konfirmasi/'.$q->link_token.'/ulang', ['revisi' => 1, 'id_peserta' => $a, 'keputusan' => 'batal', 'setuju' => 1, 'catatan' => 'Tidak bisa ikut'])->assertSessionHasNoErrors();
        $this->assertTrue(Reconfirmation::pending($q));
        $this->post('/quotation/peserta/'.$a.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'batal', 'alasan' => 'Tidak bisa ikut'])->assertSessionHasNoErrors();
        $this->assertSame('keluar', DB::table('quotation_peserta')->where('id', $a)->value('status'));
        $this->assertSame('batal', Invoice::row($old->id)->status);
        $this->get('/invoicing/create?id_sales_order='.$this->ids5['so'])->assertSessionHasErrors('invoice');
        $this->post('/quotation/peserta/'.$b.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'pindah_jadwal'])->assertSessionHasNoErrors();
        $new = $this->invoice5();
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame(1, DB::table('invoice_peserta')->where('id_invoice', $new->id)->count());
        $this->assertSame(3, DB::table('invoice_jurnal')->where('id_invoice', $old->id)->count());
        $this->assertStringContainsString('-rev1', $new->snapshot);
        $this->post('/invoicing/'.$old->id.'/pembayaran', ['tanggal' => '2026-11-14'])->assertSessionHasErrors('invoice');
    }

    public function test_reschedule_boundaries_trainer_only_and_validation_cleanup(): void
    {
        $url = '/penjualan-program/'.$this->ids5['p'].'/reschedule';
        $this->post($url, $this->reschedule5(['tgl_mulai' => '2026-11-20']))->assertSessionHasErrors('tgl_mulai');
        $this->post($url, $this->reschedule5(['evidence' => [UploadedFile::fake()->create('bad.exe', 1)]]))->assertSessionHasErrors('evidence.0');
        $this->post($url, $this->reschedule5(['tgl_mulai' => '2026-12-20']))->assertSessionHasErrors('reschedule');
        $this->assertCount(0, File::allFiles($this->root5));
        $tr = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-NEW', 'nama' => 'Trainer Baru', 'aktif' => 1]);
        $this->travelTo(now()->setDate(2026, 12, 13));
        $this->post($url, $this->reschedule5(['tgl_mulai' => '2026-12-20', 'id_trainer' => $tr]))->assertSessionHasNoErrors();
        $this->assertSame(0, Quotation::row($this->ids5['q'])->revisi);
        $this->assertSame(str_repeat('a', 32), Quotation::row($this->ids5['q'])->link_token);
        $this->assertSame(0, DB::table('quotation_peserta_konfirmasi_ulang')->count());
        $this->assertSame(1, DB::table('surat_kerja_trainer')->where('status', 'diganti')->count());
        $this->travelTo(now()->addDay());
        $this->post($url, $this->reschedule5())->assertSessionHasErrors('reschedule');
        DB::table('penjualan_program')->where('id', $this->ids5['p'])->update(['closed_date' => now()]);
        $this->post($url, $this->reschedule5())->assertSessionHasErrors();
    }

    public function test_public_follow_requires_letter_archives_previous_and_deadline_get_is_read_only(): void
    {
        $old = QuotationFiles::store(UploadedFile::fake()->image('lama.png'));
        $person = $this->ids5['people'][0];
        DB::table('quotation_peserta')->where('id', $person)->update(['surat_konfirmasi' => $old['path'], 'surat_konfirmasi_nama' => $old['name']]);
        $this->runReschedule5();
        $q = Quotation::row($this->ids5['q']);
        $url = '/konfirmasi/'.$q->link_token.'/ulang';
        $in = ['revisi' => 1, 'id_peserta' => $person, 'keputusan' => 'pindah_jadwal', 'setuju' => 1];
        $this->post($url, $in)->assertSessionHasErrors('surat');
        $in['surat'] = UploadedFile::fake()->image('baru.png');
        $this->post($url, $in)->assertSessionHasNoErrors();
        $this->assertSame($old['path'], DB::table('quotation_peserta_surat_riwayat')->value('surat_konfirmasi'));
        $this->assertNotNull(DB::table('quotation_peserta_konfirmasi_ulang')->where('id_quotation_peserta', $person)->value('diproses_date'));
        $this->post($url, $in)->assertSessionHasErrors('konfirmasi');
        $this->travelTo(Carbon::parse('2027-01-09 00:00:00', 'Asia/Jakarta'));
        $before = DB::table('quotation_peserta')->orderBy('id')->get()->toJson();
        $this->get($url)->assertOk();
        $this->assertSame($before, DB::table('quotation_peserta')->orderBy('id')->get()->toJson());
        $this->post($url, array_replace($in, ['id_peserta' => $this->ids5['people'][1]]))->assertSessionHasErrors('konfirmasi');
        $this->post('/quotation/peserta/'.$this->ids5['people'][1].'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'batal', 'alasan' => 'Diam sampai batas waktu'])->assertSessionHasNoErrors();
    }

    private function b2b5(): array
    {
        DB::table('master_customer')->insert(['id_customer' => 'C5', 'name_customer' => 'Company Lima', 'activation' => 'aktif', 'perlakuan_ppn' => 'PKP']);
        DB::table('penjualan_program')->where('id', $this->ids5['p'])->update(['jenis' => 'b2b', 'id_customer' => 'C5']);
        DB::table('quotation')->where('id', $this->ids5['q'])->update(['tujuan' => 'company', 'id_customer' => 'C5', 'skema_harga' => 'allin', 'harga_allin' => 10000000, 'kuota_peserta' => 11]);
        for ($i = 3; $i <= 11; $i++) {
            DB::table('quotation_peserta')->insert(['id_quotation' => $this->ids5['q'], 'nama' => 'Peserta '.$i, 'status' => 'deal', 'sumber' => 'company']);
        }
        DB::table('quotation_konfirmasi_company')->insert(['id_quotation' => $this->ids5['q'], 'revisi' => 0, 'nama_perusahaan' => 'Company Lima', 'alamat_perusahaan' => 'Bekasi', 'pic_nama' => 'PIC', 'pic_jabatan' => 'HR', 'pic_hp' => '081234567890', 'pic_email' => 'pic@example.test', 'jumlah_peserta' => 11, 'surat_konfirmasi' => 'uploads/konfirmasi_kehadiran/old.png', 'surat_konfirmasi_nama' => 'old.png', 'tgl_kirim' => now()]);

        return DB::table('quotation_peserta')->orderBy('id')->pluck('id')->all();
    }

    public function test_b2b_existing_cohort_minimum_and_company_refund(): void
    {
        $ids = $this->b2b5();
        $invoice = $this->invoice5(true);
        $this->runReschedule5();
        $q = Quotation::row($this->ids5['q']);
        $url = '/konfirmasi/'.$q->link_token.'/ulang';
        $in = ['revisi' => 1, 'keputusan' => 'pindah_jadwal', 'setuju' => 1, 'surat' => UploadedFile::fake()->image('company.png')];
        $this->post($url, $in + ['peserta' => array_slice($ids, 0, 9)])->assertSessionHasErrors('peserta');
        $this->post($url, $in + ['peserta' => [...array_slice($ids, 0, 10), 999999]])->assertSessionHasErrors('peserta');
        $this->post($url, $in + ['peserta' => array_slice($ids, 0, 10)])->assertSessionHasNoErrors();
        $this->assertSame(10, DB::table('quotation_peserta_konfirmasi_ulang')->where('keputusan', 'pindah_jadwal')->count());
        $this->post('/quotation/peserta/'.$ids[10].'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'tidak', 'alasan' => 'Dicoret PIC'])->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('sales_order_refund')->count());
        $this->post('/quotation/peserta/'.$ids[0].'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Company tidak ikut'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('sales_order_refund')->count());
        $this->assertSame('company', DB::table('sales_order_refund')->value('kunci_penerima'));
        $this->assertSame((string) $invoice->piutang, (string) DB::table('sales_order_refund')->value('nominal_tagihan'));
    }

    public function test_separate_refund_permissions_and_atomic_failure(): void
    {
        $admin = auth()->user();
        $this->invoice5(true);
        $this->runReschedule5();
        $person = $this->ids5['people'][0];
        $this->post('/quotation/peserta/'.$person.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Tidak ikut'])->assertSessionHasNoErrors();
        $rf = DB::table('sales_order_refund')->first();
        $staff = $this->account('finance5');
        foreach (['sales_order' => ['lihat', 'ubah'], 'sales_order_refund' => ['lihat']] as $code => $actions) {
            foreach ($actions as $aksi) {
                DB::table('ms_user_akses')->insert(['id_user' => $staff->id_user, 'id_menu' => DB::table('ms_menu')->where('kode', $code)->value('id'), 'aksi' => $aksi]);
            }
        }
        $this->login($staff);
        $this->get('/sales-order/refund/'.$rf->id)->assertOk();
        $in = ['nominal_refund' => '1', 'bank' => 'Bank', 'no_rekening' => '123456', 'atas_nama' => 'Nama', 'catatan' => 'Potongan', 'bukti' => UploadedFile::fake()->image('bukti.png')];
        $this->post('/sales-order/refund/'.$rf->id, $in)->assertForbidden();
        $this->post('/quotation/peserta/'.$person.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Tidak ikut'])->assertForbidden();
        $this->assertSame('menunggu', DB::table('sales_order_refund')->value('status'));
        $this->assertSame(0, DB::table('ms_user_akses')->where('id_user', $staff->id_user)->where('aksi', 'ubah')->where('id_menu', DB::table('ms_menu')->where('kode', 'sales_order_refund')->value('id'))->count());
        $this->login($admin);
        $count = count(File::allFiles($this->root5));
        DB::table('penjualan_program')->where('id', $this->ids5['p'])->update(['closed_date' => now()]);
        $this->post('/sales-order/refund/'.$rf->id, $in)->assertSessionHasErrors();
        $this->assertSame($count, count(File::allFiles($this->root5)));
    }

    public function test_audit_failure_rolls_back_reschedule_and_discards_new_evidence(): void
    {
        $in = $this->reschedule5();
        $p = Sales::program($this->ids5['p']);
        $this->withoutExceptionHandling();
        $fail = true;
        DB::connection()->beforeExecuting(function ($sql) use (&$fail) {
            if ($fail && str_contains($sql, 'insert into') && str_contains($sql, 'logs')) {
                $fail = false;
                throw new \RuntimeException('Audit unavailable');
            }
        });
        try {
            $this->post('/penjualan-program/'.$p->id.'/reschedule', $in);
            $this->fail('Audit failure must roll back entire transaction.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit unavailable', $e->getMessage());
        }
        $this->assertEquals($p, Sales::program($p->id));
        foreach (['penjualan_program_reschedule', 'quotation_revisi', 'quotation_peserta_konfirmasi_ulang'] as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
        $this->assertSame(str_repeat('a', 32), Quotation::row($this->ids5['q'])->link_token);
        $this->assertSame(1, DB::table('surat_kerja_trainer')->where('status', 'berlaku')->count());
        $this->assertCount(0, File::allFiles($this->root5));
    }

    public function test_wapu_paid_allocation_excludes_withheld_tax_and_refunds_sum_to_paid_funds(): void
    {
        DB::table('master_customer')->insert(['id_customer' => 'CW5', 'name_customer' => 'WAPU', 'activation' => 'aktif', 'perlakuan_ppn' => 'WAPU']);
        DB::table('quotation')->where('id', $this->ids5['q'])->update(['id_customer' => 'CW5', 'tujuan' => 'company']);
        config(['labmania.invoice.coa.uang_muka_wapu.id' => 904, 'labmania.invoice.coa.hutang_wapu.id' => 905]);
        $invoice = $this->invoice5(true);
        $this->runReschedule5();
        $this->assertSame('1910001', (string) $invoice->piutang);
        foreach ($this->ids5['people'] as $id) {
            $this->post('/quotation/peserta/'.$id.'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Tidak ikut'])->assertSessionHasNoErrors();
        }
        $total = BigDecimal::zero();
        foreach (DB::table('sales_order_refund')->pluck('nominal_tagihan') as $amount) {
            $total = $total->plus((string) $amount);
        }
        $this->assertSame((string) $invoice->piutang, (string) $total);
        $this->assertSame($invoice->snapshot, Invoice::row($invoice->id)->snapshot);
    }

    public function test_paid_refund_is_separate_capped_private_and_once(): void
    {
        $invoice = $this->invoice5(true);
        $this->runReschedule5();
        $person = $this->ids5['people'][0];
        $url = '/quotation/peserta/'.$person.'/konfirmasi-ulang';
        $this->post($url, ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Bentrok jadwal'])->assertSessionHasNoErrors();
        $rf = DB::table('sales_order_refund')->first();
        $this->assertSame('1059501', (string) $rf->nominal_tagihan);
        $this->getJson('/sales-order/refund/'.$rf->id.'?modal=1')->assertOk()->assertJsonPath('refund.id', $rf->id)->assertJsonMissingPath('refund.bukti_transfer');
        $this->assertSame('invoiced', Invoice::row($invoice->id)->status);
        $this->post($url, ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Bentrok jadwal'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('sales_order_refund')->count());
        $input = ['nominal_refund' => '1000000', 'bank' => 'Bank Uji', 'no_rekening' => '0012345678', 'atas_nama' => 'Penerima', 'catatan' => 'Potongan administrasi', 'bukti' => UploadedFile::fake()->image('transfer.png')];
        $this->post('/sales-order/refund/'.$rf->id, array_replace($input, ['nominal_refund' => '999999999']))->assertSessionHasErrors('nominal_refund');
        $this->post('/sales-order/refund/'.$rf->id, $input)->assertSessionHasNoErrors();
        $this->post('/sales-order/refund/'.$rf->id, $input)->assertSessionHasNoErrors();
        $this->assertSame('selesai', DB::table('sales_order_refund')->value('status'));
        $this->getJson('/sales-order/refund/data')->assertOk()->assertJsonPath('data.0.status', 'selesai')->assertJsonPath('data.0.nominal_refund', fn ($value) => (int) $value === 1000000)->assertJsonStructure(['data' => [['selesai_oleh']]])->assertJsonMissingPath('data.0.bukti_transfer')->assertJsonMissingPath('data.0.no_rekening');
        $this->get('/documents/refund/'.$rf->id)->assertOk();
        $this->assertSame(3, DB::table('invoice_jurnal')->count());
        $log = DB::table('logs')->where('action', 'Proses Refund')->value('data');
        $this->assertStringNotContainsString('0012345678', $log);
    }

    public function test_cancelled_invoice_history_and_revision_pdfs_preserve_original_values(): void
    {
        config(['labmania.invoice.print' => ['bank' => 'BANK UJI', 'rekening' => '1234567890', 'atas_nama' => 'PT LabMania Indonesia Jaya', 'penanda_tangan' => 'Finance Uji', 'jabatan' => 'Finance', 'kota' => 'Bekasi', 'kode_dokumen' => 'FR/LMI-FN-01/01']]);
        $old = $this->invoice5();
        $this->runReschedule5();
        $write = function (string $name, $response) {
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            if ($folder = getenv('VERIFY_PHASE5_PDF_DIR')) {
                $base = realpath(base_path('../.artifacts/fase5_reschedule_refund'));
                $target = realpath($folder);
                $this->assertNotFalse($target);
                $this->assertStringStartsWith($base.DIRECTORY_SEPARATOR, $target);
                $file = fopen($target.DIRECTORY_SEPARATOR.$name.'.pdf', 'xb');
                fwrite($file, $response->getContent());
                fclose($file);
            }
        };
        $write('quotation-lama', $this->get('/quotation/'.$this->ids5['q'].'/revisi/0/quotation'));
        $write('so-lama', $this->get('/quotation/'.$this->ids5['q'].'/revisi/0/so'));
        $write('so-rev1', $this->get('/sales-order/'.$this->ids5['so'].'/pdf'));
        $q = Quotation::row($this->ids5['q']);
        $write('konfirmasi-rev1', $this->post('/konfirmasi/'.$q->link_token.'/ulang/preview', ['revisi' => 1, 'id_peserta' => $this->ids5['people'][0]]));
        $this->post('/quotation/peserta/'.$this->ids5['people'][0].'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'batal', 'alasan' => 'Tidak ikut'])->assertSessionHasNoErrors();
        $this->assertSame($old->snapshot, Invoice::row($old->id)->snapshot);
        $write('invoice-batal', $this->get('/invoicing/'.$old->id.'/pdf'));
        $this->get('/invoicing?tab=invoiced&status=batal')->assertInertia(fn ($p) => $p->where('rows.total', 1)->where('rows.data.0.status', 'batal'));
        $this->get('/invoicing?tab=invoiced&status=invoiced')->assertInertia(fn ($p) => $p->where('rows.total', 0));
        $this->get('/invoicing/excel?tab=invoiced&status=batal')->assertOk()->assertSee('batal');
        DB::table('paket_produk')->update(['judul' => 'Judul master berubah']);
        $write('quotation-lama-master-berubah', $this->get('/quotation/'.$this->ids5['q'].'/revisi/0/quotation'));
        $sid = DB::table('surat_kerja_trainer')->where('status', 'berlaku')->value('id');
        $write('surat-tugas-rev1', $this->get('/surat-kerja-trainer/'.$sid.'/pdf'));
    }
}

<?php

namespace Tests\Feature\Sales;

use App\Support\Invoice;
use App\Support\Sales;
use App\Support\SalesOrder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

trait Phase4Scenarios
{
    private int $so;

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function seedPhase4(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 14)->startOfDay());
        $this->login($this->admin());
        $ids = $this->seedAttributes();
        $k = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-4', 'judul' => 'Program Fase Empat', 'id_jenis_produk' => $ids['ms_jenis_produk'], 'id_jenis_bidang' => $ids['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $ids['ms_jenis_sertifikasi'], 'id_varian_pelaksanaan' => $ids['ms_varian_pelaksanaan'], 'aktif' => 1]);
        $m = DB::table('master_program')->insertGetId(['nomor' => 'MP-4', 'status' => 'final', 'durasi_hari' => 2, 'target_peserta' => 10, 'harga_jual_peserta' => 1000000]);
        DB::table('master_program_produk')->insert(['id_master_program' => $m, 'id_paket_produk' => $k, 'urutan' => 1]);
        $tr = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-4', 'nama' => 'Trainer Fase Empat', 'aktif' => 1]);
        $a = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-4', 'nama' => 'Asisten Fase Empat', 'aktif' => 1]);
        $p = DB::table('penjualan_program')->insertGetId(['nomor' => 'PJ-4', 'jenis' => 'public', 'id_master_program' => $m, 'id_trainer' => $tr, 'id_asisten' => $a, 'tgl_mulai' => '2026-12-20', 'tgl_selesai' => '2026-12-21', 'waktu' => '08.00–16.00', 'lokasi' => 'Bekasi', 'status' => 'final']);
        $q = DB::table('quotation')->insertGetId(['nomor' => 'LMI/QTN/11/26-001', 'tanggal' => '2026-11-13', 'berlaku_sampai' => '2026-12-31', 'id_penjualan' => $p, 'id_sales' => $a, 'tujuan' => 'personal', 'kepada_nama' => 'Penerima', 'alamat' => 'Alamat Uji', 'status' => 'deal', 'kuota_peserta' => 2, 'skema_harga' => 'peserta', 'harga_peserta' => 1000000, 'diskon' => 50000, 'biaya_lain' => 10000, 'is_ppn' => 1]);
        foreach (['Peserta Satu', 'Peserta Dua'] as $name) {
            DB::table('quotation_peserta')->insert(['id_quotation' => $q, 'nama' => $name, 'status' => 'deal', 'sumber' => 'link']);
        }
        $this->so = DB::table('sales_order')->insertGetId(['nomor' => 'LMI/SO/11/26-001', 'id_quotation' => $q, 'id_penjualan' => $p, 'tanggal' => '2026-11-13', 'status' => 'open']);
        foreach (['1102-01-01', '2104-01-07', '4101-01-03', '4101-01-02', 'WAPU-UM', 'WAPU-HT'] as $i => $number) {
            DB::table('coa_master')->insert(['id' => 800 + $i, 'no_perkiraan' => $number, 'nama' => 'Akun uji '.$number, 'level' => '5']);
        }
    }

    private function invoiceInput(array $over = []): array
    {
        return $over + ['id_sales_order' => $this->so, 'tanggal' => '2026-11-14', 'no_faktur' => 'FAKTUR-UJI', 'coa_penjualan' => 802];
    }

    public function test_ready_invoice_server_values_replay_and_snapshot(): void
    {
        $this->post('/invoicing', $this->invoiceInput())->assertSessionHasErrors('invoice');
        $this->post('/sales-order/'.$this->so.'/siap-invoice')->assertSessionHasNoErrors();
        $this->post('/sales-order/'.$this->so.'/siap-invoice')->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('logs')->where('action', 'Siap Invoice')->count());
        $this->post('/invoicing', $this->invoiceInput(['penjualan' => 1, 'piutang' => 1]))->assertSessionHasNoErrors();
        $i = DB::table('invoice')->first();
        $this->assertSame('LMI/INV/11/26-001', $i->nomor);
        $this->assertSame('1900000', (string) $i->penjualan);
        $this->assertSame('1741667', (string) $i->dpp);
        $this->assertSame('209000', (string) $i->ppn);
        $this->assertSame('2119000', (string) $i->piutang);
        $this->assertSame(2, DB::table('invoice_peserta')->count());
        $this->assertSame(3, DB::table('invoice_jurnal')->count());
        $this->post('/invoicing', $this->invoiceInput())->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('invoice')->count());
        $this->post('/invoicing', $this->invoiceInput(['no_faktur' => 'BERBEDA']))->assertSessionHasErrors('invoice');
        DB::table('quotation')->update(['harga_peserta' => 999]);
        DB::table('paket_produk')->update(['judul' => 'Master berubah']);
        $this->assertSame('Program Fase Empat · Workshop', json_decode(Invoice::row($i->id)->snapshot, true)['judul']);
        $this->assertSame('Invoiced', SalesOrder::detail(SalesOrder::row($this->so))['so']['status_tampil']);
        $this->assertSame('siap', SalesOrder::row($this->so)->status);
    }

    public function test_payment_is_full_once_and_closed_program_locks_mutations(): void
    {
        SalesOrder::ready($this->so);
        $id = Invoice::save($this->so, $this->invoiceInput());
        $payment = ['tanggal' => '2026-11-14', 'keterangan' => 'Transfer penuh'];
        $this->post('/invoicing/'.$id.'/pembayaran', $payment)->assertSessionHasNoErrors();
        $this->post('/invoicing/'.$id.'/pembayaran', $payment)->assertSessionHasNoErrors();
        $this->assertSame(3, DB::table('invoice_jurnal')->count());
        $this->assertSame(1, DB::table('logs')->where('action', 'Catat Lunas')->count());
        $this->post('/invoicing/'.$id.'/pembayaran', ['tanggal' => '2026-11-13'])->assertSessionHasErrors('invoice');
        $this->post('/invoicing/'.$id.'/pembayaran', ['tanggal' => '2026-11-15'])->assertSessionHasErrors('tanggal');
        DB::table('penjualan_program')->update(['closed_date' => now()]);
        $this->post('/sales-order/'.$this->so.'/siap-invoice')->assertSessionHasErrors('program');
        $this->post('/invoicing/'.$id.'/pembayaran', $payment)->assertSessionHasErrors('program');
    }

    public function test_tax_matrix_requires_real_configuration_and_company_treatment(): void
    {
        DB::table('master_customer')->insert(['id_customer' => 'CO-004', 'name_customer' => 'Company Uji Empat', 'perlakuan_ppn' => null]);
        DB::table('quotation')->update(['id_customer' => 'CO-004', 'tujuan' => 'company']);
        SalesOrder::ready($this->so);
        $this->post('/invoicing', $this->invoiceInput())->assertSessionHasErrors('invoice');
        DB::table('master_customer')->update(['perlakuan_ppn' => 'WAPU']);
        $this->post('/invoicing', $this->invoiceInput())->assertSessionHasErrors('coa_penjualan');
        $this->assertSame(0, DB::table('invoice')->count());
        config(['labmania.invoice.coa.uang_muka_wapu.id' => 804, 'labmania.invoice.coa.hutang_wapu.id' => 805]);
        $id = Invoice::save($this->so, $this->invoiceInput());
        $i = Invoice::row($id);
        $this->assertSame('1910000', (string) $i->piutang);
        $this->assertSame(4, DB::table('invoice_jurnal')->count());
        $setor = ['tanggal' => '2026-11-14', 'nomor' => 'NTPN-UJI'];
        $this->post('/invoicing/'.$id.'/bukti-setor', $setor)->assertSessionHasNoErrors();
        $this->post('/invoicing/'.$id.'/bukti-setor', $setor)->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('invoice_bukti_setor')->count());
        $this->assertSame(6, DB::table('invoice_jurnal')->count());
        $reverse = DB::table('invoice_jurnal')->where('jenis', 'bukti_setor')->orderBy('urutan')->get();
        $this->assertSame(805, (int) $reverse[0]->coa);
        $this->assertSame(804, (int) $reverse[1]->coa);
        $this->assertEquals($reverse->sum('debit'), $reverse->sum('credit'));
        $this->post('/invoicing/'.$id.'/bukti-setor', ['tanggal' => '2026-11-13'])->assertSessionHasErrors('invoice');
        DB::table('invoice')->update(['status' => 'batal']);
        DB::table('master_customer')->update(['perlakuan_ppn' => 'KB']);
        $id = Invoice::save($this->so, $this->invoiceInput());
        $this->assertSame('1910000', (string) Invoice::row($id)->piutang);
        $this->assertSame(2, DB::table('invoice_jurnal')->where('id_invoice', $id)->count());
        $this->post('/invoicing/'.$id.'/bukti-setor', $setor)->assertSessionHasErrors('invoice');
        DB::table('invoice')->where('id', $id)->update(['status' => 'batal']);
        DB::table('quotation')->update(['is_ppn' => 0]);
        $id = Invoice::save($this->so, $this->invoiceInput(['no_faktur' => null]));
        $this->assertSame('0', (string) Invoice::row($id)->ppn);
        $this->assertNull(Invoice::row($id)->no_faktur);
    }

    public function test_historical_wapu_uses_snapshot_when_master_source_changes(): void
    {
        DB::table('master_customer')->insert(['id_customer' => 'CO-HIST', 'name_customer' => 'Company Historis', 'perlakuan_ppn' => 'WAPU']);
        DB::table('quotation')->update(['id_customer' => 'CO-HIST', 'tujuan' => 'company']);
        SalesOrder::ready($this->so);
        config(['labmania.invoice.coa.uang_muka_wapu.id' => 804, 'labmania.invoice.coa.hutang_wapu.id' => 805]);
        $id = Invoice::save($this->so, $this->invoiceInput());
        $before = DB::table('invoice_jurnal')->where('id_invoice', $id)->orderBy('urutan')->get()->toArray();
        // No GL connection exists here: historical reversal must not resolve its old IDs again.
        config(['labmania.coa_connection' => 'missing_gl_history_test']);
        $this->post('/invoicing/'.$id.'/bukti-setor', ['tanggal' => '2026-11-14', 'nomor' => 'NTPN-HIST'])->assertSessionHasNoErrors();
        $after = DB::table('invoice_jurnal')->where('id_invoice', $id)->where('jenis', 'invoice')->orderBy('urutan')->get()->toArray();
        $this->assertEquals($before, $after);
        $reverse = DB::table('invoice_jurnal')->where('jenis', 'bukti_setor')->orderBy('urutan')->get();
        $this->assertSame(805, (int) $reverse[0]->coa);
        $this->assertSame('WAPU-HT', $reverse[0]->no_coa);
        $this->assertSame(804, (int) $reverse[1]->coa);
        $this->assertSame('WAPU-UM', $reverse[1]->no_coa);
    }

    public function test_account_ambiguity_invalid_ids_and_missing_faktur_are_rejected(): void
    {
        SalesOrder::ready($this->so);
        $this->post('/invoicing', $this->invoiceInput(['no_faktur' => '']))->assertSessionHasErrors('no_faktur');
        $this->post('/invoicing', $this->invoiceInput(['coa_penjualan' => 999999]))->assertSessionHasErrors('coa_penjualan');
        DB::table('coa_master')->insert(['no_perkiraan' => '1102-01-01', 'nama' => 'Piutang cabang lain', 'kdcab' => 'UJI', 'level' => '5']);
        $this->post('/invoicing', $this->invoiceInput())->assertSessionHasErrors('coa_penjualan');
        config(['labmania.invoice.coa.piutang.id' => 800]);
        $this->post('/invoicing', $this->invoiceInput())->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('invoice')->count());
    }

    public function test_invoice_and_journal_are_atomic_when_audit_fails(): void
    {
        SalesOrder::ready($this->so);
        $this->app['auth']->logout();
        try {
            Invoice::save($this->so, $this->invoiceInput());
            $this->fail('Audit internal tanpa akun lolos.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('Audit internal', $e->getMessage());
        }
        $this->assertSame(0, DB::table('invoice')->count());
        $this->assertSame(0, DB::table('invoice_peserta')->count());
        $this->assertSame(0, DB::table('invoice_jurnal')->count());
    }

    public function test_unique_active_invoice_constraint_preserves_cancelled_history(): void
    {
        SalesOrder::ready($this->so);
        $id = Invoice::save($this->so, $this->invoiceInput());
        $copy = (array) Invoice::row($id);
        unset($copy['id'], $copy['so_aktif']);
        $copy['nomor'] = 'LMI/INV/11/26-999';
        try {
            DB::table('invoice')->insert($copy);
            $this->fail('Invoice aktif ganda lolos.');
        } catch (QueryException $e) {
            $this->assertContains($e->errorInfo[1], [19, 1062]);
        }
        DB::table('invoice')->where('id', $id)->update(['status' => 'batal']);
        $new = Invoice::save($this->so, $this->invoiceInput());
        $this->assertNotSame($id, $new);
        $this->assertSame(2, DB::table('invoice')->count());
        $this->assertSame(1, DB::table('invoice')->whereNotNull('so_aktif')->count());
    }

    public function test_permissions_authentication_and_parent_document_boundary(): void
    {
        $root = storage_path('framework/testing/phase4-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($root.'/uploads/quotation_dokumen');
        File::put($root.'/uploads/quotation_dokumen/uji.txt', 'Dokumen Uji');
        config(['labmania.document_root' => $root, 'labmania.legacy_document_root' => null]);
        $quote = SalesOrder::row($this->so)->id_quotation;
        $doc = DB::table('quotation_dokumen')->insertGetId(['id_quotation' => $quote, 'nama_file' => 'uji.txt', 'path_file' => 'uploads/quotation_dokumen/uji.txt', 'tipe_file' => 'pdf', 'ukuran_byte' => 11]);
        $other = DB::table('quotation_dokumen')->insertGetId(['id_quotation' => $quote + 999, 'nama_file' => 'uji.txt', 'path_file' => 'uploads/quotation_dokumen/uji.txt', 'tipe_file' => 'pdf', 'ukuran_byte' => 11]);
        try {
            $user = $this->account('finance4');
            $this->login($user);
            $this->get('/sales-order')->assertForbidden();
            $this->post('/sales-order/'.$this->so.'/siap-invoice')->assertForbidden();
            $this->get('/invoicing')->assertForbidden();
            foreach (['sales_order', 'invoicing'] as $menu) {
                DB::table('ms_user_akses')->insert(['id_user' => $user->id_user, 'id_menu' => DB::table('ms_menu')->where('kode', $menu)->value('id'), 'aksi' => 'lihat']);
            }
            $this->get('/sales-order')->assertOk();
            $this->get('/sales-order/'.$this->so)->assertOk();
            $this->get('/sales-order/'.$this->so.'/documents/quotation/'.$doc)->assertOk();
            $this->get('/documents/quotation/'.$doc)->assertForbidden();
            $this->get('/sales-order/'.$this->so.'/documents/quotation/'.$other)->assertNotFound();
            $this->get('/sales-order/'.$this->so.'/documents/trainer/'.$doc)->assertNotFound();
            DB::table('quotation_dokumen')->where('id', $doc)->update(['path_file' => 'uploads/quotation_dokumen/../../.env']);
            $this->get('/sales-order/'.$this->so.'/documents/quotation/'.$doc)->assertNotFound();
            $this->post('/invoicing', $this->invoiceInput())->assertForbidden();
            $this->post('/invoicing/1/pembayaran', ['tanggal' => '2026-11-14'])->assertForbidden();
            $this->post('/invoicing/1/bukti-setor', ['tanggal' => '2026-11-14'])->assertForbidden();
            $this->app['auth']->logout();
            $this->get('/sales-order')->assertRedirect('/login');
            $this->get('/invoicing')->assertRedirect('/login');
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_lists_filters_excel_and_configuration_block_official_print(): void
    {
        $this->get('/sales-order?jenis=public&tahun=2026&bulan=12&q=Program')->assertInertia(fn ($p) => $p->where('rows.total', 1)->where('rows.data.0.jumlah_so', 1));
        $this->get('/sales-order?tahun=2025')->assertInertia(fn ($p) => $p->where('rows.total', 0));
        $this->get('/sales-order/program/'.SalesOrder::row($this->so)->id_penjualan)->assertOk();
        SalesOrder::ready($this->so);
        $this->get('/invoicing')->assertInertia(fn ($p) => $p->where('rows.total', 1));
        $this->get('/invoicing/create?id_sales_order='.$this->so)->assertInertia(fn ($p) => $p->has('journal', 3)->where('configurationErrors', []));
        $id = Invoice::save($this->so, $this->invoiceInput());
        $this->get('/invoicing')->assertInertia(fn ($p) => $p->where('rows.total', 0));
        $this->get('/invoicing?tab=invoiced&jenis=public&tahun=2026&q=Fase')->assertInertia(fn ($p) => $p->where('rows.total', 1));
        $this->get('/invoicing/'.$id)->assertOk();
        $this->get('/invoicing/'.$id.'/pdf')->assertSessionHasErrors('pdf');
        $snapshot = json_decode(Invoice::row($id)->snapshot, true, 512, JSON_THROW_ON_ERROR);
        $snapshot['quotation']['kepada_nama'] = '=HYPERLINK("https://example.invalid")';
        DB::table('invoice')->where('id', $id)->update(['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);
        $excel = $this->get('/invoicing/excel?tab=invoiced&q=Fase')->assertOk();
        $this->assertStringContainsString('ss:Type="String"', $excel->getContent());
        $this->assertStringContainsString('=HYPERLINK', $excel->getContent());
        $this->assertStringContainsString('LMI/INV/11/26-001', $excel->getContent());
        $xml = simplexml_load_string($excel->getContent());
        $this->assertNotFalse($xml);
        $empty = $this->get('/invoicing/excel?tab=invoiced&jenis=b2b')->assertOk();
        $this->assertStringNotContainsString('LMI/INV/11/26-001', $empty->getContent());
    }

    public function test_b2b_allin_additional_deal_participants_and_month_numbering(): void
    {
        DB::table('master_customer')->insert(['id_customer' => 'CO-004', 'name_customer' => 'Company B2B', 'perlakuan_ppn' => 'PKP']);
        DB::table('penjualan_program')->update(['jenis' => 'b2b', 'id_customer' => 'CO-004']);
        DB::table('quotation')->update(['tujuan' => 'company', 'id_customer' => 'CO-004', 'skema_harga' => 'allin', 'harga_allin' => 20000000, 'diskon' => 500000, 'paket_maks_peserta' => 25, 'tambahan_per_peserta' => 300000]);
        for ($n = 3; $n <= 27; $n++) {
            DB::table('quotation_peserta')->insert(['id_quotation' => SalesOrder::row($this->so)->id_quotation, 'nama' => 'Peserta '.$n, 'status' => $n === 27 ? 'batal' : 'deal', 'sumber' => 'company']);
        }
        SalesOrder::ready($this->so);
        $id = Invoice::save($this->so, $this->invoiceInput(['tanggal' => '2026-12-01', 'coa_penjualan' => 803]));
        $i = Invoice::row($id);
        $this->assertSame('LMI/INV/12/26-001', $i->nomor);
        $this->assertSame('19800000', (string) $i->penjualan);
        $this->assertSame(26, DB::table('invoice_peserta')->count());
        $this->assertEquals($i->penjualan, DB::table('invoice_peserta')->sum('nominal'));
        $this->assertSame(1, DB::table('invoice')->count());
        $this->assertStringNotContainsString('Peserta 27', $i->snapshot);
    }

    public function test_assignment_snapshot_is_historical_and_missing_snapshot_requires_reconciliation(): void
    {
        $p = Sales::program(SalesOrder::row($this->so)->id_penjualan);
        $id = DB::table('surat_kerja_trainer')->insertGetId(['nomor' => 'SKT-2611-001', 'id_penjualan' => $p->id, 'id_trainer' => $p->id_trainer, 'id_asisten' => $p->id_asisten, 'tanggal' => '2026-11-13', 'status' => 'berlaku']);
        $this->get('/surat-kerja-trainer/'.$id)->assertSessionHasErrors('surat');
        DB::table('surat_kerja_trainer')->where('id', $id)->update(['snapshot' => json_encode(SalesOrder::programSnapshot($p), JSON_THROW_ON_ERROR)]);
        DB::table('ms_trainer')->update(['nama' => 'Master trainer berubah']);
        DB::table('penjualan_program')->update(['tgl_mulai' => '2027-01-01']);
        $data = SalesOrder::assignment($id);
        $this->assertSame('Trainer Fase Empat', $data['trainer']['nama']);
        $this->assertSame('2026-12-20', $data['program']['tgl_mulai']);
        $this->get('/surat-kerja-trainer/'.$id)->assertOk();
        $this->get('/sales-order?tab=surat')->assertInertia(fn ($p) => $p->where('rows.total', 1));
    }

    public function test_pdf_documents_render_from_historical_snapshots_and_real_print_gate(): void
    {
        config(['labmania.invoice.print' => ['bank' => 'BANK UJI SINTETIS', 'rekening' => '1234567890', 'atas_nama' => 'PT LabMania Indonesia Jaya', 'penanda_tangan' => 'Penanda Tangan Uji', 'jabatan' => 'Finance Uji', 'kota' => 'Bekasi', 'kode_dokumen' => 'FR/LMI-FN-01/01']]);
        config(['labmania.invoice.coa.uang_muka_wapu.id' => 804, 'labmania.invoice.coa.hutang_wapu.id' => 805]);
        $write = function (string $name, $response) {
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $folder = getenv('VERIFY_PHASE4_PDF_DIR');
            if ($folder) {
                $base = realpath(base_path('../.artifacts/fase4_sales_order_invoice'));
                $target = realpath($folder);
                $this->assertNotFalse($target);
                $this->assertStringStartsWith($base.DIRECTORY_SEPARATOR, $target);
                $file = fopen($target.DIRECTORY_SEPARATOR.$name.'.pdf', 'xb');
                fwrite($file, $response->getContent());
                fclose($file);
            }
        };
        $write('sales-order-public', $this->get('/sales-order/'.$this->so.'/pdf'));
        $p = Sales::program(SalesOrder::row($this->so)->id_penjualan);
        $sid = DB::table('surat_kerja_trainer')->insertGetId(['nomor' => 'SKT-2611-001', 'id_penjualan' => $p->id, 'id_trainer' => $p->id_trainer, 'id_asisten' => $p->id_asisten, 'tanggal' => '2026-11-13', 'status' => 'berlaku', 'snapshot' => json_encode(SalesOrder::programSnapshot($p), JSON_THROW_ON_ERROR)]);
        $write('surat-tugas', $this->get('/surat-kerja-trainer/'.$sid.'/pdf'));
        DB::table('surat_kerja_trainer')->where('id', $sid)->update(['status' => 'diganti']);
        $write('surat-tugas-diganti', $this->get('/surat-kerja-trainer/'.$sid.'/pdf'));
        SalesOrder::ready($this->so);
        $id = Invoice::save($this->so, $this->invoiceInput());
        DB::table('paket_produk')->update(['judul' => 'Master judul berubah setelah Invoice']);
        $write('invoice-pkp', $this->get('/invoicing/'.$id.'/pdf'));
        $this->assertSame('2026-11-13', SalesOrder::row($this->so)->tanggal);
        DB::table('master_customer')->insert(['id_customer' => 'CO-004', 'name_customer' => 'Company Uji Empat', 'perlakuan_ppn' => 'WAPU']);
        DB::table('quotation')->update(['id_customer' => 'CO-004', 'tujuan' => 'company']);
        foreach (['WAPU', 'KB', 'NON'] as $treatment) {
            DB::table('invoice')->where('id', $id)->update(['status' => 'batal']);
            DB::table('master_customer')->update(['perlakuan_ppn' => $treatment === 'NON' ? 'PKP' : $treatment]);
            DB::table('quotation')->update(['is_ppn' => $treatment === 'NON' ? 0 : 1]);
            $id = Invoice::save($this->so, $this->invoiceInput(['no_faktur' => $treatment === 'NON' ? null : 'FAKTUR-UJI']));
            $write('invoice-'.strtolower($treatment), $this->get('/invoicing/'.$id.'/pdf'));
        }
        DB::table('invoice')->where('id', $id)->update(['status' => 'batal']);
        DB::table('penjualan_program')->update(['jenis' => 'b2b', 'id_customer' => 'CO-004']);
        DB::table('quotation')->update(['is_ppn' => 1, 'skema_harga' => 'allin', 'harga_allin' => 20000000, 'diskon' => 500000, 'paket_maks_peserta' => 25, 'tambahan_per_peserta' => 300000]);
        DB::table('paket_produk')->update(['judul' => str_repeat('Program Pengujian Laboratorium ', 4)]);
        for ($n = 3; $n <= 26; $n++) {
            DB::table('quotation_peserta')->insert(['id_quotation' => SalesOrder::row($this->so)->id_quotation, 'nama' => 'Peserta Sintetis dengan nama cukup panjang nomor '.$n, 'status' => 'deal', 'sumber' => 'company']);
        }
        $id = Invoice::save($this->so, $this->invoiceInput(['coa_penjualan' => 803]));
        $write('invoice-b2b-panjang', $this->get('/invoicing/'.$id.'/pdf'));
        $write('sales-order-b2b-panjang', $this->get('/sales-order/'.$this->so.'/pdf'));
        $this->assertSame(9, DB::table('logs')->where('action', 'Cetak')->count());
    }

    public function test_menu_registration_is_idempotent_and_grants_no_new_role_permissions(): void
    {
        $before = DB::table('ms_group_akses')->count();
        (require base_path('database/migrations/2026_11_14_090000_sales_order_invoice.php'))->up();
        foreach (['sales_order', 'invoicing'] as $code) {
            $this->assertSame(1, DB::table('ms_menu')->where('kode', $code)->count());
        }
        $this->assertSame($before, DB::table('ms_group_akses')->count());
    }

    public function test_so_confirmation_letters_require_only_so_view_and_include_deal_only(): void
    {
        $root = storage_path('framework/testing/phase4-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($root.'/uploads/konfirmasi_kehadiran');
        $image = imagecreatetruecolor(100, 50);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagepng($image, $root.'/uploads/konfirmasi_kehadiran/uji.png');
        config(['labmania.document_root' => $root, 'labmania.legacy_document_root' => null]);
        try {
            DB::table('quotation_peserta')->where('nama', 'Peserta Satu')->update(['surat_konfirmasi' => 'uploads/konfirmasi_kehadiran/uji.png', 'surat_konfirmasi_nama' => 'uji.png']);
            DB::table('quotation_peserta')->where('nama', 'Peserta Dua')->update(['status' => 'batal']);
            $user = $this->account('sales4');
            $this->login($user);
            DB::table('ms_user_akses')->insert(['id_user' => $user->id_user, 'id_menu' => DB::table('ms_menu')->where('kode', 'sales_order')->value('id'), 'aksi' => 'lihat']);
            $response = $this->get('/sales-order/'.$this->so.'/letters')->assertOk();
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $other = DB::table('quotation_peserta')->where('nama', 'Peserta Dua')->value('id');
            $this->get('/sales-order/'.$this->so.'/documents/confirmation/'.$other)->assertNotFound();
        } finally {
            File::deleteDirectory($root);
        }
    }
}

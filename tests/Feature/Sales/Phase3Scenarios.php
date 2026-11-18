<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Support\Audit;
use App\Support\Quotation;
use App\Support\Sales;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

trait Phase3Scenarios
{
    private string $documents;

    private int $master;

    private int $trainer;

    private int $employee;

    private int $template;

    private int $unit;

    protected function seedPhase3(): void
    {
        $this->documents = storage_path('framework/testing/phase3-'.bin2hex(random_bytes(8)));
        config(['labmania.document_root' => $this->documents]);
        $this->travelTo(now()->setDate(2026, 11, 13)->startOfDay());
        $ids = $this->seedAttributes();
        $product = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-UJI', 'judul' => 'Program Uji Sintetis', 'id_jenis_produk' => $ids['ms_jenis_produk'], 'id_jenis_bidang' => $ids['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $ids['ms_jenis_sertifikasi'], 'id_varian_pelaksanaan' => $ids['ms_varian_pelaksanaan'], 'aktif' => 1]);
        $this->master = DB::table('master_program')->insertGetId(['nomor' => 'MP-UJI', 'status' => 'final', 'durasi_hari' => 2, 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => 1000000]);
        DB::table('master_program_produk')->insert(['id_master_program' => $this->master, 'id_paket_produk' => $product, 'urutan' => 1]);
        $this->unit = (int) (DB::table('ms_satuan')->where('category', 'rate_fixed')->where('code', 'event')->value('id')
            ?: DB::table('ms_satuan')->insertGetId(['category' => 'rate_fixed', 'code' => 'event', 'nama' => 'Event', 'deleted' => '0']));
        $this->trainer = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-UJI', 'nama' => 'Trainer Sintetis', 'aktif' => 1]);
        $this->employee = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-UJI', 'nama' => 'Sales Sintetis', 'aktif' => 1]);
        $this->template = DB::table('ms_template_konfirmasi')->insertGetId(['kode' => 'KF-UJI', 'nama' => 'Workshop', 'label_kegiatan' => 'Workshop', 'id_jenis_produk' => $ids['ms_jenis_produk'], 'isian' => '["nama","nik","email","wa"]', 'pernyataan' => 'Pernyataan uji sintetis.', 'aktif' => 1]);
        DB::table('master_customer')->insert(['id_customer' => 'CO-001', 'name_customer' => 'Company Sintetis', 'activation' => 'aktif', 'pic_name' => 'PIC Sintetis', 'address_office' => 'Alamat Uji', 'kota' => 'Kota Uji']);
    }

    protected function tearDown(): void
    {
        if (isset($this->documents) && str_starts_with($this->documents, storage_path('framework/testing/phase3-'))) {
            File::deleteDirectory($this->documents);
        }
        $this->travelBack();
        parent::tearDown();
    }

    private function salesPayload(array $over = []): array
    {
        return $over + ['jenis' => 'public', 'id_master_program' => $this->master, 'id_customer' => null, 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee,
            'tgl_mulai' => '2026-12-20', 'tgl_selesai' => '2026-12-21', 'waktu' => '08.00–16.00', 'lokasi' => 'Lokasi uji', 'jam_pembelajaran' => 16, 'target_peserta' => 10, 'harga_jual_peserta' => '1.000.000', 'target_profit' => 0,
            'biaya' => [['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Biaya sintetis', 'id_satuan' => $this->unit, 'harga' => 100000, 'is_manual' => true]]];
    }

    private function program(bool $b2b = false): int
    {
        $this->post('/penjualan-program', $this->salesPayload($b2b ? ['jenis' => 'b2b', 'id_customer' => 'CO-001'] : []))->assertSessionHasNoErrors();
        $id = (int) DB::table('penjualan_program')->orderByDesc('id')->value('id');
        $this->post('/penjualan-program/'.$id.'/finalkan')->assertSessionHasNoErrors();

        return $id;
    }

    private function quotePayload(int $p, array $over = []): array
    {
        return $over + ['id_penjualan' => $p, 'tanggal' => '2026-11-13', 'berlaku_sampai' => '2026-12-31', 'id_sales' => $this->employee, 'sumber_info' => 'Sales', 'tujuan' => 'personal', 'id_customer' => null, 'kepada_nama' => 'Penerima Uji', 'up_nama' => '', 'alamat' => 'Alamat Uji', 'kuota_peserta' => 2, 'harga_allin' => 0, 'diskon' => 50000, 'biaya_lain' => 10000, 'is_ppn' => 1, 'id_template_konfirmasi' => $this->template, 'konfirmasi_isian' => ['nama', 'nik', 'email', 'wa'], 'konfirmasi_pernyataan' => 'Pernyataan uji sintetis.', 'kolom_peserta_b2b' => [], 'keterangan' => [['kategori' => 'syarat', 'teks' => 'Syarat sintetis']]];
    }

    private function quote(int $p, array $over = []): object
    {
        $this->post('/quotation', $this->quotePayload($p, $over))->assertSessionHasNoErrors();

        return DB::table('quotation')->orderByDesc('id')->first();
    }

    private function letter(): UploadedFile
    {
        $image = imagecreatetruecolor(700, 160);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 5, 30, 60, 'Surat sintetis untuk pengujian', imagecolorallocate($image, 0, 0, 0));
        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        return UploadedFile::fake()->createWithContent('surat.png', $png);
    }

    private function participant(object $q, array $over = []): int
    {
        return DB::table('quotation_peserta')->insertGetId($over + ['id_quotation' => $q->id, 'nama' => 'Peserta Sintetis', 'status' => 'menunggu', 'sumber' => 'link', 'tgl_isi' => now()]);
    }

    public function test_phase3_has_authenticated_sales_and_quotation_routes(): void
    {
        $this->get('/penjualan-program')->assertRedirect('/login');
        $this->get('/quotation')->assertRedirect('/login');
    }

    public function test_public_confirmation_audit_never_impersonates_admin(): void
    {
        Auth::logout();
        Audit::publicConfirmation('Tambah', 'Konfirmasi quotation uji', ['jumlah' => 1], 123, 'Q-UJI');
        $log = DB::table('logs')->first();
        $this->assertNull($log->created_by);
        $this->assertSame('publik', json_decode($log->data, true)['actor_type']);
    }

    public function test_sales_lifecycle_source_costing_permissions_and_locked_final(): void
    {
        $this->login($this->admin());
        $payload = $this->salesPayload();
        $payload['biaya'][] = ['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Biaya nol', 'id_satuan' => $this->unit, 'harga' => 0, 'is_manual' => true];
        $this->post('/penjualan-program', $payload)->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Penjualan Program PJP-2611-001 tersimpan sebagai Draft · 1 item bernilai 0 dibuang.');
        $payload['biaya'] = array_slice($payload['biaya'], 0, 1);
        $id = DB::table('penjualan_program')->value('id');
        $this->assertDatabaseHas('penjualan_program', ['id' => $id, 'nomor' => 'PJP-2611-001', 'durasi_hari' => 2, 'harga_jual_peserta' => 1000000]);
        $payload['harga_jual_peserta'] = 1200000;
        $payload['biaya'][0]['harga'] = 110000;
        $this->put('/penjualan-program/'.$id, $payload)->assertSessionHasNoErrors();
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertEquals(1000000, $log['old']['harga_jual_peserta']);
        $this->assertEquals(1200000, $log['new']['harga_jual_peserta']);
        $this->assertNotEmpty($log['detail_changes']);
        $staff = $this->account();
        DB::table('ms_user_akses')->insert(['id_user' => $staff->id_user, 'id_menu' => DB::table('ms_menu')->where('kode', 'penjualan_program')->value('id'), 'aksi' => 'ubah']);
        $this->login($staff)->post('/penjualan-program/'.$id.'/finalkan')->assertForbidden();
        $this->login(User::where('username', 'root')->first());
        $this->post('/penjualan-program/'.$id.'/finalkan')->assertSessionHasNoErrors();
        $this->get('/penjualan-program')->assertInertia(fn ($p) => $p->where('rows.data.0.jam_pembelajaran', 16)->where('rows.data.0.asisten_nama', 'Sales Sintetis')->has('years', 1));
        $this->put('/penjualan-program/'.$id, $payload)->assertSessionHasErrors('program');
        // Final kini boleh dibatalkan (mockup pjBatal) selama belum Closed; alasan wajib, Closed tetap ditolak. Dampak diuji di ProgramCancelTest.
        $this->post('/penjualan-program/'.$id.'/batalkan')->assertSessionHasErrors('alasan');
        DB::table('penjualan_program')->where('id', $id)->update(['closed_date' => now()]);
        $this->post('/penjualan-program/'.$id.'/batalkan', ['alasan' => 'uji'])->assertSessionHasErrors('program');
        DB::table('penjualan_program')->where('id', $id)->update(['closed_date' => null]);
        $this->get('/penjualan-program/'.$id)->assertInertia(fn ($p) => $p->component('Sales/Form')->where('actual.peserta', 0));
    }

    public function test_quotation_server_price_preview_send_pdf_and_readonly_after_send(): void
    {
        $this->login($this->admin());
        $p = $this->program();
        $in = $this->quotePayload($p, ['harga_peserta' => 1, 'ppn_persen' => 0]);
        $this->post('/quotation', $in)->assertSessionHasNoErrors();
        $q = DB::table('quotation')->first();
        $this->assertSame('LMI/PW/11/26-001', $q->nomor);
        $this->assertEquals(1000000, $q->harga_peserta);
        $this->assertEquals(11, $q->ppn_persen);
        $this->assertSame('2119000.00', Quotation::calculate($q, 2)['grand_total']);
        $counts = [DB::table('quotation')->count(), DB::table('logs')->count()];
        $preview = $this->postJson('/quotation/preview', $in + ['id' => $q->id])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $preview->getContent());
        $this->assertSame($counts, [DB::table('quotation')->count(), DB::table('logs')->count()]);
        $pdf = $this->postJson('/quotation/'.$q->id.'/kirim')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('quotation', ['id' => $q->id, 'status' => 'terkirim']);
        $this->put('/quotation/'.$q->id, $in)->assertSessionHasErrors('quotation');
        $this->get('/quotation')->assertInertia(fn ($r) => $r->component('Quotation/Index')->where('rows.data.0.menunggu', 2)->where('rows.data.0.quotation_status.terkirim', 1));
        $this->get('/quotation/batch/'.$p)->assertOk();
        $this->get('/quotation/'.$q->id)->assertOk();
        $programPdf = $this->get('/penjualan-program/'.$p.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $confirmationPdf = $this->postJson('/konfirmasi/'.$q->link_token.'/preview', ['nama' => 'Peserta Sintetis', 'email' => 'sintetis@example.test'])->assertOk();
        $this->post('/konfirmasi/'.$q->link_token, ['nama' => 'Peserta Sintetis', 'email' => 'sintetis@example.test', 'setuju' => true, 'surat' => UploadedFile::fake()->createWithContent('signed.pdf', $confirmationPdf->getContent())])->assertSessionHasNoErrors();
        $batch = $this->get('/quotation/batch/'.$p.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        if ($dir = getenv('PHASE3_PDF_DIR')) {
            $root = realpath(base_path('.artifacts/fase3_penjualan_quotation'));
            if (! $root || ! str_starts_with($dir, $root.DIRECTORY_SEPARATOR) || is_dir($dir)) {
                throw new \RuntimeException('Bukti PDF harus direktori baru di artifact fase3.');
            }
            File::makeDirectory($dir, 0755, true);
            File::put($dir.'/quotation-public.pdf', $pdf->getContent());
            File::put($dir.'/confirmation-public.pdf', $confirmationPdf->getContent());
            File::put($dir.'/program-offer.pdf', $programPdf->getContent());
            File::put($dir.'/batch-import-pdf.pdf', $batch->getContent());
        }
    }

    public function test_public_form_accepts_draft_requires_signature_filters_fields_and_rechecks_capacity(): void
    {
        $this->login($this->admin());
        $q = $this->quote($this->program(), ['kuota_peserta' => 1]);
        $url = '/konfirmasi/'.$q->link_token;
        Auth::logout();
        $this->get($url)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertInertia(fn ($p) => $p->component('Confirmation')->missing('peserta')->where('info.sisa', 1));
        $this->post($url, ['nama' => 'Peserta Link', 'setuju' => true])->assertSessionHasErrors('surat');
        $this->post($url, ['nama' => 'Peserta Link', 'email' => 'link@example.test', 'jabatan' => 'Tidak dipilih', 'setuju' => true, 'surat' => $this->letter(), 'actor_type' => 'admin', 'input_by' => 999])->assertSessionHasNoErrors();
        $row = DB::table('quotation_peserta')->first();
        $this->assertSame('Peserta Link', $row->nama);
        $this->assertNull($row->jabatan);
        $this->assertNull($row->id_peserta);
        $this->assertNull($row->input_by);
        $this->assertFileExists($this->documents.'/'.$row->surat_konfirmasi);
        $this->assertNull(DB::table('logs')->where('sub_module', 'Data Peserta')->value('created_by'));
        $this->post($url, ['nama' => 'Melebihi Kuota', 'setuju' => true, 'surat' => $this->letter()])->assertStatus(410);
        $this->assertSame(1, DB::table('quotation_peserta')->count());
        $this->assertCount(1, File::allFiles($this->documents));
    }

    public function test_h14_inclusive_expiry_and_closed_guards(): void
    {
        $this->login($this->admin());
        $q = $this->quote($p = $this->program());
        DB::table('penjualan_program')->where('id', $p)->update(['tgl_mulai' => '2026-11-27']);
        $this->assertNull(Sales::linkState($q, Sales::program($p)));
        $this->travelTo(now()->addDay());
        $this->assertStringContainsString('H-14', Sales::linkState($q, Sales::program($p)));
        $this->postJson('/konfirmasi/'.$q->link_token.'/preview', ['nama' => 'Uji'])->assertStatus(410);
        $this->travelBack();
        $this->travelTo(now()->setDate(2026, 11, 13)->startOfDay());
        DB::table('penjualan_program')->where('id', $p)->update(['tgl_mulai' => '2026-12-20', 'closed_date' => now()]);
        $this->post('/quotation/'.$q->id.'/batalkan')->assertSessionHasErrors('program');
        $this->postJson('/quotation/'.$q->id.'/kirim')->assertUnprocessable();
        DB::table('penjualan_program')->where('id', $p)->update(['closed_date' => null]);
        DB::table('quotation')->where('id', $q->id)->update(['berlaku_sampai' => '2026-11-12']);
        $this->assertStringContainsString('berakhir', Sales::linkState(Quotation::row($q->id), Sales::program($p)));
    }

    public function test_b2b_single_quote_pic_once_minimum_ten_and_more_than_25_with_extra(): void
    {
        $this->login($this->admin());
        $p = $this->program(true);
        $over = ['kuota_peserta' => 10, 'harga_allin' => 25000000, 'diskon' => 1000000, 'kolom_peserta_b2b' => ['email']];
        $q = $this->quote($p, $over);
        $this->post('/quotation', $this->quotePayload($p, $over))->assertSessionHasErrors('id_penjualan');
        $this->assertSame('company', $q->tujuan);
        $this->assertSame('CO-001', $q->id_customer);
        $this->assertSame('26983000.00', Quotation::calculate($q, 26)['grand_total']);
        $in = ['nama_perusahaan' => 'Company Sintetis', 'alamat_perusahaan' => 'Alamat Uji', 'pic_nama' => 'PIC Sintetis', 'pic_jabatan' => 'HR', 'pic_hp' => '081234567890', 'pic_email' => 'pic@example.test', 'setuju' => true];
        Auth::logout();
        $url = '/konfirmasi/'.$q->link_token;
        $this->post($url, $in + ['peserta' => [['nama' => 'Kurang']], 'surat' => $this->letter()])->assertSessionHasErrors('peserta');
        $list = array_map(fn ($i) => ['nama' => 'Peserta '.$i, 'email' => 'peserta'.$i.'@example.test'], range(1, 26));
        $confirmationPdf = $this->postJson($url.'/preview', $in + ['peserta' => $list])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->post($url, $in + ['peserta' => [...$list, ['nama' => '', 'email' => '']], 'surat' => $this->letter()])->assertSessionHasNoErrors();
        $this->assertSame(26, DB::table('quotation_peserta')->count());
        $this->assertSame(1, DB::table('quotation_konfirmasi_company')->count());
        $this->post($url, $in + ['peserta' => $list, 'surat' => $this->letter()])->assertStatus(410);
        $this->assertCount(1, File::allFiles($this->documents));
        $this->login(User::where('username', 'root')->first());
        $offer = $this->postJson('/quotation/'.$q->id.'/kirim')->assertOk();
        $ids = DB::table('quotation_peserta')->pluck('id')->all();
        $this->post('/quotation/'.$q->id.'/deal', ['peserta' => array_slice($ids, 0, 9), 'dokumen' => [$this->letter()]])->assertSessionHasErrors('peserta');
        $this->post('/quotation/'.$q->id.'/deal', ['peserta' => $ids, 'dokumen' => [$this->letter()]])->assertSessionHasNoErrors();
        $this->assertSame(26, DB::table('ms_peserta')->count());
        $offer = $this->get('/quotation/'.$q->id.'/pdf')->assertOk();
        $batch = $this->get('/quotation/batch/'.$p.'/pdf')->assertOk();
        if (($dir = getenv('PHASE3_PDF_DIR')) && is_dir($dir)) {
            File::put($dir.'/confirmation-company.pdf', $confirmationPdf->getContent());
            File::put($dir.'/quotation-b2b.pdf', $offer->getContent());
            File::put($dir.'/batch-company-once.pdf', $batch->getContent());
        }
    }

    public function test_deal_atomic_idempotent_one_so_per_quote_and_one_active_letter_per_program(): void
    {
        $this->login($this->admin());
        $p = $this->program();
        foreach (range(1, 2) as $i) {
            $q = $this->quote($p);
            DB::table('quotation')->where('id', $q->id)->update(['status' => 'terkirim']);
            $pid = $this->participant($q, ['email' => 'same@example.test', 'nama' => 'Peserta '.$i]);
            $cancelled = $this->participant($q, ['nama' => 'Tidak Deal '.$i]);
            $this->post('/quotation/'.$q->id.'/deal', ['peserta' => [$pid], 'dokumen' => [$this->letter()]])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('quotation_peserta', ['id' => $cancelled, 'status' => 'batal', 'id_peserta' => null]);
            $this->assertSame('1064500.00', Quotation::calculate(Quotation::row($q->id), Quotation::count(Quotation::row($q->id)))['grand_total']);
            $this->post('/quotation/'.$q->id.'/deal', ['peserta' => [$pid], 'dokumen' => [$this->letter()]])->assertSessionHasNoErrors();
        }
        $this->assertSame(1, DB::table('ms_peserta')->count());
        $this->assertSame(2, DB::table('sales_order')->count());
        $this->assertSame(1, DB::table('surat_kerja_trainer')->count());
        $this->assertSame(2, DB::table('quotation_dokumen')->count());
        $this->assertCount(2, File::allFiles($this->documents));
        $this->assertSame('Peserta 1', DB::table('ms_peserta')->value('nama'));
        $this->assertSame('Peserta 2', DB::table('quotation_peserta')->where('id', $pid)->value('nama'));
        $this->assertDatabaseHas('logs', ['module' => 'Quotation', 'action' => 'Tambah', 'ref_table' => 'sales_order']);
        $snapshot = json_decode(DB::table('surat_kerja_trainer')->value('snapshot'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Trainer Sintetis', $snapshot['trainer']['nama']);
        $this->assertSame('2026-12-20', $snapshot['program']['tgl_mulai']);
    }

    public function test_identity_conflict_rolls_back_master_status_order_documents_and_audit(): void
    {
        $this->login($this->admin());
        $q = $this->quote($this->program());
        DB::table('quotation')->where('id', $q->id)->update(['status' => 'terkirim']);
        DB::table('ms_peserta')->insert(['kode' => 'PS-A', 'nama' => 'Master A', 'nik' => '111', 'email' => 'a@example.test']);
        DB::table('ms_peserta')->insert(['kode' => 'PS-B', 'nama' => 'Master B', 'nik' => '222', 'email' => 'b@example.test']);
        $first = $this->participant($q, ['nama' => 'Baru dahulu', 'email' => 'baru@example.test']);
        $second = $this->participant($q, ['nik' => '111', 'email' => 'b@example.test']);
        $logs = DB::table('logs')->count();
        $this->post('/quotation/'.$q->id.'/deal', ['peserta' => [$first, $second], 'dokumen' => [$this->letter()]])->assertSessionHasErrors('peserta');
        $this->assertSame(2, DB::table('ms_peserta')->count());
        $this->assertSame(0, DB::table('sales_order')->count());
        $this->assertSame(0, DB::table('surat_kerja_trainer')->count());
        $this->assertSame(0, DB::table('quotation_dokumen')->count());
        $this->assertSame($logs, DB::table('logs')->count());
        $this->assertDatabaseHas('quotation_peserta', ['id' => $first, 'status' => 'menunggu', 'id_peserta' => null]);
        $this->assertCount(0, File::allFiles($this->documents));
    }

    public function test_phone_match_normalizes_punctuation_and_ambiguity_is_rejected(): void
    {
        DB::table('ms_peserta')->insert(['kode' => 'PS-A', 'nama' => 'Master A', 'no_telp' => '(0812) 3456-7890']);
        $this->assertSame('PS-A', Quotation::matchParticipant(['no_wa' => '081234567890'])->kode);
        DB::table('ms_peserta')->insert(['kode' => 'PS-B', 'nama' => 'Master B', 'no_telp' => '081234567890']);
        $this->expectException(ValidationException::class);
        Quotation::matchParticipant(['no_wa' => '081234567890']);
    }

    public function test_private_document_permissions_replacement_history_and_audit_public_label(): void
    {
        $admin = $this->admin();
        $this->login($admin);
        $q = $this->quote($this->program());
        $url = '/konfirmasi/'.$q->link_token;
        $this->post($url, ['nama' => 'Peserta Uji', 'setuju' => true, 'surat' => $this->letter()])->assertSessionHasNoErrors();
        $pid = DB::table('quotation_peserta')->value('id');
        $old = DB::table('quotation_peserta')->value('surat_konfirmasi');
        $this->get('/documents/confirmation/'.$pid)->assertOk();
        $this->post('/quotation/peserta/'.$pid.'/surat', ['alasan' => 'Versi terbaca', 'surat' => $this->letter()])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('quotation_peserta_surat_riwayat', ['id_quotation_peserta' => $pid, 'surat_konfirmasi' => $old, 'alasan' => 'Versi terbaca']);
        $this->get('/quotation/'.$q->id)->assertInertia(fn ($p) => $p->has('peserta.0.history', 1));
        config(['labmania.activity_log_role_ids' => [1]]);
        $this->get('/activity-log')->assertInertia(fn ($p) => $p->where('logs.data', fn ($rows) => collect($rows)->contains('actor_label', 'Calon peserta / PIC (tanpa login)')));
        $staff = $this->account();
        $this->login($staff)->get('/documents/confirmation/'.$pid)->assertForbidden();
        $this->login($admin);
        DB::table('quotation_peserta')->where('id', $pid)->update(['surat_konfirmasi' => 'uploads/konfirmasi_kehadiran/../../.env']);
        $this->get('/documents/confirmation/'.$pid)->assertNotFound();
        $this->post($url, ['nama' => 'Serangan', 'setuju' => true, 'surat' => UploadedFile::fake()->createWithContent('evil.pdf', '<?php echo "bad";')])->assertSessionHasErrors('file');
    }

    public function test_company_quota_reopens_link_but_personal_and_expired_links_stay_closed(): void
    {
        $this->login($this->admin());
        $p = $this->program();
        $q = $this->quote($p, ['tujuan' => 'company', 'id_customer' => 'CO-001', 'kuota_peserta' => 1]);
        DB::table('quotation')->where('id', $q->id)->update(['status' => 'terkirim']);
        $this->participant($q);
        $this->assertNotNull(Sales::linkState(Quotation::row($q->id), Sales::program($p)));
        $this->post('/quotation/'.$q->id.'/kuota', ['kuota_peserta' => 2])->assertSessionHasNoErrors();
        $this->assertNull(Sales::linkState(Quotation::row($q->id), Sales::program($p)));
        $this->post('/quotation/'.$q->id.'/kuota', ['kuota_peserta' => 1])->assertSessionHasErrors('kuota_peserta');
        DB::table('quotation')->where('id', $q->id)->update(['berlaku_sampai' => '2026-11-12']);
        $this->post('/quotation/'.$q->id.'/kuota', ['kuota_peserta' => 3])->assertSessionHasNoErrors();
        $this->assertNotNull(Sales::linkState(Quotation::row($q->id), Sales::program($p)));
        $personal = $this->quote($p);
        DB::table('quotation')->where('id', $personal->id)->update(['status' => 'terkirim']);
        $this->post('/quotation/'.$personal->id.'/kuota', ['kuota_peserta' => 3])->assertSessionHasErrors('kuota_peserta');
    }

    public function test_excel_preserves_identity_text_and_formula_input_and_exports_are_audited(): void
    {
        $this->login($this->admin());
        $q = $this->quote($p = $this->program());
        $this->post('/konfirmasi/'.$q->link_token, ['nama' => '=SUM(1,2)', 'nik' => '001234567890', 'setuju' => true, 'surat' => $this->letter()])->assertSessionHasNoErrors();
        $xml = $this->get('/quotation/'.$q->id.'/excel')->assertOk()->getContent();
        $doc = new \DOMDocument;
        $this->assertTrue($doc->loadXML($xml));
        $this->assertStringContainsString('ss:Type="String">001234567890', $xml);
        $this->assertStringContainsString('ss:Type="String">=SUM(1,2)', $xml);
        $this->assertStringNotContainsString('ss:Formula', $xml);
        $id = DB::table('quotation_peserta')->value('id');
        $this->get('/documents/confirmation/'.$id.'?preview=1')->assertOk()->assertHeader('Content-Disposition', 'inline');
        $this->get('/quotation/'.$q->id.'/letters')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('logs', ['action' => 'Export Excel', 'ref_id' => $q->id]);
        DB::table('quotation_peserta')->where('id', $id)->update(['status' => 'batal']);
        $this->getJson('/quotation/batch/'.$p.'/pdf')->assertUnprocessable();
    }

    public function test_after_deal_documents_append_and_closed_blocks_upload_without_orphans(): void
    {
        $this->login($this->admin());
        $q = $this->quote($p = $this->program());
        DB::table('quotation')->where('id', $q->id)->update(['status' => 'terkirim']);
        $pid = $this->participant($q);
        $this->post('/quotation/'.$q->id.'/deal', ['peserta' => [$pid], 'dokumen' => [$this->letter()], 'dokumen_keterangan' => 'PO awal'])->assertSessionHasNoErrors();
        $this->post('/quotation/'.$q->id.'/dokumen', ['dokumen' => [$this->letter()], 'dokumen_keterangan' => 'PO susulan'])->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('quotation_dokumen')->count());
        $this->assertDatabaseHas('quotation', ['id' => $q->id, 'dokumen_keterangan' => 'PO susulan']);
        DB::table('penjualan_program')->where('id', $p)->update(['closed_date' => now()]);
        $this->get('/quotation/'.$q->id)->assertInertia(fn ($r) => $r->where('calc.n_peserta', 1)->where('can.edit', false)->has('programs', 1)->where('programs.0.id', $p));
        $this->post('/quotation/'.$q->id.'/dokumen', ['dokumen' => [$this->letter()]])->assertSessionHasErrors('program');
        $this->assertCount(2, File::allFiles($this->documents));
    }

    public function test_public_invalid_token_and_request_rate_limit(): void
    {
        $this->get('/konfirmasi/not-a-token')->assertNotFound();
        $this->login($this->admin());
        $q = $this->quote($this->program());
        Auth::logout();
        foreach (range(1, 12) as $attempt) {
            $this->postJson('/konfirmasi/'.$q->link_token.'/preview', [])->assertUnprocessable();
        }
        $this->postJson('/konfirmasi/'.$q->link_token.'/preview', [])->assertStatus(429);
        $this->assertSame(0, DB::table('quotation_peserta')->count());
    }
}

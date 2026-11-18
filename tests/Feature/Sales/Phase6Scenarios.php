<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Support\Expenses;
use App\Support\Participants;
use App\Support\Survey;
use App\Support\Xlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

trait Phase6Scenarios
{
    private int $program6;

    private int $person6;

    private int $cost6;

    private User $root6;

    private function user6(string $name, ?string $role = null): User
    {
        $u = User::forceCreate(['username' => $name, 'nm_lengkap' => $name, 'password' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
        if ($role) {
            DB::table('user_groups')->insert(['id_user' => $u->id_user, 'id_group' => $role === 'root' ? 1 : DB::table('groups')->where('nm_group', $role)->value('id_group')]);
        }

        return $u;
    }

    private function login6(User $u): void
    {
        $this->actingAs($u)->withSession(['auth.verified_id' => $u->id_user, 'auth.factor' => hash('sha256', $u->password)]);
    }

    private function grant6(User $u, string $menu, string ...$actions): void
    {
        foreach ($actions as $a) {
            DB::table('ms_user_akses')->insert(['id_user' => $u->id_user, 'id_menu' => DB::table('ms_menu')->where('kode', $menu)->value('id'), 'aksi' => $a]);
        }
    }

    private function seedPhase6(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 14)->startOfDay());
        $this->root6 = $this->user6('root_fase6', 'root');
        $this->login6($this->root6);
        config(['labmania.document_root' => storage_path('framework/testing-phase6-'.getmypid())]);
        $types = [];
        foreach (['ms_jenis_produk' => 'Training', 'ms_jenis_bidang' => 'K3', 'ms_jenis_sertifikasi' => 'BNSP', 'ms_varian_pelaksanaan' => 'Workshop'] as $t => $name) {
            $types[$t] = DB::table($t)->insertGetId(['kode' => 'F6-'.substr(md5($t), 0, 6), 'nama' => $name, 'aktif' => 1]);
        }
        $k = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-6', 'judul' => 'Program Enam', 'id_jenis_produk' => $types['ms_jenis_produk'], 'id_jenis_bidang' => $types['ms_jenis_bidang'], 'id_jenis_sertifikasi' => $types['ms_jenis_sertifikasi'], 'id_varian_pelaksanaan' => $types['ms_varian_pelaksanaan'], 'aktif' => 1]);
        $m = DB::table('master_program')->insertGetId(['nomor' => 'MP-6', 'status' => 'final', 'durasi_hari' => 2, 'target_peserta' => 10, 'harga_jual_peserta' => 100000]);
        DB::table('master_program_produk')->insert(['id_master_program' => $m, 'id_paket_produk' => $k, 'urutan' => 1]);
        $t = DB::table('ms_trainer')->insertGetId(['kode' => 'TR-6', 'nama' => 'Trainer Enam', 'aktif' => 1]);
        $s = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-6', 'nama' => 'Sales Enam', 'id_user' => $this->root6->id_user, 'aktif' => 1]);
        $this->program6 = DB::table('penjualan_program')->insertGetId(['nomor' => 'PJP-6', 'jenis' => 'public', 'id_master_program' => $m, 'id_trainer' => $t, 'id_asisten' => $s, 'durasi_hari' => 2, 'target_peserta' => 10, 'tgl_mulai' => '2026-11-10', 'tgl_selesai' => '2026-11-11', 'lokasi' => 'Bekasi', 'status' => 'final']);
        $q = DB::table('quotation')->insertGetId(['nomor' => 'QTN-6', 'id_penjualan' => $this->program6, 'id_sales' => $s, 'tanggal' => '2026-11-01', 'berlaku_sampai' => '2026-11-30', 'status' => 'deal', 'kuota_peserta' => 10]);
        $this->person6 = DB::table('quotation_peserta')->insertGetId(['id_quotation' => $q, 'nama' => 'Peserta Enam', 'no_wa' => '08123456', 'nik' => '0012345678901234', 'status' => 'deal', 'sumber' => 'link']);
        $this->cost6 = DB::table('penjualan_program_biaya')->insertGetId(['id_penjualan' => $this->program6, 'tipe' => 'fixed', 'item' => 'Sewa', 'harga' => 100000]);
    }

    private function input6(array $over = []): array
    {
        return $over + ['tipe_biaya' => 'fixed', 'jenis' => 'kasbon', 'deskripsi' => 'Sewa tempat', 'bank' => 'Bank Uji', 'no_rekening' => '00123456', 'nama_rekening' => 'Penerima', 'items' => [['key' => 'c'.$this->cost6, 'qty' => '0.50', 'harga' => '100000']]];
    }

    public function test_budget_reservation_and_migration_without_grants(): void
    {
        $this->assertSame('100000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        $id = Expenses::save($this->program6, $this->input6(), false);
        $this->assertSame('50000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        $this->assertDatabaseHas('pengajuan_biaya', ['id' => $id, 'nomor' => 'LMI/KS/11/26-001', 'total' => 50000, 'status' => 'menunggu']);
        $this->assertSame(0, DB::table('ms_group_akses')->whereIn('id_group', DB::table('groups')->whereIn('nm_group', ['Admin', 'Direktur'])->pluck('id_group'))->count());
        Expenses::transition($id, false, 'batal');
        $this->assertSame('100000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        $this->assertStringNotContainsString('00123456', DB::table('logs')->where('module', 'Pengajuan Biaya')->value('data'));
    }

    public function test_approval_paid_and_closed_existing_requests(): void
    {
        $id = Expenses::save($this->program6, $this->input6(), false);
        $this->post('/pengajuan-biaya/pengajuan/'.$id.'/setujui')->assertForbidden();
        $director = $this->user6('direktur6', 'Direktur');
        $this->grant6($director, 'approval_pengajuan_biaya', 'lihat', 'ubah');
        $this->login6($director);
        $this->post('/pengajuan-biaya/pengajuan/'.$id.'/setujui')->assertForbidden();
        $this->grant6($director, 'approval_pengajuan_biaya', 'approve');
        $this->post('/pengajuan-biaya/pengajuan/'.$id.'/setujui')->assertSessionHasNoErrors();
        $this->post('/pengajuan-biaya/pengajuan/'.$id.'/paid', ['paid_date' => '2026-11-14'])->assertForbidden();
        $this->login6($this->root6);
        $this->post('/pengajuan-biaya/pengajuan/'.$id.'/paid', ['paid_date' => '2026-11-15'])->assertSessionHasErrors('paid_date');
        DB::table('penjualan_program')->where('id', $this->program6)->update(['closed_date' => now()]);
        $this->post('/pengajuan-biaya/pengajuan/'.$id.'/paid', ['paid_date' => '2026-11-14'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pengajuan_biaya', ['id' => $id, 'status' => 'paid']);
        $this->assertSame('50000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        $this->post('/pengajuan-biaya/pengajuan/'.$id.'/paid', ['paid_date' => '2026-11-14'])->assertSessionHasErrors('pengajuan');
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6())->assertSessionHasErrors('program');
    }

    public function test_category_budget_overbudget_new_item_and_rejection_release(): void
    {
        $id = Expenses::save($this->program6, $this->input6(['items' => [['key' => 'c'.$this->cost6, 'qty' => '1', 'harga' => '100000']]]), false);
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6())->assertSessionHasErrors('items');
        $ob = Expenses::save($this->program6, ['tipe_biaya' => 'fixed', 'alasan' => 'Tambahan kegiatan', 'items' => [['key' => null, 'item' => 'Transport baru', 'qty' => '2', 'harga' => '25000']]], true);
        $d = $this->user6('approver6', 'Direktur');
        $this->grant6($d, 'approval_pengajuan_biaya', 'approve');
        $this->login6($d);
        Expenses::transition($ob, true, 'setujui');
        Expenses::transition($id, false, 'tolak', ['alasan_tolak' => 'Ajukan ulang']);
        $this->assertSame('150000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        $this->login6($this->root6);
        Expenses::save($this->program6, $this->input6(['items' => [['key' => 'c'.$this->cost6, 'qty' => '1', 'harga' => '140000']]]), false);
        $this->assertSame('10000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        $extra = DB::table('pengajuan_overbudget_detail')->where('id_overbudget', $ob)->value('id');
        Expenses::save($this->program6, $this->input6(['items' => [['key' => 'o'.$extra, 'qty' => '0.2', 'harga' => '25000']]]), false);
        $this->assertSame('5000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        $this->assertDatabaseHas('pengajuan_biaya_detail', ['id_overbudget_detail' => $extra, 'id_penjualan_biaya' => null, 'total' => 5000]);
        $this->assertSame(100000, (int) DB::table('penjualan_program_biaya')->where('id', $this->cost6)->value('harga'));
    }

    public function test_data_scope_admin_is_not_superadmin_and_direct_payment_requires_evidence(): void
    {
        $staff = $this->user6('outsider6');
        $this->grant6($staff, 'pengajuan_biaya', 'lihat', 'tambah');
        $this->login6($staff);
        $this->get('/pengajuan-biaya/program/'.$this->program6)->assertForbidden();
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6())->assertForbidden();
        $this->login6($this->root6);
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6(['jenis' => 'direct_payment']))->assertSessionHasErrors('bukti');
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6(['items' => [['key' => 'c99999', 'qty' => '1', 'harga' => '1']]]))->assertSessionHasErrors('items');
        $admin = $this->user6('adminbisnis6', 'Admin');
        $this->login6($admin);
        $this->get('/pengajuan-biaya/program/'.$this->program6)->assertForbidden();
        $this->grant6($admin, 'pengajuan_biaya', 'lihat');
        $this->get('/pengajuan-biaya/program/'.$this->program6)->assertOk();
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6())->assertForbidden();
    }

    public function test_certificate_name_is_separate_and_official_issue_disabled(): void
    {
        $this->post('/manage-peserta/peserta/'.$this->person6.'/nama', ['nama_sertifikat' => 'Nama Benar'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('quotation_peserta', ['id' => $this->person6, 'nama' => 'Peserta Enam']);
        $this->assertDatabaseHas('quotation_peserta_sertifikat', ['id_quotation_peserta' => $this->person6, 'nama_sertifikat' => 'Nama Benar', 'nama_konfirmasi_by' => $this->root6->id_user]);
        $this->post('/manage-peserta/peserta/'.$this->person6.'/terbit')->assertSessionHasErrors('sertifikat');
        $this->post('/manage-peserta/peserta/'.$this->person6.'/pengiriman', ['status_kirim' => 'soft', 'tgl_kirim' => '2026-11-14', 'kirim_via' => 'WA'])->assertSessionHasErrors('status_kirim');
        DB::table('quotation_peserta')->where('id', $this->person6)->update(['status' => 'keluar']);
        $this->assertSame(0, Participants::query($this->program6)->count());
        $this->post('/manage-peserta/peserta/'.$this->person6.'/nama', ['nama_sertifikat' => 'Tidak boleh'])->assertNotFound();
    }

    public function test_global_data_roles_cannot_be_delegated_by_staff(): void
    {
        DB::table('groups')->where('id_group', 1)->update(['nm_group' => 'admin']);
        $admin = DB::table('groups')->where('nm_group', 'Admin')->where('id_group', '<>', 1)->value('id_group');
        $this->put('/master/role/'.$admin, ['nama' => 'Admin', 'ket' => 'Admin bisnis'])->assertSessionHasNoErrors();
        $this->put('/master/role/1', ['nama' => 'admin', 'ket' => 'Superadmin legacy'])->assertSessionHasNoErrors();
        $staff = $this->user6('delegator6');
        $this->grant6($staff, 'master_data.users', 'tambah');
        $this->grant6($staff, 'permission_group', 'tambah', 'ubah');
        $this->login6($staff);
        foreach (['Admin', 'Direktur'] as $role) {
            $id = DB::table('groups')->where('nm_group', $role)->where('id_group', '<>', 1)->value('id_group');
            $this->post('/master/user', ['nama' => 'Tidak boleh', 'id_group' => $id])->assertSessionHasErrors('id_group');
            $this->post('/master/role', ['nama' => $role])->assertForbidden();
            $this->put('/master/role/'.$id, ['nama' => 'Nama lain'])->assertForbidden();
        }
        $this->assertDatabaseMissing('ms_user_karyawan', ['nama' => 'Tidak boleh']);
    }

    public function test_bnsp_validation_upload_and_closed_administration(): void
    {
        $url = '/manage-peserta/peserta/'.$this->person6.'/bnsp';
        $this->post($url, ['hasil' => 'kompeten', 'id_card' => 'belum'])->assertUnprocessable();
        DB::table('ms_jenis_produk')->where('nama', 'Training')->update(['nama' => 'Sertifikasi']);
        $this->post($url, ['hasil' => 'kompeten', 'id_card' => 'belum', 'tgl_kirim_bnsp' => '2026-11-14'])->assertSessionHasErrors('tgl_terima_bnsp');
        DB::table('penjualan_program')->where('id', $this->program6)->update(['closed_date' => now()]);
        $this->post($url, ['hasil' => 'kompeten', 'id_card' => 'sudah', 'tgl_terima_bnsp' => '2026-11-13', 'tgl_kirim_bnsp' => '2026-11-14', 'bukti' => UploadedFile::fake()->image('bnsp.png')])->assertSessionHasNoErrors();
        $id = DB::table('quotation_peserta_sertifikat')->value('id');
        $this->get('/documents/bnsp/'.$id)->assertOk();
        $this->assertSame(1, (int) Participants::query()->first()->has_bnsp_file);
    }

    public function test_close_date_invoice_cancellation_and_survey_shutdown(): void
    {
        $i = $this->invoice6();
        DB::table('invoice')->where('id', $i)->update(['tanggal_bayar' => '2026-11-14', 'status' => 'batal']);
        $this->post('/manage-peserta/'.$this->program6.'/close')->assertSessionHasErrors('program');
        DB::table('invoice')->where('id', $i)->update(['status' => 'invoiced']);
        DB::table('penjualan_program')->where('id', $this->program6)->update(['tgl_selesai' => '2026-11-14']);
        $this->post('/manage-peserta/'.$this->program6.'/close')->assertSessionHasErrors('program');
        DB::table('penjualan_program')->where('id', $this->program6)->update(['tgl_selesai' => '2026-11-13']);
        DB::table('program_survey')->insert(['id_penjualan' => $this->program6, 'token' => str_repeat('s', 20), 'status' => 'buka']);
        $this->post('/manage-peserta/'.$this->program6.'/close')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('program_survey', ['id_penjualan' => $this->program6, 'status' => 'tutup']);
    }

    public function test_budget_unit_factors_and_decimal_quantity(): void
    {
        foreach (['Event' => '100000.00', 'Day' => '200000.00', 'Peserta/Event' => '1000000.00', 'Peserta/Day' => '2000000.00'] as $unit => $expected) {
            $id = DB::table('ms_satuan')->where('nama', $unit)->value('id');
            $id ??= DB::table('ms_satuan')->insertGetId(['nama' => $unit, 'code' => 'F6-'.substr(md5($unit), 0, 6)]);
            DB::table('penjualan_program_biaya')->where('id', $this->cost6)->update(['id_satuan' => $id]);
            $this->assertSame($expected, Expenses::budget($this->program6, 'fixed')['budget']);
        }
        $id = Expenses::save($this->program6, $this->input6(['items' => [['key' => 'c'.$this->cost6, 'qty' => '0.33', 'harga' => '101']]]), false);
        $this->assertSame('33.33', Expenses::money(DB::table('pengajuan_biaya')->where('id', $id)->value('total')));
    }

    public function test_audit_failure_rolls_back_expense_header_details_and_log(): void
    {
        $fail = true;
        DB::listen(function ($query) use (&$fail) {
            if ($fail && preg_match('/^insert into ["`]logs["`]/i', $query->sql)) {
                throw new \RuntimeException('Audit fixture gagal');
            }
        });
        try {
            Expenses::save($this->program6, $this->input6(), false);
            $this->fail('Audit gagal tetapi transaksi tetap sukses.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit fixture gagal', $e->getMessage());
        } finally {
            $fail = false;
        }
        $this->assertSame(0, DB::table('pengajuan_biaya')->count());
        $this->assertSame(0, DB::table('pengajuan_biaya_detail')->count());
        $this->assertSame(0, DB::table('logs')->where('module', 'Pengajuan Biaya')->count());
        $this->assertSame('100000.00', Expenses::budget($this->program6, 'fixed')['sisa']);
    }

    public function test_close_requires_reconfirmation_and_refund_completion(): void
    {
        $invoice = $this->invoice6();
        DB::table('invoice')->where('id', $invoice)->update(['tanggal_bayar' => '2026-11-14']);
        $reschedule = DB::table('penjualan_program_reschedule')->insertGetId(['id_penjualan' => $this->program6, 'tgl_mulai_lama' => '2026-10-10', 'tgl_selesai_lama' => '2026-10-11', 'tgl_mulai_baru' => '2026-11-10', 'tgl_selesai_baru' => '2026-11-11', 'id_trainer_lama' => 1, 'id_trainer_baru' => 1, 'id_asisten_lama' => 1, 'id_asisten_baru' => 1, 'alasan' => 'Fixture', 'request_id' => str_repeat('r', 36), 'request_hash' => str_repeat('h', 64), 'snapshot' => '{}']);
        $answer = DB::table('quotation_peserta_konfirmasi_ulang')->insertGetId(['id_quotation_peserta' => $this->person6, 'id_reschedule' => $reschedule, 'revisi' => 0]);
        $url = '/manage-peserta/'.$this->program6.'/close';
        $this->post($url)->assertSessionHasErrors('program');
        $this->assertStringContainsString('Konfirmasi ulang', session('errors')->first('program'));
        DB::table('quotation_peserta_konfirmasi_ulang')->where('id', $answer)->update(['diproses_date' => now()]);
        $refund = DB::table('sales_order_refund')->insertGetId(['nomor' => 'RF-6', 'tanggal' => '2026-11-14', 'id_sales_order' => DB::table('invoice')->where('id', $invoice)->value('id_sales_order'), 'id_quotation_peserta' => $this->person6, 'revisi' => 0, 'id_invoice' => $invoice, 'id_konfirmasi_ulang' => $answer, 'kunci_penerima' => 'peserta:'.$this->person6, 'nominal_tagihan' => 100000, 'alasan' => 'Fixture', 'snapshot' => '{}']);
        $this->post($url)->assertSessionHasErrors('program');
        $this->assertStringContainsString('Refund belum selesai', session('errors')->first('program'));
        DB::table('sales_order_refund')->where('id', $refund)->update(['status' => 'selesai']);
        $this->post($url)->assertSessionHasNoErrors();
    }

    private function invoice6(string $ppn = 'PKP'): int
    {
        $q = DB::table('quotation')->where('id_penjualan', $this->program6)->value('id');
        $s = DB::table('sales_order')->insertGetId(['nomor' => 'SO-6', 'id_quotation' => $q, 'id_penjualan' => $this->program6, 'tanggal' => '2026-11-01', 'status' => 'siap']);
        $i = DB::table('invoice')->insertGetId(['nomor' => 'INV-6', 'tanggal' => '2026-11-01', 'id_sales_order' => $s, 'id_quotation' => $q, 'id_penjualan' => $this->program6, 'cara_tagih' => 'satu', 'perlakuan_ppn' => $ppn, 'penjualan' => 100000, 'dpp' => 100000, 'ppn' => 12000, 'biaya_lain' => 0, 'total_tagihan' => 112000, 'piutang' => 112000, 'coa_penjualan' => 1, 'status' => 'invoiced', 'snapshot' => '{}']);
        DB::table('invoice_peserta')->insert(['id_invoice' => $i, 'id_quotation_peserta' => $this->person6, 'nominal' => 100000, 'nama' => 'Peserta Enam']);

        return $i;
    }

    public function test_close_requires_payment_wapu_and_keeps_administration_open(): void
    {
        $this->post('/manage-peserta/'.$this->program6.'/close')->assertSessionHasErrors('program');
        $i = $this->invoice6('WAPU');
        DB::table('invoice')->where('id', $i)->update(['tanggal_bayar' => '2026-11-14']);
        $this->post('/manage-peserta/'.$this->program6.'/close')->assertSessionHasErrors('program');
        DB::table('invoice_bukti_setor')->insert(['id_invoice' => $i, 'tanggal' => '2026-11-14']);
        $this->post('/manage-peserta/'.$this->program6.'/close')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('penjualan_program', ['id' => $this->program6, 'status' => 'final', 'closed_by' => $this->root6->id_user]);
        $this->post('/manage-peserta/peserta/'.$this->person6.'/catatan', ['catatan_monitoring' => 'Tindak lanjut'])->assertSessionHasNoErrors();
        $this->post('/manage-peserta/peserta/'.$this->person6.'/nama', ['nama_sertifikat' => 'Nama sesudah Closed'])->assertSessionHasNoErrors();
        $this->post('/manage-peserta/'.$this->program6.'/close')->assertSessionHasErrors('program');
        $this->post('/manage-peserta/'.$this->program6.'/survey/buka')->assertSessionHasErrors('program');
    }

    public function test_survey_public_gate_and_single_answer_snapshot(): void
    {
        $token = str_repeat('a', 20);
        $this->get('/survey/'.$token)->assertStatus(503);
        $this->post('/manage-peserta/'.$this->program6.'/survey/buka')->assertSessionHasErrors('survey');
        config(['labmania.operasional.survey_public_enabled' => true]);
        $qid = DB::table('survey_pertanyaan')->insertGetId(['urutan' => 1, 'bagian' => 'Materi', 'teks' => 'Pertanyaan fixture', 'tipe' => 'skala', 'wajib' => 1, 'aktif' => 1]);
        Survey::toggle($this->program6, true);
        $s = DB::table('program_survey')->first();
        $this->assertSame(20, strlen($s->token));
        $data = ['id_quotation_peserta' => $this->person6, 'jawaban' => [$qid => 5]];
        $this->post('/survey/'.$s->token, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('logs', ['module' => 'Manage Peserta', 'action' => 'Isi Survey', 'created_by' => null]);
        $this->post('/survey/'.$s->token, $data)->assertSessionHasErrors('id_quotation_peserta');
        $this->post('/survey/'.$s->token, ['id_quotation_peserta' => 999999, 'jawaban' => [$qid => 5]])->assertSessionHasErrors('id_quotation_peserta');
        DB::table('survey_pertanyaan')->where('id', $qid)->update(['teks' => 'Berubah']);
        $this->assertDatabaseHas('survey_jawaban_detail', ['pertanyaan_teks' => 'Pertanyaan fixture', 'nilai' => 5]);
    }

    public function test_xlsx_keeps_identifier_and_formula_like_text(): void
    {
        $response = Xlsx::response('Uji', [['0012345678901234', '08123456', '=1+1', '<nama>']]);
        $path = tempnam(sys_get_temp_dir(), 'test-xlsx-');
        file_put_contents($path, $response->getContent());
        $z = new \ZipArchive;
        try {
            $this->assertTrue($z->open($path));
            $xml = $z->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('0012345678901234', $xml);
            $this->assertStringContainsString('08123456', $xml);
            $this->assertStringContainsString('=1+1', $xml);
            $this->assertStringNotContainsString('<f>', $xml);
            $this->assertNotFalse(simplexml_load_string($xml));
        } finally {
            $z->close();
            unlink($path);
        }
    }

    public function test_pages_exports_pdf_and_closed_program_status(): void
    {
        foreach (['/manage-peserta', '/manage-peserta/'.$this->program6, '/manage-peserta/monitoring', '/pengajuan-biaya', '/pengajuan-biaya/program/'.$this->program6, '/pengajuan-biaya/approval'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/manage-peserta/monitoring?export=1')->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get('/manage-peserta/'.$this->program6.'/excel')->assertOk();
        for ($i = 0; $i < 10; $i++) {
            DB::table('quotation_peserta')->insert(['id_quotation' => DB::table('quotation')->value('id'), 'nama' => $i === 0 ? 'Dr. Muhammad Abdurrahman Prasetyo Wibowo, M.Si.' : 'Peserta '.($i + 2), 'instansi' => $i === 0 ? 'PT Laboratorium Pengujian Mutu dan Sertifikasi Indonesia' : 'PT Contoh', 'status' => 'deal', 'sumber' => 'link']);
        }
        foreach (['hadir', 'name-table', 'draft'] as $kind) {
            $response = $this->get('/manage-peserta/'.$this->program6.'/pdf/'.$kind);
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertTrue(str_starts_with($response->getContent(), '%PDF-'), 'PDF harus dimulai header tanpa output sebelum PDF.');
            if ($folder = getenv('VERIFY_PHASE6_PDF_DIR')) {
                $base = realpath(base_path('.artifacts/fase6_operasional_biaya'));
                $target = realpath($folder);
                $this->assertStringStartsWith($base.DIRECTORY_SEPARATOR, $target);
                $file = fopen($target.DIRECTORY_SEPARATOR.$kind.'.pdf', 'xb');
                fwrite($file, $response->getContent());
                fclose($file);
            }
        }
    }

    public function test_upload_scope_approval_evidence_and_atomic_cleanup(): void
    {
        $file = UploadedFile::fake()->image('bukti.png');
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6(['jenis' => 'direct_payment', 'bukti' => [$file]]))->assertSessionHasNoErrors();
        $id = DB::table('pengajuan_biaya_lampiran')->value('id');
        $this->get('/documents/expense/'.$id)->assertOk();
        $d = $this->user6('direktur_bukti', 'Direktur');
        $this->grant6($d, 'approval_pengajuan_biaya', 'lihat', 'approve');
        $this->login6($d);
        $this->get('/documents/expense/'.$id)->assertOk();
        $this->get('/pengajuan-biaya/pengajuan/'.DB::table('pengajuan_biaya')->value('id'))->assertOk();
        $outsider = $this->user6('outsider_bukti');
        $this->grant6($outsider, 'pengajuan_biaya', 'lihat');
        $this->login6($outsider);
        $this->get('/documents/expense/'.$id)->assertForbidden();
        $this->login6($this->root6);
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6(['bukti' => [UploadedFile::fake()->create('bad.exe', 10)]]))->assertSessionHasErrors('bukti.0');
        $before = count(File::allFiles(config('labmania.document_root')));
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6(['bukti' => [UploadedFile::fake()->image('too-much.png')], 'items' => [['key' => 'c'.$this->cost6, 'qty' => '100', 'harga' => '100000']]]))->assertSessionHasErrors('items');
        $this->assertSame($before, count(File::allFiles(config('labmania.document_root'))));
    }

    protected function tearDown(): void
    {
        $path = config('labmania.document_root');
        if (str_starts_with($path, storage_path('framework/testing-phase6-'))) {
            File::deleteDirectory($path);
        }
        $this->travelBack();
        parent::tearDown();
    }

    public function test_budget_per_day_uses_each_master_program_duration(): void
    {
        $day = (int) (DB::table('ms_satuan')->where('nama', 'Day')->value('id') ?? DB::table('ms_satuan')->insertGetId(['nama' => 'Day', 'code' => 'F6-DAY']));
        DB::table('penjualan_program_biaya')->where('id_penjualan', $this->program6)->delete();
        $first = DB::table('penjualan_program_master')->insertGetId(['id_penjualan' => $this->program6, 'id_master_program' => 1, 'urutan' => 1, 'durasi_hari' => 2, 'tgl_mulai' => '2026-11-10', 'tgl_selesai' => '2026-11-11']);
        $second = DB::table('penjualan_program_master')->insertGetId(['id_penjualan' => $this->program6, 'id_master_program' => 2, 'urutan' => 2, 'durasi_hari' => 5, 'tgl_mulai' => '2026-12-01', 'tgl_selesai' => '2026-12-05']);
        // Rentang program 26 hari; harga per hari 100.000. Benar: 100.000 x 2 + 100.000 x 5 = 700.000, bukan 100.000 x 26 x 2 item.
        DB::table('penjualan_program')->where('id', $this->program6)->update(['durasi_hari' => 26, 'tgl_mulai' => '2026-11-10', 'tgl_selesai' => '2026-12-05']);
        foreach ([$first, $second] as $master) {
            DB::table('penjualan_program_biaya')->insert(['id_penjualan' => $this->program6, 'id_penjualan_master' => $master, 'tipe' => 'fixed', 'item' => 'Konsumsi', 'id_satuan' => $day, 'harga' => 100000]);
        }
        $budget = Expenses::budget($this->program6, 'fixed');
        $this->assertSame('700000.00', $budget['budget']);
        $this->assertSame(['200000.00', '500000.00'], array_column($budget['items'], 'estimasi'));
        $this->assertSame(['2.00', '5.00'], array_column($budget['items'], 'qty'));
    }
}

<?php

namespace Tests\Feature\Sales;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Alur approval Refund (RF-FR-01 sampai RF-FR-07, [JMR-4], [JMR-5]). Memakai seed Phase5: invoice lunas 1.059.501 per peserta. */
trait RefundApprovalScenarios
{
    private function newRefund(): object
    {
        $this->invoice5(true);
        $this->runReschedule5();
        $this->post('/quotation/peserta/'.$this->ids5['people'][0].'/konfirmasi-ulang', ['revisi' => 1, 'keputusan' => 'refund', 'alasan' => 'Bentrok jadwal'])->assertSessionHasNoErrors();

        return DB::table('sales_order_refund')->first();
    }

    private function actor(string $name, array $grants): \App\Models\User
    {
        $user = $this->account($name);
        foreach ($grants as $code => $actions) {
            foreach ($actions as $aksi) {
                DB::table('ms_user_akses')->insert(['id_user' => $user->id_user, 'id_menu' => DB::table('ms_menu')->where('kode', $code)->value('id'), 'aksi' => $aksi]);
            }
        }

        return $user;
    }

    private function transfer(): array
    {
        return ['nominal_refund' => '1000000', 'bank' => 'Bank Uji', 'no_rekening' => '0012345678', 'atas_nama' => 'Penerima', 'catatan' => 'Potongan', 'bukti' => UploadedFile::fake()->image('t.png')];
    }

    public function test_new_refund_waits_for_approval_and_cannot_be_transferred_yet(): void
    {
        $rf = $this->newRefund();
        $this->assertSame('menunggu', $rf->status);
        $this->post('/refund/'.$rf->id, $this->transfer())->assertSessionHasErrors('refund');
        $this->assertSame('menunggu', DB::table('sales_order_refund')->value('status'));
    }

    public function test_approve_then_transfer_records_history_and_log(): void
    {
        $rf = $this->newRefund();
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'setujui', 'catatan' => 'Sesuai S&K'])->assertSessionHasNoErrors();
        $this->assertSame('disetujui', DB::table('sales_order_refund')->value('status'));
        $h = DB::table('sales_order_refund_riwayat')->first();
        $this->assertSame('setujui', $h->jenis);
        $this->assertNotNull($h->oleh);
        $this->assertSame(1, DB::table('logs')->where('action', 'Approval Refund')->count());
        // Keputusan yang sama diulang tidak menggandakan riwayat (replay aman).
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'setujui'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('sales_order_refund_riwayat')->count());
        // Keputusan berlawanan pada refund yang sudah diputuskan ditolak.
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'tolak', 'catatan' => 'Salah'])->assertSessionHasErrors('refund');
        $this->assertSame('disetujui', DB::table('sales_order_refund')->value('status'));
        $this->post('/refund/'.$rf->id, $this->transfer())->assertSessionHasNoErrors();
        $this->assertSame('selesai', DB::table('sales_order_refund')->value('status'));
    }

    public function test_reject_requires_reason_blocks_transfer_and_resubmit_returns_to_approval(): void
    {
        $rf = $this->newRefund();
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'tolak'])->assertSessionHasErrors('catatan');
        $this->assertSame('menunggu', DB::table('sales_order_refund')->value('status'));
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'tolak', 'catatan' => 'Nominal belum dipotong'])->assertSessionHasNoErrors();
        $this->assertSame('ditolak', DB::table('sales_order_refund')->value('status'));
        $this->post('/refund/'.$rf->id, $this->transfer())->assertSessionHasErrors('refund');

        // Ajukan ulang: alasan, nominal (1 s.d. nilai invoice), dan catatan wajib; maksimal tidak boleh lewat nilai invoice.
        $url = '/refund/'.$rf->id.'/ajukan-ulang';
        $this->post($url, ['alasan' => '', 'nominal' => '900000', 'catatan' => 'x'])->assertSessionHasErrors('alasan');
        $this->post($url, ['alasan' => 'Bentrok', 'nominal' => '900000', 'catatan' => ''])->assertSessionHasErrors('catatan');
        $this->post($url, ['alasan' => 'Bentrok', 'nominal' => '0', 'catatan' => 'x'])->assertSessionHasErrors('nominal');
        $this->post($url, ['alasan' => 'Bentrok', 'nominal' => '99999999999', 'catatan' => 'x'])->assertSessionHasErrors('nominal');
        $this->assertSame('ditolak', DB::table('sales_order_refund')->value('status'));
        $this->post($url, ['alasan' => 'Bentrok jadwal, dipotong admin', 'nominal' => '1000000', 'catatan' => 'Nominal dikurangi biaya admin'])->assertSessionHasNoErrors();
        $row = DB::table('sales_order_refund')->first();
        $this->assertSame('menunggu', $row->status);
        $this->assertSame('1000000', (string) (int) $row->nominal_tagihan);
        $h = DB::table('sales_order_refund_riwayat')->orderBy('id')->get();
        $this->assertSame(['tolak', 'ajukan_ulang'], $h->pluck('jenis')->all());
        $this->assertSame('1059501', (string) (int) $h[1]->nominal_lama);
        // Hanya refund Ditolak yang dapat diajukan ulang.
        $this->post($url, ['alasan' => 'Lagi', 'nominal' => '1000000', 'catatan' => 'x'])->assertSessionHasErrors('refund');
        // Tanpa batas pengajuan ulang: tolak lagi lalu ajukan ulang lagi.
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'tolak', 'catatan' => 'Masih salah'])->assertSessionHasNoErrors();
        $this->post($url, ['alasan' => 'Perbaikan kedua', 'nominal' => '1000000', 'catatan' => 'Sudah benar'])->assertSessionHasNoErrors();
        $this->assertSame('menunggu', DB::table('sales_order_refund')->value('status'));
        $this->assertSame(2, DB::table('sales_order_refund_riwayat')->where('jenis', 'ajukan_ulang')->count());
    }

    public function test_permissions_are_separated_between_list_and_approval(): void
    {
        $rf = $this->newRefund();
        $admin = auth()->user();
        // Sales/Marketing: Daftar Refund lihat + tambah (ajukan ulang), tanpa approve dan tanpa proses transfer.
        $sales = $this->actor('sales-rf', ['refund_daftar' => ['lihat', 'tambah']]);
        // Finance: Approval Refund lihat + approve, dan Daftar Refund lihat + ubah (proses transfer).
        $finance = $this->actor('finance-rf', ['refund_approval' => ['lihat', 'approve'], 'refund_daftar' => ['lihat', 'ubah']]);
        $viewer = $this->actor('viewer-rf', ['refund_daftar' => ['lihat']]);

        $this->login($viewer);
        $this->get('/refund')->assertOk();
        $this->get('/refund/approval')->assertForbidden();
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'setujui'])->assertForbidden();
        $this->post('/refund/'.$rf->id.'/ajukan-ulang', ['alasan' => 'x', 'nominal' => '1', 'catatan' => 'x'])->assertForbidden();
        $this->post('/refund/'.$rf->id, $this->transfer())->assertForbidden();

        $this->login($sales);
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'setujui'])->assertForbidden();
        $this->assertSame('menunggu', DB::table('sales_order_refund')->value('status'));

        $this->login($finance);
        $this->get('/refund/approval')->assertOk();
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'tolak', 'catatan' => 'Perbaiki'])->assertSessionHasNoErrors();
        // Finance tanpa hak tambah tidak dapat mengajukan ulang; Sales dengan hak tambah dapat.
        $this->post('/refund/'.$rf->id.'/ajukan-ulang', ['alasan' => 'x', 'nominal' => '1000000', 'catatan' => 'x'])->assertForbidden();
        $this->login($sales);
        $this->post('/refund/'.$rf->id.'/ajukan-ulang', ['alasan' => 'Perbaikan', 'nominal' => '1000000', 'catatan' => 'Sudah diperbaiki'])->assertSessionHasNoErrors();
        $this->assertSame('menunggu', DB::table('sales_order_refund')->value('status'));
        $this->login($admin);
    }

    public function test_lists_order_and_filters_and_old_urls_redirect(): void
    {
        $rf = $this->newRefund();
        $this->get('/sales-order/refund')->assertRedirect('/refund');
        $this->get('/sales-order/refund/'.$rf->id)->assertRedirect('/refund/'.$rf->id);
        $this->getJson('/refund/data?draw=1&start=0&length=10')->assertOk()->assertJsonPath('data.0.status', 'menunggu');
        $this->getJson('/refund/approval/data?draw=1&start=0&length=10')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/refund/approval/data?draw=1&start=0&length=10&tab=riwayat')->assertOk()->assertJsonCount(0, 'data');
        $this->post('/refund/'.$rf->id.'/keputusan', ['keputusan' => 'setujui']);
        $this->getJson('/refund/approval/data?draw=1&start=0&length=10&tab=riwayat')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/refund/approval/data?draw=1&start=0&length=10')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/refund/data?draw=1&start=0&length=10&status=disetujui')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/refund/data?draw=1&start=0&length=10&status=selesai')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_migration_maps_legacy_pending_to_approved_moves_grants_and_is_idempotent(): void
    {
        $rf = $this->newRefund();
        // Susun ulang keadaan sebelum migrasi: refund lama menunggu Finance, menu lama aktif dengan hak role.
        DB::table('sales_order_refund')->where('id', $rf->id)->update(['status' => 'menunggu']);
        // Menu lama sudah ada di fixture (migrasi awal); aktifkan kembali seperti sebelum migrasi Refund approval.
        DB::table('ms_menu')->where('kode', 'sales_order_refund')->update(['aktif' => 1, 'link' => '/sales-order/refund']);
        $oldMenu = (int) DB::table('ms_menu')->where('kode', 'sales_order_refund')->value('id');
        foreach (['lihat', 'ubah'] as $aksi) {
            DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => $oldMenu, 'aksi' => $aksi]);
            DB::table('ms_group_akses')->insertOrIgnore(['id_group' => 2, 'id_menu' => $oldMenu, 'aksi' => $aksi]);
        }
        DB::table('ms_user_akses')->insert(['id_user' => 55, 'id_menu' => $oldMenu, 'aksi' => 'lihat']);
        DB::table('ms_menu')->whereIn('kode', ['refund_daftar', 'refund_approval'])->delete();
        // Kolom sebab dibuang agar migrasi terbaca sebagai "pertama kali".
        \Illuminate\Support\Facades\Schema::table('sales_order_refund', fn ($t) => $t->dropColumn('sebab'));

        $migration = require base_path('database/migrations/2026_11_18_100000_refund_approval.php');
        $migration->up();

        $this->assertSame('disetujui', DB::table('sales_order_refund')->where('id', $rf->id)->value('status'), 'Refund lama yang belum selesai dianggap sudah disetujui.');
        $daftar = DB::table('ms_menu')->where('kode', 'refund_daftar')->value('id');
        $approval = DB::table('ms_menu')->where('kode', 'refund_approval')->value('id');
        $this->assertNotNull($daftar);
        $this->assertNotNull($approval);
        $this->assertSame(['lihat', 'tambah', 'ubah'], DB::table('ms_menu_aksi')->where('id_menu', $daftar)->orderBy('aksi')->pluck('aksi')->all());
        $this->assertSame(['approve', 'lihat'], DB::table('ms_menu_aksi')->where('id_menu', $approval)->orderBy('aksi')->pluck('aksi')->all());
        $this->assertSame(['lihat', 'ubah'], DB::table('ms_group_akses')->where('id_group', 2)->where('id_menu', $daftar)->orderBy('aksi')->pluck('aksi')->all(), 'Hak role pindah ke Daftar Refund.');
        $this->assertSame(0, DB::table('ms_group_akses')->where('id_menu', $approval)->count(), 'Approval Refund tidak diberikan otomatis.');
        $this->assertSame(1, DB::table('ms_user_akses')->where('id_user', 55)->where('id_menu', $daftar)->count());
        $this->assertSame(0, (int) DB::table('ms_menu')->where('id', $oldMenu)->value('aktif'), 'Menu lama dinonaktifkan, bukan dihapus.');
        $this->assertSame(2, DB::table('ms_group_akses')->where('id_menu', $oldMenu)->count(), 'Baris hak menu lama tetap utuh.');

        // Dijalankan lagi: tidak mengubah apa pun, dan refund baru yang menunggu approval tidak ikut dipetakan.
        DB::table('sales_order_refund')->where('id', $rf->id)->update(['status' => 'menunggu']);
        $before = [DB::table('ms_group_akses')->count(), DB::table('ms_menu')->count(), DB::table('ms_user_akses')->count()];
        $migration->up();
        $this->assertSame('menunggu', DB::table('sales_order_refund')->where('id', $rf->id)->value('status'), 'Migrasi ulang tidak boleh memetakan status lagi.');
        $this->assertSame($before, [DB::table('ms_group_akses')->count(), DB::table('ms_menu')->count(), DB::table('ms_user_akses')->count()]);
    }
}

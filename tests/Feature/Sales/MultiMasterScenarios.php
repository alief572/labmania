<?php

namespace Tests\Feature\Sales;

use App\Support\Costing;
use App\Support\Reschedule;
use App\Support\Sales;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Beberapa Master Program dalam satu Penjualan Program (PJ-FR-32, [JMR-1], [JMR-2], [JUM-2]). Memakai seed Phase3. */
trait MultiMasterScenarios
{
    private function multiSecondMaster(int $days = 3): int
    {
        $bidang = DB::table('ms_jenis_bidang')->insertGetId(['kode' => 'X-B2', 'nama' => 'Lingkungan', 'aktif' => 1, 'created_date' => now()]);
        $product = DB::table('paket_produk')->insertGetId(['nomor' => 'PC-DUA', 'judul' => 'Program Kedua', 'id_jenis_produk' => 1, 'id_jenis_bidang' => $bidang, 'id_jenis_sertifikasi' => 1, 'id_varian_pelaksanaan' => 1, 'aktif' => 1]);
        $master = DB::table('master_program')->insertGetId(['nomor' => 'MP-DUA', 'status' => 'final', 'durasi_hari' => $days, 'jam_pembelajaran' => 24, 'target_peserta' => 10, 'harga_jual_peserta' => 2000000]);
        DB::table('master_program_produk')->insert(['id_master_program' => $master, 'id_paket_produk' => $product, 'urutan' => 1]);

        return $master;
    }

    private function multiRow(int $master, array $over = []): array
    {
        return $over + ['id_master_program' => $master, 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee, 'tgl_mulai' => '2026-12-20', 'tgl_selesai' => '2026-12-21',
            'jam_pembelajaran' => 16, 'harga_jual_peserta' => '1.000.000', 'target_profit' => 0,
            'biaya' => [['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Sewa', 'id_satuan' => $this->unit, 'harga' => 100000, 'is_manual' => true]]];
    }

    private function multiPayload(array $masters, array $over = []): array
    {
        return $over + ['jenis' => 'public', 'id_customer' => null, 'waktu' => '08.00', 'lokasi' => 'Lokasi uji', 'target_peserta' => 10, 'masters' => $masters];
    }

    public function test_dua_master_program_menyimpan_baris_biaya_dan_agregat_header(): void
    {
        $this->login($this->admin());
        $second = $this->multiSecondMaster();
        $this->post('/penjualan-program', $this->multiPayload([
            $this->multiRow($this->master),
            $this->multiRow($second, ['tgl_mulai' => '2026-12-28', 'tgl_selesai' => '2026-12-30', 'jam_pembelajaran' => 24, 'harga_jual_peserta' => '2.000.000', 'target_profit' => 500000]),
        ]))->assertSessionHasNoErrors();

        $p = DB::table('penjualan_program')->first();
        $this->assertSame('2026-12-20', $p->tgl_mulai, 'Tanggal program = mulai paling awal');
        $this->assertSame('2026-12-30', $p->tgl_selesai, 'Tanggal program = selesai paling akhir');
        $this->assertSame(11, (int) $p->durasi_hari, 'Durasi program = rentang tanggal');
        $this->assertSame(40, (int) $p->jam_pembelajaran, 'JP program = jumlah JP');
        $this->assertSame(3000000, (int) $p->harga_jual_peserta, 'Harga program = jumlah harga');
        $this->assertSame(500000, (int) $p->target_profit);
        $this->assertSame($this->master, (int) $p->id_master_program, 'Header menyimpan Master Program pertama');

        $rows = DB::table('penjualan_program_master')->where('id_penjualan', $p->id)->orderBy('urutan')->get();
        $this->assertCount(2, $rows);
        $this->assertSame([2, 3], $rows->pluck('durasi_hari')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(2, DB::table('penjualan_program_biaya')->where('id_penjualan', $p->id)->count());
        foreach ($rows as $r) {
            $this->assertSame(1, DB::table('penjualan_program_biaya')->where('id_penjualan_master', $r->id)->count(), 'Tiap biaya terikat pada Master Program-nya');
        }
        $this->get('/penjualan-program/'.$p->id)->assertInertia(fn ($page) => $page->component('Sales/Form')->has('program.masters', 2)->where('program.masters.1.judul', fn ($v) => str_contains($v, 'Program Kedua')));
    }

    public function test_biaya_per_hari_memakai_durasi_tiap_master_program(): void
    {
        $this->login($this->admin());
        $second = $this->multiSecondMaster();
        $day = (int) (DB::table('ms_satuan')->where('nama', 'Day')->value('id') ?: DB::table('ms_satuan')->insertGetId(['category' => 'rate_fixed', 'code' => 'day', 'nama' => 'Day', 'deleted' => '0']));
        $perDay = fn () => [['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Konsumsi', 'id_satuan' => $day, 'harga' => 100000, 'is_manual' => true]];
        $this->post('/penjualan-program', $this->multiPayload([
            $this->multiRow($this->master, ['biaya' => $perDay()]),
            $this->multiRow($second, ['tgl_mulai' => '2026-12-28', 'tgl_selesai' => '2026-12-30', 'biaya' => $perDay()]),
        ]))->assertSessionHasNoErrors();

        $p = DB::table('penjualan_program')->first();
        $multi = Sales::costing(Sales::masters($p), 10);
        // 100.000 x 2 hari + 100.000 x 3 hari = 500.000; bukan 100.000 x 11 hari (durasi header) x 2 item.
        $this->assertEquals(500000, $multi['total_fixed']);
        $wrong = Costing::calculate($p->durasi_hari, 10, $p->harga_jual_peserta, Sales::costs($p->id));
        $this->assertNotEquals($wrong['total_fixed'], $multi['total_fixed'], 'Durasi header tidak boleh dipakai untuk biaya per hari');
    }

    public function test_satu_master_program_sama_dengan_costing_lama(): void
    {
        $this->login($this->admin());
        $this->post('/penjualan-program', $this->multiPayload([$this->multiRow($this->master)]))->assertSessionHasNoErrors();
        $p = DB::table('penjualan_program')->first();
        $new = Sales::costing(Sales::masters($p), 10);
        $old = Costing::calculate($p->durasi_hari, 10, $p->harga_jual_peserta, Sales::costs($p->id));
        foreach (['total_fixed', 'total_var', 'total_hpp', 'pendapatan', 'profit', 'bep_peserta'] as $k) {
            $this->assertEquals($old[$k], $new[$k], $k);
        }
    }

    public function test_master_program_sama_dua_kali_ditolak_dan_jadwal_wajib_per_baris(): void
    {
        $this->login($this->admin());
        $this->post('/penjualan-program', $this->multiPayload([$this->multiRow($this->master), $this->multiRow($this->master)]))->assertSessionHasErrors('masters');
        $this->assertSame(0, DB::table('penjualan_program')->count());

        $second = $this->multiSecondMaster();
        $incomplete = $this->multiRow($second);
        unset($incomplete['tgl_mulai']);
        $this->post('/penjualan-program', $this->multiPayload([$this->multiRow($this->master), $incomplete]))->assertSessionHasErrors('masters.1.tgl_mulai');
        $this->assertSame(0, DB::table('penjualan_program')->count());
    }

    public function test_edit_menambah_dan_menghapus_master_program_dan_finalkan_memvalidasi_semua(): void
    {
        $this->login($this->admin());
        $second = $this->multiSecondMaster();
        $this->post('/penjualan-program', $this->multiPayload([$this->multiRow($this->master)]))->assertSessionHasNoErrors();
        $id = (int) DB::table('penjualan_program')->value('id');

        $this->put('/penjualan-program/'.$id, $this->multiPayload([$this->multiRow($this->master), $this->multiRow($second, ['tgl_mulai' => '2026-12-28', 'tgl_selesai' => '2026-12-29'])]))->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('penjualan_program_master')->where('id_penjualan', $id)->count());

        $this->put('/penjualan-program/'.$id, $this->multiPayload([$this->multiRow($second, ['tgl_mulai' => '2026-12-28', 'tgl_selesai' => '2026-12-29'])]))->assertSessionHasNoErrors();
        $rows = DB::table('penjualan_program_master')->where('id_penjualan', $id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame($second, (int) DB::table('penjualan_program')->where('id', $id)->value('id_master_program'), 'Header mengikuti Master Program pertama yang tersisa');
        $this->assertSame(1, DB::table('penjualan_program_biaya')->where('id_penjualan', $id)->count(), 'Biaya Master Program yang dihapus ikut hilang');

        $this->post('/penjualan-program/'.$id.'/finalkan')->assertSessionHasNoErrors();
        $this->assertSame('final', Sales::program($id)->status);
        $this->put('/penjualan-program/'.$id, $this->multiPayload([$this->multiRow($second)]))->assertSessionHasErrors('program');
    }

    public function test_data_lama_tanpa_baris_master_dibaca_dari_header(): void
    {
        $this->login($this->admin());
        $this->post('/penjualan-program', $this->multiPayload([$this->multiRow($this->master)]))->assertSessionHasNoErrors();
        $id = (int) DB::table('penjualan_program')->value('id');
        DB::table('penjualan_program_master')->where('id_penjualan', $id)->delete();
        DB::table('penjualan_program_biaya')->where('id_penjualan', $id)->update(['id_penjualan_master' => null]);

        $masters = Sales::masters(Sales::program($id));
        $this->assertCount(1, $masters);
        $this->assertSame($this->master, (int) $masters[0]['id_master_program']);
        $this->assertCount(1, $masters[0]['biaya']);

        // Migrasi menyalin header menjadi satu Master Program dan menghubungkan biayanya.
        (require base_path('database/migrations/2026_11_19_110000_penjualan_multi_master.php'))->up();
        $this->assertSame(1, DB::table('penjualan_program_master')->where('id_penjualan', $id)->count());
        $this->assertNotNull(DB::table('penjualan_program_biaya')->where('id_penjualan', $id)->value('id_penjualan_master'));
    }

    private function finalMulti(): array
    {
        $second = $this->multiSecondMaster();
        $this->post('/penjualan-program', $this->multiPayload([
            $this->multiRow($this->master, ['tgl_mulai' => '2026-12-20', 'tgl_selesai' => '2026-12-21']),
            $this->multiRow($second, ['tgl_mulai' => '2027-02-10', 'tgl_selesai' => '2027-02-12']),
        ]))->assertSessionHasNoErrors();
        $id = (int) DB::table('penjualan_program')->value('id');
        $this->post('/penjualan-program/'.$id.'/finalkan')->assertSessionHasNoErrors();

        return [$id, $second];
    }

    private function multiReschedule(int $id, array $masters, string $reason = 'Customer minta ubah jadwal')
    {
        $p = Sales::program($id);

        return $this->post('/penjualan-program/'.$id.'/reschedule', ['request_id' => (string) Str::uuid(), 'version' => Reschedule::version($p),
            'masters' => $masters, 'alasan' => $reason, 'evidence' => [UploadedFile::fake()->image('bukti.png')]]);
    }

    public function test_reschedule_mengubah_satu_master_program_dan_menghitung_ulang_jadwal_program(): void
    {
        $this->login($this->admin());
        [$id, $second] = $this->finalMulti();
        $this->multiReschedule($id, [['id_master_program' => $second, 'tgl_mulai' => '2027-03-01', 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee]])->assertSessionHasNoErrors();

        $rows = DB::table('penjualan_program_master')->where('id_penjualan', $id)->orderBy('urutan')->get();
        $this->assertSame('2026-12-20', $rows[0]->tgl_mulai, 'Master Program yang tidak diubah tetap');
        $this->assertSame(['2027-03-01', '2027-03-03'], [$rows[1]->tgl_mulai, $rows[1]->tgl_selesai], 'Selesai = mulai + durasi - 1');
        $p = Sales::program($id);
        $this->assertSame(['2026-12-20', '2027-03-03'], [$p->tgl_mulai, $p->tgl_selesai], 'Jadwal program dihitung ulang dari semua Master Program');
        $this->assertSame(1, (int) $p->revisi, 'Tanggal berubah = satu putaran revisi');
        $detail = DB::table('penjualan_program_reschedule_detail')->get();
        $this->assertCount(1, $detail, 'Riwayat hanya mencatat Master Program yang berubah');
        $this->assertSame([$second, '2027-02-10', '2027-03-01'], [(int) $detail[0]->id_master_program, $detail[0]->tgl_mulai_lama, $detail[0]->tgl_mulai_baru]);
    }

    public function test_reschedule_beberapa_master_program_sekaligus_satu_putaran_revisi(): void
    {
        $this->login($this->admin());
        [$id, $second] = $this->finalMulti();
        $this->multiReschedule($id, [
            ['id_master_program' => $this->master, 'tgl_mulai' => '2026-12-27', 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee],
            ['id_master_program' => $second, 'tgl_mulai' => '2027-02-17', 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('penjualan_program_reschedule')->count(), 'Satu reschedule');
        $this->assertSame(2, DB::table('penjualan_program_reschedule_detail')->count(), 'Dua Master Program tercatat');
        $this->assertSame(1, (int) Sales::program($id)->revisi, 'Satu putaran revisi, bukan dua');
    }

    public function test_master_program_yang_lewat_h7_terkunci_dan_tanpa_perubahan_ditolak(): void
    {
        $this->login($this->admin());
        [$id, $second] = $this->finalMulti();
        $this->travelTo(now()->setDate(2026, 12, 15)->startOfDay());
        $this->assertNull(Reschedule::reason(Sales::program($id)), 'Aksi tetap tersedia selama masih ada Master Program yang terbuka');

        $this->multiReschedule($id, [['id_master_program' => $this->master, 'tgl_mulai' => '2026-12-30', 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee]])->assertSessionHasErrors('reschedule');
        $this->multiReschedule($id, [['id_master_program' => $second, 'tgl_mulai' => '2027-02-10', 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee]])->assertSessionHasErrors('reschedule');
        $this->multiReschedule($id, [['id_master_program' => $second, 'tgl_mulai' => '2027-02-24', 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee]])->assertSessionHasNoErrors();
        $this->assertSame('2026-12-20', DB::table('penjualan_program_master')->where('id_penjualan', $id)->where('urutan', 1)->value('tgl_mulai'));

        $this->travelTo(now()->setDate(2027, 2, 20)->startOfDay());
        $this->assertNotNull(Reschedule::reason(Sales::program($id)));
    }

    public function test_daftar_memuat_master_program_per_penjualan_dan_surat_tugas_berisi_tim(): void
    {
        $this->login($this->admin());
        [$id, $second] = $this->finalMulti();

        $this->get('/penjualan-program')->assertOk()->assertInertia(fn ($page) => $page->component('Sales/Index'));
        $rows = $this->getJson('/penjualan-program/data?draw=1&start=0&length=10')->assertOk()->json('data');
        $this->assertCount(2, $rows[0]['masters'], 'Kolom Pelaksanaan merinci tiap Master Program');
        $this->assertSame(['MP-UJI', 'MP-DUA'], array_column($rows[0]['masters'], 'nomor'));

        $snapshot = \App\Support\SalesOrder::programSnapshot(Sales::program($id));
        $this->assertCount(2, $snapshot['tim'], 'Surat Tugas menyimpan tim tiap Master Program');
        $this->assertSame($second, $snapshot['tim'][1]['id_master_program']);
        $single = $this->multiPayload([$this->multiRow($this->master)]);
        $this->post('/penjualan-program', $single)->assertSessionHasNoErrors();
        $oneId = (int) DB::table('penjualan_program')->orderByDesc('id')->value('id');
        $this->assertArrayNotHasKey('tim', \App\Support\SalesOrder::programSnapshot(Sales::program($oneId)), 'Satu Master Program: snapshot tidak berubah');
    }

    public function test_penawaran_program_mencantumkan_terdiri_dari_untuk_beberapa_master_program(): void
    {
        $this->login($this->admin());
        [$id] = $this->finalMulti();
        $this->get('/penjualan-program/'.$id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $html = view('pdf.program-offer', ['p' => Sales::program($id), 'title' => 'Judul', 'company' => null, 'trainer' => 'T',
            'parts' => [['judul' => 'Program A', 'tgl_mulai' => '2026-12-20', 'tgl_selesai' => '2026-12-21', 'jam_pembelajaran' => 16, 'trainer' => 'Budi']]])->render();
        $this->assertStringContainsString('Terdiri dari', $html);
        $this->assertStringContainsString('Program A', $html);
        $single = view('pdf.program-offer', ['p' => Sales::program($id), 'title' => 'Judul', 'company' => null, 'trainer' => 'Budi', 'parts' => []])->render();
        $this->assertStringNotContainsString('Terdiri dari', $single);
    }

    public function test_costing_daftar_dan_dashboard_memakai_durasi_tiap_master_program(): void
    {
        $this->login($this->admin());
        $second = $this->multiSecondMaster();
        $day = (int) (DB::table('ms_satuan')->where('nama', 'Day')->value('id') ?: DB::table('ms_satuan')->insertGetId(['category' => 'rate_fixed', 'code' => 'day', 'nama' => 'Day', 'deleted' => '0']));
        $perDay = fn () => [['id_rate' => null, 'tipe' => 'fixed', 'item' => 'Konsumsi', 'id_satuan' => $day, 'harga' => 100000, 'is_manual' => true]];
        $this->post('/penjualan-program', $this->multiPayload([
            $this->multiRow($this->master, ['tgl_mulai' => '2026-12-20', 'tgl_selesai' => '2026-12-21', 'biaya' => $perDay()]),
            $this->multiRow($second, ['tgl_mulai' => '2027-02-10', 'tgl_selesai' => '2027-02-12', 'biaya' => $perDay()]),
        ]))->assertSessionHasNoErrors();
        $p = DB::table('penjualan_program')->first();
        $this->assertGreaterThan(50, (int) $p->durasi_hari, 'Rentang program jauh lebih panjang dari jumlah hari Master Program');

        $costs = Sales::costsFor([$p->id])->get($p->id)->map(fn ($b) => (array) $b)->all();
        $row = Sales::costingOf($p, $costs, Sales::durationsFor([$p->id]));
        $this->assertEquals(500000, $row['total_fixed'], '100.000 x 2 hari + 100.000 x 3 hari');
        $this->assertEquals(Sales::costing(Sales::masters($p), 10)['total_hpp'], $row['total_hpp'], 'Sama dengan costing form');

        $list = $this->getJson('/penjualan-program/data?draw=1&start=0&length=10')->assertOk()->json('data.0');
        $this->assertEquals(500000, $list['costing']['total_fixed']);
    }
}

<?php

namespace Tests\Feature\Sales;

use Illuminate\Support\Facades\DB;

trait OperationalDataTableScenarios
{
    public function test_quotation_participants_table_scopes_quotation_before_pagination(): void
    {
        if (! DB::table('ms_menu')->where('kode', 'quotation_program')->exists()) {
            $menu = DB::table('ms_menu')->insertGetId(['kode' => 'quotation_program', 'judul' => 'Quotation', 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 99, 'aktif' => 1, 'sistem' => 0]);
            DB::table('ms_menu_aksi')->insert(['id_menu' => $menu, 'aksi' => 'lihat']);
        }
        $quote = (array) DB::table('quotation')->where('id', DB::table('quotation_peserta')->where('id', $this->person6)->value('id_quotation'))->first();
        $id = $quote['id'];
        unset($quote['id']);
        $other = DB::table('quotation')->insertGetId(array_replace($quote, ['nomor' => 'QTN-OTHER']));
        DB::table('quotation_peserta')->insert(['id_quotation' => $other, 'nama' => 'TABLE-OTHER', 'status' => 'deal', 'sumber' => 'link']);
        for ($i = 1; $i <= 12; $i++) {
            DB::table('quotation_peserta')->insert(['id_quotation' => $id, 'nama' => sprintf('TABLE-%02d', $i), 'status' => $i === 12 ? 'batal' : 'menunggu', 'sumber' => 'link']);
        }
        $path = '/quotation/batch/'.$this->program6.'/data?data=1&id_quotation='.$id.'&peserta_status=all&length=10&order[0][column]=1&order[0][dir]=asc';
        $this->getJson($path.'&search[value]=TABLE-')->assertOk()->assertJsonPath('recordsTotal', 13)->assertJsonPath('recordsFiltered', 12)->assertJsonCount(10, 'data')->assertJsonPath('data.0.nama', 'TABLE-01');
        $this->getJson($path.'&search[value]=TABLE-&start=10')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.1.status', 'batal');
        $this->getJson($path.'&search[value]=OTHER')->assertOk()->assertJsonPath('recordsFiltered', 0);
        $this->getJson('/quotation/batch/'.$this->program6.'/data?data=1&id_quotation='.$id.'&peserta_status=batal')->assertOk()->assertJsonPath('recordsFiltered', 1);
        $program = (array) DB::table('penjualan_program')->where('id', $this->program6)->first();
        unset($program['id']);
        $otherProgram = DB::table('penjualan_program')->insertGetId(array_replace($program, ['nomor' => 'PJP-OTHER']));
        $this->getJson('/quotation/batch/'.$otherProgram.'/data?data=1&id_quotation='.$id.'&peserta_status=all')->assertOk()->assertJsonPath('recordsTotal', 0);
        $this->getJson($path.'&id_quotation=bad')->assertUnprocessable();
        $this->login6($this->user6('quotation_table_denied'));
        $this->getJson($path)->assertForbidden();
    }

    public function test_operational_tables_page_search_and_order_before_enrichment(): void
    {
        if (! DB::table('ms_menu')->where('kode', 'quotation_program')->exists()) {
            $id = DB::table('ms_menu')->insertGetId(['kode' => 'quotation_program', 'judul' => 'Quotation', 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 99, 'aktif' => 1, 'sistem' => 0]);
            DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => 'lihat']);
        }
        $program = (array) DB::table('penjualan_program')->where('id', $this->program6)->first();
        unset($program['id']);
        for ($i = 1; $i <= 31; $i++) {
            DB::table('penjualan_program')->insert(array_replace($program, ['nomor' => sprintf('TABLE-%03d', $i)]));
        }
        $path = '/pengajuan-biaya/data?draw=3&length=10&order[0][column]=1&order[0][dir]=asc&search[value]=TABLE-';
        $first = $this->getJson($path.'&start=0')->assertOk()->assertJsonPath('recordsFiltered', 31)->assertJsonCount(10, 'data')->json('data');
        $second = $this->getJson($path.'&start=10')->assertOk()->assertJsonPath('data.0.nomor', 'TABLE-011')->json('data');
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->getJson($path.'&start=7')->assertOk()->assertJsonPath('data.0.nomor', 'TABLE-008');
        $this->getJson('/pengajuan-biaya/data?search[value]=TABLE-030&length=10')->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nomor', 'TABLE-030');
        $counts = [];
        foreach ([10, 25] as $length) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/quotation/data?length='.$length)->assertOk();
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        $this->assertSame($counts[0], $counts[1], 'Query ringkasan dan enrichment Quotation tidak boleh bertambah per baris.');
    }

    public function test_participant_tabs_count_closed_only_and_expose_searchable_master_code(): void
    {
        DB::table('penjualan_program')->where('id', $this->program6)->update(['tgl_selesai' => '2020-01-01']);
        $this->getJson('/manage-peserta/data')->assertOk()->assertJsonPath('meta.counts.ongoing', 1)->assertJsonPath('meta.counts.selesai', 0)->assertJsonCount(1, 'data');
        $this->getJson('/manage-peserta/'.$this->program6.'/data')->assertOk()->assertJsonStructure(['data' => [['kode', 'nama']]]);
        $this->getJson('/manage-peserta/monitoring/data')->assertOk()->assertJsonStructure(['meta' => ['years']]);
        DB::table('penjualan_program')->where('id', $this->program6)->update(['closed_date' => now()]);
        $this->getJson('/manage-peserta/data?tab=selesai')->assertOk()->assertJsonPath('meta.counts.ongoing', 0)->assertJsonPath('meta.counts.selesai', 1)->assertJsonCount(1, 'data');
    }

    public function test_approval_counts_ignore_search_and_self_approval_is_hidden(): void
    {
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6())->assertSessionHasNoErrors();
        $this->getJson('/pengajuan-biaya/approval/data')->assertOk()->assertJsonPath('meta.counts.pengajuan', 1)
            ->assertJsonPath('data.0.canApprove', false)->assertJsonStructure(['data' => [['nomor', 'request_by', 'jenis', 'judul', 'jenis_program', 'deskripsi']]]);
        $this->getJson('/pengajuan-biaya/approval/data?q=no-such-request')->assertOk()->assertJsonPath('recordsFiltered', 0)->assertJsonPath('meta.counts.pengajuan', 1);
        $this->get('/pengajuan-biaya/program/'.$this->program6)->assertOk()->assertInertia(fn ($p) => $p->has('budgets.fixed')->has('budgets.variable'));
    }

    public function test_operational_sidebar_badges_follow_list_scope_and_view_permission(): void
    {
        $codes = ['invoicing', 'manage_peserta', 'monitoring_peserta', 'pengajuan_biaya', 'approval_pengajuan_biaya', 'refund_daftar', 'refund_approval'];
        foreach ($codes as $code) {
            $url = collect(config('navigation'))->firstWhere('menu', $code)['url'];
            DB::table('ms_menu')->where('kode', $code)->update(['parent_id' => 0, 'link' => $url]);
        }
        $permissions = array_fill_keys($codes, ['View']);
        $this->post('/pengajuan-biaya/program/'.$this->program6.'/pengajuan', $this->input6())->assertSessionHasNoErrors();
        $items = collect(\App\Support\Navigation::items($permissions))->keyBy('menu');
        $this->assertSame(0, $items['invoicing']['count']);
        $this->assertSame(1, $items['manage_peserta']['count']);
        $this->assertSame(1, $items['monitoring_peserta']['count']);
        $this->assertSame(1, $items['pengajuan_biaya']['count']);
        $this->assertSame(1, $items['approval_pengajuan_biaya']['count']);
        $this->assertSame(0, $items['refund_daftar']['count']);
        $this->assertSame(0, $items['refund_approval']['count']);
        $this->login6($this->user6('badge_unrelated'));
        $items = collect(\App\Support\Navigation::items($permissions))->keyBy('menu');
        $this->assertSame(0, $items['pengajuan_biaya']['count']);
        $this->assertSame(0, $items['approval_pengajuan_biaya']['count']);
        $this->assertSame([], \App\Support\Navigation::items([]));
    }

    public function test_operational_datatables_contract_and_permissions(): void
    {
        foreach (['penjualan_program', 'quotation_program', 'refund_daftar', 'refund_approval'] as $code) {
            if (! DB::table('ms_menu')->where('kode', $code)->exists()) {
                $id = DB::table('ms_menu')->insertGetId(['kode' => $code, 'judul' => $code, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 99, 'aktif' => 1, 'sistem' => 0]);
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => 'lihat']);
            }
        }
        $paths = ['/program/data', '/penjualan-program/data', '/quotation/data', '/sales-order/data', '/sales-order/data?tab=surat',
            '/invoicing/data', '/invoicing/data?tab=invoiced', '/manage-peserta/data', '/manage-peserta/monitoring/data',
            '/manage-peserta/'.$this->program6.'/data', '/pengajuan-biaya/data', '/pengajuan-biaya/data?tab=pengajuan',
            '/pengajuan-biaya/approval/data', '/pengajuan-biaya/approval/data?tab=overbudget', '/refund/data', '/refund/approval/data', '/activity-log/data', '/quotation/batch/'.$this->program6.'/data', '/quotation/batch/'.$this->program6.'/data?data=1', '/sales-order/program/'.$this->program6.'/data', '/pengajuan-biaya/program/'.$this->program6.'/data?kategori=fixed&kind=pengajuan', '/pengajuan-biaya/program/'.$this->program6.'/data?kategori=variable&kind=overbudget'];
        foreach ($paths as $path) {
            $url = $path.(str_contains($path, '?') ? '&' : '?').'draw=7&start=0&length=10';
            $response = $this->getJson($url);
            $this->assertSame(200, $response->status(), $path.' '.$response->getContent());
            $response->assertJsonPath('draw', 7)->assertJsonStructure(['recordsTotal', 'recordsFiltered', 'data']);
            $this->assertLessThanOrEqual(10, count($response->json('data')), $path);
            $this->assertGreaterThanOrEqual($response->json('recordsFiltered'), $response->json('recordsTotal'), $path);
            $this->getJson($url.'&search[value]=unmatched-no-record-123')->assertOk()->assertJsonPath('recordsFiltered', 0);
            $this->getJson($url.'&length=-1')->assertUnprocessable();
            $this->getJson($url.'&order[0][column]=999&order[0][dir]=asc')->assertUnprocessable();
        }
        $this->login6($this->user6('table_no_access'));
        foreach ($paths as $path) {
            $this->getJson($path)->assertForbidden();
        }
    }
}

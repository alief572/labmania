<?php

namespace Tests\Feature\Master;

use App\Support\Navigation;
use App\Support\SidebarToBe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait SidebarAllScenarios
{
    use SidebarMigrationScenarios;

    private function prepareAll(): void
    {
        $this->migrationFixture();
        DB::table('ms_menu')->where('kode', 'sales_order_refund')->update(['aktif' => 0]);
        DB::table('ms_menu')->insert(['kode' => 'dashboard', 'judul' => 'Dashboard', 'link' => '/', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 1, 'aktif' => 1, 'sistem' => 0]);
        Auth::setUser($this->admin());
    }

    public function test_all_modules_preserve_ids_grants_status_and_are_idempotent(): void
    {
        $this->prepareAll();
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        $protected = DB::table('ms_menu')->orderBy('id')->get(['id', 'kode', 'link', 'aktif'])->toJson();
        $grants = [];
        foreach (['ms_menu_aksi', 'ms_group_akses', 'ms_user_akses'] as $table) {
            $grants[$table] = DB::table($table)->orderBy('id_menu')->orderBy('aksi')->get()->toJson();
        }
        $this->assertNotEmpty(SidebarToBe::syncAll());
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
        $this->assertSame(0, DB::table('logs')->count());
        SidebarToBe::syncAll(true);
        $this->assertSame($protected, DB::table('ms_menu')->whereIn('id', array_column(json_decode($protected, true), 'id'))->orderBy('id')->get(['id', 'kode', 'link', 'aktif'])->toJson());
        foreach ($grants as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id_menu')->orderBy('aksi')->get()->toJson());
        }
        $expected = ['Dashboard', 'Master Data', 'Penjualan Program', 'Quotation', 'Invoice', 'Refund', 'Manage Peserta', 'Pengajuan Biaya', 'Activity Log', 'Pengaturan'];
        $this->assertSame($expected, DB::table('ms_menu')->where('parent_id', 0)->where('aktif', 1)->orderBy('urutan')->pluck('judul')->all());
        $items = Navigation::items(['refund_daftar' => ['View'], 'refund_approval' => ['View'], 'sales_order' => ['View']]);
        $this->assertSame(['Quotation', 'Refund', 'Refund'], array_column($items, 'group'));
        $this->assertSame(['', '', ''], array_column($items, 'subgroupLabel'));
        $created = json_decode(DB::table('logs')->where('ref_no', 'm_invoice')->value('data'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNull($created['old']);
        $stored = (array) DB::table('ms_menu')->where('kode', 'm_invoice')->first();
        $logged = $created['new'];
        ksort($stored);
        ksort($logged);
        $this->assertSame($stored, $logged);
        $this->assertSame((int) Auth::id(), (int) $created['new']['created_by']);
        $count = DB::table('logs')->count();
        $this->assertSame([], SidebarToBe::syncAll(true));
        $this->assertSame($count, DB::table('logs')->count());
    }

    public function test_missing_menu_and_write_failure_leave_no_partial_updates(): void
    {
        $this->prepareAll();
        DB::unprepared(DB::getDriverName() === 'sqlite'
            ? "CREATE TRIGGER fail_all BEFORE UPDATE ON ms_menu WHEN NEW.kode = 'activity_log' BEGIN SELECT RAISE(ABORT, 'uji gagal tulis'); END"
            : "ALTER TABLE ms_menu ADD CONSTRAINT fail_all CHECK (kode <> 'activity_log' OR judul <> 'Activity Log')");
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        try {
            SidebarToBe::syncAll(true);
            $this->fail('Kesalahan tulis harus membatalkan transaksi.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
            $this->assertSame(0, DB::table('logs')->count());
        }
        DB::table('ms_menu')->where('kode', 'refund_approval')->delete();
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        try {
            SidebarToBe::syncAll(true);
            $this->fail('Menu wajib hilang harus ditolak.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('refund_approval', $e->getMessage());
            $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
        }
    }

    public function test_command_requires_valid_actor_and_keeps_read_only_default(): void
    {
        $this->prepareAll();
        $this->artisan('labmania:sidebar-to-be', ['--semua-modul' => true])->assertSuccessful();
        $this->artisan('labmania:sidebar-to-be', ['--semua-modul' => true, '--terapkan' => true])->assertFailed();
        $this->artisan('labmania:sidebar-to-be', ['--semua-modul' => true, '--terapkan' => true, '--aktor' => Auth::id()])->assertSuccessful();
    }
}

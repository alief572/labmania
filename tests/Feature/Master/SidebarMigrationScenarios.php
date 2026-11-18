<?php

namespace Tests\Feature\Master;

use App\Support\Navigation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait SidebarMigrationScenarios
{
    private function migrationFixture(): void
    {
        foreach (['m_master_data', 'm_penjualan', 'm_quotation', 'm_pengaturan'] as $code) {
            DB::table('ms_menu')->updateOrInsert(['kode' => $code], ['judul' => $code, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 1, 'aktif' => 1, 'sistem' => 0]);
        }
        foreach (collect(config('navigation'))->filter(fn ($item) => $item['menu'])->unique('menu') as $item) {
            DB::table('ms_menu')->updateOrInsert(['kode' => $item['menu']], ['judul' => 'Lama', 'link' => $item['url'], 'parent_id' => 0, 'kelompok' => 8, 'urutan' => 100, 'aktif' => 1, 'sistem' => 0]);
        }
        // Migrasi sidebar 16-11-2026 masih mengacu menu lama sales_order_refund (sudah dijalankan di instalasi nyata sebelum modul
        // Refund dipisah). Fixture memuatnya agar migrasi lama tetap teruji; navigasi baru memakai refund_daftar dan refund_approval.
        DB::table('ms_menu')->updateOrInsert(['kode' => 'sales_order_refund'], ['judul' => 'Lama', 'link' => '/sales-order/refund', 'parent_id' => 0, 'kelompok' => 8, 'urutan' => 100, 'aktif' => 1, 'sistem' => 0]);
        $id = DB::table('ms_menu')->where('kode', 'master_data.rate')->value('id');
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => $id, 'aksi' => 'lihat']);
        DB::table('ms_user_akses')->insert(['id_user' => 97, 'id_menu' => $id, 'aksi' => 'ubah']);
        DB::table('ms_menu')->where('kode', 'master_trainer')->update(['aktif' => 0]);
        $quotation = DB::table('ms_menu')->where('kode', 'm_quotation')->value('id');
        DB::table('ms_menu')->whereIn('kode', ['refund_daftar', 'refund_approval'])->update(['parent_id' => $quotation, 'kelompok' => 3]);
    }

    private function applySidebarMigration(): void
    {
        (require base_path('database/migrations/2026_11_16_090000_sidebar_ui_to_be.php'))->up();
    }

    public function test_sidebar_migration_updates_only_layout_and_preserves_all_grants(): void
    {
        $this->migrationFixture();
        $before = DB::table('ms_menu')->orderBy('id')->get()->map(fn ($row) => collect($row)->except(['judul', 'parent_id', 'kelompok', 'urutan'])->all())->all();
        $grants = [];
        foreach (['ms_group_akses', 'ms_user_akses', 'ms_menu_aksi'] as $table) {
            $grants[$table] = DB::table($table)->orderBy('id_menu')->orderBy('aksi')->get()->toJson();
        }
        $this->applySidebarMigration();
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->map(fn ($row) => collect($row)->except(['judul', 'parent_id', 'kelompok', 'urutan'])->all())->all());
        foreach ($grants as $table => $content) {
            $this->assertSame($content, DB::table($table)->orderBy('id_menu')->orderBy('aksi')->get()->toJson());
        }
        $master = DB::table('ms_menu')->where('kode', 'm_master_data')->value('id');
        $this->assertDatabaseHas('ms_menu', ['kode' => 'master_data.rate', 'judul' => 'Master Rate', 'parent_id' => $master, 'kelompok' => 1]);
        $this->assertDatabaseHas('ms_menu', ['kode' => 'permission_group', 'parent_id' => $master, 'kelompok' => 2]);
        $this->assertDatabaseHas('ms_menu', ['kode' => 'activity_log', 'parent_id' => 0]);
        $this->assertDatabaseHas('ms_menu', ['kode' => 'menu_labmania', 'parent_id' => DB::table('ms_menu')->where('kode', 'm_pengaturan')->value('id')]);
        $this->assertSame([], Navigation::items(['master_data.rate' => ['Manage']]));
        $items = Navigation::items(['master_data.rate' => ['View'], 'master_program' => ['View'], 'refund_daftar' => ['View']]);
        $this->assertSame(['master_data.rate', 'master_program', 'refund_daftar'], array_column($items, 'menu'));
        // Daftar Refund berada di kelompok 3 "Refund" di bawah Quotation (config/navigation-groups.php), bukan lagi di Sales Order.
        $this->assertSame(['Product', 'Master Program', 'Refund'], array_column($items, 'subgroupLabel'));
        $after = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        $this->applySidebarMigration();
        $this->assertSame($after, DB::table('ms_menu')->orderBy('id')->get()->toJson());
    }

    public function test_sidebar_migration_missing_menu_fails_before_any_update(): void
    {
        $this->migrationFixture();
        DB::table('ms_menu')->where('kode', 'master_data.role')->delete();
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        try {
            $this->applySidebarMigration();
            $this->fail('Menu wajib hilang harus ditolak.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('master_data.role', $error->getMessage());
        }
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
    }

    public function test_sidebar_migration_inactive_parent_fails_before_any_update(): void
    {
        $this->migrationFixture();
        DB::table('ms_menu')->where('kode', 'm_pengaturan')->update(['aktif' => 0]);
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        try {
            $this->applySidebarMigration();
            $this->fail('Induk tidak aktif harus ditolak.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('m_pengaturan', $error->getMessage());
        }
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
    }
}

<?php

namespace Tests\Feature\Master;

use Illuminate\Support\Facades\DB;

class SidebarMigrationTest extends MasterTestCase
{
    use SidebarMigrationScenarios;

    public function test_duplicate_code_is_rejected_without_partial_writes(): void
    {
        $this->migrationFixture();
        $row = (array) DB::table('ms_menu')->where('kode', 'master_data.role')->first();
        unset($row['id']);
        DB::table('ms_menu')->insert($row);
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        try {
            $this->applySidebarMigration();
            $this->fail('Kode duplikat harus ditolak.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('duplikat', $error->getMessage());
        }
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
    }

    public function test_write_failure_rolls_back_earlier_menu_updates(): void
    {
        $this->migrationFixture();
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        DB::unprepared("CREATE TRIGGER fail_sidebar BEFORE UPDATE ON ms_menu WHEN NEW.kode = 'activity_log' BEGIN SELECT RAISE(ABORT, 'uji gagal tulis'); END");
        try {
            $this->applySidebarMigration();
            $this->fail('Kesalahan tulis harus ditolak.');
        } catch (\Illuminate\Database\QueryException $error) {
            $this->assertStringContainsString('uji gagal tulis', $error->getMessage());
        }
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
    }
}

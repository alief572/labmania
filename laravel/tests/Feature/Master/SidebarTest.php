<?php

namespace Tests\Feature\Master;

use App\Support\SidebarToBe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SidebarTest extends MasterTestCase
{
    use SidebarScenarios;

    public function test_command_requires_valid_superadmin_and_preserves_simulation(): void
    {
        $root = $this->sidebarFixture();
        $this->artisan('labmania:sidebar-to-be')->assertSuccessful();
        $this->assertSame(0, DB::table('logs')->count());
        $this->artisan('labmania:sidebar-to-be', ['--terapkan' => true])->assertFailed();
        $staff = $this->account();
        $this->artisan('labmania:sidebar-to-be', ['--terapkan' => true, '--aktor' => $staff->id_user])->assertFailed();
        $this->artisan('labmania:sidebar-to-be', ['--terapkan' => true, '--aktor' => $root->id_user])->assertSuccessful();
        $this->assertSame(15, DB::table('logs')->count());
    }

    public function test_guard_rejects_production_and_source_database(): void
    {
        app()->instance('env', 'production');
        $this->expectExceptionMessage('Ditolak');
        SidebarToBe::sync(true);
    }

    public function test_mid_transaction_failure_rolls_back_menu_grants_and_audit(): void
    {
        $this->sidebarFixture();
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        DB::unprepared("CREATE TRIGGER fail_role BEFORE INSERT ON ms_menu WHEN NEW.kode = 'master_data.role' BEGIN SELECT RAISE(ABORT, 'uji gagal tulis'); END");
        $this->artisan('labmania:sidebar-to-be', ['--terapkan' => true, '--aktor' => Auth::id()])->assertFailed();
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
        $this->assertSame(0, DB::table('logs')->count());
        $this->assertSame(1, DB::table('ms_group_akses')->count());
        $this->assertSame(1, DB::table('ms_user_akses')->count());
    }

    public function test_guard_rejects_legacy_database_even_in_local_environment(): void
    {
        app()->instance('env', 'local');
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'db_labmania', 'database.connections.mysql.host' => '127.0.0.1']);
        $this->expectExceptionMessage('Ditolak');
        SidebarToBe::guard();
    }
}

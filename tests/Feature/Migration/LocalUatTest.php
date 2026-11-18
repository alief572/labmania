<?php

namespace Tests\Feature\Migration;

use App\Models\User;
use App\Support\LocalUat;
use App\Support\Permissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocalUatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite' || DB::getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Fixture UAT hanya SQLite :memory:.');
        }
        $this->withoutVite();
        config(['labmania.local_uat_enabled' => true, 'labmania.activity_log_role_ids' => [3]]);
        foreach (['foundation', 'master'] as $fixture) {
            DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/'.$fixture.'.sql'));
        }
        foreach (['quotation_peserta', 'quotation', 'penjualan_program'] as $table) {
            DB::statement('DROP TABLE '.$table);
        }
        DB::unprepared(file_get_contents(__DIR__.'/../../fixtures/sales_base.sql'));
        foreach (['2026_11_13_130000_penjualan_quotation_to_be', '2026_11_14_090000_sales_order_invoice', '2026_11_14_100000_reschedule_refund', '2026_11_14_110000_operasional_biaya', '2026_11_17_090000_quotation_top'] as $migration) {
            (require base_path('database/migrations/'.$migration.'.php'))->up();
        }
        DB::table('groups')->delete();
        DB::table('ms_menu_aksi')->delete();
        DB::table('ms_menu')->delete();
        DB::statement('CREATE TABLE migrations (id INTEGER PRIMARY KEY, migration TEXT, batch INTEGER)');
        DB::table('migrations')->insert(['migration' => 'schema_only_uat', 'batch' => 1]);
    }

    public function test_bootstrap_is_repeatable_and_keeps_user_changes(): void
    {
        $passwords = LocalUat::bootstrap();
        $this->assertCount(6, $passwords);
        $this->artisan('labmania:uat', ['--check' => true])->assertSuccessful();
        $user = User::where('username', 'admin')->sole();
        $this->assertTrue(password_verify($passwords['admin'], $user->password));
        DB::table('paket_produk')->update(['judul' => 'Diubah saat UAT']);
        $this->assertSame([], LocalUat::bootstrap());
        $this->assertSame($user->password, $user->fresh()->password);
        $this->assertSame('Diubah saat UAT', DB::table('paket_produk')->value('judul'));
        $this->assertSame(6, User::count());
        $this->assertSame(2, DB::table('master_program')->count());
        $this->assertSame(2, DB::table('penjualan_program')->count());
        $this->assertDatabaseHas('penjualan_program', ['jenis' => 'b2b', 'status' => 'final']);
        $this->assertDatabaseHas('logs', ['module' => 'Master Program', 'action' => 'Finalkan']);
    }

    public function test_password_only_login_reaches_dashboard_and_preserves_permissions_and_logout(): void
    {
        config(['labmania.local_uat_password_only' => true]);
        $passwords = LocalUat::bootstrap();
        $user = User::where('username', 'uat_sales')->sole();
        $this->post('/login', ['username' => $user->username, 'password' => $passwords[$user->username]])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('auth.pending_id'));
        $this->assertSame('password', session('auth.mode'));
        $this->assertNull($user->fresh()->ga_secret);
        $this->get('/')->assertOk();
        $this->get('/permissions')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/')->assertRedirect('/login');
    }

    public function test_password_only_rejects_wrong_password_and_inactive_accounts(): void
    {
        config(['labmania.local_uat_password_only' => true]);
        $passwords = LocalUat::bootstrap();
        $this->post('/login', ['username' => 'admin', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->assertGuest();
        User::where('username', 'admin')->update(['st_aktif' => 0]);
        $this->post('/login', ['username' => 'admin', 'password' => $passwords['admin']])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_password_only_session_expires_after_password_change(): void
    {
        config(['labmania.local_uat_password_only' => true]);
        $passwords = LocalUat::bootstrap();
        $login = ['username' => 'admin', 'password' => $passwords['admin']];
        $this->post('/login', $login)->assertRedirect('/');
        User::where('username', 'admin')->update(['password' => password_hash('changed-password', PASSWORD_DEFAULT)]);
        Auth::forgetUser();
        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', array_replace($login, ['password' => 'changed-password']))->assertRedirect('/');
        config(['labmania.local_uat_password_only' => false]);
        $this->get('/')->assertOk();
        $this->assertAuthenticated();
    }

    public function test_guard_and_nonempty_database_reject_before_seed(): void
    {
        $this->artisan('labmania:uat', ['--check' => true])->assertFailed();
        config(['labmania.local_uat_enabled' => false]);
        $this->artisan('labmania:uat')->assertFailed();
        $this->assertSame(0, User::count());
        config(['labmania.local_uat_enabled' => true]);
        app()['env'] = 'production';
        $this->artisan('labmania:uat')->assertFailed();
        app()['env'] = 'testing';
        User::forceCreate(['username' => 'existing', 'st_aktif' => 1]);
        $this->expectException(\RuntimeException::class);
        LocalUat::bootstrap();
    }

    public function test_mysql_guard_checks_host_database_user_url_and_environment_without_connecting(): void
    {
        $original = config('database.default');
        $valid = ['driver' => 'mysql', 'host' => 'uat-db', 'port' => 3306, 'database' => 'labmania_laravel', 'username' => 'labmania_uat', 'password' => '', 'url' => null];
        try {
            app()['env'] = 'local';
            config(['database.default' => 'guardprobe', 'database.connections.guardprobe' => $valid]);
            LocalUat::guard();
            foreach (['host' => '127.0.0.1', 'port' => 3307, 'database' => 'db_labmania', 'username' => 'root', 'url' => 'mysql://elsewhere/db'] as $key => $value) {
                DB::purge('guardprobe');
                config(['database.connections.guardprobe' => array_replace($valid, [$key => $value])]);
                try {
                    LocalUat::guard();
                    $this->fail('Guard menerima '.$key.' yang salah.');
                } catch (\RuntimeException $e) {
                    $this->assertStringContainsString('Perintah ditolak', $e->getMessage());
                }
            }
        } finally {
            DB::purge('guardprobe');
            config(['database.default' => $original]);
            app()['env'] = 'testing';
        }
    }

    public function test_role_grants_are_limited(): void
    {
        LocalUat::bootstrap();
        $sales = User::where('username', 'uat_sales')->sole();
        $this->assertFalse(Permissions::allows($sales, 'master_program', 'Approve'));
        $this->assertFalse(Permissions::allows($sales, 'permission_group', 'Manage'));
        $this->assertTrue(Permissions::allows(User::where('username', 'uat_direktur')->sole(), 'approval_pengajuan_biaya', 'Approve'));
        $this->assertTrue(Permissions::allows(User::where('username', 'uat_manajemen')->sole(), 'activity_log', 'View'));
        $this->assertFalse(Permissions::allows(User::where('username', 'uat_admin')->sole(), 'activity_log', 'View'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Audit;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Test hanya boleh SQLite :memory:.');
        }
        $this->withoutVite();
        DB::unprepared(file_get_contents(__DIR__.'/../fixtures/foundation.sql'));
        DB::table('groups')->insert([
            ['id_group' => 1, 'nm_group' => 'Superadmin', 'ket' => ''],
            ['id_group' => 2, 'nm_group' => 'Sales', 'ket' => ''],
        ]);
        foreach ([1 => ['activity_log', 'Activity Log'], 2 => ['permission_group', 'Role & Permission'], 3 => ['master_program', 'Master Program'], 4 => ['quotation_program', 'Quotation'], 5 => ['master_data.users', 'Master User'], 6 => ['penjualan_program', 'Penjualan Program']] as $id => [$code, $label]) {
            DB::table('ms_menu')->insert(['id' => $id, 'kode' => $code, 'judul' => $label, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => $id, 'aktif' => 1, 'sistem' => 0]);
            foreach (['lihat', 'ubah', 'aktif'] as $action) {
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $action]);
            }
        }
    }

    private function account(array $attributes = []): User
    {
        return User::forceCreate($attributes + ['username' => 'sales', 'nm_lengkap' => 'Sales Uji', 'password' => password_hash('known-password', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
    }

    private function verified(User $user): static
    {
        return $this->actingAs($user)->withSession(['auth.verified_id' => $user->id_user, 'auth.factor' => hash('sha256', $user->password)]);
    }

    private function grant(User $user, int $menu, string $action): void
    {
        DB::table('ms_user_akses')->insert(['id_user' => $user->id_user, 'id_menu' => $menu, 'aksi' => $action]);
    }

    public function test_disabled_and_ambiguous_usernames_are_rejected(): void
    {
        $this->account(['st_aktif' => 0]);
        $this->post('/login', ['username' => 'sales', 'password' => 'known-password'])->assertSessionHasErrors('username');
        $this->account();
        $this->post('/login', ['username' => 'sales', 'password' => 'known-password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_account_without_password_cannot_authenticate_with_dummy_hash_password(): void
    {
        $this->account(['password' => null]);
        $this->post('/login', ['username' => 'sales', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertNull(session('auth.pending_id'));
    }

    public function test_role_and_override_add_rights_without_granting_approve(): void
    {
        $user = $this->account();
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 2]);
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => 3, 'aksi' => 'aktif']);
        $this->assertTrue(Permissions::allows($user, 'master_program', 'Manage'));
        $this->assertFalse(Permissions::allows($user, 'master_program', 'Approve'));
        $this->grant($user, 3, 'approve');
        $this->assertTrue(Permissions::allows($user, 'master_program', 'Approve'));
        $this->assertArrayNotHasKey('ga_secret', $user->toArray());
    }

    public function test_log_and_permission_endpoints_require_server_authorization(): void
    {
        $this->verified($this->account())->get('/activity-log')->assertForbidden();
        $this->get('/permissions')->assertForbidden();
        $this->put('/permissions/roles/2', ['grants' => []])->assertForbidden();
    }

    public function test_superadmin_role_cannot_be_rewritten_and_role_edits_capture_diff(): void
    {
        $user = $this->account();
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 1]);
        $this->verified($user)->put('/permissions/roles/1', ['grants' => []])->assertForbidden();
        $this->put('/permissions/roles/2', ['grants' => ['3:Manage', '3:Approve']])->assertRedirect();
        $this->assertDatabaseHas('ms_group_akses', ['id_group' => 2, 'id_menu' => 3, 'aksi' => 'approve']);
        $data = json_decode(DB::table('logs')->latest('id')->value('data'), true);
        $this->assertArrayHasKey('old', $data);
        $this->assertArrayHasKey('new', $data);
        $this->assertNotEmpty($data['detail_changes']);
        $this->assertSame(['old' => false, 'new' => true], $data['detail_changes']['master_program - approve']);
        $this->put('/permissions/roles/2', ['grants' => ['3:Invented']])->assertSessionHasErrors('grants');
    }

    public function test_audit_redacts_secrets_and_does_not_assign_public_actor(): void
    {
        $user = $this->account();
        $this->verified($user);
        write_log('Master Data', 'Edit', 'Uji audit', ['old' => ['password' => 'old'], 'new' => ['password' => 'new'], 'detail_changes' => ['Token' => ['old' => 'a', 'new' => 'b']]], 'users', $user->id_user, 'uji');
        $log = DB::table('logs')->first();
        $this->assertSame($user->id_user, $log->created_by);
        $this->assertStringNotContainsString('"new":"new"', $log->data);
        $this->assertStringContainsString('[REDACTED]', $log->data);
        Auth::logout();
        $this->expectException(\LogicException::class);
        write_log('Publik', 'Tambah', 'Tanpa aktor');
    }

    public function test_deactivated_account_cannot_reuse_verified_session(): void
    {
        $user = $this->account();
        $this->verified($user);
        $user->forceFill(['st_aktif' => 0])->save();
        $this->get('/')->assertRedirect('/login');
    }

    public function test_audit_keeps_only_substantial_changes_without_losing_decimal_precision(): void
    {
        $data = Audit::normalize([
            'sub_module' => 'Uji',
            'old' => ['updated_date' => 'old', 'harga' => null, 'qty' => '0.00', 'total' => '3744000000.0000', 'biaya' => '9007199254740992.0000', 'nama' => null],
            'new' => ['updated_date' => 'new', 'harga' => 0, 'qty' => 0, 'total' => '3744000000', 'biaya' => '9007199254740992.0001', 'nama' => 0],
            'detail_changes' => [
                'Item - Qty' => ['old' => '', 'new' => '0.0000'],
                'Item - Harga' => ['old' => '100', 'new' => '100.00009'],
                'Item - Total' => ['old' => '100', 'new' => '100.0001'],
                'Item - Nama' => ['old' => 'abc', 'new' => 'def'],
            ],
        ]);
        $this->assertSame(['biaya' => '9007199254740992.0000', 'nama' => null], $data['old']);
        $this->assertSame(['biaya' => '9007199254740992.0001', 'nama' => 0], $data['new']);
        $this->assertSame(['Item - Total', 'Item - Nama'], array_keys($data['detail_changes']));
        $this->assertSame('Uji', $data['sub_module']);
        $huge = Audit::normalize(['old' => ['total' => '1e999999999'], 'new' => ['total' => 0]]);
        $this->assertSame('1e999999999', $huge['old']['total']);
    }

    public function test_failed_login_uses_security_log_without_credentials_or_fake_actor(): void
    {
        Log::shouldReceive('warning')->once()->with('auth.login_failed', ['ip' => '127.0.0.1']);
        $this->post('/login', ['username' => 'unknown', 'password' => 'private-password'])->assertSessionHasErrors('username');
        $this->assertDatabaseCount('logs', 0);
        $this->assertGuest();
    }

    public function test_override_adds_to_role_and_preserves_print_and_inactive_menu_grants(): void
    {
        $admin = $this->account();
        DB::table('user_groups')->insert(['id_user' => $admin->id_user, 'id_group' => 1]);
        $user = $this->account(['username' => 'target']);
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 2]);
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => 3, 'aksi' => 'ubah']);
        $this->grant($user, 4, 'cetak');
        DB::table('ms_menu')->where('id', 1)->update(['aktif' => 0]);
        $this->grant($user, 1, 'lihat');
        $this->verified($admin)->put('/permissions/users/'.$user->id_user, ['grants' => ['3:Manage', '3:Approve']])->assertRedirect();
        $this->assertTrue(Permissions::allows($user, 'master_program', 'Manage'));
        $this->assertTrue(Permissions::allows($user, 'master_program', 'Approve'));
        $this->assertFalse(Permissions::allows($user, 'activity_log', 'View'));
        $this->assertDatabaseHas('ms_user_akses', ['id_user' => $user->id_user, 'id_menu' => 4, 'aksi' => 'cetak']);
        $this->assertDatabaseHas('ms_user_akses', ['id_user' => $user->id_user, 'id_menu' => 1, 'aksi' => 'lihat']);
        $this->assertDatabaseMissing('ms_user_akses', ['id_user' => $user->id_user, 'id_menu' => 3, 'aksi' => 'ubah']);
        $this->put('/permissions/users/'.$user->id_user, ['grants' => []])->assertRedirect();
        $this->assertTrue(Permissions::allows($user, 'master_program', 'Manage'));
        $this->assertFalse(Permissions::allows($user, 'master_program', 'Approve'));
        $this->put('/permissions/users/'.$admin->id_user, ['grants' => []])->assertForbidden();
        $this->put('/permissions/users/'.$user->id_user, ['grants' => ['1:View']])->assertSessionHasErrors('grants');
    }

    public function test_log_filters_accept_end_date_alone_and_redact_historical_payload(): void
    {
        $user = $this->account();
        config(['labmania.activity_log_role_ids' => [2]]);
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 2]);
        $this->grant($user, 1, 'lihat');
        $this->verified($user);
        DB::table('logs')->insert([
            'module' => 'Uji', 'action' => 'Edit', 'description' => 'Uji sejarah', 'created_by' => $user->id_user,
            'created_date' => '2026-11-12 15:00:00', 'ref_table' => 'users', 'ref_id' => $user->id_user, 'ref_no' => 'UJI-1',
            'data' => json_encode(['old' => ['password' => 'secret-old', 'qty' => '0.00'], 'new' => ['password' => 'secret-new', 'qty' => 0]]),
        ]);
        $this->get('/activity-log?to=2026-11-13&ref_no=UJI-1')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ActivityLog')->has('menus')->where('logs.total', 1)->where('logs.data.0.data.old', ['password' => '[REDACTED]'])
            ->where('modules', ['Uji'])->missing('auth.password')->missing('auth.ga_secret'));
        $this->get('/activity-log?from=2026-11-13&to=2026-11-12')->assertSessionHasErrors('to');
        $this->get('/activity-log?ref_no=UNKNOWN')->assertOk()->assertInertia(fn (Assert $page) => $page->where('logs.total', 0));
        $this->get('/activity-log?ref_table=users&ref_id=999')->assertOk()->assertInertia(fn (Assert $page) => $page->where('logs.total', 0));
        $this->get('/activity-log?user=999')->assertOk()->assertInertia(fn (Assert $page) => $page->where('logs.total', 0));
        // Paritas CI3: kata kunci juga mencari username pelaku.
        $this->get('/activity-log?keyword='.$user->username)->assertOk()->assertInertia(fn (Assert $page) => $page->where('logs.total', 1));
        $this->get('/activity-log?keyword=tidak-ada-orang-ini')->assertOk()->assertInertia(fn (Assert $page) => $page->where('logs.total', 0));
    }

    public function test_activity_log_override_cannot_bypass_eligible_roles(): void
    {
        $user = $this->account();
        $this->grant($user, 1, 'lihat');
        $this->verified($user)->get('/activity-log')->assertForbidden();
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 2]);
        $this->get('/activity-log')->assertForbidden();
        config(['labmania.activity_log_role_ids' => [2]]);
        $this->get('/activity-log')->assertOk();
        DB::table('ms_user_akses')->where('id_user', $user->id_user)->delete();
        $this->get('/activity-log')->assertForbidden();
    }

    public static function documentSources(): array
    {
        return [['quotation', 4, 'id_quotation'], ['reschedule', 6, 'id_reschedule']];
    }

    #[DataProvider('documentSources')]
    public function test_private_document_requires_view_and_rejects_paths_outside_storage(string $source, int $menu, string $parent): void
    {
        DB::unprepared(file_get_contents(__DIR__.'/../fixtures/documents.sql'));
        $user = $this->account();
        $root = storage_path('framework/testing/documents-'.bin2hex(random_bytes(8)));
        $definition = config('labmania.documents.'.$source);
        $directory = $root.'/private/'.$definition['prefix'];
        $url = '/documents/'.$source;
        mkdir($directory, 0777, true);
        file_put_contents($directory.'/test.pdf', '%PDF synthetic document');
        file_put_contents($root.'/outside.pdf', '%PDF outside private root');
        config(['labmania.document_root' => $root.'/private', 'labmania.legacy_document_root' => null]);
        try {
            DB::table($definition['table'])->insert([
                'id' => 1, $parent => 42, 'nama_file' => 'uji.pdf', 'path_file' => $definition['prefix'].'test.pdf', 'tipe_file' => 'pdf', 'ukuran_byte' => 23,
            ]);
            $this->get($url.'/1')->assertRedirect('/login');
            $this->verified($user)->get($url.'/1')->assertForbidden();
            $this->grant($user, $menu, 'lihat');
            $this->get($url.'/1')->assertOk()->assertDownload('uji.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertDatabaseHas('logs', ['action' => 'Unduh', 'ref_table' => $definition['table'], 'ref_id' => 1, 'created_by' => $user->id_user]);
            foreach ([$definition['prefix'].'../../../outside.pdf', $root.'/outside.pdf', $definition['prefix'].'missing.pdf', $definition['prefix'].'invalid'.chr(0).'.pdf'] as $path) {
                DB::table($definition['table'])->where('id', 1)->update(['path_file' => $path]);
                $this->get($url.'/1')->assertNotFound();
            }
            $this->get('/documents/unknown/1')->assertNotFound();
            $this->get($url.'/99')->assertNotFound();
        } finally {
            unlink($directory.'/test.pdf');
            unlink($root.'/outside.pdf');
            rmdir($directory);
            rmdir(dirname($directory));
            rmdir($root.'/private');
            rmdir($root);
        }
    }

    public function test_retention_is_disabled_by_default_and_only_prunes_more_than_sixty_days(): void
    {
        $this->travelTo(Carbon::parse('2026-11-13 10:00:00'));
        $user = $this->account();
        foreach ([61, 60, 1] as $days) {
            DB::table('logs')->insert(['module' => 'Uji', 'action' => 'Tambah', 'description' => 'Retensi', 'created_by' => $user->id_user, 'created_date' => now()->subDays($days)]);
        }
        $this->artisan('logs:prune')->assertFailed();
        $this->assertDatabaseCount('logs', 3);
        config(['labmania.audit_retention_enabled' => true]);
        $this->artisan('logs:prune')->assertSuccessful();
        $this->assertDatabaseCount('logs', 2);
        $this->assertDatabaseHas('logs', ['created_date' => '2026-09-14 10:00:00']);
        $this->assertDatabaseHas('users', ['id_user' => $user->id_user]);
    }
}

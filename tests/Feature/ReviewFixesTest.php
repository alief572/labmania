<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Audit;
use App\Support\Permissions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewFixesTest extends TestCase
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
        foreach ([1 => 'activity_log', 2 => 'permission_group', 3 => 'master_program', 4 => 'quotation_program', 5 => 'master_data.users'] as $id => $code) {
            DB::table('ms_menu')->insert(['id' => $id, 'kode' => $code, 'judul' => $code, 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => $id, 'aktif' => 1, 'sistem' => 0]);
            foreach (['lihat', 'ubah', 'aktif'] as $action) {
                DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => $action]);
            }
        }
    }

    private function account(string $name, array $attributes = []): User
    {
        return User::forceCreate($attributes + ['username' => $name, 'nm_lengkap' => $name, 'password' => password_hash('known-password', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
    }

    private function verified(User $user): static
    {
        return $this->actingAs($user)->withSession(['auth.verified_id' => $user->id_user, 'auth.factor' => hash('sha256', $user->password)]);
    }

    private function grant(User $user, int $menu, string $action): void
    {
        DB::table('ms_user_akses')->insert(['id_user' => $user->id_user, 'id_menu' => $menu, 'aksi' => $action]);
    }

    /** Aktor dengan Manage Master User (5) dan View/Manage Master Program (3), tanpa Approve. */
    private function manager(): User
    {
        $actor = $this->account('manager');
        foreach ([[5, 'lihat'], [5, 'ubah'], [3, 'lihat'], [3, 'ubah']] as [$menu, $action]) {
            $this->grant($actor, $menu, $action);
        }

        return $actor;
    }

    public function test_actor_cannot_edit_own_override_or_own_role_but_superadmin_can_grant_freely(): void
    {
        $actor = $this->manager();
        $this->grant($actor, 2, 'lihat');
        $this->grant($actor, 2, 'ubah');
        DB::table('user_groups')->insert(['id_user' => $actor->id_user, 'id_group' => 2]);
        $this->verified($actor)->put('/permissions/users/'.$actor->id_user, ['grants' => ['3:Approve']])->assertForbidden();
        $this->put('/permissions/roles/2', ['grants' => ['3:Approve']])->assertForbidden();
        $this->assertFalse(Permissions::allows($actor, 'master_program', 'Approve'));

        $root = $this->account('root');
        DB::table('user_groups')->insert(['id_user' => $root->id_user, 'id_group' => 1]);
        $target = $this->account('target');
        $this->verified($root)->put('/permissions/users/'.$target->id_user, ['grants' => ['3:Approve']])->assertRedirect();
        $this->assertTrue(Permissions::allows($target, 'master_program', 'Approve'));
    }

    public function test_actor_can_grant_others_rights_they_do_not_hold(): void
    {
        $actor = $this->manager();
        $target = $this->account('target');
        $this->assertFalse(Permissions::allows($actor, 'master_program', 'Approve'));
        $this->verified($actor)->put('/permissions/users/'.$target->id_user, ['grants' => ['3:Approve']])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ms_user_akses', ['id_user' => $target->id_user, 'aksi' => 'approve']);
        $this->put('/permissions/users/'.$target->id_user, ['grants' => ['3:Manage']])->assertRedirect();
        $this->assertDatabaseHas('ms_user_akses', ['id_user' => $target->id_user, 'id_menu' => 3, 'aksi' => 'ubah']);
        $this->put('/permissions/users/'.$target->id_user, ['grants' => []])->assertRedirect();
        $this->assertDatabaseMissing('ms_user_akses', ['id_user' => $target->id_user, 'id_menu' => 3]);
    }

    public function test_saving_matrix_preserves_untouched_aktif_rows_and_does_not_upgrade_aktif_to_ubah(): void
    {
        $root = $this->account('root');
        DB::table('user_groups')->insert(['id_user' => $root->id_user, 'id_group' => 1]);
        DB::table('ms_group_akses')->insert([['id_group' => 2, 'id_menu' => 3, 'aksi' => 'aktif'], ['id_group' => 2, 'id_menu' => 4, 'aksi' => 'lihat'], ['id_group' => 2, 'id_menu' => 4, 'aksi' => 'cetak']]);
        $this->verified($root)->put('/permissions/roles/2', ['grants' => ['3:Manage', '4:View']])->assertRedirect();
        $this->assertDatabaseHas('ms_group_akses', ['id_group' => 2, 'id_menu' => 3, 'aksi' => 'aktif']);
        $this->assertDatabaseMissing('ms_group_akses', ['id_group' => 2, 'id_menu' => 3, 'aksi' => 'ubah']);
        $this->assertDatabaseHas('ms_group_akses', ['id_group' => 2, 'id_menu' => 4, 'aksi' => 'cetak']);
        DB::table('ms_menu_aksi')->where('id_menu', 4)->where('aksi', 'ubah')->delete();
        $this->put('/permissions/roles/2', ['grants' => ['3:Manage', '4:View', '4:Manage']])->assertRedirect();
        $this->assertDatabaseHas('ms_group_akses', ['id_group' => 2, 'id_menu' => 4, 'aksi' => 'aktif']);
    }

    public function test_login_is_limited_per_ip_and_per_username_across_pairs(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/login', ['username' => 'user'.$i, 'password' => 'x'])->assertSessionHasErrors('username');
        }
        $this->post('/login', ['username' => 'another', 'password' => 'x'])->assertStatus(429);
    }

    public function test_log_filters_treat_zero_as_a_real_value(): void
    {
        $root = $this->account('root');
        DB::table('user_groups')->insert(['id_user' => $root->id_user, 'id_group' => 1]);
        foreach (['0', 'ABC'] as $no) {
            DB::table('logs')->insert(['module' => 'Uji', 'action' => 'Edit', 'description' => 'Log '.$no, 'created_by' => $root->id_user, 'created_date' => now(), 'ref_no' => $no]);
        }
        $this->verified($root)->get('/activity-log?ref_no=0')->assertInertia(fn ($page) => $page->where('logs.total', 1));
        $this->get('/activity-log?keyword=0')->assertInertia(fn ($page) => $page->where('logs.total', 1));
    }

    public function test_audit_compares_identifiers_as_text_but_keeps_nominal_tolerance(): void
    {
        $data = Audit::normalize([
            'old' => ['hp' => '0812345', 'kode' => '007', 'qty' => '0.00', 'total' => '3744000000.0000'],
            'new' => ['hp' => '812345', 'kode' => '7', 'qty' => 0, 'total' => '3744000000'],
        ]);
        $this->assertSame(['hp' => '0812345', 'kode' => '007'], $data['old']);
        $this->assertSame(['hp' => '812345', 'kode' => '7'], $data['new']);
    }

    public function test_permission_map_query_count_does_not_grow_with_menu_count(): void
    {
        $user = $this->account('user');
        $count = function () use ($user) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            Permissions::map($user);

            return count(DB::getQueryLog());
        };
        $before = $count();
        foreach (range(10, 40) as $id) {
            DB::table('ms_menu')->insert(['id' => $id, 'kode' => 'menu'.$id, 'judul' => 'm', 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => $id, 'aktif' => 1, 'sistem' => 0]);
            DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => 'lihat']);
        }
        $this->assertSame($before, $count());
    }

    public function test_log_filter_dropdowns_refresh_after_new_module_logs(): void
    {
        $root = $this->account('root');
        DB::table('user_groups')->insert(['id_user' => $root->id_user, 'id_group' => 1]);
        $this->verified($root)->get('/activity-log')->assertInertia(fn ($page) => $page->where('modules', []));
        write_log('Modul Baru', 'Tambah', 'Uji', null, 'users', $root->id_user, 'x');
        $this->get('/activity-log')->assertInertia(fn ($page) => $page->where('modules', ['Modul Baru']));
    }
}

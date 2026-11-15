<?php

namespace Tests\Feature\Master;

use App\Models\User;
use App\Support\Navigation;
use App\Support\Permissions;
use App\Support\SidebarToBe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;

trait SidebarScenarios
{
    private function sidebarFixture(): User
    {
        $root = User::forceCreate(['username' => 'sidebar_root', 'nm_lengkap' => 'Sidebar Root', 'password' => password_hash('known-password', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0]);
        DB::table('user_groups')->insert(['id_user' => $root->id_user, 'id_group' => 1]);
        Auth::setUser($root);
        $id = DB::table('ms_menu')->where('kode', 'master_data.role')->value('id');
        foreach (['ms_menu_aksi', 'ms_group_akses', 'ms_user_akses'] as $table) {
            DB::table($table)->where('id_menu', $id)->delete();
        }
        DB::table('ms_menu')->where('kode', 'master_data.role')->delete();
        $definitions = ['m_master_data' => ['Master Data', '#']];
        foreach (SidebarToBe::GROUPS as $members) {
            foreach ($members as $code => $title) {
                if ($code !== 'master_data.role') {
                    $definitions[$code] = [$title, collect(config('navigation'))->firstWhere('menu', $code)['url']];
                }
            }
        }
        foreach ($definitions as $code => [$title, $link]) {
            DB::table('ms_menu')->updateOrInsert(['kode' => $code], ['judul' => $title, 'link' => $link, 'parent_id' => 0, 'kelompok' => 7, 'urutan' => 1, 'aktif' => 1, 'sistem' => 0]);
        }
        $source = DB::table('ms_menu')->where('kode', 'permission_group')->value('id');
        foreach (['lihat', 'tambah', 'ubah', 'aktif', 'hapus'] as $action) {
            DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => $source, 'aksi' => $action]);
        }
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => $source, 'aksi' => 'lihat']);
        DB::table('ms_user_akses')->insert(['id_user' => $root->id_user, 'id_menu' => $source, 'aksi' => 'aktif']);

        return $root;
    }

    public function test_sidebar_simulation_is_read_only_and_apply_preserves_existing_grants(): void
    {
        $root = $this->sidebarFixture();
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        $this->assertCount(15, SidebarToBe::sync());
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
        $this->assertSame(0, DB::table('logs')->count());
        $this->assertCount(15, SidebarToBe::sync(true));
        $id = DB::table('ms_menu')->where('kode', 'master_data.role')->value('id');
        $this->assertDatabaseHas('ms_group_akses', ['id_group' => 2, 'id_menu' => $id, 'aksi' => 'lihat']);
        $this->assertDatabaseHas('ms_user_akses', ['id_user' => $root->id_user, 'id_menu' => $id, 'aksi' => 'aktif']);
        $this->assertSame(2, DB::table('ms_group_akses')->count());
        $this->assertSame(2, DB::table('ms_user_akses')->count());
        $nav = Navigation::items(Permissions::map($root));
        $expected = [];
        foreach (SidebarToBe::GROUPS as $group => $members) {
            foreach ($members as $title) {
                $expected[] = [config('navigation-groups.m_master_data')[$group], $title];
            }
        }
        $this->assertSame($expected, array_map(fn ($item) => [$item['subgroupLabel'], $item['label']], $nav));
        $this->assertSame('/master/role', collect($nav)->firstWhere('menu', 'master_data.role')['url']);
        $this->assertSame('/permissions', collect($nav)->firstWhere('menu', 'permission_group')['url']);
        DB::table('ms_group_akses')->where('id_menu', $id)->delete();
        DB::table('ms_user_akses')->where('id_menu', $id)->delete();
        $logs = DB::table('logs')->count();
        $this->assertSame([], SidebarToBe::sync(true));
        $this->assertSame($logs, DB::table('logs')->count());
        $this->assertSame(0, DB::table('ms_group_akses')->where('id_menu', $id)->count());
        $this->assertSame(0, DB::table('ms_user_akses')->where('id_menu', $id)->count());
    }

    public function test_sidebar_missing_source_and_link_collision_leave_no_partial_changes(): void
    {
        $this->sidebarFixture();
        DB::table('ms_menu')->where('kode', 'master_trainer')->delete();
        $before = DB::table('ms_menu')->orderBy('id')->get()->toJson();
        try {
            SidebarToBe::sync(true);
            $this->fail('Menu sumber hilang harus ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('master_trainer', $e->getMessage());
        }
        $this->assertSame($before, DB::table('ms_menu')->orderBy('id')->get()->toJson());
        $this->assertSame(0, DB::table('logs')->count());
        DB::table('ms_menu')->insert(['kode' => 'master_trainer', 'judul' => 'Trainer', 'link' => 'master/trainer', 'parent_id' => 0, 'kelompok' => 7, 'urutan' => 1, 'aktif' => 1, 'sistem' => 0]);
        DB::table('ms_menu')->where('kode', 'master_trainer')->update(['link' => '/master/role']);
        $this->expectExceptionMessage('berbenturan');
        SidebarToBe::sync(true);
    }

    public function test_sidebar_group_labels_follow_code_not_title_and_hide_unauthorized_items(): void
    {
        $root = $this->sidebarFixture();
        SidebarToBe::sync(true);
        $parent = DB::table('ms_menu')->where('kode', 'm_master_data')->value('id');
        DB::table('ms_menu')->where('id', $parent)->update(['judul' => 'Nama baru']);
        $nav = Navigation::items(['master_trainer' => ['View']]);
        $this->assertCount(1, $nav);
        $this->assertSame('Nama baru', $nav[0]['group']);
        $this->assertSame('Peserta & Trainer', $nav[0]['subgroupLabel']);
        DB::table('ms_menu')->where('kode', 'master_trainer')->update(['kelompok' => 99]);
        $this->assertSame('', Navigation::items(['master_trainer' => ['View']])[0]['subgroupLabel']);
        DB::table('ms_menu')->where('id', $parent)->update(['aktif' => 0]);
        $this->assertSame([], Navigation::items(Permissions::map($root)));
    }

    public function test_master_role_gate_is_independent_and_keeps_own_role_protection(): void
    {
        $this->sidebarFixture();
        SidebarToBe::sync(true);
        $staff = User::forceCreate(['username' => 'sidebar_staff', 'nm_lengkap' => 'Staf', 'password' => password_hash('known-password', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0]);
        DB::table('user_groups')->insert(['id_user' => $staff->id_user, 'id_group' => 2]);
        $this->actingAs($staff)->withSession(['auth.verified_id' => $staff->id_user, 'auth.factor' => hash('sha256', $staff->password)]);
        $this->get('/master/role')->assertOk();
        $this->post('/master/role', ['nama' => 'Tidak boleh'])->assertForbidden();
        $id = DB::table('ms_menu')->where('kode', 'master_data.role')->value('id');
        DB::table('ms_group_akses')->where('id_menu', $id)->delete();
        $this->get('/master/role')->assertForbidden();
        $this->get('/permissions')->assertOk();
        foreach (['lihat', 'tambah', 'ubah', 'hapus'] as $action) {
            DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => $id, 'aksi' => $action]);
        }
        $this->put('/master/role/2', ['nama' => 'Sendiri'])->assertForbidden();
        $this->delete('/master/role/2')->assertForbidden();
        $this->post('/master/role', ['nama' => 'Role biasa', 'ket' => 'uji'])->assertRedirect()->assertSessionHasNoErrors();
        DB::table('ms_group_akses')->where('id_menu', DB::table('ms_menu')->where('kode', 'permission_group')->value('id'))->delete();
        $this->get('/permissions')->assertForbidden();
        $this->get('/master/role')->assertOk()->assertInertia(fn (Assert $page) => $page->where('can.permissions', false));
    }
}

<?php

namespace Tests\Feature\Master;

use App\Support\Navigation;
use App\Support\Permissions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

class NavigationTest extends MasterTestCase
{
    private function menu(string $code, string $link, array $overrides = []): int
    {
        $id = DB::table('ms_menu')->insertGetId($overrides + ['kode' => $code, 'judul' => $code, 'link' => $link, 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 100, 'aktif' => 1, 'sistem' => 0]);
        DB::table('ms_menu_aksi')->insert(['id_menu' => $id, 'aksi' => 'lihat']);

        return $id;
    }

    public function test_database_parent_group_order_labels_and_permissions_define_navigation(): void
    {
        $parent = $this->menu('module', '#', ['judul' => 'Modul dari DB', 'urutan' => 1]);
        $second = $this->menu('second', 'master/company', ['judul' => 'Company DB', 'parent_id' => $parent, 'urutan' => 1, 'kelompok' => 2]);
        $first = $this->menu('first', 'master/peserta', ['judul' => 'Peserta DB', 'parent_id' => $parent, 'urutan' => 2, 'kelompok' => 1]);
        $this->menu('orphan', 'master/trainer', ['parent_id' => 999]);
        $this->menu('unsafe', 'https://example.com');
        $this->menu('guest_route', 'login');
        $this->menu('post_route', 'logout');
        $this->menu('missing', 'laporan/belum-ada');
        $this->menu('disabled', 'master/rate/fixed', ['aktif' => 0]);
        $map = Permissions::map($this->admin());
        $items = Navigation::items($map);
        $this->assertSame(['first', 'second'], array_column($items, 'menu'));
        $this->assertSame(['Peserta DB', 'Company DB'], array_column($items, 'label'));
        $this->assertSame(['Modul dari DB', 'Modul dari DB'], array_column($items, 'group'));
        $this->assertSame(['1', '2'], array_column($items, 'subgroupKey'));
        $this->assertCount(1, Navigation::items(['first' => ['View']]));
        $this->assertSame([], Navigation::items(['second' => ['Manage']]));
        DB::table('ms_menu')->where('id', $parent)->update(['aktif' => 0]);
        $this->assertSame([], Navigation::items($map));
        $this->assertNotSame($first, $second);
    }

    public function test_legacy_routes_resolve_and_unknown_active_links_are_reported(): void
    {
        $this->assertSame('/permissions', Navigation::url('permission_group', 'permission_group'));
        $this->assertSame('/master/rate/fixed', Navigation::url('master_data/rate', 'master_data.rate'));
        $this->assertSame('/', Navigation::url('dashboard_labmania', 'dashboard'));
        $this->assertSame('/master/role', Navigation::url('master/role', 'custom'));
        $this->assertNull(Navigation::url('//example.com', 'custom'));
        $this->assertNull(Navigation::url('master/user/1', 'custom'));
        $id = $this->menu('unmapped', 'laporan/belum-ada');
        $this->assertContains($id, array_column(Navigation::unmapped(), 'id'));
        DB::table('ms_menu')->where('id', $id)->update(['aktif' => 0]);
        $this->assertNotContains($id, array_column(Navigation::unmapped(), 'id'));
    }

    public function test_crud_title_move_and_toggle_change_shared_navigation_without_restarting(): void
    {
        $this->menu('menu_labmania', 'menu_labmania');
        $parent = $this->menu('custom_module', '#', ['judul' => 'Modul DB', 'urutan' => 0]);
        $first = $this->menu('custom_peserta', 'master/peserta', ['judul' => 'Satu', 'urutan' => 1, 'parent_id' => $parent]);
        $second = $this->menu('custom_company', 'master/company', ['judul' => 'Dua', 'urutan' => 2, 'parent_id' => $parent]);
        $this->login($this->admin())->get('/pengaturan/menu')->assertOk()->assertInertia(fn (Assert $page) => $page->where('navigation.0.label', 'Satu'));
        $this->put('/pengaturan/menu/'.$first, ['judul' => 'Nama baru', 'link' => 'master/peserta', 'parent_id' => $parent, 'aksi' => ['lihat']])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/pengaturan/menu/'.$second.'/pindah/up')->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success', 'Menu Dua dipindahkan.');
        $this->assertSame([$second, $first], DB::table('ms_menu')->whereIn('id', [$first, $second])->orderBy('urutan')->pluck('id')->all());
        $this->get('/pengaturan/menu')->assertInertia(fn (Assert $page) => $page->where('navigation.0.label', 'Dua')->where('navigation.1.label', 'Nama baru'));
        $this->post('/pengaturan/menu/'.$second.'/toggle')->assertSessionHasNoErrors();
        $this->get('/pengaturan/menu')->assertInertia(fn (Assert $page) => $page->where('navigation.0.label', 'Nama baru'));
        $this->assertDatabaseHas('logs', ['module' => 'Menu Sidebar', 'action' => 'Edit']);
    }
}

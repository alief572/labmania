<?php

namespace Tests\Feature\Master;

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/** Role & Permission: daftar role server-side + data matriks per role untuk modal (mockup produk-to-be). */
class RolePermissionListTest extends MasterTestCase
{
    public function test_halaman_berisi_kpi_dan_tanpa_data_role_di_props(): void
    {
        $this->login($this->admin())->get('/permissions')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Permissions')->where('kpi.roles', 2)->has('kpi.menus')->has('kpi.hak')->where('superadmin', 1)->where('can.manage', true));
    }

    public function test_daftar_role_jumlah_user_hak_dan_cuplikan_kode(): void
    {
        $staf = $this->account('staf');
        DB::table('user_groups')->insert(['id_user' => $staf->id_user, 'id_group' => 2]);
        DB::table('ms_group_akses')->insert([['id_group' => 2, 'id_menu' => self::MENUS['master_data.users'], 'aksi' => 'lihat'], ['id_group' => 2, 'id_menu' => self::MENUS['master_data.users'], 'aksi' => 'ubah']]);
        $this->allow($staf, 'master_data.role', 'lihat');

        $rows = $this->login($this->admin())->getJson('/permissions/data?draw=1&start=0&length=25')->assertOk()->json('data');
        $byId = collect($rows)->keyBy('id_group');
        $this->assertTrue($byId[1]['sistem']);
        $this->assertSame($byId[1]['total'], $byId[1]['n_hak']);
        $this->assertFalse($byId[2]['sistem']);
        $this->assertSame(1, (int) $byId[2]['n_user']);
        $this->assertSame(1, (int) $byId[2]['n_override']);
        $this->assertSame(2, $byId[2]['n_hak']);
        $this->assertEqualsCanonicalizing(['master_data.users.view', 'master_data.users.manage'], $byId[2]['hak']);
    }

    public function test_pencarian_nama_role_dan_hak_view_dibutuhkan(): void
    {
        $this->login($this->admin())->getJson('/permissions/data?draw=1&start=0&length=25&search[value]=Staf')->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->login($this->account('tanpa'))->getJson('/permissions/data?draw=1&start=0&length=25')->assertForbidden();
    }

    public function test_data_matriks_satu_role_memuat_modul_dan_hak_terpilih(): void
    {
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => self::MENUS['master_data.users'], 'aksi' => 'lihat']);
        $json = $this->login($this->admin())->getJson('/permissions/roles/2')->assertOk()->json();
        $this->assertSame(2, $json['targetId']);
        $this->assertSame(0, $json['n_user']);
        $this->assertContains(self::MENUS['master_data.users'].':View', $json['selected']);
        $this->assertTrue($json['editable']);
        $this->assertArrayHasKey('modul', $json['menus'][0]);
        $this->assertFalse($this->getJson('/permissions/roles/1')->json('editable'));
        $this->getJson('/permissions/roles/999')->assertNotFound();
        $this->login($this->account('tanpa'))->getJson('/permissions/roles/2')->assertForbidden();
    }
}

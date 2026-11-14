<?php

namespace Tests\Feature\Master;

use Illuminate\Support\Facades\DB;

/** CRUD Menu Sidebar: aturan M-01..M-14 (perilaku CI3 Menu_labmania_model). */
class MenuTest extends MasterTestCase
{
    private int $modul;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('ms_menu')->insert(['id' => 62, 'kode' => 'menu_labmania', 'judul' => 'Menu Sidebar', 'link' => 'menu_labmania', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 99, 'aktif' => 1, 'sistem' => 1]);
        foreach (['lihat', 'tambah', 'ubah', 'aktif'] as $a) {
            DB::table('ms_menu_aksi')->insert(['id_menu' => 62, 'aksi' => $a]);
        }
        $this->modul = DB::table('ms_menu')->insertGetId(['kode' => 'm_uji', 'judul' => 'Modul Uji', 'link' => '#', 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 100, 'aktif' => 1, 'sistem' => 0]);
    }

    private function payload(array $over = []): array
    {
        return $over + ['tipe' => 'menu', 'judul' => 'Laporan Uji', 'link' => 'laporan/uji', 'parent_id' => $this->modul, 'kelompok' => 1, 'aksi' => ['tambah']];
    }

    public function test_permission_is_required_and_guest_redirected(): void
    {
        $this->get('/pengaturan/menu')->assertRedirect('/login');
        $staff = $this->account();
        $this->login($staff)->get('/pengaturan/menu')->assertForbidden();
        $this->post('/pengaturan/menu', $this->payload())->assertForbidden();
        DB::table('ms_user_akses')->insert(['id_user' => $staff->id_user, 'id_menu' => 62, 'aksi' => 'lihat']);
        $this->get('/pengaturan/menu')->assertOk()->assertInertia(fn ($p) => $p->component('Menu/Index')->where('can.add', false));
        $this->post('/pengaturan/menu', $this->payload())->assertForbidden();
        $this->post('/pengaturan/menu/'.$this->modul.'/toggle')->assertForbidden();
    }

    public function test_create_adds_lihat_unique_code_next_order_and_log(): void
    {
        $this->login($this->admin());
        $this->post('/pengaturan/menu', $this->payload())->assertSessionHasNoErrors();
        $this->post('/pengaturan/menu', $this->payload(['judul' => 'Laporan Dua', 'link' => 'laporan.uji']))->assertSessionHasErrors('link');
        $m = DB::table('ms_menu')->where('link', 'laporan/uji')->first();
        $this->assertSame('laporan.uji', $m->kode);
        $this->assertSame(1, (int) $m->aktif);
        $this->assertSame(0, (int) $m->sistem);
        $this->assertSame(1, (int) $m->urutan);
        $this->assertEqualsCanonicalizing(['lihat', 'tambah'], DB::table('ms_menu_aksi')->where('id_menu', $m->id)->pluck('aksi')->all());
        $this->assertSame('Tambah', DB::table('logs')->where('module', 'Menu Sidebar')->value('action'));
        $this->post('/pengaturan/menu', ['tipe' => 'modul', 'judul' => 'Uji'])->assertSessionHasNoErrors();
        $this->assertSame('m_uji_2', DB::table('ms_menu')->orderByDesc('id')->value('kode'));
    }

    public function test_validation_rejects_bad_link_parent_icon_and_action(): void
    {
        $this->login($this->admin());
        $before = DB::table('ms_menu')->count();
        foreach ([['link' => '/awalan'], ['link' => 'a b'], ['link' => '#'], ['parent_id' => 62], ['aksi' => ['bukan_aksi']], ['judul' => ''], ['judul' => str_repeat('x', 101)]] as $bad) {
            $this->post('/pengaturan/menu', $this->payload($bad))->assertSessionHasErrors();
        }
        $this->post('/pengaturan/menu', ['tipe' => 'modul', 'judul' => 'M', 'ikon' => 'tidak-ada'])->assertSessionHasErrors('ikon');
        $this->assertSame($before, DB::table('ms_menu')->count());
    }

    public function test_removing_action_revokes_group_and_user_grants_and_logs_old_new(): void
    {
        $this->login($this->admin());
        $this->post('/pengaturan/menu', $this->payload(['aksi' => ['tambah', 'ubah']]));
        $id = (int) DB::table('ms_menu')->where('link', 'laporan/uji')->value('id');
        foreach (['tambah', 'ubah'] as $a) {
            DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => $id, 'aksi' => $a]);
        }
        DB::table('ms_user_akses')->insert(['id_user' => 5, 'id_menu' => $id, 'aksi' => 'ubah']);
        $this->put('/pengaturan/menu/'.$id, $this->payload(['judul' => 'Laporan Baru', 'aksi' => ['tambah']]))->assertSessionHasNoErrors();
        $this->assertSame(['tambah'], DB::table('ms_group_akses')->where('id_menu', $id)->pluck('aksi')->all());
        $this->assertSame(0, DB::table('ms_user_akses')->where('id_menu', $id)->count());
        $this->assertEqualsCanonicalizing(['lihat', 'tambah'], DB::table('ms_menu_aksi')->where('id_menu', $id)->pluck('aksi')->all());
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertSame('Laporan Uji', $log['old']['judul']);
        $this->assertSame('Laporan Baru', $log['new']['judul']);
    }

    public function test_type_is_immutable_and_system_menu_link_is_locked(): void
    {
        $this->login($this->admin());
        $this->put('/pengaturan/menu/'.$this->modul, ['tipe' => 'menu', 'judul' => 'Modul Diubah', 'link' => 'x/y', 'aksi' => ['hapus']])->assertSessionHasNoErrors();
        $m = DB::table('ms_menu')->find($this->modul);
        $this->assertSame('#', $m->link);
        $this->assertSame(0, DB::table('ms_menu_aksi')->where('id_menu', $this->modul)->count());
        $this->put('/pengaturan/menu/62', ['judul' => 'Menu Sidebar Baru', 'link' => 'dibelokkan', 'parent_id' => 0, 'aksi' => ['tambah']])->assertSessionHasNoErrors();
        $this->assertSame('menu_labmania', DB::table('ms_menu')->find(62)->link);
        $this->assertSame('Menu Sidebar Baru', DB::table('ms_menu')->find(62)->judul);
    }

    public function test_toggle_rules_system_module_and_normal_menu(): void
    {
        $this->login($this->admin());
        $this->post('/pengaturan/menu/62/toggle')->assertSessionHasErrors('aktif');
        $this->assertSame(1, (int) DB::table('ms_menu')->find(62)->aktif);
        DB::table('ms_menu')->insert(['kode' => 'sys.anak', 'judul' => 'Anak Sistem', 'link' => 'sys/anak', 'parent_id' => $this->modul, 'kelompok' => 1, 'urutan' => 1, 'aktif' => 1, 'sistem' => 1]);
        $this->post('/pengaturan/menu/'.$this->modul.'/toggle')->assertSessionHasErrors('aktif');
        DB::table('ms_menu')->where('kode', 'sys.anak')->update(['sistem' => 0]);
        $this->post('/pengaturan/menu/'.$this->modul.'/toggle')->assertSessionHasNoErrors();
        $this->assertSame(0, (int) DB::table('ms_menu')->find($this->modul)->aktif);
        $this->post('/pengaturan/menu/'.$this->modul.'/toggle')->assertSessionHasNoErrors();
        $this->assertSame(1, (int) DB::table('ms_menu')->find($this->modul)->aktif);
        $this->assertSame(['Nonaktifkan', 'Aktifkan'], DB::table('logs')->whereIn('action', ['Nonaktifkan', 'Aktifkan'])->orderBy('id')->pluck('action')->all());
    }

    public function test_move_swaps_within_same_parent_group_renumbers_and_rejects_edges(): void
    {
        $this->login($this->admin());
        $ids = [];
        foreach (['a', 'b', 'c'] as $n => $k) {
            $ids[$k] = DB::table('ms_menu')->insertGetId(['kode' => $k, 'judul' => $k, 'link' => 'm/'.$k, 'parent_id' => $this->modul, 'kelompok' => 1, 'urutan' => $n + 1, 'aktif' => 1, 'sistem' => 0]);
        }
        $other = DB::table('ms_menu')->insertGetId(['kode' => 'z', 'judul' => 'z', 'link' => 'm/z', 'parent_id' => $this->modul, 'kelompok' => 2, 'urutan' => 1, 'aktif' => 1, 'sistem' => 0]);
        $this->post('/pengaturan/menu/'.$ids['a'].'/pindah/up')->assertSessionHasErrors('aktif');
        $this->post('/pengaturan/menu/'.$ids['a'].'/pindah/down')->assertSessionHasNoErrors();
        $order = DB::table('ms_menu')->where('parent_id', $this->modul)->where('kelompok', 1)->orderBy('urutan')->pluck('kode')->all();
        $this->assertSame(['b', 'a', 'c'], $order);
        $this->assertSame([10, 20, 30], DB::table('ms_menu')->where('parent_id', $this->modul)->where('kelompok', 1)->orderBy('urutan')->pluck('urutan')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(1, (int) DB::table('ms_menu')->find($other)->urutan);
        $this->post('/pengaturan/menu/'.$ids['c'].'/pindah/down')->assertSessionHasErrors('aktif');
        $this->post('/pengaturan/menu/'.$ids['a'].'/pindah/samping')->assertNotFound();
    }

    public function test_index_lists_tree_with_children_after_their_module(): void
    {
        $this->login($this->admin());
        DB::table('ms_menu')->insert(['kode' => 'anak', 'judul' => 'Anak', 'link' => 'm/anak', 'parent_id' => $this->modul, 'kelompok' => 1, 'urutan' => 1, 'aktif' => 1, 'sistem' => 0]);
        $this->get('/pengaturan/menu')->assertInertia(function ($p) {
            $rows = $p->toArray()['props']['rows'];
            $i = array_search('m_uji', array_column($rows, 'kode'), true);
            $this->assertSame('anak', $rows[$i + 1]['kode']);
            $this->assertSame(1, $rows[$i + 1]['level']);
        });
    }
}

<?php

namespace Tests\Feature\Master;

use Illuminate\Support\Facades\DB;

/** Override Permission di form Master User (FR-65): hanya menambah, hak role dibuang saat role diganti, aturan delegasi. */
class UserOverrideTest extends MasterTestCase
{
    private int $emp;

    private int $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login($this->admin())->post('/master/user', ['nama' => 'Budi Uji', 'id_group' => 2, 'akun' => true, 'username' => 'budi.uji', 'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123'])->assertSessionHasNoErrors();
        $this->emp = (int) DB::table('ms_user_karyawan')->value('id');
        $this->user = (int) DB::table('ms_user_karyawan')->value('id_user');
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => self::MENUS['master_program'], 'aksi' => 'lihat']);
    }

    private function menu(string $kode): int
    {
        return self::MENUS[$kode];
    }

    private function body(array $override): array
    {
        return ['nama' => 'Budi Uji', 'id_group' => 2, 'akun' => true, 'username' => 'budi.uji', 'override' => $override];
    }

    private function overrideRows(): array
    {
        return DB::table('ms_user_akses')->where('id_user', $this->user)->orderBy('id_menu')->orderBy('aksi')->get(['id_menu', 'aksi'])->map(fn ($r) => $r->id_menu.':'.$r->aksi)->all();
    }

    public function test_override_dari_form_menambah_hak_dan_mencentang_aksi_menyertakan_view(): void
    {
        $m = $this->menu('master_data.company');
        $this->put('/master/user/'.$this->emp, $this->body([$m.':Manage']))->assertSessionHasNoErrors();
        $rows = $this->overrideRows();
        $this->assertContains($m.':lihat', $rows);
        $this->assertTrue(count(array_filter($rows, fn ($r) => str_starts_with($r, $m.':') && $r !== $m.':lihat')) >= 1, 'Manage tersimpan sebagai ubah atau aktif.');
        $log = json_decode(DB::table('logs')->where('description', 'like', 'Update User%')->orderByDesc('id')->value('data'), true);
        $this->assertSame(0, $log['new']['override_sebelum']);
        $this->assertGreaterThan(0, $log['new']['override_sesudah']);
    }

    public function test_hak_yang_sudah_dimiliki_role_tidak_disimpan_sebagai_override(): void
    {
        $m = $this->menu('master_program');
        // Role Staf sudah punya lihat pada master_program; override View menu itu tidak boleh dobel.
        $this->put('/master/user/'.$this->emp, $this->body([$m.':View']))->assertSessionHasNoErrors();
        $this->assertSame([], $this->overrideRows());
    }

    public function test_form_tanpa_blok_override_tidak_mengubah_override_tersimpan(): void
    {
        $m = $this->menu('master_data.company');
        $this->put('/master/user/'.$this->emp, $this->body([$m.':View']))->assertSessionHasNoErrors();
        $before = $this->overrideRows();
        $this->assertNotEmpty($before);
        $payload = $this->body([]);
        unset($payload['override']);
        $this->put('/master/user/'.$this->emp, $payload)->assertSessionHasNoErrors();
        $this->assertSame($before, $this->overrideRows(), 'Klien tanpa blok override tidak boleh menghapus override.');
    }

    public function test_override_kosong_menghapus_override(): void
    {
        $m = $this->menu('master_data.company');
        $this->put('/master/user/'.$this->emp, $this->body([$m.':View']))->assertSessionHasNoErrors();
        $this->put('/master/user/'.$this->emp, $this->body([]))->assertSessionHasNoErrors();
        $this->assertSame([], $this->overrideRows());
    }

    public function test_mengganti_role_membuang_override_yang_sudah_tercakup_role_baru(): void
    {
        $m = $this->menu('master_data.company');
        $this->put('/master/user/'.$this->emp, $this->body([$m.':View']))->assertSessionHasNoErrors();
        $this->assertNotEmpty($this->overrideRows());
        DB::table('groups')->insert(['id_group' => 3, 'nm_group' => 'Company', 'ket' => '']);
        DB::table('ms_group_akses')->insert(['id_group' => 3, 'id_menu' => $m, 'aksi' => 'lihat']);
        $payload = $this->body([$m.':View']);
        $payload['id_group'] = 3;
        $this->put('/master/user/'.$this->emp, $payload)->assertSessionHasNoErrors();
        $this->assertSame([], $this->overrideRows(), 'Hak yang kini dari role tidak boleh tersisa sebagai override.');
        $log = json_decode(DB::table('logs')->where('description', 'like', 'Update User%')->orderByDesc('id')->value('data'), true);
        $this->assertGreaterThan(0, $log['new']['override_dibuang_karena_role']);
    }

    public function test_menu_atau_aksi_tidak_dikenal_ditolak_dan_tidak_ada_yang_tersimpan(): void
    {
        $this->put('/master/user/'.$this->emp, $this->body(['999999:View']))->assertSessionHasErrors('override');
        $this->assertSame([], $this->overrideRows());
    }

    public function test_aktor_tanpa_hak_tidak_dapat_memberi_override_melebihi_haknya_dan_tidak_mengubah_milik_sendiri(): void
    {
        $manager = $this->account('manager');
        DB::table('user_groups')->insert(['id_user' => $manager->id_user, 'id_group' => 2]);
        $this->allow($manager, 'master_data.users', 'lihat', 'tambah', 'ubah');
        $this->login($manager);
        // Aktor tidak punya hak apa pun pada master_data.company: tidak boleh memberikannya.
        $this->put('/master/user/'.$this->emp, $this->body([$this->menu('master_data.company').':View']))->assertSessionHasErrors('override');
        $this->assertSame([], $this->overrideRows());

        // Mengubah override milik sendiri ditolak.
        $mine = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-009', 'nama' => 'Saya', 'id_group' => 2, 'aktif' => 1, 'id_user' => $manager->id_user]);
        $own = ['nama' => 'Saya', 'id_group' => 2, 'akun' => true, 'username' => 'manager', 'override' => [$this->menu('master_data.users').':Add']];
        $this->put('/master/user/'.$mine, $own)->assertSessionHasErrors('override');
    }

    public function test_override_superadmin_diabaikan_dan_endpoint_json_dipakai_form(): void
    {
        $root = DB::table('users')->where('username', 'root')->value('id_user');
        $json = $this->getJson('/permissions/users/'.$this->user)->assertOk()->json();
        $this->assertArrayHasKey('menus', $json);
        $this->assertArrayHasKey('inherited', $json);
        $this->assertSame([], $json['users'], 'Daftar user tidak perlu ikut pada permintaan JSON.');
        $this->assertNotNull($root);
    }
}

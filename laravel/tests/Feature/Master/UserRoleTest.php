<?php

namespace Tests\Feature\Master;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRoleTest extends MasterTestCase
{
    private function payload(array $over = []): array
    {
        return $over + ['nama' => 'Budi Uji', 'id_group' => 2, 'akun' => true, 'username' => 'budi.uji', 'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123', 'telp' => '0812-3456-7890', 'email' => 'budi@uji.test'];
    }

    /** Aktor dengan Manage + Add Master User, tanpa Superadmin. */
    private function manager(): User
    {
        $user = $this->account('manager');
        DB::table('user_groups')->insert(['id_user' => $user->id_user, 'id_group' => 2]);
        $this->allow($user, 'master_data.users', 'lihat', 'tambah', 'ubah');

        return $user;
    }

    public function test_tambah_user_dengan_akun_hash_role_dan_log_tanpa_password(): void
    {
        $this->login($this->admin())->post('/master/user', $this->payload())->assertSessionHasNoErrors();
        $emp = DB::table('ms_user_karyawan')->first();
        $this->assertSame('USR-001', $emp->kode);
        $akun = DB::table('users')->where('username', 'budi.uji')->first();
        $this->assertSame($akun->id_user, $emp->id_user);
        $this->assertTrue(password_verify('rahasia-123', $akun->password));
        $this->assertSame('081234567890', $emp->telp);
        $this->assertSame(2, (int) DB::table('user_groups')->where('id_user', $akun->id_user)->value('id_group'));
        $log = DB::table('logs')->where('action', 'Tambah')->value('data');
        $this->assertStringNotContainsString('rahasia-123', $log);
        $this->assertStringNotContainsString($akun->password, $log);
    }

    public function test_validasi_username_password_nik_dan_tanggal(): void
    {
        $admin = $this->login($this->admin());
        $admin->post('/master/user', $this->payload(['username' => 'Besar!']))->assertSessionHasErrors('username');
        $admin->post('/master/user', $this->payload(['password' => 'pendek', 'password_confirmation' => 'pendek']))->assertSessionHasErrors('password');
        $admin->post('/master/user', $this->payload(['password_confirmation' => 'beda']))->assertSessionHasErrors('password');
        $admin->post('/master/user', $this->payload(['nik' => '123']))->assertSessionHasErrors('nik');
        $admin->post('/master/user', $this->payload(['tgl_lahir' => '2000-01-01', 'tgl_masuk' => '1999-01-01']))->assertSessionHasErrors('tgl_masuk');
        $admin->post('/master/user', $this->payload(['id_group' => '']))->assertSessionHasErrors('id_group');
        $admin->post('/master/user', $this->payload(['id_group' => 77]))->assertSessionHasErrors('id_group');
        $this->assertSame(0, DB::table('ms_user_karyawan')->count());
        $this->assertSame(1, DB::table('users')->count());
    }

    public function test_username_unik_tanpa_beda_huruf_dan_role_superadmin_tidak_bisa_dipilih(): void
    {
        $this->login($this->admin())->post('/master/user', $this->payload())->assertSessionHasNoErrors();
        $this->post('/master/user', $this->payload(['nama' => 'Lain']))->assertSessionHasErrors('username');
        $this->post('/master/user', $this->payload(['nama' => 'Lain', 'username' => 'lain.user', 'id_group' => 1]))->assertSessionHasErrors('id_group');
        $this->assertSame(1, DB::table('ms_user_karyawan')->count());
    }

    public function test_edit_tanpa_password_mempertahankan_hash_dan_dengan_password_menggantinya(): void
    {
        $this->login($this->admin())->post('/master/user', $this->payload());
        $emp = DB::table('ms_user_karyawan')->first();
        $hash = DB::table('users')->where('id_user', $emp->id_user)->value('password');
        $this->put("/master/user/{$emp->id}", $this->payload(['nama' => 'Budi Baru', 'password' => '', 'password_confirmation' => '']))->assertSessionHasNoErrors();
        $this->assertSame($hash, DB::table('users')->where('id_user', $emp->id_user)->value('password'));
        $this->assertSame('Budi Baru', DB::table('users')->where('id_user', $emp->id_user)->value('nm_lengkap'));
        $this->put("/master/user/{$emp->id}", $this->payload(['password' => 'baru-12345', 'password_confirmation' => 'baru-12345']));
        $this->assertTrue(password_verify('baru-12345', DB::table('users')->where('id_user', $emp->id_user)->value('password')));
        $log = DB::table('logs')->where('action', 'Edit')->orderByDesc('id')->value('data');
        $this->assertStringNotContainsString('baru-12345', $log);
        $this->assertSame('Budi Uji', json_decode(DB::table('logs')->where('action', 'Edit')->orderBy('id')->value('data'), true)['old']['nama']);
    }

    public function test_toggle_menonaktifkan_akun_login_dan_tidak_bisa_nonaktifkan_diri_sendiri(): void
    {
        $manager = $this->manager();
        $emp = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-009', 'nama' => 'Saya', 'id_group' => 2, 'aktif' => 1, 'id_user' => $manager->id_user]);
        $this->login($manager)->post("/master/user/$emp/toggle")->assertSessionHasErrors('status');
        $this->assertSame(1, (int) DB::table('users')->where('id_user', $manager->id_user)->value('st_aktif'));

        $this->post('/master/user', $this->payload())->assertSessionHasNoErrors();
        $target = DB::table('ms_user_karyawan')->where('nama', 'Budi Uji')->first();
        $this->post("/master/user/{$target->id}/toggle")->assertRedirect();
        $this->assertSame(0, (int) DB::table('users')->where('id_user', $target->id_user)->value('st_aktif'));
        $this->assertSame(0, (int) DB::table('ms_user_karyawan')->where('id', $target->id)->value('aktif'));
        $this->assertSame('USR-010', $target->kode);
        $this->assertDatabaseHas('logs', ['action' => 'Nonaktifkan', 'ref_no' => 'USR-010']);
    }

    public function test_superadmin_tidak_bisa_diubah_dinonaktifkan_atau_direset_oleh_non_superadmin(): void
    {
        $root = $this->admin();
        $emp = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-001', 'nama' => 'Root', 'id_group' => 1, 'aktif' => 1, 'id_user' => $root->id_user]);
        $this->login($this->manager());
        $this->put("/master/user/$emp", $this->payload(['id_group' => 1, 'username' => 'root']))->assertForbidden();
        $this->post("/master/user/$emp/toggle")->assertForbidden();
        $this->post("/master/user/$emp/reset-password", ['password' => 'baru-12345', 'password_confirmation' => 'baru-12345'])->assertForbidden();
        $this->assertTrue($root->fresh()->active());
    }

    public function test_tidak_bisa_memberi_role_yang_haknya_melebihi_hak_aktor_atau_mengubah_role_sendiri(): void
    {
        $manager = $this->manager();
        DB::table('groups')->insert(['id_group' => 3, 'nm_group' => 'Besar', 'ket' => '']);
        DB::table('ms_group_akses')->insert(['id_group' => 3, 'id_menu' => self::MENUS['master_program'], 'aksi' => 'approve']);
        DB::table('ms_group_akses')->insert(['id_group' => 2, 'id_menu' => self::MENUS['master_data.users'], 'aksi' => 'lihat']);
        $this->login($manager);
        $this->post('/master/user', $this->payload(['id_group' => 3]))->assertSessionHasErrors('id_group');
        $this->assertSame(0, DB::table('ms_user_karyawan')->count());
        $this->post('/master/user', $this->payload(['id_group' => 2]))->assertSessionHasNoErrors();

        $mine = DB::table('ms_user_karyawan')->insertGetId(['kode' => 'USR-009', 'nama' => 'Saya', 'id_group' => 2, 'aktif' => 1, 'id_user' => $manager->id_user]);
        $this->put("/master/user/$mine", $this->payload(['id_group' => 3, 'username' => 'manager']))->assertSessionHasErrors('id_group');
        $this->assertSame(2, (int) DB::table('user_groups')->where('id_user', $manager->id_user)->value('id_group'));
    }

    public function test_reset_password_hash_dan_log_tanpa_sandi_serta_akun_tanpa_login_ditolak(): void
    {
        $this->login($this->admin())->post('/master/user', $this->payload());
        $this->post('/master/user', ['nama' => 'Tanpa Akun', 'id_group' => 2]);
        [$withAccount, $without] = DB::table('ms_user_karyawan')->orderBy('id')->pluck('id')->all();
        $this->post("/master/user/$withAccount/reset-password", ['password' => 'pendek', 'password_confirmation' => 'pendek'])->assertSessionHasErrors('password');
        $this->post("/master/user/$without/reset-password", ['password' => 'sandi-baru-1', 'password_confirmation' => 'sandi-baru-1'])->assertSessionHasErrors('password');
        $this->post("/master/user/$withAccount/reset-password", ['password' => 'sandi-baru-1', 'password_confirmation' => 'sandi-baru-1'])->assertSessionHasNoErrors();
        $idUser = DB::table('ms_user_karyawan')->where('id', $withAccount)->value('id_user');
        $this->assertTrue(password_verify('sandi-baru-1', DB::table('users')->where('id_user', $idUser)->value('password')));
        $row = DB::table('logs')->where('action', 'Reset Password')->first();
        $this->assertNull($row->data);
        $this->assertStringNotContainsString('sandi-baru-1', json_encode($row));
    }

    public function test_hak_user_diperiksa_di_server(): void
    {
        $viewer = $this->account('viewer');
        $this->allow($viewer, 'master_data.users', 'lihat');
        $this->login($viewer)->get('/master/user')->assertOk();
        $this->post('/master/user', $this->payload())->assertForbidden();
        $this->put('/master/user/1', $this->payload())->assertForbidden();
        $this->post('/master/user/1/toggle')->assertForbidden();
        $this->post('/master/user/1/reset-password', ['password' => 'x'])->assertForbidden();
    }

    public function test_role_crud_nama_unik_superadmin_terkunci_dan_hapus_diblokir(): void
    {
        $this->login($this->admin());
        $this->post('/master/role', ['nama' => 'Sales Baru', 'ket' => 'uji'])->assertSessionHasNoErrors();
        $this->post('/master/role', ['nama' => 'sales baru'])->assertSessionHasErrors('nama');
        $this->post('/master/role', ['nama' => str_repeat('a', 51)])->assertSessionHasErrors('nama');
        $id = DB::table('groups')->where('nm_group', 'Sales Baru')->value('id_group');
        $this->put("/master/role/$id", ['nama' => 'Sales Final', 'ket' => 'x'])->assertSessionHasNoErrors();
        $log = json_decode(DB::table('logs')->where('action', 'Edit')->value('data'), true);
        $this->assertSame('Sales Baru', $log['old']['nm_group']);
        $this->assertSame('Sales Final', $log['new']['nm_group']);
        $this->put('/master/role/1', ['nama' => 'Dihack'])->assertSessionHasErrors('nama');
        $this->delete('/master/role/1')->assertSessionHasErrors('hapus');
        DB::table('user_groups')->insert(['id_user' => 1, 'id_group' => $id]);
        $this->delete("/master/role/$id")->assertSessionHasErrors('hapus');
        $this->assertSame(1, DB::table('groups')->where('id_group', $id)->count());
        DB::table('user_groups')->where('id_group', $id)->delete();
        DB::table('ms_group_akses')->insert(['id_group' => $id, 'id_menu' => 1, 'aksi' => 'lihat']);
        $this->delete("/master/role/$id")->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('groups')->where('id_group', $id)->count());
        $this->assertSame(0, DB::table('ms_group_akses')->where('id_group', $id)->count());
    }

    public function test_role_milik_sendiri_tidak_bisa_diubah_atau_dihapus_oleh_non_superadmin(): void
    {
        $manager = $this->manager();
        $this->allow($manager, 'master_data.role', 'lihat', 'tambah', 'ubah', 'hapus');
        $this->login($manager);
        $this->put('/master/role/2', ['nama' => 'Sendiri'])->assertForbidden();
        $this->delete('/master/role/2')->assertForbidden();
        $this->post('/master/role', ['nama' => 'Baru'])->assertSessionHasNoErrors();
        $this->assertSame('Staf', DB::table('groups')->where('id_group', 2)->value('nm_group'));
    }
}

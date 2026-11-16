<?php

namespace Tests\Feature\Master;

use Illuminate\Support\Facades\DB;
use Tests\Support\ServerDataTableScenarios;

class ServerDataTableTest extends MasterTestCase
{
    use ServerDataTableScenarios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login($this->admin());
    }

    public function test_semua_endpoint_memerlukan_login_dan_hak_view_masing_masing(): void
    {
        $this->app['auth']->forgetGuards();
        foreach (self::TABLES as $path => $menu) {
            $this->getJson('/master/'.$path.'/data')->assertUnauthorized();
        }
        $user = $this->account('tanpa_hak');
        $this->login($user);
        foreach (self::TABLES as $path => $menu) {
            $this->getJson('/master/'.$path.'/data')->assertForbidden();
        }
        $this->allow($user, 'master_data.role', 'lihat');
        $this->getJson('/master/role/data')->assertOk();
        $this->getJson('/master/user/data')->assertForbidden();
        $this->getJson('/master/jenis-produk/data')->assertForbidden();
        $this->post('/master/role', ['nama' => 'Dilarang'])->assertForbidden();
        $this->assertDatabaseMissing('groups', ['nm_group' => 'Dilarang']);
    }

    public function test_index_hanya_mengirim_konfigurasi_bukan_baris_tabel(): void
    {
        DB::table('ms_jenis_produk')->insert(['kode' => 'JP-1', 'nama' => 'Rahasia baris']);
        foreach (array_keys(self::TABLES) as $path) {
            $this->get('/master/'.$path)->assertOk()->assertInertia(fn ($page) => $page->missing('rows'));
        }
    }
}

<?php

namespace Tests\Feature\Master;

use App\Support\Coa;
use App\Support\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

class CoaSourceTest extends MasterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.gl_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'labmania.coa_connection' => 'gl_test']);
        DB::connection('gl_test')->statement('CREATE TABLE coa_master (id INTEGER PRIMARY KEY, no_perkiraan TEXT, nama TEXT, kdcab TEXT, level TEXT)');
        DB::table('coa_master')->insert(['id' => 24, 'no_perkiraan' => 'OLD', 'nama' => 'Akun lama berbeda']);
        Coa::query()->insert(['id' => 24, 'no_perkiraan' => '1102-01-01', 'nama' => 'Piutang GL', 'kdcab' => '101-A', 'level' => '5']);
        DB::connection('gl_test')->table('coa_master')->insert([
            ['id' => 4, 'no_perkiraan' => 'HEADER', 'nama' => 'Header GL', 'level' => '4'],
            ['id' => 6, 'no_perkiraan' => 'EMPTY', 'nama' => 'Tanpa level', 'level' => null],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('gl_test');
        parent::tearDown();
    }

    public function test_invoice_resolves_gl_even_when_legacy_id_collides(): void
    {
        $this->assertSame('Piutang GL', Invoice::account('piutang')->nama);
        $this->assertSame('Piutang GL', Invoice::account('piutang', 24)->nama);
        $this->assertSame('Akun lama berbeda', DB::table('coa_master')->where('id', 24)->value('nama'));
    }

    public function test_missing_gl_account_never_falls_back_to_legacy(): void
    {
        Coa::query()->delete();
        $this->expectException(ValidationException::class);
        Invoice::account('piutang', 24);
    }

    public function test_rate_dropdown_and_validation_use_gl(): void
    {
        $this->login($this->admin());
        DB::table('ms_satuan')->insert(['id' => 91, 'nama' => 'Event', 'code' => 'event', 'category' => 'rate_fixed']);
        $this->get('/master/rate/fixed')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Master/Rate')->has('coa', 1)->where('coa.0.no_perkiraan', '1102-01-01'));
        $input = ['item' => 'Biaya GL', 'id_satuan' => 91, 'default_cost' => 100, 'coa' => 'OLD'];
        $this->post('/master/rate/fixed', $input)->assertSessionHasErrors('coa');
        $input['coa'] = 'HEADER';
        $this->post('/master/rate/fixed', $input)->assertSessionHasErrors('coa');
        $input['coa'] = '1102-01-01';
        $this->post('/master/rate/fixed', $input)->assertSessionHasNoErrors();
        $this->assertSame('1102-01-01', DB::table('ms_rate')->value('coa'));
        $this->assertSame(1, Coa::query()->count());
    }

    public function test_invoice_rejects_non_level_five_even_by_direct_id(): void
    {
        $this->assertSame([24], Coa::query()->pluck('id')->all());
        $this->expectException(ValidationException::class);
        Invoice::account('piutang', 4);
    }

    public function test_invoice_rejects_configured_non_level_five_account(): void
    {
        config(['labmania.invoice.coa.piutang.id' => 6]);
        $this->expectException(ValidationException::class);
        Invoice::account('piutang');
    }

    public function test_unchanged_rate_coa_must_still_be_level_five(): void
    {
        $this->login($this->admin());
        DB::table('ms_satuan')->insert(['id' => 91, 'nama' => 'Event', 'code' => 'event', 'category' => 'rate_fixed']);
        $id = DB::table('ms_rate')->insertGetId(['kode' => 'FC-LAMA', 'tipe' => 'fixed', 'item' => 'Rate Lama',
            'id_satuan' => 91, 'default_cost' => 100, 'coa' => 'HEADER', 'aktif' => 1]);
        $input = ['item' => 'Rate Diubah', 'id_satuan' => 91, 'default_cost' => 100, 'coa' => 'HEADER'];
        $this->put('/master/rate/fixed/'.$id, $input)->assertSessionHasErrors('coa');
        $this->assertSame('Rate Lama', DB::table('ms_rate')->where('id', $id)->value('item'));
        $input['coa'] = '1102-01-01';
        $this->put('/master/rate/fixed/'.$id, $input)->assertSessionHasNoErrors();
        $this->assertSame('1102-01-01', DB::table('ms_rate')->where('id', $id)->value('coa'));
    }
}

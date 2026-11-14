<?php

namespace Tests\Mysql;

use App\Models\User;
use App\Support\Quotation;
use App\Support\Sales;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Sales\Phase3Scenarios;

class Phase3MysqlTest extends MysqlTestCase
{
    use Phase3Scenarios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfiguration();
        $this->seedPhase3();
    }

    protected function seedAttributes(): array
    {
        return $this->attributes();
    }

    protected function login(User $user): static
    {
        return $this->loginAs($user);
    }

    protected function admin(): User
    {
        $user = $this->superadmin();
        $user->update(['username' => 'root']);

        return $user;
    }

    protected function account(string $name = 'staf'): User
    {
        return User::forceCreate(['username' => $name, 'nm_lengkap' => $name, 'password' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
    }

    public function test_generated_unique_surat_tugas_preserves_history_and_so_is_unique_per_quotation(): void
    {
        $this->login($this->admin());
        $p = $this->program();
        $q = $this->quote($p);
        $base = ['id_penjualan' => $p, 'id_trainer' => $this->trainer, 'id_asisten' => $this->employee, 'tanggal' => today()];
        DB::table('surat_kerja_trainer')->insert($base + ['nomor' => 'ST-1', 'status' => 'diganti']);
        DB::table('surat_kerja_trainer')->insert($base + ['nomor' => 'ST-2', 'status' => 'diganti']);
        DB::table('surat_kerja_trainer')->insert($base + ['nomor' => 'ST-3', 'status' => 'berlaku']);
        try {
            DB::table('surat_kerja_trainer')->insert($base + ['nomor' => 'ST-4', 'status' => 'berlaku']);
            $this->fail('Surat aktif ganda lolos.');
        } catch (QueryException $e) {
            $this->assertSame(1062, $e->errorInfo[1]);
        }
        $this->assertSame(3, DB::table('surat_kerja_trainer')->count());
        DB::table('sales_order')->insert(['id_penjualan' => $p, 'id_quotation' => $q->id, 'nomor' => 'SO-1', 'tanggal' => today(), 'status' => 'open']);
        $this->expectException(QueryException::class);
        DB::table('sales_order')->insert(['id_penjualan' => $p, 'id_quotation' => $q->id, 'nomor' => 'SO-2', 'tanggal' => today(), 'status' => 'open']);
    }

    public function test_parallel_confirmation_and_deal_wait_for_same_program_lock(): void
    {
        $this->login($this->admin());
        $p = $this->program();
        $q = $this->quote($p);
        DB::beginTransaction();
        Sales::program($p, true);
        $cfg = config('database.connections.mysql');
        $second = new \PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']}", $cfg['username'], $cfg['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $second->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $second->beginTransaction();
        try {
            $statement = $second->prepare('SELECT id FROM penjualan_program WHERE id = ? FOR UPDATE');
            $statement->execute([$p]);
            $this->fail('Permintaan kedua melewati lock program.');
        } catch (\PDOException $e) {
            $this->assertSame(1205, $e->errorInfo[1]);
        } finally {
            $second->rollBack();
            DB::rollBack();
        }
        $this->assertSame('draft', Quotation::row($q->id)->status);
    }
}

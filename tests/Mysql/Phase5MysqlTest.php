<?php

namespace Tests\Mysql;

use App\Models\User;
use App\Support\Sales;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Sales\Phase5Scenarios;

class Phase5MysqlTest extends MysqlTestCase
{
    use Phase5Scenarios;
    use \Tests\Feature\Sales\TopReconfirmationScenarios;
    use \Tests\Feature\Sales\RefundApprovalScenarios;

    public function test_concurrent_revision_payment_and_refund_share_program_lock_and_unique_request(): void
    {
        $this->runReschedule5();
        $row = DB::table('penjualan_program_reschedule')->first();
        $cfg = config('database.connections.mysql');
        $second = new \PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']}", $cfg['username'], $cfg['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $second->exec('SET SESSION innodb_lock_wait_timeout = 1');
        DB::beginTransaction();
        Sales::program($row->id_penjualan, true);
        $second->beginTransaction();
        try {
            $st = $second->prepare('SELECT id FROM penjualan_program WHERE id = ? FOR UPDATE');
            $st->execute([$row->id_penjualan]);
            $this->fail('Concurrent mutation bypassed program lock.');
        } catch (\PDOException $e) {
            $this->assertSame(1205, $e->errorInfo[1]);
        } finally {
            $second->rollBack();
            DB::rollBack();
        }
        $duplicate = (array) $row;
        unset($duplicate['id']);
        try {
            DB::table('penjualan_program_reschedule')->insert($duplicate);
            $this->fail('Duplicate request accepted.');
        } catch (QueryException $e) {
            $this->assertSame(1062, $e->errorInfo[1]);
        }
        $this->assertSame(1, DB::table('penjualan_program_reschedule')->count());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfiguration();
        $this->seedPhase5();
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
        return $this->superadmin();
    }

    protected function account(string $name): User
    {
        return User::forceCreate(['username' => $name, 'nm_lengkap' => $name, 'password' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]), 'st_aktif' => 1, 'deleted' => 0, 'ga_secret' => 'JBSWY3DPEHPK3PXP']);
    }
}

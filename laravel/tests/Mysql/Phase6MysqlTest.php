<?php

namespace Tests\Mysql;

use App\Support\Expenses;
use App\Support\Participants;
use App\Support\Sales;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Feature\Sales\Phase6Scenarios;

class Phase6MysqlTest extends MysqlTestCase
{
    use Phase6Scenarios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfiguration();
        (require base_path('database/migrations/2026_11_14_110000_operasional_biaya.php'))->up();
        $this->seedPhase6();
    }

    public function test_program_lock_and_database_constraints_protect_budget_and_close(): void
    {
        $id = Expenses::save($this->program6, $this->input6(), false);
        $cfg = config('database.connections.mysql');
        $other = new \PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']}", $cfg['username'], $cfg['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $other->exec('SET SESSION innodb_lock_wait_timeout=1');
        DB::beginTransaction();
        Sales::program($this->program6, true);
        try {
            $st = $other->prepare('SELECT id FROM penjualan_program WHERE id=? FOR UPDATE');
            $st->execute([$this->program6]);
            $this->fail('Lock program dilewati.');
        } catch (\PDOException $e) {
            $this->assertSame(1205, $e->errorInfo[1]);
        } finally {
            DB::rollBack();
        }
        $row = (array) DB::table('pengajuan_biaya')->where('id', $id)->first();
        unset($row['id']);
        try {
            DB::table('pengajuan_biaya')->insert($row);
            $this->fail('Nomor ganda diterima.');
        } catch (QueryException $e) {
            $this->assertSame(1062, $e->errorInfo[1]);
        }
        try {
            DB::table('pengajuan_biaya_detail')->insert(['id_pengajuan' => $id, 'item' => 'Tanpa sumber', 'qty' => 1, 'harga' => 1, 'total' => 1]);
            $this->fail('Sumber kosong diterima.');
        } catch (QueryException $e) {
            $this->assertSame(3819, $e->errorInfo[1]);
        }
    }

    public function test_competing_requests_cannot_overspend_or_write_after_close(): void
    {
        $input = $this->input6(['items' => [['key' => 'c'.$this->cost6, 'qty' => '1', 'harga' => '100000']]]);
        DB::beginTransaction();
        Sales::program($this->program6, true);
        $worker = $this->expenseWorker($input);
        try {
            Expenses::save($this->program6, $input, false);
            DB::commit();
            $worker->wait();
            $this->assertSame(0, $worker->getExitCode(), $worker->getErrorOutput());
            $this->assertStringContainsString('items', $worker->getOutput());
            $this->assertSame(1, DB::table('pengajuan_biaya')->count());
            $this->assertSame('0.00', Expenses::budget($this->program6, 'fixed')['sisa']);
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            $worker->stop();
        }
        Expenses::transition(DB::table('pengajuan_biaya')->value('id'), false, 'batal');
        $invoice = $this->invoice6();
        DB::table('invoice')->where('id', $invoice)->update(['tanggal_bayar' => '2026-11-14']);
        DB::beginTransaction();
        Sales::program($this->program6, true);
        $worker = $this->expenseWorker($input);
        try {
            Participants::close($this->program6);
            DB::commit();
            $worker->wait();
            $this->assertSame(0, $worker->getExitCode(), $worker->getErrorOutput());
            $this->assertStringContainsString('program', $worker->getOutput());
            $this->assertSame(1, DB::table('pengajuan_biaya')->count());
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            $worker->stop();
        }
    }

    private function expenseWorker(array $input): Process
    {
        $code = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$in = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
config(['database.default' => 'mysql', 'database.connections.mysql' => $in['database']]);
Illuminate\Support\Facades\DB::purge('mysql');
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'labmania_laravel_test') exit(2);
Illuminate\Support\Facades\Date::setTestNow('2026-11-14 00:00:00');
Illuminate\Support\Facades\Auth::loginUsingId($in['actor']);
echo "ready\n"; flush();
try {
    App\Support\Expenses::save($in['program'], $in['input'], false);
    echo 'unexpected-success';
} catch (Illuminate\Validation\ValidationException $e) {
    echo json_encode(array_keys($e->errors()));
}
PHP;
        $worker = new Process([PHP_BINARY, '-r', $code], base_path(), ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'labmania_laravel_test', 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array']);
        $worker->setInput(json_encode(['database' => config('database.connections.mysql'), 'actor' => $this->root6->id_user, 'program' => $this->program6, 'input' => $input], JSON_THROW_ON_ERROR));
        $worker->setTimeout(60);
        $worker->start();
        $ready = $worker->waitUntil(fn ($type, $output) => str_contains($output, 'ready'));
        $this->assertTrue($ready, $worker->getErrorOutput());

        return $worker;
    }
}

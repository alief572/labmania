<?php

namespace Tests\Mysql;

use App\Support\MigrationFailure;
use App\Support\MigrationManifest;
use App\Support\MigrationRehearsal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrationMysqlTest extends MysqlTestCase
{
    public function test_packaged_schema_and_two_rehearsals_preserve_financial_history(): void
    {
        $this->assertSame('labmania_laravel_test', DB::getDatabaseName());
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (MigrationRehearsal::tables(DB::connection()) as $table) {
                DB::statement('DROP TABLE `'.$table['name'].'`');
            }
            DB::unprepared(file_get_contents(base_path('database/schema/rehearsal.mysql.sql')));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        $this->assertSame(7, DB::table('migrations')->count());
        DB::table('groups')->insert(['id_group' => 1, 'nm_group' => 'admin', 'ket' => 'Legacy Superadmin']);
        $rootUser = $this->superadmin();
        $master = DB::table('master_program')->insertGetId(['nomor' => 'MP-HISTORY', 'id_paket_produk' => 1, 'status' => 'final', 'jam_pembelajaran' => 8]);
        $program = DB::table('penjualan_program')->insertGetId(['nomor' => 'PJP-HISTORY', 'id_master_program' => $master, 'jenis' => 'public']);
        $quotation = DB::table('quotation')->insertGetId(['nomor' => 'QTN-HISTORY', 'id_penjualan' => $program, 'tanggal' => '2025-01-02', 'status' => 'deal']);
        $so = DB::table('sales_order')->insertGetId(['nomor' => 'SO-HISTORY', 'id_quotation' => $quotation, 'id_penjualan' => $program, 'tanggal' => '2025-01-03']);
        $coa = DB::table('coa_master')->insertGetId(['no_perkiraan' => 'HISTORY', 'nama' => 'Akun fixture']);
        $snapshot = '{"historis":"nilai master saat dokumen dibuat","amount":"9007199254740993"}';
        DB::table('invoice')->insert(['nomor' => 'INV-HISTORY', 'tanggal' => '2025-01-03', 'id_sales_order' => $so, 'id_quotation' => $quotation,
            'id_penjualan' => $program, 'cara_tagih' => 'satu', 'perlakuan_ppn' => 'KB', 'penjualan' => '9007199254740993', 'dpp' => '9007199254740993',
            'ppn' => 0, 'biaya_lain' => 0, 'total_tagihan' => '9007199254740993', 'piutang' => '9007199254740993', 'coa_penjualan' => $coa,
            'snapshot' => $snapshot, 'created_by' => $rootUser->id_user, 'created_date' => '2025-01-03 12:13:14']);
        DB::table('logs')->insert(['module' => 'Invoice', 'action' => 'Tambah', 'description' => 'Histori fixture', 'created_by' => $rootUser->id_user, 'created_date' => '2025-01-03 12:13:14']);

        $root = sys_get_temp_dir().'/labmania-mysql-migration-'.bin2hex(random_bytes(8));
        mkdir($root.'/snapshot/files', 0777, true);
        mkdir($root.'/documents', 0777, true);
        file_put_contents($root.'/snapshot/dump.sql', '-- synthetic schema/data fixture only');
        config(['database.connections.migration_source' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
            'migration.state_root' => $root.'/state', 'labmania.document_root' => $root.'/documents']);
        DB::purge('migration_source');
        $source = DB::connection('migration_source');
        $manifest = ['version' => 1, 'snapshot' => ['id' => 'synthetic-mysql', 'captured_at' => '2026-11-14T10:00:00+07:00', 'consistent' => true,
            'dump' => ['path' => 'dump.sql', 'sha256' => hash_file('sha256', $root.'/snapshot/dump.sql')], 'files_root' => 'files', 'files' => []],
            'approval' => ['by' => 'Pemilik fixture', 'at' => '2026-11-14'], 'tables' => [], 'excluded_source_tables' => [], 'empty_target_tables' => [],
            'relations' => [['table' => 'penjualan_program', 'columns' => ['id_master_program'], 'references' => 'master_program', 'reference_columns' => ['id']]]];
        $pending = [];
        try {
            foreach (MigrationRehearsal::tables(DB::connection()) as $table) {
                $name = $table['name'];
                if ($name === 'migrations') {
                    continue;
                }
                if (DB::table($name)->count() === 0) {
                    $manifest['empty_target_tables'][$name] = 'Tidak ada data dalam fixture sintetis.';

                    continue;
                }
                $columns = DB::getSchemaBuilder()->getColumns($name);
                $writable = array_values(array_filter($columns, fn ($c) => ! ($c['generation'] ?? null)));
                $source->getSchemaBuilder()->create($name, function (Blueprint $t) use ($writable) {
                    foreach ($writable as $column) {
                        $t->text($column['name'])->nullable();
                    }
                });
                foreach (DB::table($name)->get() as $row) {
                    $source->table($name)->insert(array_intersect_key((array) $row, array_flip(array_column($writable, 'name'))));
                }
                $query = $source->table($name);
                foreach ($writable as $column) {
                    $query->orderBy($column['name']);
                }
                $hash = hash_init('sha256');
                foreach ($query->cursor() as $row) {
                    hash_update($hash, MigrationRehearsal::canonical((array) $row)."\n");
                }
                $primary = collect(DB::getSchemaBuilder()->getIndexes($name))->first(fn ($i) => $i['primary']);
                $names = array_column($writable, 'name');
                $pending[$name] = ['source' => $name, 'target' => $name, 'key' => $primary['columns'], 'columns' => array_combine($names, $names),
                    'expected_rows' => $source->table($name)->count(), 'source_sha256' => hash_final($hash), 'omit_columns' => []];
            }
            while ($pending) {
                $progress = false;
                foreach ($pending as $name => $table) {
                    $parents = array_column(DB::getSchemaBuilder()->getForeignKeys($name), 'foreign_table');
                    if (array_intersect($parents, array_keys($pending))) {
                        continue;
                    }
                    $manifest['tables'][] = $table;
                    unset($pending[$name]);
                    $progress = true;
                }
                $this->assertTrue($progress, 'Siklus FK fixture belum dipetakan.');
            }
            file_put_contents($root.'/snapshot/manifest.json', json_encode($manifest));
            foreach ([1, 2] as $run) {
                $this->clearTarget();
                config(['migration.state_root' => $root.'/state-'.$run]);
                $service = new MigrationRehearsal(new MigrationManifest($root.'/snapshot/manifest.json'));
                $report = $service->run('impor', true);
                $this->assertTrue($report['verified']);
                $this->assertSame('9007199254740993', $report['decimal_totals']['invoice']['piutang']);
                $this->assertSame($snapshot, DB::table('invoice')->value('snapshot'));
                $this->assertSame('2025-01-03 12:13:14', DB::table('logs')->value('created_date'));
                $this->assertSame('admin', DB::table('groups')->where('id_group', 1)->value('nm_group'));
                $this->assertTrue($service->run('impor', true)['verified']);
                $this->assertTrue($service->run('verifikasi')['verified']);
                $reports[] = $report['tables'];
            }
            $this->assertSame($reports[0], $reports[1]);
            $this->assertSame(1, DB::table('invoice')->count());

            $source->table('invoice')->update(['status' => 'status_tidak_dikenal']);
            $query = $source->table('invoice');
            foreach ($source->getSchemaBuilder()->getColumns('invoice') as $column) {
                $query->orderBy($column['name']);
            }
            $hash = hash_init('sha256');
            foreach ($query->cursor() as $row) {
                hash_update($hash, MigrationRehearsal::canonical((array) $row)."\n");
            }
            foreach ($manifest['tables'] as &$table) {
                if ($table['target'] === 'invoice') {
                    $table['source_sha256'] = hash_final($hash);
                }
            }
            unset($table);
            file_put_contents($root.'/snapshot/invalid-status.json', json_encode($manifest));
            $this->clearTarget();
            config(['migration.state_root' => $root.'/invalid-state']);
            try {
                (new MigrationRehearsal(new MigrationManifest($root.'/snapshot/invalid-status.json')))->run('impor', true);
                $this->fail('Status asing diterima.');
            } catch (MigrationFailure $e) {
                $this->assertStringContainsString('enum', $e->getMessage());
            }
            $this->assertSame(0, DB::table('users')->count());
            $this->assertFileDoesNotExist($root.'/invalid-state/receipt.json');
        } finally {
            DB::purge('migration_source');
            File::deleteDirectory($root);
        }
    }

    private function clearTarget(): void
    {
        $this->assertSame('labmania_laravel_test', DB::getDatabaseName());
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (MigrationRehearsal::tables(DB::connection()) as $table) {
                if ($table['name'] !== 'migrations') {
                    DB::table($table['name'])->delete();
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}

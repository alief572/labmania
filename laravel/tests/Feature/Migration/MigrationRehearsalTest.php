<?php

namespace Tests\Feature\Migration;

use App\Support\MigrationFailure;
use App\Support\MigrationManifest;
use App\Support\MigrationOperations;
use App\Support\MigrationRehearsal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrationRehearsalTest extends TestCase
{
    private string $root;

    private array $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/labmania-rehearsal-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/snapshot/files/uploads/quotation_dokumen', 0777, true);
        mkdir($this->root.'/target', 0777, true);
        file_put_contents($this->root.'/snapshot/dump.sql', '-- synthetic only');
        file_put_contents($this->root.'/snapshot/files/uploads/quotation_dokumen/test.pdf', '%PDF synthetic');
        config(['database.connections.migration_source' => config('database.connections.sqlite'),
            'migration.state_root' => $this->root.'/state', 'labmania.document_root' => $this->root.'/target']);
        DB::purge('migration_source');
        foreach (['sqlite', 'migration_source'] as $name) {
            DB::connection($name)->getSchemaBuilder()->create('users', function (Blueprint $t) {
                $t->integer('id_user')->primary();
                $t->string('username')->unique();
                $t->string('password');
                $t->string('ga_secret')->nullable();
                $t->dateTime('created_date')->nullable();
            });
        }
        $row = ['id_user' => 17, 'username' => 'fixture', 'password' => 'legacy-hash', 'ga_secret' => 'LEGACYTOTP', 'created_date' => null];
        DB::connection('migration_source')->table('users')->insert($row);
        $this->manifest = [
            'version' => 1,
            'snapshot' => ['id' => 'synthetic', 'captured_at' => '2026-11-14T10:00:00+07:00', 'consistent' => true,
                'dump' => ['path' => 'dump.sql', 'sha256' => hash_file('sha256', $this->root.'/snapshot/dump.sql')],
                'files_root' => 'files', 'files' => [['path' => 'uploads/quotation_dokumen/test.pdf', 'size' => 14,
                    'sha256' => hash_file('sha256', $this->root.'/snapshot/files/uploads/quotation_dokumen/test.pdf')]]],
            'approval' => ['by' => 'Pemilik fixture', 'at' => '2026-11-14'],
            'tables' => [['source' => 'users', 'target' => 'users', 'key' => ['id_user'],
                'columns' => array_combine(array_keys($row), array_keys($row)), 'omit_columns' => [],
                'expected_rows' => 1, 'source_sha256' => $this->digest([$row])]],
            'excluded_source_tables' => [], 'empty_target_tables' => [], 'relations' => [],
        ];
    }

    protected function tearDown(): void
    {
        DB::disconnect('migration_source');
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    private function digest(array $rows): string
    {
        $raw = '';
        foreach ($rows as $row) {
            ksort($row);
            $raw .= json_encode(array_map(fn ($v) => $v === null ? null : (string) $v, $row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        }

        return hash('sha256', $raw);
    }

    private function service(bool $allowLoginMetadata = false): MigrationRehearsal
    {
        file_put_contents($this->root.'/snapshot/manifest.json', json_encode($this->manifest));

        return new MigrationRehearsal(new MigrationManifest($this->root.'/snapshot/manifest.json'), $allowLoginMetadata);
    }

    public function test_simulation_does_not_write_and_apply_preserves_history_and_is_idempotent(): void
    {
        $this->assertSame(1, $this->service()->run('impor', false)['tables']['users']['missing']);
        $this->assertSame(0, DB::table('users')->count());
        $this->assertDirectoryDoesNotExist($this->root.'/state');
        $first = $this->service()->run('impor', true);
        $this->assertTrue($first['verified']);
        $this->assertSame(1, DB::table('users')->count());
        $user = DB::table('users')->first();
        $this->assertSame(17, $user->id_user);
        $this->assertSame('legacy-hash', $user->password);
        $this->assertSame('LEGACYTOTP', $user->ga_secret);
        $this->assertNull($user->created_date);
        $this->assertSame('%PDF synthetic', file_get_contents($this->root.'/target/uploads/quotation_dokumen/test.pdf'));
        $this->assertTrue($this->service()->run('impor', true)['verified']);
        $this->assertTrue($this->service()->run('verifikasi')['verified']);
        $this->assertSame(1, DB::table('users')->count());
    }

    public function test_conflicting_target_row_is_rejected_without_overwrite(): void
    {
        DB::table('users')->insert(['id_user' => 17, 'username' => 'different', 'password' => 'untouched']);
        $this->expectException(\RuntimeException::class);
        try {
            $this->service()->run('impor', true);
        } finally {
            $this->assertSame('untouched', DB::table('users')->value('password'));
        }
    }

    public function test_readonly_verification_tolerates_only_login_metadata_and_still_rejects_password_changes(): void
    {
        foreach (['sqlite', 'migration_source'] as $connection) {
            DB::connection($connection)->getSchemaBuilder()->table('users', function (Blueprint $t) {
                $t->string('ip')->nullable();
                $t->dateTime('login_terakhir')->nullable();
            });
        }
        $this->manifest['tables'][0]['columns'] += ['ip' => 'ip', 'login_terakhir' => 'login_terakhir'];
        $this->manifest['tables'][0]['source_sha256'] = $this->digest([(array) DB::connection('migration_source')->table('users')->first()]);
        $this->service()->run('impor', true);
        DB::table('users')->update(['ip' => '127.0.0.1', 'login_terakhir' => '2026-11-15 10:00:00']);
        try {
            $this->service()->run('verifikasi');
            $this->fail('Verifikasi default harus tetap ketat.');
        } catch (MigrationFailure $e) {
            $this->assertStringContainsString('Konflik target: users', $e->getMessage());
        }
        $report = $this->service(true)->run('verifikasi');
        $this->assertTrue($report['verified']);
        $this->assertSame(['ip', 'login_terakhir'], $report['tables']['users']['comparison_ignored_columns']);
        $this->assertSame(1, $report['tables']['users']['login_metadata_changed_rows']);
        $this->assertSame('127.0.0.1', DB::table('users')->value('ip'));
        try {
            $this->service(true)->run('impor', true);
            $this->fail('Toleransi tidak boleh untuk impor.');
        } catch (MigrationFailure $e) {
            $this->assertStringContainsString('baca-saja', $e->getMessage());
        }
        DB::table('users')->update(['password' => 'changed-fixture']);
        $this->expectException(MigrationFailure::class);
        $this->service(true)->run('verifikasi');
    }

    public function test_snapshot_database_tampering_is_rejected(): void
    {
        DB::connection('migration_source')->table('users')->where('id_user', 17)->update(['username' => 'changed']);
        $this->expectException(\RuntimeException::class);
        $this->service()->run('impor', true);
    }

    public function test_manifest_switch_is_rejected_even_when_rows_match(): void
    {
        $this->service()->run('impor', true);
        $this->manifest['snapshot']['id'] = 'other';
        $this->expectException(\RuntimeException::class);
        $this->service()->run('impor', true);
    }

    public function test_extra_source_table_cannot_be_silently_skipped(): void
    {
        DB::connection('migration_source')->getSchemaBuilder()->create('history', fn (Blueprint $t) => $t->integer('id'));
        $this->expectException(\RuntimeException::class);
        $this->service()->run('profil');
    }

    public function test_corrupted_private_file_is_rejected(): void
    {
        file_put_contents($this->root.'/snapshot/files/uploads/quotation_dokumen/test.pdf', 'changed');
        $this->expectException(\RuntimeException::class);
        $this->service()->run('impor', true);
    }

    public function test_production_environment_is_rejected_before_connecting(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(\RuntimeException::class);
        $this->service()->run('impor', true);
    }

    public function test_missing_key_is_rejected(): void
    {
        config(['app.key' => '']);
        $this->expectException(\RuntimeException::class);
        $this->service()->run('impor', true);
    }

    public function test_same_connection_is_rejected(): void
    {
        config(['migration.source' => 'sqlite']);
        $this->expectException(\RuntimeException::class);
        $this->service()->run('profil');
    }

    public function test_changed_application_key_is_rejected_after_import(): void
    {
        $this->service()->run('impor', true);
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
        $this->expectException(\RuntimeException::class);
        $this->service()->run('verifikasi');
    }

    public function test_extra_target_file_is_rejected(): void
    {
        file_put_contents($this->root.'/target/unlisted.pdf', 'fixture');
        $this->expectException(\RuntimeException::class);
        $this->service()->run('impor', true);
    }

    public function test_orphan_prevents_verified_receipt(): void
    {
        foreach (['sqlite', 'migration_source'] as $name) {
            DB::connection($name)->getSchemaBuilder()->create('children', function (Blueprint $t) {
                $t->integer('id')->primary();
                $t->integer('id_user');
            });
        }
        $row = ['id' => 1, 'id_user' => 999];
        DB::connection('migration_source')->table('children')->insert($row);
        $this->manifest['tables'][] = ['source' => 'children', 'target' => 'children', 'key' => ['id'],
            'columns' => ['id' => 'id', 'id_user' => 'id_user'], 'omit_columns' => [], 'expected_rows' => 1, 'source_sha256' => $this->digest([$row])];
        $this->manifest['relations'][] = ['table' => 'children', 'columns' => ['id_user'], 'references' => 'users', 'reference_columns' => ['id_user']];
        try {
            $this->service()->run('impor', true);
            $this->fail('Orphan diterima.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Orphan', $e->getMessage());
        }
        $receipt = json_decode(file_get_contents($this->root.'/state/receipt.json'), true);
        $this->assertNotSame('verified', $receipt['status']);
        $this->expectException(\RuntimeException::class);
        $this->service()->run('verifikasi');
    }

    public function test_explicit_correction_preserves_original_timestamp_and_key(): void
    {
        $correction = $this->root.'/snapshot/correction.jsonl';
        file_put_contents($correction, json_encode(['key' => ['id_user' => 17], 'values' => ['username' => 'resolved'], 'reason' => 'Keputusan pemilik fixture'])."\n");
        $this->manifest['tables'][0]['overrides'] = ['path' => 'correction.jsonl', 'sha256' => hash_file('sha256', $correction)];
        $this->assertTrue($this->service()->run('impor', true)['verified']);
        $this->assertSame('resolved', DB::table('users')->value('username'));
        $this->assertNull(DB::table('users')->value('created_date'));
        $this->assertSame(17, DB::table('users')->value('id_user'));
    }

    public function test_cli_output_does_not_expose_password_or_totp(): void
    {
        $this->service();
        $this->artisan('labmania:migrasi-impor', ['--manifest' => $this->root.'/snapshot/manifest.json'])
            ->doesntExpectOutputToContain('legacy-hash')->doesntExpectOutputToContain('LEGACYTOTP')->assertSuccessful();
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_profile_allows_unapproved_inventory_without_writes(): void
    {
        $this->manifest['tables'] = [];
        $this->manifest['approval'] = ['by' => null, 'at' => null];
        file_put_contents($this->root.'/snapshot/manifest.json', json_encode($this->manifest));
        $service = new MigrationRehearsal(new MigrationManifest($this->root.'/snapshot/manifest.json', false));
        $report = $service->run('profil');
        $this->assertSame(1, $report['inventory']['users']['rows']);
        $this->assertArrayHasKey('sha256', $report['inventory']['users']);
        $this->assertFalse($report['verified']);
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_retry_after_interrupted_table_does_not_duplicate_committed_rows(): void
    {
        foreach (['sqlite', 'migration_source'] as $name) {
            DB::connection($name)->getSchemaBuilder()->create('children', function (Blueprint $t) {
                $t->integer('id')->primary();
                $t->integer('id_user');
            });
        }
        $row = ['id' => 1, 'id_user' => 17];
        DB::connection('migration_source')->table('children')->insert($row);
        $this->manifest['tables'][] = ['source' => 'children', 'target' => 'children', 'key' => ['id'], 'columns' => ['id' => 'id', 'id_user' => 'id_user'],
            'omit_columns' => [], 'expected_rows' => 1, 'source_sha256' => $this->digest([$row])];
        $interrupt = true;
        DB::listen(function ($query) use (&$interrupt) {
            if ($interrupt && str_starts_with($query->sql, 'insert into "children"')) {
                throw new \RuntimeException('Synthetic interruption');
            }
        });
        try {
            $this->service()->run('impor', true);
            $this->fail('Interupsi tidak terjadi.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic interruption', $e->getMessage());
        }
        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame(0, DB::table('children')->count());
        $interrupt = false;
        $this->assertTrue($this->service()->run('impor', true)['verified']);
        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame(1, DB::table('children')->count());
    }

    public function test_runtime_check_does_not_claim_cutover_without_required_configuration(): void
    {
        $report = (new MigrationOperations)->readiness();
        $this->assertFalse($report['runtime_ready']);
        $this->assertFalse($report['cutover_ready']);
        $this->assertFalse($report['checks']['role_Admin']);
        $this->assertFalse($report['checks']['menu_manage_peserta']);
        $this->assertDirectoryDoesNotExist($this->root.'/state');
    }

    public function test_file_target_cannot_be_inside_immutable_source(): void
    {
        mkdir($this->root.'/snapshot/files/nested-target');
        config(['labmania.document_root' => $this->root.'/snapshot/files/nested-target']);
        $this->expectException(\RuntimeException::class);
        $this->service()->run('impor', true);
    }

    public function test_finance_deferral_never_ignores_operational_failures_or_claims_production_cutover(): void
    {
        DB::unprepared('CREATE TABLE groups (id_group INTEGER PRIMARY KEY, nm_group TEXT);
            CREATE TABLE ms_menu (id INTEGER PRIMARY KEY, kode TEXT, judul TEXT, link TEXT, aktif INTEGER);
            CREATE TABLE coa_master (id INTEGER PRIMARY KEY, no_perkiraan TEXT, nama TEXT, level TEXT)');
        DB::table('groups')->insert([['id_group' => 2, 'nm_group' => 'Admin'], ['id_group' => 3, 'nm_group' => 'Direktur']]);
        foreach (['1102-01-01', '2104-01-07', '4101-01-03', '4101-01-02'] as $id => $number) {
            DB::table('coa_master')->insert(['id' => $id + 1, 'no_perkiraan' => $number, 'nama' => 'Akun fixture', 'level' => '5']);
        }
        mkdir($this->root.'/fonts');
        foreach (['times.ttf', 'timesbd.ttf'] as $font) {
            file_put_contents($this->root.'/fonts/'.$font, 'fixture, bukan font cetak');
        }
        config(['navigation' => [], 'session.cookie' => 'labmania_realdata_session', 'app.debug' => false,
            'labmania.operasional.font_dir' => $this->root.'/fonts', 'labmania.audit_retention_enabled' => false]);
        foreach (['bank', 'rekening', 'atas_nama', 'penanda_tangan', 'jabatan'] as $key) {
            config(['labmania.invoice.print.'.$key => null]);
        }
        $operations = new MigrationOperations;
        $this->assertFalse($operations->readiness()['runtime_ready']);
        $report = $operations->readiness(true);
        $this->assertTrue($report['runtime_ready']);
        $this->assertTrue($report['operational_ready']);
        $this->assertTrue($report['finance_deferred']);
        $this->assertFalse($report['finance_ready']);
        $this->assertFalse($report['cutover_ready']);
        $this->assertFalse($report['checks']['print_bank']);
        DB::table('coa_master')->where('id', 1)->delete();
        $report = $operations->readiness(true);
        $this->assertFalse($report['runtime_ready']);
        $this->assertFalse($report['checks']['coa_piutang']);
        config(['session.cookie' => 'ci_session']);
        $this->assertFalse($operations->readiness(true)['checks']['session_isolated']);
    }

    public function test_nullable_legacy_product_projects_child_without_dropping_header_or_fabricating_timestamp(): void
    {
        DB::connection('migration_source')->getSchemaBuilder()->create('legacy_program', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('id_paket_produk')->nullable();
            $t->dateTime('created_date')->nullable();
        });
        $rows = [['id' => 1, 'id_paket_produk' => 9, 'created_date' => null], ['id' => 2, 'id_paket_produk' => null, 'created_date' => null]];
        DB::connection('migration_source')->table('legacy_program')->insert($rows);
        DB::getSchemaBuilder()->create('program_header', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('id_paket_produk')->nullable();
            $t->dateTime('created_date')->nullable();
        });
        DB::getSchemaBuilder()->create('program_product', function (Blueprint $t) {
            $t->integer('id_master_program');
            $t->integer('id_paket_produk');
            $t->dateTime('created_date')->nullable();
        });
        $base = ['source' => 'legacy_program', 'expected_rows' => 2, 'source_sha256' => $this->digest($rows), 'omit_columns' => []];
        $this->manifest['tables'][] = $base + ['target' => 'program_header', 'key' => ['id'], 'columns' => ['id' => 'id', 'id_paket_produk' => 'id_paket_produk', 'created_date' => 'created_date']];
        $this->manifest['tables'][] = $base + ['target' => 'program_product', 'key' => ['id_master_program', 'id_paket_produk'],
            'columns' => ['id_master_program' => 'id', 'id_paket_produk' => 'id_paket_produk', 'created_date' => 'created_date'],
            'projection_not_null' => ['id_paket_produk'], 'projection_reason' => 'Relasi Product hanya jika FK legacy berisi nilai.'];
        $this->assertTrue($this->service()->run('impor', true)['verified']);
        $this->assertSame(2, DB::table('program_header')->count());
        $this->assertSame(1, DB::table('program_product')->count());
        $this->assertNull(DB::table('program_product')->value('created_date'));
    }
}

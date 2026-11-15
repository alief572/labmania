<?php

namespace Tests\Feature\Migration;

use App\Support\MigrationManifest;
use App\Support\MigrationRehearsal;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrationManifestTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/labmania-manifest-'.bin2hex(random_bytes(8));
        mkdir($this->root);
        mkdir($this->root.'/files');
        file_put_contents($this->root.'/snapshot.sql', '-- synthetic');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    private function manifest(array $change = []): string
    {
        $data = array_replace_recursive([
            'version' => 1,
            'snapshot' => ['id' => 'synthetic', 'captured_at' => '2026-11-14T10:00:00+07:00', 'consistent' => true,
                'dump' => ['path' => 'snapshot.sql', 'sha256' => hash_file('sha256', $this->root.'/snapshot.sql')], 'files_root' => 'files', 'files' => []],
            'approval' => ['by' => 'Pemilik data uji', 'at' => '2026-11-14'],
            'tables' => [['source' => 'users', 'target' => 'users', 'key' => ['id_user'], 'columns' => ['id_user' => 'id_user'],
                'expected_rows' => 0, 'source_sha256' => hash('sha256', ''), 'omit_columns' => []]],
            'excluded_source_tables' => [], 'empty_target_tables' => [], 'relations' => [],
        ], $change);
        file_put_contents($this->root.'/manifest.json', json_encode($data));

        return $this->root.'/manifest.json';
    }

    public function test_valid_manifest_is_bound_to_dump_and_approval(): void
    {
        $manifest = new MigrationManifest($this->manifest());
        $this->assertSame('synthetic', $manifest->data['snapshot']['id']);
        $this->assertSame(64, strlen($manifest->hash));
        $this->assertSame(realpath($this->root.'/snapshot.sql'), $manifest->file('snapshot.sql'));
    }

    public function test_changed_dump_is_rejected(): void
    {
        $path = $this->manifest();
        file_put_contents($this->root.'/snapshot.sql', '-- changed');
        $this->expectException(\RuntimeException::class);
        new MigrationManifest($path);
    }

    public function test_unsafe_path_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        new MigrationManifest($this->manifest(['snapshot' => ['dump' => ['path' => '../snapshot.sql']]]));
    }

    public function test_unapproved_mapping_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        new MigrationManifest($this->manifest(['approval' => ['by' => '']]));
    }

    public function test_sql_identifier_injection_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        new MigrationManifest($this->manifest(['tables' => [['source' => 'users; DROP TABLE users']]]));
    }

    public function test_decimal_comparison_keeps_large_values_exact_and_null_distinct(): void
    {
        $columns = ['amount' => ['type_name' => 'decimal']];
        $this->assertSame('{"amount":"9007199254740993"}', MigrationRehearsal::canonical(['amount' => '9007199254740993.000'], $columns));
        $this->assertSame('{"amount":"0"}', MigrationRehearsal::canonical(['amount' => '0.00'], $columns));
        $this->assertSame('{"amount":null}', MigrationRehearsal::canonical(['amount' => null], $columns));
    }
}

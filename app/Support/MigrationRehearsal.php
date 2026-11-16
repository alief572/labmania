<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Illuminate\Database\Connection;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class MigrationRehearsal
{
    private Connection $source;

    private Connection $target;

    public function __construct(private readonly MigrationManifest $manifest, private readonly bool $allowLoginMetadata = false) {}

    public function run(string $mode, bool $apply = false): array
    {
        MigrationManifest::check(in_array($mode, ['profil', 'impor', 'verifikasi'], true) && (! $apply || $mode === 'impor'), 'Mode migrasi tidak valid.');
        MigrationManifest::check(! $this->allowLoginMetadata || ($mode === 'verifikasi' && ! $apply), 'Toleransi metadata login hanya untuk verifikasi baca-saja.');
        $start = microtime(true);
        $this->guard($apply);
        $lock = null;
        if ($apply) {
            $root = config('migration.state_root');
            if (! is_dir($root) && ! mkdir($root, 0700, true)) {
                throw new MigrationFailure('Folder status migrasi tidak dapat dibuat.');
            }
            $lock = fopen($root.'/import.lock', 'c');
            MigrationManifest::check($lock !== false && flock($lock, LOCK_EX | LOCK_NB), 'Import lain sedang berjalan.');
        }
        try {
            $binding = ['manifest_sha256' => $this->manifest->hash, 'key_fingerprint' => hash('sha256', config('app.key'))];
            $receipt = config('migration.state_root').'/receipt.json';
            if (is_file($receipt)) {
                $old = json_decode(file_get_contents($receipt), true, 64, JSON_THROW_ON_ERROR);
                MigrationManifest::check(($old['binding'] ?? null) === $binding, 'Manifest atau APP_KEY berbeda dari import sebelumnya.');
            }
            $report = ['manifest_sha256' => $this->manifest->hash, 'snapshot' => $this->manifest->data['snapshot']['id'],
                'mode' => $apply ? 'terapkan' : $mode, 'source' => $this->source->getDatabaseName(),
                'target' => $this->target->getDatabaseName(), 'verified' => false];
            if ($mode === 'profil' && $this->manifest->data['tables'] === []) {
                foreach (self::tables($this->source) as $table) {
                    $name = $table['name'];
                    MigrationManifest::identifier($name);
                    $hash = hash_init('sha256');
                    $count = 0;
                    foreach ($this->sourceRows(['source' => $name]) as $row) {
                        hash_update($hash, self::canonical((array) $row)."\n");
                        $count++;
                    }
                    $report['inventory'][$name] = ['rows' => $count, 'sha256' => hash_final($hash),
                        'columns' => array_map(fn ($c) => array_intersect_key($c, array_flip(['name', 'type', 'type_name', 'nullable', 'auto_increment'])), $this->source->getSchemaBuilder()->getColumns($name)),
                        'indexes' => $this->source->getSchemaBuilder()->getIndexes($name)];
                }
                $report['files'] = $this->files(false);

                return $report + ['seconds' => round(microtime(true) - $start, 3)];
            }
            $report['source_tables'] = $this->profile();
            $report['files'] = $this->files(false);
            if ($mode === 'profil') {
                return $report + ['seconds' => round(microtime(true) - $start, 3)];
            }
            foreach ($this->manifest->data['tables'] as $table) {
                $actual = $report['source_tables'][$table['source']];
                MigrationManifest::check($actual['rows'] === $table['expected_rows'] && hash_equals($table['source_sha256'], $actual['sha256']), 'Checksum/jumlah sumber berbeda: '.$table['source']);
                $report['tables'][$table['target']] = $this->compare($table, false);
            }
            if ($apply) {
                $this->receipt($receipt, ['binding' => $binding, 'status' => 'in_progress']);
                foreach ($this->manifest->data['tables'] as $table) {
                    // ponytail: commit per tabel; MyISAM COA tidak atomik. Retry hanya menambah baris yang belum ada, tanpa update/delete.
                    $this->target->transaction(fn () => $this->compare($table, true));
                }
                $this->files(true);
                foreach ($this->manifest->data['tables'] as $table) {
                    $report['tables'][$table['target']] = $this->compare($table, false);
                }
            }
            if ($apply || $mode === 'verifikasi') {
                foreach ($report['tables'] as $name => $table) {
                    MigrationManifest::check($table['missing'] === 0, 'Target belum lengkap: '.$name);
                }
                MigrationManifest::check($this->files(false)['missing'] === 0, 'Berkas target belum lengkap.');
                $report['relations'] = $this->relations();
                $report['document_references'] = $this->documentReferences();
                foreach ($this->manifest->data['tables'] as $table) {
                    foreach ($this->target->getSchemaBuilder()->getColumns($table['target']) as $column) {
                        if (in_array($column['type_name'], ['decimal', 'numeric'], true)) {
                            $report['decimal_totals'][$table['target']][$column['name']] = (string) $this->target->table($table['target'])->sum($column['name']);
                        }
                    }
                }
                $this->identity();
                $report['verified'] = true;
            }
            $report['seconds'] = round(microtime(true) - $start, 3);
            $report['peak_memory_bytes'] = memory_get_peak_usage(true);
            if ($apply) {
                $this->receipt($receipt, ['binding' => $binding, 'status' => 'verified', 'report' => $report]);
            }

            return $report;
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    private function guard(bool $apply): void
    {
        $this->target = self::target($apply);
        $test = app()->runningUnitTests() && app()->environment('testing');
        $this->source = DB::connection(config('migration.source'));
        $s = $this->source->getConfig();
        $t = $this->target->getConfig();
        if ($test) {
            foreach ([$s, $t] as $config) {
                MigrationManifest::check(($config['driver'] === 'sqlite' && $config['database'] === ':memory:') || ($config['driver'] === 'mysql' && str_ends_with($config['database'], '_test')), 'Tes migrasi hanya pada database test terisolasi.');
            }
        } else {
            MigrationManifest::check($s['driver'] === 'mysql' && $t['driver'] === 'mysql'
                && $s['host'] === 'rehearsal-db' && $t['host'] === 'rehearsal-db'
                && $s['database'] === 'labmania_legacy_snapshot' && $t['database'] === 'labmania_laravel'
                && $s['username'] === 'snapshot_reader', 'Sumber/target bukan pasangan rehearsal yang diizinkan.');
            MigrationManifest::check(! $apply || app()->isDownForMaintenance(), 'Import memerlukan maintenance mode rehearsal.');
        }
        MigrationManifest::check($this->source !== $this->target, 'Koneksi sumber dan target sama.');
        if ($s['driver'] === 'mysql') {
            MigrationManifest::check(($s['host'] ?? '').':'.($s['port'] ?? '').'/'.$s['database'] !== ($t['host'] ?? '').':'.($t['port'] ?? '').'/'.$t['database'], 'Database sumber dan target sama.');
            $this->source->statement('SET SESSION TRANSACTION READ ONLY');
        }
    }

    public static function target(bool $maintenance = false): Connection
    {
        $test = app()->runningUnitTests() && app()->environment('testing');
        MigrationManifest::check($test || app()->environment('rehearsal'), 'Perintah hanya untuk lingkungan rehearsal.');
        MigrationManifest::check(is_string(config('app.key')) && config('app.key') !== '', 'APP_KEY wajib tersedia.');
        $rawKey = str_starts_with(config('app.key'), 'base64:') ? base64_decode(substr(config('app.key'), 7), true) : config('app.key');
        MigrationManifest::check(is_string($rawKey) && Encrypter::supported($rawKey, config('app.cipher')), 'APP_KEY tidak valid untuk cipher aplikasi.');
        $target = DB::connection();
        $config = $target->getConfig();
        if ($test) {
            MigrationManifest::check(($config['driver'] === 'sqlite' && $config['database'] === ':memory:') || ($config['driver'] === 'mysql' && str_ends_with($config['database'], '_test')), 'Database target tes harus terisolasi.');
        } else {
            MigrationManifest::check($config['driver'] === 'mysql' && $config['host'] === 'rehearsal-db' && $config['database'] === 'labmania_laravel', 'Target bukan database rehearsal.');
            MigrationManifest::check(! $maintenance || app()->isDownForMaintenance(), 'Operasi memerlukan maintenance mode rehearsal.');
        }

        return $target;
    }

    public static function tables(Connection $db): array
    {
        $schema = $db->getSchemaBuilder();

        // Laravel 13 getTables(null) mencakup schema lain yang dapat diakses akun.
        return $schema->getTables($schema->getCurrentSchemaName());
    }

    private function profile(): array
    {
        $data = $this->manifest->data;
        $sourceTables = array_column(self::tables($this->source), 'name');
        $targetTables = array_column(self::tables($this->target), 'name');
        $mappedSources = array_unique(array_column($data['tables'], 'source'));
        $mappedTargets = array_column($data['tables'], 'target');
        MigrationManifest::check(! array_intersect($mappedSources, array_keys($data['excluded_source_tables'])), 'Tabel sumber dipetakan sekaligus dikecualikan.');
        MigrationManifest::check(! array_diff($sourceTables, [...$mappedSources, ...array_keys($data['excluded_source_tables'])]), 'Ada tabel sumber belum dipetakan/dijelaskan.');
        MigrationManifest::check(! array_diff($mappedSources, $sourceTables), 'Tabel sumber tidak ditemukan.');
        MigrationManifest::check(! array_diff($targetTables, [...$mappedTargets, ...array_keys($data['empty_target_tables']), 'migrations']), 'Ada tabel target belum dipetakan/dijelaskan.');
        MigrationManifest::check(! array_diff($mappedTargets, $targetTables), 'Tabel target tidak ditemukan.');
        foreach ($data['empty_target_tables'] as $name => $reason) {
            MigrationManifest::check(in_array($name, $targetTables, true) && ! in_array($name, $mappedTargets, true) && $this->target->table($name)->count() === 0, 'Tabel yang dinyatakan kosong tidak sesuai: '.$name);
        }
        $report = [];
        foreach ($sourceTables as $name) {
            if (in_array($name, $mappedSources, true)) {
                $mapping = current(array_filter($data['tables'], fn ($t) => $t['source'] === $name));
                $columns = array_column($this->source->getSchemaBuilder()->getColumns($name), 'name');
                foreach (array_filter($data['tables'], fn ($t) => $t['source'] === $name) as $plan) {
                    MigrationManifest::check(! array_diff($columns, [...array_values($plan['columns']), ...array_keys($plan['omit_columns'])]), 'Kolom sumber belum dipetakan/dijelaskan: '.$name);
                    MigrationManifest::check(! array_diff(array_values($plan['columns']), $columns), 'Kolom sumber tidak ditemukan: '.$name);
                    MigrationManifest::check(! array_diff($plan['projection_not_null'] ?? [], $columns), 'Kolom proyeksi sumber tidak ditemukan.');
                }
                $hash = hash_init('sha256');
                $n = 0;
                foreach ($this->sourceRows($mapping) as $row) {
                    hash_update($hash, self::canonical((array) $row)."\n");
                    $n++;
                }
                $report[$name] = ['rows' => $n, 'sha256' => hash_final($hash)];
            } else {
                $report[$name] = ['rows' => $this->source->table($name)->count(), 'excluded' => true];
            }
        }

        return $report;
    }

    private function sourceRows(array $table): \Generator
    {
        $query = $this->source->table($table['source']);
        $schema = $this->source->getSchemaBuilder();
        $primary = collect($schema->getIndexes($table['source']))->first(fn ($i) => $i['primary']);
        $order = $primary['columns'] ?? array_column($schema->getColumns($table['source']), 'name');
        foreach ($order as $column) {
            $query->orderBy($column);
        }
        yield from $query->cursor();
    }

    private function rows(array $table): \Generator
    {
        $overrides = [];
        if (isset($table['overrides'])) {
            foreach ($this->jsonRows($table['overrides']) as $entry) {
                MigrationManifest::check(isset($entry['key'], $entry['values'], $entry['reason']) && is_array($entry['key']) && is_array($entry['values']) && is_string($entry['reason']) && trim($entry['reason']) !== '', 'Koreksi wajib menyebut key, values dan alasan.');
                MigrationManifest::check(array_keys($entry['key']) === $table['key'] && ! array_intersect(array_keys($entry['values']), $table['key']), 'Koreksi tidak boleh mengganti key.');
                MigrationManifest::check($table['target'] !== 'users' || ! array_intersect(array_keys($entry['values']), ['password', 'ga_secret']), 'Password hash dan TOTP historis wajib disalin persis.');
                $key = self::canonical($entry['key']);
                MigrationManifest::check(! isset($overrides[$key]), 'Koreksi key ganda.');
                $overrides[$key] = $entry['values'];
            }
        }
        foreach ($this->sourceRows($table) as $source) {
            $source = (array) $source;
            if (array_filter($table['projection_not_null'] ?? [], fn ($column) => $source[$column] === null)) {
                continue;
            }
            $row = $table['values'] ?? [];
            foreach ($table['columns'] as $to => $from) {
                $row[$to] = $source[$from];
            }
            $key = self::canonical(array_intersect_key($row, array_flip($table['key'])));
            if (isset($overrides[$key])) {
                $row = array_replace($row, $overrides[$key]);
                unset($overrides[$key]);
            }
            yield $row;
        }
        MigrationManifest::check($overrides === [], 'Koreksi mengacu pada key sumber yang tidak ada.');
        if (isset($table['extra_rows'])) {
            yield from $this->jsonRows($table['extra_rows']);
        }
    }

    private function jsonRows(array $file): \Generator
    {
        $handle = fopen($this->manifest->hashedFile($file), 'r');
        try {
            while (($line = fgets($handle)) !== false) {
                try {
                    $row = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    throw new MigrationFailure('JSONL rekonsiliasi tidak valid.');
                }
                MigrationManifest::check(is_array($row) && ! array_is_list($row), 'Baris rekonsiliasi harus object.');
                yield $row;
            }
        } finally {
            fclose($handle);
        }
    }

    private function compare(array $table, bool $apply): array
    {
        $schema = $this->target->getSchemaBuilder();
        $columns = array_column($schema->getColumns($table['target']), null, 'name');
        MigrationManifest::check(! array_diff($table['key'], array_keys($columns)), 'Kolom key target tidak ditemukan.');
        $missing = 0;
        $loginChanges = 0;
        $ignored = $this->allowLoginMetadata && $table['target'] === 'users' ? ['ip', 'login_terakhir'] : [];
        MigrationManifest::check(! array_intersect($table['key'], $ignored), 'Key tidak boleh diabaikan dalam verifikasi.');
        $seen = [];
        // ponytail: indeks key per tabel di memori; gunakan staging unique-key jika profil volume melewati batas memori.
        $hash = hash_init('sha256');
        foreach ($this->rows($table) as $row) {
            MigrationManifest::check(! array_diff(array_keys($row), array_keys($columns)), 'Kolom target tidak dikenal: '.$table['target']);
            foreach ($columns as $name => $column) {
                if (($column['generation'] ?? null) || ($column['auto_increment'] ?? false)) {
                    continue;
                }
                MigrationManifest::check(array_key_exists($name, $row), 'Kolom target harus dipetakan eksplisit: '.$table['target'].'.'.$name);
                MigrationManifest::check($row[$name] !== null || $column['nullable'], 'Nilai wajib kosong: '.$table['target'].'.'.$name);
                if ($row[$name] !== null && str_starts_with($column['type'], 'enum(')) {
                    MigrationManifest::check(in_array((string) $row[$name], str_getcsv(substr($column['type'], 5, -1), ',', "'", '\\'), true), 'Status/nilai enum tidak dikenal: '.$table['target'].'.'.$name);
                }
            }
            $key = array_intersect_key($row, array_flip($table['key']));
            MigrationManifest::check(count($key) === count($table['key']) && ! in_array(null, $key, true), 'Key target kosong.');
            $signature = self::canonical($key);
            MigrationManifest::check(! isset($seen[$signature]), 'Key sumber/rekonsiliasi ganda: '.$table['target']);
            $seen[$signature] = true;
            $matches = $this->target->table($table['target'])->where($key)->limit(2)->get();
            MigrationManifest::check($matches->count() <= 1, 'Key target ambigu: '.$table['target']);
            $existing = $matches->first();
            if ($existing) {
                $actual = array_intersect_key((array) $existing, $row);
                $expected = $row;
                if (array_intersect_key($actual, array_flip($ignored)) !== array_intersect_key($expected, array_flip($ignored))) {
                    $loginChanges++;
                }
                $actual = array_diff_key($actual, array_flip($ignored));
                $expected = array_diff_key($expected, array_flip($ignored));
                MigrationManifest::check(self::canonical($actual, $columns) === self::canonical($expected, $columns), 'Konflik target: '.$table['target'].' key_sha256='.hash('sha256', $signature));
            } else {
                $missing++;
                if ($apply) {
                    $this->target->table($table['target'])->insert($row);
                }
            }
            hash_update($hash, self::canonical($row, $columns)."\n");
        }
        MigrationManifest::check($this->target->table($table['target'])->count() === count($seen) - ($apply ? 0 : $missing), 'Ada baris target di luar manifest: '.$table['target']);

        return ['rows' => count($seen), 'missing' => $apply ? 0 : $missing, 'sha256' => hash_final($hash),
            'comparison_ignored_columns' => $ignored, 'login_metadata_changed_rows' => $loginChanges];
    }

    public static function canonical(array $row, array $columns = []): string
    {
        ksort($row);
        foreach ($row as $name => &$value) {
            if ($value !== null) {
                $value = (string) $value;
                if (in_array($columns[$name]['type_name'] ?? '', ['decimal', 'numeric'], true)) {
                    $value = (string) BigDecimal::of($value)->strippedOfTrailingZeros();
                }
            }
        }

        return json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function files(bool $apply): array
    {
        $snapshot = $this->manifest->data['snapshot'];
        $source = $this->manifest->file($snapshot['files_root'], true);
        $target = config('labmania.document_root');
        MigrationManifest::check(is_dir($target) && ! is_link($target) && realpath($source) !== realpath($target), 'Root berkas target tidak sah/belum dibuat.');
        MigrationManifest::check(! str_starts_with(realpath($target), $source.DIRECTORY_SEPARATOR) && ! str_starts_with($source, realpath($target).DIRECTORY_SEPARATOR), 'Root sumber dan target berkas tidak boleh saling mencakup.');
        $expected = array_column($snapshot['files'], null, 'path');
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS)) as $file) {
            MigrationManifest::check(! $file->isLink(), 'Symlink berkas target ditolak.');
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($target) + 1));
            MigrationManifest::check(isset($expected[$relative]), 'Ada berkas target di luar manifest.');
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)) as $file) {
            MigrationManifest::check(! $file->isLink(), 'Symlink berkas sumber ditolak.');
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($source) + 1));
            MigrationManifest::check(isset($expected[$relative]), 'Ada berkas sumber di luar manifest.');
        }
        $missing = 0;
        foreach ($expected as $relative => $file) {
            $from = $this->manifest->file($snapshot['files_root'].'/'.$relative);
            MigrationManifest::check(filesize($from) === $file['size'] && hash_equals($file['sha256'], hash_file('sha256', $from)), 'Checksum/ukuran berkas sumber berbeda.');
            $to = $target;
            foreach (explode('/', $relative) as $part) {
                $to .= DIRECTORY_SEPARATOR.$part;
                MigrationManifest::check(! is_link($to), 'Symlink berkas target ditolak.');
            }
            if (file_exists($to)) {
                MigrationManifest::check(is_file($to) && hash_equals($file['sha256'], hash_file('sha256', $to)), 'Konflik berkas target.');
            } else {
                $missing++;
                if ($apply) {
                    if (! is_dir(dirname($to)) && ! mkdir(dirname($to), 0700, true)) {
                        throw new MigrationFailure('Folder dokumen target gagal dibuat.');
                    }
                    $input = fopen($from, 'rb');
                    $output = fopen($to, 'xb');
                    MigrationManifest::check($input !== false && $output !== false, 'Berkas privat gagal dibuka.');
                    try {
                        MigrationManifest::check(stream_copy_to_stream($input, $output) === $file['size'], 'Salin berkas privat tidak lengkap; target perlu dipulihkan.');
                    } finally {
                        fclose($input);
                        fclose($output);
                    }
                    chmod($to, 0600);
                    MigrationManifest::check(hash_equals($file['sha256'], hash_file('sha256', $to)), 'Checksum hasil salin berbeda.');
                }
            }
        }

        return ['rows' => count($expected), 'bytes' => array_sum(array_column($expected, 'size')), 'missing' => $apply ? 0 : $missing];
    }

    private function relations(): array
    {
        $relations = $this->manifest->data['relations'];
        foreach ($this->manifest->data['tables'] as $table) {
            foreach ($this->target->getSchemaBuilder()->getForeignKeys($table['target']) as $fk) {
                $relations[] = ['table' => $table['target'], 'columns' => $fk['columns'], 'references' => $fk['foreign_table'], 'reference_columns' => $fk['foreign_columns']];
            }
        }
        foreach ($relations as $r) {
            $query = $this->target->table($r['table'].' as child');
            foreach ($r['columns'] as $column) {
                $query->whereNotNull('child.'.$column);
            }
            $n = $query->whereNotExists(function ($q) use ($r) {
                $q->selectRaw('1')->from($r['references'].' as parent');
                foreach ($r['columns'] as $i => $column) {
                    $q->whereColumn('child.'.$column, 'parent.'.$r['reference_columns'][$i]);
                }
            })->count();
            MigrationManifest::check($n === 0, 'Orphan target: '.$r['table'].' → '.$r['references']);
        }

        return ['checked' => count($relations), 'orphans' => 0];
    }

    private function identity(): void
    {
        $schema = $this->target->getSchemaBuilder();
        if ($schema->hasTable('groups')) {
            MigrationManifest::check($this->target->table('groups')->where('id_group', 1)->exists(), 'Role 1 Superadmin sumber wajib dipertahankan.');
        }
        if ($schema->hasTable('users')) {
            $duplicates = $this->target->table('users')->selectRaw('LOWER(username) as login')->groupByRaw('LOWER(username)')->havingRaw('COUNT(*) > 1')->exists();
            MigrationManifest::check(! $duplicates, 'Username ambigu; pemetaan akun wajib diselesaikan.');
            foreach ($this->target->table('users')->where('ga_secret', 'like', 'encrypted:%')->cursor() as $user) {
                try {
                    Crypt::decryptString(substr($user->ga_secret, 10));
                } catch (\Throwable) {
                    throw new MigrationFailure('TOTP historis terenkripsi memerlukan APP_KEY Laravel asal yang benar.');
                }
            }
        }
    }

    private function documentReferences(): int
    {
        $files = array_column($this->manifest->data['snapshot']['files'], null, 'path');
        $count = 0;
        $schema = $this->target->getSchemaBuilder();
        foreach (config('labmania.documents') as $definition) {
            if (! $schema->hasTable($definition['table']) || ! $schema->hasColumn($definition['table'], $definition['path'])) {
                continue;
            }
            foreach ($this->target->table($definition['table'])->whereNotNull($definition['path'])->cursor() as $row) {
                $path = $row->{$definition['path']};
                if ($path === '') {
                    continue;
                }
                $path = str_replace('\\', '/', ($definition['bare'] ?? false) ? $definition['prefix'].$path : $path);
                MigrationManifest::relative($path);
                MigrationManifest::check(str_starts_with($path, $definition['prefix']) && isset($files[$path]), 'Referensi berkas privat tidak lengkap: '.$definition['table']);
                $count++;
            }
        }

        return $count;
    }

    private function receipt(string $path, array $data): void
    {
        $temp = $path.'.tmp';
        MigrationManifest::check(file_put_contents($temp, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX) !== false, 'Status migrasi gagal ditulis.');
        chmod($temp, 0600);
        MigrationManifest::check(rename($temp, $path), 'Status migrasi gagal diterbitkan.');
    }
}

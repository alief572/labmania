<?php

namespace App\Support;

class MigrationManifest
{
    public readonly array $data;

    public readonly string $hash;

    public readonly string $root;

    public function __construct(string $path, bool $approved = true)
    {
        self::check(is_file($path) && ! is_link($path), 'Manifest tidak ditemukan atau berupa symlink.');
        $raw = file_get_contents($path);
        $this->root = dirname(realpath($path));
        $this->hash = hash('sha256', $raw);
        try {
            $this->data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new MigrationFailure('JSON manifest tidak valid.');
        }
        $errors = validator($this->data, [
            'version' => 'required|integer|in:1',
            'snapshot.id' => 'required|string|regex:/^[a-zA-Z0-9_-]+$/',
            'snapshot.captured_at' => 'required|date',
            'snapshot.consistent' => 'required|accepted',
            'snapshot.dump.path' => 'required|string',
            'snapshot.dump.sha256' => 'required|regex:/^[a-f0-9]{64}$/',
            'snapshot.files_root' => 'required|string',
            'snapshot.files' => 'present|array',
            'approval.by' => $approved ? 'required|string' : 'nullable|string', 'approval.at' => $approved ? 'required|date' : 'nullable|date',
            'tables' => $approved ? 'required|array|min:1' : 'present|array',
            'tables.*.source' => 'required|string', 'tables.*.target' => 'required|string|distinct',
            'tables.*.key' => 'required|array|min:1', 'tables.*.columns' => 'required|array|min:1',
            'tables.*.expected_rows' => 'required|integer|min:0',
            'tables.*.source_sha256' => 'required|regex:/^[a-f0-9]{64}$/',
            'tables.*.omit_columns' => 'present|array',
            'excluded_source_tables' => 'present|array', 'empty_target_tables' => 'present|array',
            'relations' => 'present|array',
        ])->errors();
        self::check($errors->isEmpty(), 'Kontrak manifest tidak lengkap: '.implode(', ', $errors->keys()));
        $this->hashedFile($this->data['snapshot']['dump']);
        $this->file($this->data['snapshot']['files_root'], true);
        foreach ($this->data['tables'] as $table) {
            foreach ([$table['source'], $table['target'], ...$table['key'], ...array_keys($table['columns']), ...array_values($table['columns']), ...array_keys($table['omit_columns']), ...array_keys($table['values'] ?? []), ...($table['projection_not_null'] ?? [])] as $name) {
                self::identifier($name);
            }
            if (! empty($table['projection_not_null'])) {
                self::check(is_string($table['projection_reason'] ?? null) && trim($table['projection_reason']) !== '', 'Proyeksi wajib memiliki alasan.');
                self::check(collect($this->data['tables'])->contains(fn ($other) => $other['source'] === $table['source'] && $other['target'] !== $table['target'] && empty($other['projection_not_null'])), 'Proyeksi hanya boleh jika seluruh sumber disalin oleh pemetaan lain.');
            }
            self::check(count($table['key']) === count(array_unique($table['key'])), 'Key ganda.');
            self::check(! array_diff($table['key'], array_keys($table['columns'])), 'Key harus disalin dari sumber.');
            self::check(! array_intersect(array_keys($table['columns']), array_keys($table['values'] ?? [])), 'Kolom disalin sekaligus diberi konstanta.');
            foreach (['overrides', 'extra_rows'] as $field) {
                if (isset($table[$field])) {
                    $this->hashedFile($table[$field]);
                }
            }
            foreach ($table['omit_columns'] as $reason) {
                self::check(is_string($reason) && trim($reason) !== '', 'Penghilangan kolom wajib beralasan.');
            }
        }
        foreach (['excluded_source_tables', 'empty_target_tables'] as $field) {
            foreach ($this->data[$field] as $name => $reason) {
                self::identifier($name);
                self::check(is_string($reason) && trim($reason) !== '', 'Pengecualian tabel wajib beralasan.');
            }
        }
        $paths = [];
        foreach ($this->data['snapshot']['files'] as $file) {
            self::check(is_array($file) && isset($file['path'], $file['sha256'], $file['size']) && is_int($file['size']) && $file['size'] >= 0, 'Entri berkas tidak valid.');
            self::relative($file['path']);
            self::check(! isset($paths[$file['path']]), 'Path berkas ganda.');
            $paths[$file['path']] = true;
            self::check(preg_match('/^[a-f0-9]{64}$/D', $file['sha256']) === 1, 'Hash berkas tidak valid.');
        }
        foreach ($this->data['relations'] as $relation) {
            self::check(isset($relation['table'], $relation['columns'], $relation['references'], $relation['reference_columns']) && is_array($relation['columns']) && is_array($relation['reference_columns']) && count($relation['columns']) > 0 && count($relation['columns']) === count($relation['reference_columns']), 'Relasi tidak valid.');
            foreach ([$relation['table'], $relation['references'], ...$relation['columns'], ...$relation['reference_columns']] as $name) {
                self::identifier($name);
            }
        }
    }

    public function file(string $relative, bool $directory = false): string
    {
        self::relative($relative);
        $path = $this->root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($path);
        self::check($real !== false && str_starts_with($real, $this->root.DIRECTORY_SEPARATOR) && ! is_link($path), 'Berkas di luar root snapshot atau tidak ditemukan.');
        $walk = $this->root;
        foreach (explode('/', $relative) as $part) {
            $walk .= DIRECTORY_SEPARATOR.$part;
            self::check(! is_link($walk), 'Symlink snapshot ditolak.');
        }
        self::check($directory ? is_dir($real) : is_file($real), 'Jenis path snapshot salah.');

        return $real;
    }

    public function hashedFile(array $file): string
    {
        self::check(isset($file['path'], $file['sha256']) && is_string($file['sha256']) && preg_match('/^[a-f0-9]{64}$/D', $file['sha256']) === 1, 'Referensi berkas harus memiliki SHA-256.');
        $path = $this->file($file['path']);
        self::check(hash_equals($file['sha256'], hash_file('sha256', $path)), 'Checksum berkas snapshot berbeda.');

        return $path;
    }

    public static function identifier(mixed $name): void
    {
        self::check(is_string($name) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $name) === 1, 'Identifier SQL tidak valid.');
    }

    public static function relative(string $path): void
    {
        self::check($path !== '' && ! preg_match('/[\\\\:\x00-\x1f]/', $path) && ! str_starts_with($path, '/') && ! array_intersect(explode('/', $path), ['', '.', '..']), 'Path relatif tidak aman.');
    }

    public static function check(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new MigrationFailure($message);
        }
    }
}

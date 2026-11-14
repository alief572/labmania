<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class MigrationOperations
{
    /**
     * Opsi klien mariadb untuk MySQL rehearsal. Sertifikat server self-signed sehingga verifikasi default klien
     * MariaDB 11 gagal ("self-signed certificate in certificate chain"). Koneksi hanya melewati jaringan Compose
     * `internal: true` tanpa published port; enkripsi tetap aktif, hanya pemeriksaan rantai sertifikat dilewati.
     * Regresi 14-11-2026 (rehearsal pertama di container nyata).
     *
     * @return list<string>
     */
    public static function clientTlsOptions(): array
    {
        return ['--ssl-verify-server-cert=0'];
    }

    public function source(MigrationManifest $manifest): array
    {
        MigrationRehearsal::target(true);
        $config = config('database.connections.'.config('migration.source'));
        MigrationManifest::check(($config['host'] ?? null) === 'rehearsal-db' && ($config['database'] ?? null) === 'labmania_legacy_snapshot', 'Sumber bukan snapshot rehearsal.');
        $db = DB::connection(config('migration.source'));
        MigrationManifest::check(MigrationRehearsal::tables($db) === [], 'Snapshot sumber harus kosong; gunakan project/volume rehearsal baru.');
        $dump = $manifest->hashedFile($manifest->data['snapshot']['dump']);
        $this->sqlCompatibility($dump);
        MigrationManifest::check(is_string(config('migration.loader_password')) && strlen(config('migration.loader_password')) >= 32, 'Password loader belum tersedia.');
        $config['username'] = 'snapshot_loader';
        $config['password'] = config('migration.loader_password');
        $this->mysql($config, $dump);

        return ['source_restored' => true, 'dump_sha256' => $manifest->data['snapshot']['dump']['sha256'], 'verified' => false];
    }

    /** Tabel yang memiliki minimal satu kolom generated. */
    public static function generatedTables($db): array
    {
        $schema = $db->getSchemaBuilder();

        return array_values(array_filter(array_column(MigrationRehearsal::tables($db), 'name'),
            fn ($table) => collect($schema->getColumns($table))->contains(fn ($c) => ! empty($c['generation']))));
    }

    /** INSERT seluruh baris satu tabel tanpa kolom generated; kolom biner ditolak daripada dirusak diam-diam. */
    public static function generatedTableInserts($db, string $table): string
    {
        MigrationManifest::identifier($table);
        $columns = array_values(array_filter($db->getSchemaBuilder()->getColumns($table), fn ($c) => empty($c['generation'])));
        foreach ($columns as $c) {
            MigrationManifest::identifier($c['name']);
            MigrationManifest::check(! preg_match('/blob|binary|^bit$/i', (string) $c['type_name']), 'Backup tabel berkolom generated belum mendukung kolom biner: '.$table.'.'.$c['name']);
        }
        $pdo = $db->getPdo();
        $names = implode(',', array_map(fn ($c) => '`'.$c['name'].'`', $columns));
        $out = '';
        $batch = [];
        $flush = function () use (&$out, &$batch, $table, $names) {
            if ($batch) {
                $out .= "INSERT INTO `$table` ($names) VALUES\n".implode(",\n", $batch).";\n";
                $batch = [];
            }
        };
        foreach ($db->table($table)->cursor() as $row) {
            $row = (array) $row;
            $batch[] = '('.implode(',', array_map(fn ($c) => $row[$c['name']] === null ? 'NULL' : $pdo->quote((string) $row[$c['name']]), $columns)).')';
            if (count($batch) >= 200) {
                $flush();
            }
        }
        $flush();

        return $out;
    }

    public function backup(string $name): array
    {
        $db = MigrationRehearsal::target(true);
        $destination = $this->archivePath($name);
        MigrationManifest::check(! file_exists($destination) && ! is_link($destination), 'Backup existing tidak boleh ditimpa.');
        $root = config('migration.state_root');
        MigrationManifest::check(is_file($root.'/receipt.json'), 'Backup rehearsal memerlukan receipt import terverifikasi.');
        $receipt = json_decode(file_get_contents($root.'/receipt.json'), true, 64, JSON_THROW_ON_ERROR);
        MigrationManifest::check(($receipt['status'] ?? '') === 'verified' && ($receipt['binding']['key_fingerprint'] ?? '') === hash('sha256', config('app.key')), 'Receipt/APP_KEY belum valid.');
        $temp = $root.'/backup-'.bin2hex(random_bytes(8));
        mkdir($temp, 0700);
        try {
            $config = $db->getConfig();
            // Nilai kolom generated ikut di dump dan ditolak MySQL saat restore (3105): datanya ditulis ulang tanpa kolom itu.
            $generated = self::generatedTables($db);
            $process = new Process(['mariadb-dump', ...self::clientTlsOptions(), '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'],
                '--single-transaction', '--skip-lock-tables', '--skip-add-locks', '--skip-triggers', '--hex-blob', '--default-character-set=utf8mb4',
                ...array_map(fn ($t) => '--ignore-table-data='.$config['database'].'.'.$t, $generated),
                '--result-file='.$temp.'/database.sql', $config['database']], null, ['MYSQL_PWD' => $config['password']], null, 3600);
            MigrationManifest::check($process->run() === 0, 'Dump backup gagal; pesan SQL tidak dipublikasikan.');
            if ($generated) {
                // ponytail: dibaca setelah dump tanpa snapshot bersama; aman karena backup dijalankan saat aplikasi maintenance tanpa penulis. Ganti dengan snapshot transaksi bila backup online dibutuhkan.
                $extra = "\n-- Data tabel berkolom generated, tanpa kolom generated\nSET FOREIGN_KEY_CHECKS=0;\n";
                foreach ($generated as $table) {
                    $extra .= self::generatedTableInserts($db, $table);
                }
                file_put_contents($temp.'/database.sql', $extra."SET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);
            }
            $environment = ['APP_KEY' => config('app.key'), 'ACTIVITY_LOG_ROLE_IDS' => implode(',', config('labmania.activity_log_role_ids'))];
            foreach (config('labmania.invoice.coa') as $key => $value) {
                $environment['INVOICE_COA_'.strtoupper($key).'_ID'] = $value['id'];
            }
            foreach (config('labmania.invoice.print') as $key => $value) {
                $environment['INVOICE_'.strtoupper($key)] = $value;
            }
            file_put_contents($temp.'/application-config.json', json_encode($environment, JSON_THROW_ON_ERROR));
            chmod($temp.'/application-config.json', 0600);
            $files = ['database.sql' => $temp.'/database.sql', 'application-config.json' => $temp.'/application-config.json', 'migration/receipt.json' => $root.'/receipt.json'];
            $documents = config('labmania.document_root');
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($documents, \FilesystemIterator::SKIP_DOTS)) as $file) {
                MigrationManifest::check(! $file->isLink(), 'Symlink dokumen backup ditolak.');
                $name = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($documents) + 1));
                $files['documents/'.$name] = $file->getPathname();
            }
            (new MigrationArchive)->write($destination, $files, (string) config('migration.backup_password'));

            return ['backup' => basename($destination), 'bytes' => filesize($destination), 'sha256' => hash_file('sha256', $destination), 'restore_tested' => false];
        } finally {
            File::deleteDirectory($temp);
        }
    }

    public function restore(string $name): array
    {
        $db = MigrationRehearsal::target(true);
        foreach (MigrationRehearsal::tables($db) as $table) {
            MigrationManifest::check($table['name'] === 'migrations' || $db->table($table['name'])->count() === 0, 'Restore hanya ke target baru tanpa data bisnis.');
        }
        $documents = config('labmania.document_root');
        MigrationManifest::check(is_dir($documents) && count(scandir($documents)) === 2 && ! is_file(config('migration.state_root').'/receipt.json'), 'Dokumen/status target restore harus kosong.');
        $destination = config('migration.state_root').'/recovery-'.bin2hex(random_bytes(8));
        $inventory = (new MigrationArchive)->read($this->archivePath($name), $destination, (string) config('migration.backup_password'));
        MigrationManifest::check(isset($inventory['database.sql'], $inventory['application-config.json'], $inventory['migration/receipt.json']), 'Komponen backup DB/konfigurasi/status tidak lengkap.');
        foreach (array_keys($inventory) as $entry) {
            MigrationManifest::check(in_array($entry, ['database.sql', 'application-config.json', 'migration/receipt.json'], true) || str_starts_with($entry, 'documents/'), 'Entri di luar cakupan backup.');
        }
        $this->sqlCompatibility($destination.'/database.sql');
        $this->mysql($db->getConfig(), $destination.'/database.sql');
        if (is_dir($destination.'/documents')) {
            File::copyDirectory($destination.'/documents', $documents);
        }
        foreach ($inventory as $entry => $meta) {
            if (str_starts_with($entry, 'documents/')) {
                $path = $documents.'/'.substr($entry, 10);
                MigrationManifest::check(is_file($path) && hash_equals($meta['sha256'], hash_file('sha256', $path)), 'Berkas hasil restore tidak lengkap.');
            }
        }
        MigrationManifest::check(copy($destination.'/migration/receipt.json', config('migration.state_root').'/receipt.json'), 'Receipt restore gagal disalin.');

        return ['restored' => true, 'entries' => count($inventory), 'recovered_environment' => $destination.'/application-config.json',
            'verified' => false, 'next' => 'Pulihkan konfigurasi rahasia ke environment project restore, buat ulang app, lalu jalankan migrasi-verifikasi.'];
    }

    public function readiness(): array
    {
        MigrationRehearsal::target();
        DB::select('SELECT 1');
        $checks = ['database' => true, 'debug_off' => ! config('app.debug'), 'retention_off' => ! config('labmania.audit_retention_enabled'),
            'session_isolated' => config('session.cookie') === 'labmania_rehearsal_session', 'private_documents' => is_dir(config('labmania.document_root')) && ! is_dir(public_path('storage'))];
        $file = config('labmania.document_root').'/probe-'.bin2hex(random_bytes(8));
        $handle = @fopen($file, 'x');
        $checks['storage_write'] = is_resource($handle);
        if ($handle) {
            fwrite($handle, 'probe');
            fclose($handle);
            $checks['storage_read'] = file_get_contents($file) === 'probe';
            unlink($file);
        }
        foreach (array_keys(config('labmania.invoice.coa')) as $key) {
            try {
                Invoice::account($key);
                $checks['coa_'.$key] = true;
            } catch (\Throwable) {
                $checks['coa_'.$key] = false;
            }
        }
        foreach (['bank', 'rekening', 'atas_nama', 'penanda_tangan', 'jabatan', 'kota', 'kode_dokumen'] as $key) {
            $checks['print_'.$key] = trim((string) config('labmania.invoice.print.'.$key)) !== '';
        }
        foreach (['times.ttf', 'timesbd.ttf'] as $font) {
            $checks['font_'.$font] = is_file(config('labmania.operasional.font_dir').'/'.$font);
        }
        $schema = DB::getSchemaBuilder();
        foreach (['Admin', 'Direktur'] as $role) {
            $checks['role_'.$role] = $schema->hasTable('groups') && DB::table('groups')->where('id_group', '<>', 1)->where('nm_group', $role)->exists();
        }
        foreach (array_unique(array_filter(array_column(config('navigation'), 'menu'))) as $code) {
            $menu = $schema->hasTable('ms_menu') ? DB::table('ms_menu')->where('kode', $code)->where('aktif', 1)->value('id') : null;
            $checks['menu_'.$code] = $menu !== null && $schema->hasTable('ms_menu_aksi') && DB::table('ms_menu_aksi')->where('id_menu', $menu)->where('aksi', 'lihat')->exists();
        }

        return ['checks' => $checks, 'runtime_ready' => ! in_array(false, $checks, true), 'cutover_ready' => false,
            'remaining' => 'Rekonsiliasi histori, dua rehearsal, restore terverifikasi, keputusan BA dan matriks akses tetap harus dibuktikan dalam checklist serah terima.'];
    }

    public function locked(callable $operation): array
    {
        MigrationRehearsal::target(true);
        $root = config('migration.state_root');
        if (! is_dir($root)) {
            mkdir($root, 0700, true);
        }
        $handle = fopen($root.'/import.lock', 'c');
        MigrationManifest::check($handle !== false && flock($handle, LOCK_EX | LOCK_NB), 'Operasi migrasi lain sedang berjalan.');
        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function archivePath(string $name): string
    {
        MigrationManifest::check(preg_match('/^[a-zA-Z0-9_-]+\.zip$/D', $name) === 1, 'Arsip harus nama file ZIP tanpa path.');
        $root = realpath(config('migration.backup_root'));
        MigrationManifest::check($root !== false && is_dir($root) && ! is_link(config('migration.backup_root')), 'Root backup belum tersedia.');

        return $root.DIRECTORY_SEPARATOR.$name;
    }

    private function mysql(array $config, string $dump): void
    {
        $input = fopen($dump, 'rb');
        try {
            $process = new Process(['mariadb', ...self::clientTlsOptions(), '--binary-mode', '--default-character-set=utf8mb4', '--host='.$config['host'], '--port='.$config['port'],
                '--user='.$config['username'], '--database='.$config['database']], null, ['MYSQL_PWD' => $config['password']], $input, 3600);
            MigrationManifest::check($process->run() === 0, 'Restore SQL gagal; target belum valid. Gunakan volume baru setelah kegagalan.');
        } finally {
            fclose($input);
        }
    }

    private function sqlCompatibility(string $file): void
    {
        $handle = fopen($file, 'rb');
        try {
            while (($line = fgets($handle)) !== false) {
                // snapshot_loader sengaja tanpa grant LOCK TABLES; dump dengan LOCK/UNLOCK akan gagal di tengah dan
                // menyisakan sumber parsial. Tolak lebih awal dengan petunjuk, jangan menambah grant.
                MigrationManifest::check(! preg_match('/^\s*(?:UN)?LOCK\s+TABLES\b/i', $line), 'Dump memuat LOCK/UNLOCK TABLES. Ekspor ulang dengan --skip-add-locks (loader tanpa grant LOCK TABLES).');
                MigrationManifest::check(! preg_match('/^\s*(?:(?:CREATE|DROP)\s+DATABASE\b|USE\s+|(?:\/\*![0-9]+\s+)?(?:CREATE\b[^\r\n]*\b)?DEFINER\s*=)/i', $line), 'Dump harus tanpa CREATE/DROP DATABASE, USE atau DEFINER. Ekspor ulang dengan format sesuai runbook.');
            }
        } finally {
            fclose($handle);
        }
    }
}

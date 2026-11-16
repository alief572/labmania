<?php

namespace Tests\Feature\Migration;

use App\Support\MigrationFailure;
use App\Support\MigrationOperations;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regresi dari rehearsal pertama di container nyata (14-11-2026): dua bug yang tidak terlihat di SQLite/MySQL tes
 * karena perintah klien mariadb baru berjalan terhadap server rehearsal sungguhan.
 */
class MigrationOperationsTest extends TestCase
{
    private function compat(string $content): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dump');
        file_put_contents($path, $content);
        try {
            (new ReflectionMethod(MigrationOperations::class, 'sqlCompatibility'))->invoke(new MigrationOperations, $path);
        } finally {
            unlink($path);
        }
    }

    public function test_klien_mariadb_melewati_verifikasi_rantai_sertifikat_self_signed_tetapi_tetap_enkripsi(): void
    {
        $options = MigrationOperations::clientTlsOptions();
        $this->assertContains('--ssl-verify-server-cert=0', $options);
        $this->assertNotContains('--skip-ssl', $options, 'Enkripsi tidak boleh dimatikan, hanya verifikasi rantai.');
        $this->assertNotContains('--ssl-mode=DISABLED', $options);
    }

    public function test_dump_dengan_lock_tables_ditolak_sebelum_menulis_apa_pun(): void
    {
        foreach (["LOCK TABLES `ci_sessions` WRITE;\n", "  lock  tables x write;\n", "UNLOCK TABLES;\n"] as $line) {
            try {
                $this->compat("CREATE TABLE t (id int);\n".$line);
                $this->fail('Dump dengan LOCK/UNLOCK seharusnya ditolak: '.trim($line));
            } catch (MigrationFailure $e) {
                $this->assertStringContainsString('--skip-add-locks', $e->getMessage());
            }
        }
    }

    public function test_dump_bersih_tetap_diterima_dan_aturan_lama_tidak_longgar(): void
    {
        $this->compat("CREATE TABLE t (id int);\nINSERT INTO t VALUES (1);\n");
        $this->expectException(MigrationFailure::class);
        $this->compat("CREATE DATABASE x;\n");
    }

    public function test_definer_dan_use_tetap_ditolak(): void
    {
        foreach (["USE db;\n", "/*!50017 DEFINER=`root`@`%`*/\n"] as $line) {
            try {
                $this->compat($line);
                $this->fail('Harus ditolak: '.trim($line));
            } catch (MigrationFailure $e) {
                $this->assertStringContainsString('DEFINER', $e->getMessage());
            }
        }
    }

    public function test_backup_menulis_ulang_data_tabel_berkolom_generated_tanpa_kolom_itu(): void
    {
        // Regresi rehearsal data asli 14-11-2026: dump biasa memuat nilai kolom generated dan restore ditolak MySQL (3105).
        DB::statement('CREATE TABLE gen_uji (id INTEGER PRIMARY KEY, aktif INTEGER NOT NULL, nama TEXT, kombinasi TEXT GENERATED ALWAYS AS (CASE WHEN aktif = 1 THEN nama ELSE NULL END) STORED)');
        DB::statement('CREATE TABLE biasa_uji (id INTEGER PRIMARY KEY)');
        DB::table('gen_uji')->insert([['id' => 1, 'aktif' => 1, 'nama' => "O'Brien"], ['id' => 2, 'aktif' => 0, 'nama' => null]]);
        $db = DB::connection();
        $this->assertSame(['gen_uji'], MigrationOperations::generatedTables($db));
        $sql = MigrationOperations::generatedTableInserts($db, 'gen_uji');
        $this->assertStringContainsString('INSERT INTO `gen_uji` (`id`,`aktif`,`nama`) VALUES', $sql);
        $this->assertStringNotContainsString('kombinasi', $sql);
        $this->assertStringContainsString("'O''Brien'", $sql);
        $this->assertStringContainsString('NULL', $sql);
        DB::statement('DROP TABLE gen_uji');
        DB::unprepared('CREATE TABLE gen_uji (id INTEGER PRIMARY KEY, aktif INTEGER NOT NULL, nama TEXT, kombinasi TEXT GENERATED ALWAYS AS (CASE WHEN aktif = 1 THEN nama ELSE NULL END) STORED);'.str_replace('`', '"', $sql));
        $this->assertSame(["O'Brien", null], DB::table('gen_uji')->orderBy('id')->pluck('kombinasi')->all());
    }

    public function test_backup_menolak_kolom_biner_pada_tabel_berkolom_generated(): void
    {
        DB::statement('CREATE TABLE gen_biner (id INTEGER PRIMARY KEY, isi BLOB, g INTEGER GENERATED ALWAYS AS (id + 1) STORED)');
        $this->expectException(\Exception::class);
        MigrationOperations::generatedTableInserts(DB::connection(), 'gen_biner');
    }
}

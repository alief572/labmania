<?php

namespace Tests\Feature\Migration;

use App\Support\MigrationArchive;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class MigrationArchiveTest extends TestCase
{
    public function test_encrypted_backup_round_trip_preserves_binary_files_and_rejects_wrong_password(): void
    {
        $root = sys_get_temp_dir().'/labmania-archive-'.bin2hex(random_bytes(8));
        mkdir($root);
        file_put_contents($root.'/source', "private\0binary");
        try {
            $archive = new MigrationArchive;
            $archive->write($root.'/backup.zip', ['database.sql' => $root.'/source', 'environment.env' => $root.'/source'], str_repeat('a', 64));
            $this->assertFileExists($root.'/backup.zip');
            $zip = new ZipArchive;
            $zip->open($root.'/backup.zip');
            $this->assertFalse($zip->getFromName('environment.env'));
            $zip->close();
            $inventory = $archive->read($root.'/backup.zip', $root.'/restored', str_repeat('a', 64));
            $this->assertCount(2, $inventory);
            $this->assertSame("private\0binary", file_get_contents($root.'/restored/environment.env'));
            $this->expectException(\RuntimeException::class);
            $archive->read($root.'/backup.zip', $root.'/wrong-password', str_repeat('b', 64));
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_archive_rejects_traversal_and_refuses_overwriting_existing_backup(): void
    {
        $root = sys_get_temp_dir().'/labmania-archive-'.bin2hex(random_bytes(8));
        mkdir($root);
        file_put_contents($root.'/source', 'fixture');
        file_put_contents($root.'/existing.zip', 'preserve');
        try {
            $archive = new MigrationArchive;
            try {
                $archive->write($root.'/bad.zip', ['../escape' => $root.'/source'], str_repeat('a', 64));
                $this->fail('Traversal diterima.');
            } catch (\RuntimeException) {
                $this->assertFileDoesNotExist($root.'/bad.zip');
            }
            file_put_contents($root.'/existing.zip', 'preserve');
            $this->expectException(\RuntimeException::class);
            $archive->write($root.'/existing.zip', ['database.sql' => $root.'/source'], str_repeat('a', 64));
        } finally {
            $this->assertSame('preserve', file_get_contents($root.'/existing.zip'));
            File::deleteDirectory($root);
        }
    }
}

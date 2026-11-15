<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use ZipArchive;

class MigrationArchive
{
    public function write(string $path, array $files, string $password): void
    {
        MigrationManifest::check(strlen($password) >= 32 && ZipArchive::isEncryptionMethodSupported(ZipArchive::EM_AES_256), 'Password backup minimal 32 karakter dan ZIP AES-256 wajib tersedia.');
        MigrationManifest::check(! file_exists($path) && ! is_link($path), 'Backup existing tidak boleh ditimpa.');
        foreach ($files as $name => $source) {
            MigrationManifest::relative($name);
            MigrationManifest::check($name !== 'inventory.json' && is_file($source) && ! is_link($source), 'Entri backup tidak sah.');
        }
        $temp = $path.'.partial-'.bin2hex(random_bytes(8));
        $zip = new ZipArchive;
        MigrationManifest::check($zip->open($temp, ZipArchive::CREATE | ZipArchive::EXCL) === true, 'Arsip backup gagal dibuat.');
        $closed = false;
        try {
            $zip->setPassword($password);
            $inventory = [];
            foreach ($files as $name => $source) {
                $inventory[$name] = ['size' => filesize($source), 'sha256' => hash_file('sha256', $source)];
                MigrationManifest::check($zip->addFile($source, $name) && $zip->setEncryptionName($name, ZipArchive::EM_AES_256), 'Enkripsi entri backup gagal.');
            }
            MigrationManifest::check($zip->addFromString('inventory.json', json_encode($inventory, JSON_THROW_ON_ERROR)) && $zip->setEncryptionName('inventory.json', ZipArchive::EM_AES_256), 'Inventory backup gagal dibuat.');
            MigrationManifest::check($zip->close(), 'Arsip backup gagal diselesaikan.');
            $closed = true;
            chmod($temp, 0600);
            MigrationManifest::check(! file_exists($path) && rename($temp, $path), 'Arsip backup gagal diterbitkan.');
        } finally {
            if (! $closed) {
                @$zip->close();
            }
            if (is_file($temp)) {
                unlink($temp);
            }
        }
    }

    public function read(string $path, string $destination, string $password): array
    {
        MigrationManifest::check(is_file($path) && ! is_link($path) && ! file_exists($destination) && ! is_link($destination), 'Restore memerlukan arsip biasa dan folder tujuan baru.');
        $zip = new ZipArchive;
        MigrationManifest::check($zip->open($path) === true, 'Arsip backup tidak dapat dibuka.');
        $stage = $destination.'.partial-'.bin2hex(random_bytes(8));
        try {
            $zip->setPassword($password);
            $raw = @$zip->getFromName('inventory.json');
            MigrationManifest::check(is_string($raw) && strlen($raw) <= 64 * 1024 * 1024, 'Password backup salah atau inventory rusak.');
            $inventory = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
            MigrationManifest::check(is_array($inventory) && $zip->numFiles === count($inventory) + 1, 'Inventory backup tidak sesuai.');
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                MigrationManifest::relative($stat['name']);
                MigrationManifest::check(! isset($names[$stat['name']]) && ($stat['encryption_method'] ?? null) === ZipArchive::EM_AES_256 && ($stat['name'] === 'inventory.json' || isset($inventory[$stat['name']])), 'Entri backup ganda/tidak terenkripsi/tidak terdaftar.');
                $names[$stat['name']] = true;
            }
            MigrationManifest::check(mkdir($stage, 0700, true), 'Folder staging restore gagal dibuat.');
            foreach ($inventory as $name => $entry) {
                MigrationManifest::relative($name);
                MigrationManifest::check(isset($entry['size'], $entry['sha256']) && is_int($entry['size']) && $entry['size'] >= 0 && preg_match('/^[a-f0-9]{64}$/D', $entry['sha256']) === 1, 'Metadata backup tidak sah.');
                $file = $stage.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
                if (! is_dir(dirname($file))) {
                    mkdir(dirname($file), 0700, true);
                }
                $input = @$zip->getStream($name);
                MigrationManifest::check(is_resource($input), 'Dekripsi entri backup gagal.');
                $output = fopen($file, 'xb');
                try {
                    MigrationManifest::check(stream_copy_to_stream($input, $output, $entry['size'] + 1) === $entry['size'], 'Ukuran hasil restore berbeda.');
                } finally {
                    fclose($input);
                    fclose($output);
                }
                chmod($file, 0600);
                MigrationManifest::check(hash_equals($entry['sha256'], hash_file('sha256', $file)), 'Checksum hasil restore berbeda.');
            }
            MigrationManifest::check(! file_exists($destination) && rename($stage, $destination), 'Hasil restore gagal diterbitkan.');

            return $inventory;
        } finally {
            $zip->close();
            if (is_dir($stage)) {
                File::deleteDirectory($stage);
            }
        }
    }
}

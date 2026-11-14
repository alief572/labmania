<?php

namespace App\Console\Commands;

use App\Support\MigrationFailure;
use App\Support\MigrationManifest;
use App\Support\MigrationOperations;
use App\Support\MigrationRehearsal;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class MigrationOperation extends Command
{
    protected $signature = 'labmania:migrasi-operasi {aksi : sumber|kesiapan|backup|restore} {--manifest=} {--arsip=}';

    protected $description = 'Operasi CLI snapshot/backup/restore khusus rehearsal terisolasi';

    public function handle(): int
    {
        try {
            $operations = new MigrationOperations;
            $action = $this->argument('aksi');
            MigrationManifest::check(in_array($action, ['sumber', 'kesiapan', 'backup', 'restore'], true), 'Aksi tidak dikenal.');
            if ($action !== 'kesiapan') {
                $target = MigrationRehearsal::target(true);
                $this->line('Lingkungan: '.app()->environment().'; koneksi: '.$target->getName().'; database: '.$target->getDatabaseName());
            }
            $report = match ($action) {
                'kesiapan' => $operations->readiness(),
                'sumber' => $operations->locked(fn () => $operations->source(new MigrationManifest((string) $this->option('manifest'), false))),
                'backup' => $operations->locked(fn () => $operations->backup((string) $this->option('arsip'))),
                'restore' => $operations->locked(fn () => $operations->restore((string) $this->option('arsip'))),
            };
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return ($report['runtime_ready'] ?? true) ? self::SUCCESS : self::FAILURE;
        } catch (QueryException) {
            $this->error('Operasi database gagal; rincian SQL dan data tidak dipublikasikan.');
        } catch (MigrationFailure $e) {
            $this->error($e->getMessage());
        } catch (\Throwable) {
            $this->error('Operasi gagal. Jangan membuka target sebelum rekonsiliasi ulang.');
        }

        return self::FAILURE;
    }
}

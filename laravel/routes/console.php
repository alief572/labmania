<?php

use App\Support\MigrationFailure;
use App\Support\MigrationManifest;
use App\Support\MigrationRehearsal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

foreach (['profil', 'impor', 'verifikasi'] as $operation) {
    Artisan::command('labmania:migrasi-'.$operation.' {--manifest= : Manifest snapshot dan pemetaan yang disahkan} {--terapkan : Tulis hanya untuk operasi impor}', function () use ($operation) {
        try {
            MigrationManifest::check($operation === 'impor' || ! $this->option('terapkan'), '--terapkan hanya tersedia untuk impor.');
            MigrationManifest::check(is_string($this->option('manifest')) && $this->option('manifest') !== '', '--manifest wajib diberikan.');
            $service = new MigrationRehearsal(new MigrationManifest($this->option('manifest'), $operation !== 'profil'));
            if ($this->option('terapkan')) {
                $target = MigrationRehearsal::target(true);
                $this->line('Lingkungan: '.app()->environment().'; koneksi: '.$target->getName().'; database: '.$target->getDatabaseName());
            }
            $report = $service->run($operation, (bool) $this->option('terapkan'));
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return 0;
        } catch (QueryException) {
            $this->error('Operasi database gagal. Nilai/baris SQL tidak ditampilkan; periksa schema, constraint dan pemetaan secara privat.');
        } catch (MigrationFailure $e) {
            $this->error($e->getMessage());
        } catch (Throwable) {
            $this->error('Operasi migrasi gagal. Hasil belum valid; periksa snapshot dan konfigurasi secara privat.');
        }

        return 1;
    })->purpose('Rehearsal migrasi '.$operation.'; sumber snapshot baca saja, target terisolasi');
}

Artisan::command('logs:prune', function () {
    if (! config('labmania.audit_retention_enabled')) {
        $this->error('Retensi belum diaktifkan untuk database Laravel terpisah.');

        return 1;
    }
    $deleted = DB::table('logs')->where('created_date', '<', now()->subDays(60))->delete();
    $this->info($deleted.' catatan melewati retensi 60 hari dihapus.');

    return 0;
})->purpose('Hapus Activity Log yang melewati retensi 60 hari');

Schedule::command('logs:prune')->dailyAt('02:00')->withoutOverlapping()
    ->when(fn () => config('labmania.audit_retention_enabled'));

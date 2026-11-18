<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembatalan Penjualan Program Final (mockup pjBatal, PJ-FR-14..17): quotation, Sales Order, invoice belum dibayar, Surat Tugas
 * dan pengajuan biaya yang menunggu ikut Batal; invoice yang sudah dibayar menjadi Refund.
 * - sales_order.status dan surat_kerja_trainer.status mendapat nilai batal (SO Batal tetap terlihat di tab SO Batal).
 * - sales_order_refund.id_konfirmasi_ulang boleh NULL: refund karena program batal tidak berasal dari konfirmasi ulang peserta.
 * - penjualan_program.alasan_batal menyimpan alasan wajib saat dibatalkan.
 * Idempoten. Hanya salinan Laravel atau database test terisolasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration hanya untuk salinan Laravel atau database test terisolasi.');
        }
        if (! Schema::hasColumn('penjualan_program', 'alasan_batal')) {
            Schema::table('penjualan_program', fn (Blueprint $t) => $t->string('alasan_batal', 300)->nullable());
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_order MODIFY status ENUM('open','siap','batal') NOT NULL DEFAULT 'open'");
            DB::statement("ALTER TABLE surat_kerja_trainer MODIFY status ENUM('berlaku','diganti','batal') NOT NULL DEFAULT 'berlaku'");
            DB::statement('ALTER TABLE sales_order_refund MODIFY id_konfirmasi_ulang INT UNSIGNED NULL');
        } else {
            // SQLite (tes): enum berupa CHECK constraint; change() membangun ulang tabel.
            Schema::table('sales_order', fn (Blueprint $t) => $t->enum('status', ['open', 'siap', 'batal'])->default('open')->change());
            Schema::table('surat_kerja_trainer', fn (Blueprint $t) => $t->enum('status', ['berlaku', 'diganti', 'batal'])->default('berlaku')->change());
            Schema::table('sales_order_refund', fn (Blueprint $t) => $t->unsignedInteger('id_konfirmasi_ulang')->nullable()->change());
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi pembatalan Penjualan Program tidak dapat dibalik: status batal sudah dipakai data.');
    }
};

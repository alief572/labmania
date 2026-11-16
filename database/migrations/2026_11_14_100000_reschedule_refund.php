<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration fase 5 hanya pada salinan Laravel/test terisolasi.');
        }
        if (! Schema::hasTable('penjualan_program_reschedule')) {
            Schema::create('penjualan_program_reschedule', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_penjualan')->index();
                foreach (['tgl_mulai_lama', 'tgl_selesai_lama', 'tgl_mulai_baru', 'tgl_selesai_baru'] as $c) {
                    $t->date($c);
                }
                foreach (['id_trainer_lama', 'id_trainer_baru', 'id_asisten_lama', 'id_asisten_baru'] as $c) {
                    $t->integer($c);
                }
                $t->string('alasan', 500);
                $t->char('request_id', 36)->unique();
                $t->char('request_hash', 64);
                $t->longText('snapshot');
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('penjualan_program_reschedule_evidence')) {
            Schema::create('penjualan_program_reschedule_evidence', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('id_reschedule');
                $t->foreign('id_reschedule')->references('id')->on('penjualan_program_reschedule')->restrictOnDelete();
                $t->string('nama_file', 255);
                $t->string('path_file', 255);
                $t->enum('tipe_file', ['jpg', 'png', 'doc', 'docx', 'pdf']);
                $t->integer('ukuran_byte');
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('quotation_revisi')) {
            Schema::create('quotation_revisi', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_quotation')->index();
                $t->unsignedInteger('id_reschedule');
                $t->foreign('id_reschedule')->references('id')->on('penjualan_program_reschedule')->restrictOnDelete();
                $t->integer('revisi');
                $t->char('link_token_hash', 64)->nullable()->unique();
                $t->longText('snapshot');
                $t->unique(['id_quotation', 'revisi'], 'uk_qrev_quotation_revisi');
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('quotation_peserta_konfirmasi_ulang')) {
            Schema::create('quotation_peserta_konfirmasi_ulang', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_quotation_peserta')->index();
                $t->unsignedInteger('id_reschedule');
                $t->foreign('id_reschedule')->references('id')->on('penjualan_program_reschedule')->restrictOnDelete();
                $t->integer('revisi');
                $t->enum('keputusan', ['pindah_jadwal', 'batal', 'refund', 'tidak'])->nullable();
                $t->string('catatan', 500)->nullable();
                $t->string('surat_konfirmasi', 255)->nullable();
                $t->string('surat_konfirmasi_nama', 150)->nullable();
                $t->enum('sumber', ['link', 'company', 'sales'])->nullable();
                $t->dateTime('tgl_isi')->nullable();
                $t->integer('diproses_by')->nullable();
                $t->dateTime('diproses_date')->nullable();
                $t->unique(['id_quotation_peserta', 'revisi'], 'uk_qku_peserta_revisi');
                $this->audit($t);
            });
        }
        Schema::table('quotation_peserta', fn (Blueprint $t) => $t->enum('status', ['menunggu', 'deal', 'batal', 'keluar'])->default('menunggu')->change());
        foreach (['keluar_jenis' => 20, 'keluar_alasan' => 500] as $c => $length) {
            $this->add('quotation_peserta', $c, fn ($t) => $t->string($c, $length)->nullable());
        }
        foreach (['keluar_revisi', 'keluar_by', 'created_by', 'updated_by'] as $c) {
            $this->add('quotation_peserta', $c, fn ($t) => $t->integer($c)->nullable());
        }
        foreach (['keluar_date', 'created_date', 'updated_date'] as $c) {
            $this->add('quotation_peserta', $c, fn ($t) => $t->dateTime($c)->nullable());
        }
        if (! Schema::hasTable('sales_order_refund')) {
            Schema::create('sales_order_refund', function (Blueprint $t) {
                $t->increments('id');
                $t->string('nomor', 30)->unique();
                $t->date('tanggal');
                $t->unsignedInteger('id_sales_order');
                $t->foreign('id_sales_order')->references('id')->on('sales_order')->restrictOnDelete();
                $t->integer('id_quotation_peserta')->nullable()->index();
                $t->integer('revisi');
                $t->unsignedInteger('id_invoice');
                $t->foreign('id_invoice')->references('id')->on('invoice')->restrictOnDelete();
                $t->unsignedInteger('id_konfirmasi_ulang');
                $t->foreign('id_konfirmasi_ulang')->references('id')->on('quotation_peserta_konfirmasi_ulang')->restrictOnDelete();
                $t->string('kunci_penerima', 40);
                $t->unique(['id_invoice', 'kunci_penerima'], 'uk_refund_invoice_penerima');
                $t->decimal('nominal_tagihan', 18, 0);
                $t->enum('status', ['menunggu', 'selesai'])->default('menunggu');
                $t->decimal('nominal_refund', 18, 0)->nullable();
                $t->string('bank', 50)->nullable();
                $t->string('no_rekening', 30)->nullable();
                $t->string('atas_nama', 100)->nullable();
                $t->string('bukti_transfer', 255)->nullable();
                $t->string('bukti_transfer_nama', 255)->nullable();
                $t->string('alasan', 500);
                $t->string('catatan', 500)->nullable();
                $t->longText('snapshot');
                $t->integer('selesai_by')->nullable();
                $t->dateTime('selesai_date')->nullable();
                $this->audit($t);
            });
        }
        $this->add('invoice', 'batal_by', fn ($t) => $t->integer('batal_by')->nullable());
        $this->add('invoice', 'batal_date', fn ($t) => $t->dateTime('batal_date')->nullable());
        $this->add('invoice', 'batal_alasan', fn ($t) => $t->string('batal_alasan', 500)->nullable());
        if (! DB::table('ms_menu')->where('kode', 'sales_order_refund')->exists()) {
            DB::table('ms_menu')->insert(['kode' => 'sales_order_refund', 'judul' => 'Refund', 'link' => '/sales-order/refund', 'parent_id' => DB::table('ms_menu')->where('kode', 'sales_order')->value('id') ?? 0, 'kelompok' => 1, 'urutan' => 44, 'aktif' => 1, 'sistem' => 0]);
        }
        $menu = DB::table('ms_menu')->where('kode', 'sales_order_refund')->value('id');
        foreach (['lihat', 'ubah'] as $action) {
            DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => $menu, 'aksi' => $action]);
        }
    }

    private function add(string $table, string $column, Closure $define): void
    {
        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, $define);
        }
    }

    private function audit(Blueprint $t): void
    {
        $t->integer('created_by')->nullable();
        $t->dateTime('created_date')->nullable();
        $t->integer('updated_by')->nullable();
        $t->dateTime('updated_date')->nullable();
    }

    public function down(): void
    {
        throw new RuntimeException('Rollback destruktif ditolak; pulihkan salinan dari backup terverifikasi.');
    }
};

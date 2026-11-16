<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $name = DB::connection()->getDatabaseName();
        if (! in_array($name, ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration fase 3 hanya untuk salinan Laravel dan database test terisolasi.');
        }
        // Data historis perlu rehearsal tersendiri; jangan menciptakan nama, waktu isian, atau token historis.
        foreach (['quotation' => 'link_token', 'quotation_peserta' => 'nama'] as $table => $column) {
            if (! Schema::hasColumn($table, $column) && DB::table($table)->exists()) {
                throw new RuntimeException("$table berisi data lama; jalankan rekonsiliasi migrasi data sebelum perubahan fase 3.");
            }
        }
        $this->add('penjualan_program', 'jam_pembelajaran', fn ($t) => $t->integer('jam_pembelajaran')->nullable());
        $this->add('penjualan_program', 'id_asisten', fn ($t) => $t->integer('id_asisten')->nullable());
        $this->add('penjualan_program', 'revisi', fn ($t) => $t->integer('revisi')->default(0));
        foreach (['kepada_nama' => 100, 'dokumen_keterangan' => 200] as $field => $size) {
            $this->add('quotation', $field, fn ($t) => $t->string($field, $size)->nullable());
        }
        $this->add('quotation', 'kuota_peserta', fn ($t) => $t->integer('kuota_peserta')->default(1));
        $this->add('quotation', 'link_token', fn ($t) => $t->string('link_token', 40)->nullable()->unique('uk_quotation_link_token'));
        $this->add('quotation', 'link_dibuat', fn ($t) => $t->dateTime('link_dibuat')->nullable());
        $this->add('quotation', 'paket_maks_peserta', fn ($t) => $t->integer('paket_maks_peserta')->nullable());
        $this->add('quotation', 'tambahan_per_peserta', fn ($t) => $t->decimal('tambahan_per_peserta', 15, 2)->nullable());
        $this->add('quotation', 'kolom_peserta_b2b', fn ($t) => $t->json('kolom_peserta_b2b')->nullable());
        $this->add('quotation', 'revisi', fn ($t) => $t->integer('revisi')->default(0));
        if (Schema::hasIndex('quotation_peserta', 'uk_quotation_peserta')) {
            Schema::table('quotation_peserta', fn ($t) => $t->dropUnique('uk_quotation_peserta'));
        }
        Schema::table('quotation_peserta', fn ($t) => $t->integer('id_peserta')->nullable()->change());
        $this->add('quotation_peserta', 'sumber', fn ($t) => $t->enum('sumber', ['link', 'manual', 'company'])->default('link'));
        $this->add('quotation_peserta', 'tgl_isi', fn ($t) => $t->dateTime('tgl_isi')->nullable());
        $this->add('quotation_peserta', 'input_by', fn ($t) => $t->integer('input_by')->nullable());
        foreach (['nama' => 150, 'nik' => 20, 'jabatan' => 100, 'instansi' => 150, 'no_hp' => 20, 'no_wa' => 20, 'email' => 100, 'pengalaman' => 100, 'lsp' => 100, 'surat_konfirmasi' => 255, 'surat_konfirmasi_nama' => 150] as $field => $size) {
            $this->add('quotation_peserta', $field, fn ($t) => $t->string($field, $size)->nullable());
        }
        $this->add('quotation_peserta', 'alamat_instansi', fn ($t) => $t->text('alamat_instansi')->nullable());
        $this->add('quotation_peserta', 'isian_tambahan', fn ($t) => $t->text('isian_tambahan')->nullable());
        $this->add('quotation_peserta', 'tgl_habis_sertifikat', fn ($t) => $t->date('tgl_habis_sertifikat')->nullable());
        if (! Schema::hasIndex('quotation_peserta', 'idx_qp_quotation')) {
            Schema::table('quotation_peserta', fn ($t) => $t->index('id_quotation', 'idx_qp_quotation'));
        }
        if (! Schema::hasTable('quotation_dokumen')) {
            Schema::create('quotation_dokumen', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_quotation')->index();
                $t->string('nama_file', 255);
                $t->string('path_file', 255);
                $t->enum('tipe_file', ['pdf', 'jpg', 'png', 'doc', 'docx', 'xls', 'xlsx']);
                $t->integer('ukuran_byte');
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('quotation_konfirmasi_company')) {
            Schema::create('quotation_konfirmasi_company', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_quotation');
                $t->integer('revisi')->default(0);
                $t->unique(['id_quotation', 'revisi'], 'uk_qkc_quotation_revisi');
                $t->string('nama_perusahaan', 150);
                $t->text('alamat_perusahaan');
                $t->string('pic_nama', 100);
                $t->string('pic_jabatan', 100);
                $t->string('pic_hp', 20);
                $t->string('pic_email', 100);
                $t->integer('jumlah_peserta');
                $t->string('surat_konfirmasi', 255);
                $t->string('surat_konfirmasi_nama', 255);
                $t->dateTime('tgl_kirim');
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('quotation_peserta_surat_riwayat')) {
            Schema::create('quotation_peserta_surat_riwayat', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_quotation_peserta')->index();
                $t->string('surat_konfirmasi', 255);
                $t->string('surat_konfirmasi_nama', 150);
                $t->enum('sumber', ['reschedule', 'ganti_sales']);
                $t->string('alasan', 200)->nullable();
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('sales_order')) {
            Schema::create('sales_order', function (Blueprint $t) {
                $t->increments('id');
                $t->string('nomor', 30)->unique('uk_so_nomor');
                $t->integer('id_quotation')->unique('uk_so_quotation');
                $t->integer('id_penjualan')->index();
                $t->date('tanggal');
                $t->enum('status', ['open', 'siap'])->default('open');
                $t->integer('siap_by')->nullable();
                $t->dateTime('siap_date')->nullable();
                $t->integer('revisi')->default(0);
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('surat_kerja_trainer')) {
            Schema::create('surat_kerja_trainer', function (Blueprint $t) {
                $t->increments('id');
                $t->string('nomor', 30)->unique();
                $t->integer('id_penjualan')->index();
                $t->integer('id_trainer');
                $t->integer('id_asisten');
                $t->date('tanggal');
                $t->enum('status', ['berlaku', 'diganti'])->default('berlaku');
                $t->integer('id_reschedule')->nullable();
                // NULL memberi ruang surat Diganti tanpa menghapus riwayat pada fase reschedule.
                $t->integer('program_berlaku')->nullable()->storedAs("CASE WHEN status = 'berlaku' THEN id_penjualan ELSE NULL END");
                $t->unique('program_berlaku', 'uk_skt_program_berlaku');
                $this->audit($t);
            });
        }
        Schema::table('logs', fn ($t) => $t->integer('created_by')->nullable()->change());
    }

    private function add(string $table, string $column, Closure $definition): void
    {
        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
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
        throw new RuntimeException('Rollback destruktif ditolak. Pulihkan salinan dari backup terverifikasi; jangan menghapus riwayat transaksi.');
    }
};

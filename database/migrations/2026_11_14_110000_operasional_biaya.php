<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql' && ! in_array(DB::connection()->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test'], true)) {
            throw new RuntimeException('Migration fase 6 hanya untuk database Laravel terpisah.');
        }
        $this->create('quotation_peserta_sertifikat', function (Blueprint $t) {
            $t->integer('id_quotation_peserta')->unique();
            $t->foreign('id_quotation_peserta')->references('id')->on('quotation_peserta')->restrictOnDelete();
            $t->string('no_sertifikat', 60)->nullable();
            $t->date('tgl_terbit')->nullable();
            $t->string('file_sertifikat', 255)->nullable();
            $t->enum('status_kirim', ['belum', 'soft', 'hard'])->default('belum');
            $t->date('tgl_kirim')->nullable();
            $t->string('kirim_via', 100)->nullable();
            $t->string('lsp', 100)->nullable();
            $t->string('skema', 150)->nullable();
            $t->enum('metode', ['ukom', 'porto'])->nullable();
            $t->enum('hasil', ['belum', 'kompeten', 'belum_kompeten'])->default('belum');
            $t->string('no_sertifikat_bnsp', 60)->nullable();
            foreach (['tgl_terima_bnsp', 'tgl_kirim_bnsp', 'berlaku_sampai', 'draft_kirim_date'] as $c) {
                $t->date($c)->nullable();
            }
            $t->enum('id_card', ['belum', 'sudah'])->default('belum');
            $t->string('file_sertifikat_bnsp', 255)->nullable();
            $t->string('catatan_monitoring', 500)->nullable();
            $t->string('nama_sertifikat', 150)->nullable();
            $t->integer('nama_konfirmasi_by')->nullable();
            $t->dateTime('nama_konfirmasi_date')->nullable();
        });
        $this->create('program_survey', function (Blueprint $t) {
            $t->integer('id_penjualan')->unique();
            $t->foreign('id_penjualan')->references('id')->on('penjualan_program')->restrictOnDelete();
            $t->string('token', 20)->unique();
            $t->enum('status', ['belum', 'buka', 'tutup'])->default('belum');
            foreach (['dibuka', 'ditutup'] as $c) {
                $t->integer($c.'_by')->nullable();
                $t->dateTime($c.'_date')->nullable();
            }
        });
        $this->create('survey_pertanyaan', function (Blueprint $t) {
            $t->integer('urutan');
            $t->string('bagian', 100);
            $t->string('teks', 255);
            $t->enum('tipe', ['skala', 'pilihan', 'teks']);
            $t->json('opsi')->nullable();
            $t->boolean('wajib')->default(true);
            $t->boolean('aktif')->default(true);
        });
        $this->create('survey_jawaban', function (Blueprint $t) {
            $t->integer('id_program_survey');
            $t->foreign('id_program_survey')->references('id')->on('program_survey')->restrictOnDelete();
            $t->integer('id_quotation_peserta');
            $t->foreign('id_quotation_peserta')->references('id')->on('quotation_peserta')->restrictOnDelete();
            $t->dateTime('tgl_isi');
            $t->unique(['id_program_survey', 'id_quotation_peserta'], 'uk_survey_peserta');
        });
        $this->create('survey_jawaban_detail', function (Blueprint $t) {
            $t->integer('id_survey_jawaban');
            $t->foreign('id_survey_jawaban')->references('id')->on('survey_jawaban')->restrictOnDelete();
            $t->integer('id_survey_pertanyaan');
            $t->foreign('id_survey_pertanyaan')->references('id')->on('survey_pertanyaan')->restrictOnDelete();
            $t->string('pertanyaan_teks', 255);
            $t->integer('nilai')->nullable();
            $t->text('jawaban')->nullable();
            $t->unique(['id_survey_jawaban', 'id_survey_pertanyaan'], 'uk_survey_jawaban_detail');
        });
        foreach (['pengajuan_overbudget', 'pengajuan_biaya'] as $table) {
            $this->create($table, function (Blueprint $t) use ($table) {
                $t->string('nomor', 30)->unique();
                $t->integer('id_penjualan')->index();
                $t->foreign('id_penjualan')->references('id')->on('penjualan_program')->restrictOnDelete();
                $t->enum('tipe_biaya', ['fixed', 'variable']);
                if ($table === 'pengajuan_biaya') {
                    $t->enum('jenis', ['kasbon', 'direct_payment']);
                    $t->string('bank', 50);
                    $t->string('no_rekening', 30);
                    $t->string('nama_rekening', 100);
                    $t->integer('paid_by')->nullable();
                    $t->date('paid_date')->nullable();
                    $t->string('paid_keterangan', 150)->nullable();
                }
                $t->date('tanggal');
                $t->text($table === 'pengajuan_biaya' ? 'deskripsi' : 'alasan');
                $t->decimal('total', 15, 2);
                $t->enum('status', $table === 'pengajuan_biaya' ? ['menunggu', 'disetujui', 'paid', 'ditolak', 'batal'] : ['menunggu', 'disetujui', 'ditolak', 'batal'])->default('menunggu');
                $t->integer('request_by')->index();
                foreach (['approved', 'closed'] as $c) {
                    $t->integer($c.'_by')->nullable();
                    $t->dateTime($c.'_date')->nullable();
                }
                $t->text('alasan_tolak')->nullable();
            });
        }
        $this->create('pengajuan_overbudget_detail', function (Blueprint $t) {
            $t->integer('id_overbudget');
            $t->foreign('id_overbudget')->references('id')->on('pengajuan_overbudget')->restrictOnDelete();
            $this->item($t);
        });
        $this->create('pengajuan_biaya_detail', function (Blueprint $t) {
            $t->integer('id_pengajuan');
            $t->foreign('id_pengajuan')->references('id')->on('pengajuan_biaya')->restrictOnDelete();
            $t->integer('id_overbudget_detail')->nullable();
            $t->foreign('id_overbudget_detail')->references('id')->on('pengajuan_overbudget_detail')->restrictOnDelete();
            $this->item($t);
        });
        if (DB::connection()->getDriverName() === 'mysql' && ! DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='pengajuan_biaya_detail' AND CONSTRAINT_NAME='ck_pengajuan_sumber'")) {
            DB::statement('ALTER TABLE pengajuan_biaya_detail ADD CONSTRAINT ck_pengajuan_sumber CHECK ((id_penjualan_biaya IS NULL) <> (id_overbudget_detail IS NULL))');
        }
        $this->create('pengajuan_biaya_lampiran', function (Blueprint $t) {
            $t->integer('id_pengajuan');
            $t->foreign('id_pengajuan')->references('id')->on('pengajuan_biaya')->restrictOnDelete();
            $t->string('nama_file', 150);
            $t->string('file_path', 255);
        });
        foreach (['Admin', 'Direktur'] as $role) {
            if (! DB::table('groups')->where('nm_group', $role)->where('id_group', '<>', 1)->exists()) {
                DB::table('groups')->insert(['nm_group' => $role, 'ket' => 'Role bisnis fase 6; anggota dan izin ditentukan administrator.']);
            }
        }
        foreach (['manage_peserta' => ['Manage Peserta', '/manage-peserta', ['lihat', 'ubah']], 'monitoring_peserta' => ['Monitoring Peserta', '/manage-peserta/monitoring', ['lihat', 'ubah']], 'pengajuan_biaya' => ['Pengajuan Biaya', '/pengajuan-biaya', ['lihat', 'tambah', 'ubah']], 'approval_pengajuan_biaya' => ['Approval Pengajuan', '/pengajuan-biaya/approval', ['lihat', 'approve']]] as $code => [$title, $link, $actions]) {
            if (! DB::table('ms_menu')->where('kode', $code)->exists()) {
                DB::table('ms_menu')->insert(['kode' => $code, 'judul' => $title, 'link' => $link, 'parent_id' => 0, 'kelompok' => 1, 'urutan' => 50, 'aktif' => 1, 'sistem' => 0]);
            }
            foreach ($actions as $a) {
                DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => DB::table('ms_menu')->where('kode', $code)->value('id'), 'aksi' => $a]);
            }
        }
    }

    private function item(Blueprint $t): void
    {
        $t->integer('id_penjualan_biaya')->nullable();
        $t->foreign('id_penjualan_biaya')->references('id')->on('penjualan_program_biaya')->restrictOnDelete();
        $t->string('item', 150);
        $t->decimal('qty', 10, 2);
        $t->decimal('harga', 15, 2);
        $t->decimal('total', 15, 2);
    }

    private function create(string $name, Closure $columns): void
    {
        if (! Schema::hasTable($name)) {
            Schema::create($name, function (Blueprint $t) use ($columns) {
                $t->engine = 'InnoDB';
                $t->integer('id', true);
                $columns($t);
                $t->integer('created_by')->nullable();
                $t->dateTime('created_date')->nullable();
                $t->integer('updated_by')->nullable();
                $t->dateTime('updated_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Rollback destruktif ditolak; pulihkan backup terverifikasi.');
    }
};

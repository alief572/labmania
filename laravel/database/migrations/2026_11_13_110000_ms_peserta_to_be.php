<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * ms_peserta to-be (ERD, QTN-FR-33): kolom nik, dan field wajib dilonggarkan ke NULL supaya peserta dari Deal
 * bisa "Belum lengkap" (FR-15). Hanya bagian ms_peserta dari SQL CI3 20261112_to_be_quotation_so.sql;
 * tabel quotation*, penjualan_program, dan sisanya milik fase 3. Hanya untuk salinan terisolasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        abort_if(DB::getDatabaseName() === 'db_labmania', 500, 'Migrasi ini tidak boleh dijalankan pada database CI3.');
        if (! Schema::hasColumn('ms_peserta', 'nik')) {
            Schema::table('ms_peserta', fn (Blueprint $t) => $t->string('nik', 20)->nullable()->after('email'));
        }
        DB::statement("ALTER TABLE ms_peserta MODIFY COLUMN gender ENUM('L','P') NULL, MODIFY COLUMN instansi VARCHAR(150) NULL, MODIFY COLUMN id_jenis_bidang INT NULL, MODIFY COLUMN no_telp VARCHAR(20) NULL, MODIFY COLUMN email VARCHAR(100) NULL");
    }

    public function down(): void
    {
        // Salinan terisolasi: pulihkan dengan menyalin ulang dari sumber.
    }
};

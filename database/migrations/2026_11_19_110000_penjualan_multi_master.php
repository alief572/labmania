<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Beberapa Master Program dalam satu Penjualan Program (PJ-FR-32, PJ-FR-34, [JMR-1], [JMR-2], [JUM-2], [JUM-3]).
 * Tiap Master Program punya jadwal, Jam Pembelajaran, trainer, asisten, harga jual, target profit, dan biaya sendiri.
 * Header penjualan_program tetap menyimpan nilai program (rentang tanggal, jumlah JP, jumlah harga) agar modul hilir tidak berubah.
 * Data lama: satu baris master per penjualan, disalin dari header; biaya lama dihubungkan ke baris itu. Idempoten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration hanya untuk salinan Laravel atau database test terisolasi.');
        }
        if (! Schema::hasTable('penjualan_program_master')) {
            Schema::create('penjualan_program_master', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_penjualan');
                $t->integer('id_master_program');
                $t->unsignedSmallInteger('urutan')->default(1);
                $t->date('tgl_mulai')->nullable();
                $t->date('tgl_selesai')->nullable();
                $t->integer('durasi_hari')->default(1);
                $t->integer('jam_pembelajaran')->nullable();
                $t->integer('id_trainer')->nullable();
                $t->integer('id_asisten')->nullable();
                $t->decimal('harga_jual_peserta', 15, 2)->default(0);
                $t->decimal('target_profit', 15, 2)->default(0);
                $t->integer('created_by')->nullable();
                $t->dateTime('created_date')->nullable();
                $t->integer('updated_by')->nullable();
                $t->dateTime('updated_date')->nullable();
                $t->unique(['id_penjualan', 'id_master_program'], 'uk_pjm_program_master');
                $t->unique(['id_penjualan', 'urutan'], 'uk_pjm_program_urutan');
            });
        }
        if (! Schema::hasColumn('penjualan_program_biaya', 'id_penjualan_master')) {
            Schema::table('penjualan_program_biaya', function (Blueprint $t) {
                $t->integer('id_penjualan_master')->nullable()->index('idx_pjb_master');
            });
        }
        if (! Schema::hasTable('penjualan_program_reschedule_detail')) {
            Schema::create('penjualan_program_reschedule_detail', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('id_reschedule')->index();
                $t->integer('id_penjualan_master');
                $t->integer('id_master_program');
                $t->unsignedSmallInteger('urutan');
                $t->date('tgl_mulai_lama');
                $t->date('tgl_selesai_lama');
                $t->date('tgl_mulai_baru');
                $t->date('tgl_selesai_baru');
                $t->integer('id_trainer_lama')->nullable();
                $t->integer('id_trainer_baru')->nullable();
                $t->integer('id_asisten_lama')->nullable();
                $t->integer('id_asisten_baru')->nullable();
                $t->integer('created_by')->nullable();
                $t->dateTime('created_date')->nullable();
                $t->integer('updated_by')->nullable();
                $t->dateTime('updated_date')->nullable();
            });
        }
        // Data lama: satu Master Program per penjualan, disalin dari header (tidak mengubah nilai yang sudah ada).
        DB::statement('INSERT INTO penjualan_program_master (id_penjualan, id_master_program, urutan, tgl_mulai, tgl_selesai, durasi_hari, jam_pembelajaran, id_trainer, id_asisten, harga_jual_peserta, target_profit, created_by, created_date)
            SELECT p.id, p.id_master_program, 1, p.tgl_mulai, p.tgl_selesai, p.durasi_hari, p.jam_pembelajaran, p.id_trainer, p.id_asisten, COALESCE(p.harga_jual_peserta, 0), COALESCE(p.target_profit, 0), p.created_by, p.created_date
            FROM penjualan_program p WHERE NOT EXISTS (SELECT 1 FROM penjualan_program_master m WHERE m.id_penjualan = p.id)');
        DB::statement('UPDATE penjualan_program_biaya SET id_penjualan_master = (SELECT m.id FROM penjualan_program_master m WHERE m.id_penjualan = penjualan_program_biaya.id_penjualan AND m.urutan = 1) WHERE id_penjualan_master IS NULL');
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi multi Master Program tidak dapat dibalik: data per Master Program sudah dipakai.');
    }
};

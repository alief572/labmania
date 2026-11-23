<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * [MMB-1] 13-11-2026: Master Program kembali satu Product. master_program.id_paket_produk kembali,
 * master_program_produk dihapus. Berhenti (tanpa mengubah apa pun) bila ada program dengan lebih dari satu Product,
 * agar tidak ada data Product yang dibuang diam-diam.
 * db_labmania hanya diizinkan dengan ALLOW_DB_LABMANIA_MIGRATION=1 pada proses ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        abort_if(DB::getDatabaseName() === 'db_labmania' && getenv('ALLOW_DB_LABMANIA_MIGRATION') !== '1', 500, 'db_labmania hanya boleh dimigrasi dengan ALLOW_DB_LABMANIA_MIGRATION=1.');

        if (! Schema::hasTable('master_program_produk')) {
            return;
        }

        $multi = DB::table('master_program_produk as x')->join('master_program as mp', 'mp.id', '=', 'x.id_master_program')
            ->groupBy('mp.id', 'mp.nomor')->havingRaw('COUNT(*) > 1')->pluck('mp.nomor');
        abort_if($multi->isNotEmpty(), 500, 'Program dengan lebih dari satu Product: '.$multi->implode(', ').'. Putuskan Product tunggalnya dulu.');

        if (! Schema::hasColumn('master_program', 'id_paket_produk')) {
            Schema::table('master_program', fn (Blueprint $t) => $t->integer('id_paket_produk')->nullable()->after('nomor'));
        }
        DB::statement('UPDATE master_program mp JOIN master_program_produk x ON x.id_master_program = mp.id SET mp.id_paket_produk = x.id_paket_produk');

        $kosong = DB::table('master_program')->whereNull('id_paket_produk')->pluck('nomor');
        abort_if($kosong->isNotEmpty(), 500, 'Program tanpa Product: '.$kosong->implode(', ').'. Kolom tidak dijadikan wajib.');

        Schema::table('master_program', function (Blueprint $t) {
            $t->integer('id_paket_produk')->nullable(false)->change();
            $t->index('id_paket_produk', 'idx_mp_paket');
        });
        Schema::drop('master_program_produk');
    }

    public function down(): void
    {
        abort_if(DB::getDatabaseName() === 'db_labmania' && getenv('ALLOW_DB_LABMANIA_MIGRATION') !== '1', 500, 'db_labmania hanya boleh dimigrasi dengan ALLOW_DB_LABMANIA_MIGRATION=1.');

        if (! Schema::hasTable('master_program_produk')) {
            Schema::create('master_program_produk', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_master_program');
                $t->integer('id_paket_produk');
                $t->integer('urutan');
                $t->integer('created_by')->nullable();
                $t->dateTime('created_date')->nullable();
                $t->unique(['id_master_program', 'id_paket_produk'], 'uk_mpp_program_paket');
                $t->unique(['id_master_program', 'urutan'], 'uk_mpp_program_urutan');
            });
        }
        if (Schema::hasColumn('master_program', 'id_paket_produk')) {
            DB::statement('INSERT IGNORE INTO master_program_produk (id_master_program, id_paket_produk, urutan, created_by, created_date) SELECT id, id_paket_produk, 1, created_by, COALESCE(created_date, NOW()) FROM master_program WHERE id_paket_produk IS NOT NULL');
            Schema::table('master_program', function (Blueprint $t) {
                $t->dropIndex('idx_mp_paket');
                $t->dropColumn('id_paket_produk');
            });
        }
    }
};

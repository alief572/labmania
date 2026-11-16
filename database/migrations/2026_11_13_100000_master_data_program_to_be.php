<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Skema to-be fase 2 (Master Data + Master Program). HANYA untuk salinan terisolasi labmania_laravel.
 * Bukan replay SQL CI3 20261112_*.sql; tiap langkah memakai guard dan aman dijalankan ulang.
 * Tidak mengisi data: tidak ada backfill JP, Varian, atau NPWP.
 */
return new class extends Migration
{
    private const ATTR = ['ms_jenis_produk', 'ms_jenis_sertifikasi', 'ms_jenis_bidang', 'ms_varian_pelaksanaan'];

    public function up(): void
    {
        abort_if(DB::getDatabaseName() === 'db_labmania', 500, 'Migrasi ini tidak boleh dijalankan pada database CI3.');

        if (! Schema::hasTable('ms_varian_pelaksanaan')) {
            Schema::create('ms_varian_pelaksanaan', function (Blueprint $t) {
                $t->increments('id');
                $t->string('kode', 20)->unique();
                $t->string('nama', 100);
                $t->tinyInteger('aktif')->default(1);
                $t->integer('created_by')->nullable();
                $t->dateTime('created_date')->nullable();
                $t->integer('updated_by')->nullable();
                $t->dateTime('updated_date')->nullable();
            });
        }

        foreach (self::ATTR as $table) {
            if (! $this->hasIndexOn($table, ['nama'])) {
                $dups = DB::table($table)->select('nama')->groupBy('nama')->havingRaw('COUNT(*) > 1')->pluck('nama');
                abort_if($dups->isNotEmpty(), 500, "Nama ganda di $table: ".$dups->implode(', ').'. Perbaiki data dulu.');
                Schema::table($table, fn (Blueprint $t) => $t->unique('nama', "uk_{$table}_nama"));
            }
        }

        if (! Schema::hasColumn('paket_produk', 'id_varian_pelaksanaan')) {
            Schema::table('paket_produk', fn (Blueprint $t) => $t->integer('id_varian_pelaksanaan')->nullable()->after('id_jenis_bidang'));
        }
        if (! Schema::hasColumn('paket_produk', 'kombinasi_aktif')) {
            $dups = DB::table('paket_produk')->where('aktif', 1)
                ->selectRaw('id_jenis_produk, id_jenis_sertifikasi, id_jenis_bidang, IFNULL(id_varian_pelaksanaan,0) v, COUNT(*) n')
                ->groupBy('id_jenis_produk', 'id_jenis_sertifikasi', 'id_jenis_bidang', 'v')->having('n', '>', 1)->count();
            abort_if($dups > 0, 500, "Ada $dups kombinasi Product aktif ganda. Perbaiki data dulu.");
            if ($this->hasIndexNamed('paket_produk', 'uk_paket_kombinasi')) {
                Schema::table('paket_produk', fn (Blueprint $t) => $t->dropUnique('uk_paket_kombinasi'));
            }
            // Unik hanya di antara Product aktif (FR-22): nilai NULL untuk nonaktif tidak bentrok.
            DB::statement("ALTER TABLE paket_produk ADD COLUMN kombinasi_aktif VARCHAR(60) GENERATED ALWAYS AS (CASE WHEN aktif = 1 THEN CONCAT(id_jenis_produk,'-',id_jenis_sertifikasi,'-',id_jenis_bidang,'-',IFNULL(id_varian_pelaksanaan,0)) END) STORED, ADD UNIQUE KEY uk_paket_kombinasi_aktif (kombinasi_aktif)");
        }

        if (! Schema::hasTable('paket_produk_lingkup')) {
            Schema::create('paket_produk_lingkup', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_paket_produk');
                $t->integer('urutan');
                $t->string('lingkup', 200);
                $t->integer('created_by')->nullable();
                $t->dateTime('created_date')->nullable();
                $t->index(['id_paket_produk', 'urutan'], 'idx_ppl_paket');
            });
        }

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
            abort_if(DB::table('master_program')->whereNotNull('id_paket_produk')->count() !== DB::table('master_program_produk')->count(), 500, 'Salinan Product program tidak lengkap; kolom lama tidak dihapus.');
            Schema::table('master_program', fn (Blueprint $t) => $t->dropColumn('id_paket_produk'));
        }
        if (! Schema::hasColumn('master_program', 'jam_pembelajaran')) {
            // Nullable sampai data lama dibersihkan (fase 7); validasi aplikasi mewajibkan JP > 0.
            Schema::table('master_program', fn (Blueprint $t) => $t->unsignedInteger('jam_pembelajaran')->nullable()->after('durasi_hari'));
        }

        $this->menu();
    }

    private function menu(): void
    {
        $parent = DB::table('ms_menu')->where('kode', 'm_master_data')->value('id');
        if (! DB::table('ms_menu')->where('kode', 'master_data.varian_pelaksanaan')->exists()) {
            DB::table('ms_menu')->where('parent_id', $parent)->where('urutan', '>=', 4)->increment('urutan');
            $id = DB::table('ms_menu')->insertGetId(['parent_id' => $parent, 'kode' => 'master_data.varian_pelaksanaan', 'judul' => 'Varian Pelaksanaan', 'link' => 'master_data/varian_pelaksanaan', 'kelompok' => 1, 'urutan' => 4, 'aktif' => 1, 'sistem' => 0, 'created_date' => now()]);
            foreach (['lihat', 'tambah', 'ubah', 'aktif'] as $aksi) {
                DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => $id, 'aksi' => $aksi]);
            }
        }
        DB::table('ms_menu')->where('kode', 'master_data.paket_produk')->update(['judul' => 'Product']);
        $katalog = DB::table('ms_menu')->where('kode', 'master_data.katalog')->value('id');
        foreach (['ubah', 'cetak'] as $aksi) {
            DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => $katalog, 'aksi' => $aksi]);
        }
        // Sengaja tidak menyalin hak group lama ke menu/aksi baru: hak baru default tidak diberikan sampai dipetakan.
    }

    private function hasIndexOn(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['unique'] && $index['columns'] === $columns) {
                return true;
            }
        }

        return false;
    }

    private function hasIndexNamed(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $name);
    }

    public function down(): void
    {
        // Salinan terisolasi: pulihkan dengan menyalin ulang dari sumber, bukan rollback parsial.
    }
};

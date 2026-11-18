<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Refund sendiri dengan approval (RF-FR-01, RF-FR-05, RF-FR-07, [MMB-6], [JMR-4], [JMR-5]).
 * Status: menunggu (approval) -> disetujui -> selesai; menunggu -> ditolak -> (perbaiki dan ajukan ulang) -> menunggu.
 * Dua menu baru menggantikan sales_order_refund: refund_daftar (lihat/tambah/ubah) dan refund_approval (lihat/approve).
 */
return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection();
        if (! in_array($db->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration hanya untuk salinan Laravel atau database test terisolasi.');
        }
        // Status lama hanya menunggu dan selesai. Refund lama yang belum selesai sudah ditangani Finance tanpa tahap approval,
        // sehingga dipetakan ke disetujui (siap ditransfer) agar tidak menahan transfer yang sudah disiapkan.
        // Idempoten: dijalankan hanya bila kolom sebab belum ada, yaitu pada migrasi pertama; refund baru berstatus menunggu sungguhan.
        $first = ! Schema::hasColumn('sales_order_refund', 'sebab');
        if ($first) {
            Schema::table('sales_order_refund', fn (Blueprint $t) => $t->enum('sebab', ['reschedule', 'batal'])->default('reschedule'));
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_order_refund MODIFY status ENUM('menunggu','disetujui','ditolak','selesai') NOT NULL DEFAULT 'menunggu'");
        } else {
            // SQLite (tes): enum berupa CHECK constraint; change() membangun ulang tabel dengan daftar status baru.
            Schema::table('sales_order_refund', fn (Blueprint $t) => $t->enum('status', ['menunggu', 'disetujui', 'ditolak', 'selesai'])->default('menunggu')->change());
        }
        if ($first) {
            DB::table('sales_order_refund')->where('status', 'menunggu')->update(['status' => 'disetujui']);
        }
        if (! Schema::hasTable('sales_order_refund_riwayat')) {
            Schema::create('sales_order_refund_riwayat', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('id_refund')->index();
                $t->enum('jenis', ['setujui', 'tolak', 'ajukan_ulang']);
                $t->text('catatan')->nullable();
                $t->decimal('nominal_lama', 18, 0)->nullable();
                $t->decimal('nominal_baru', 18, 0)->nullable();
                $t->string('alasan_lama', 500)->nullable();
                $t->string('alasan_baru', 500)->nullable();
                $t->integer('oleh')->nullable();
                $t->dateTime('waktu');
            });
        }
        $this->menus();
    }

    private function menus(): void
    {
        $old = DB::table('ms_menu')->where('kode', 'sales_order_refund')->first();
        $parent = DB::table('ms_menu')->where('kode', 'm_quotation')->value('id') ?? 0;
        $make = function (string $kode, string $judul, string $link, int $urutan, array $actions) use ($parent) {
            if (! DB::table('ms_menu')->where('kode', $kode)->exists()) {
                DB::table('ms_menu')->insert(['kode' => $kode, 'judul' => $judul, 'link' => $link, 'parent_id' => $parent, 'kelompok' => 3, 'urutan' => $urutan, 'aktif' => 1, 'sistem' => 0]);
            }
            $id = (int) DB::table('ms_menu')->where('kode', $kode)->value('id');
            foreach ($actions as $action) {
                DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => $id, 'aksi' => $action]);
            }

            return $id;
        };
        $daftar = $make('refund_daftar', 'Daftar Refund', '/refund', 50, ['lihat', 'tambah', 'ubah']);
        $make('refund_approval', 'Approval Refund', '/refund/approval', 51, ['lihat', 'approve']);
        if (! $old) {
            return;
        }
        // Hak role yang sudah memegang menu lama pindah ke Daftar Refund: lihat tetap lihat; ubah (proses transfer) tetap ubah.
        // Approval Refund sengaja tidak diberikan otomatis: Finance diatur lewat Role & Permission (RF-FR-01).
        foreach (DB::table('ms_group_akses')->where('id_menu', $old->id)->get() as $grant) {
            if (in_array($grant->aksi, ['lihat', 'ubah'], true)) {
                DB::table('ms_group_akses')->insertOrIgnore(['id_group' => $grant->id_group, 'id_menu' => $daftar, 'aksi' => $grant->aksi]);
            }
        }
        foreach (DB::table('ms_user_akses')->where('id_menu', $old->id)->get() as $grant) {
            if (in_array($grant->aksi, ['lihat', 'ubah'], true)) {
                DB::table('ms_user_akses')->insertOrIgnore(['id_user' => $grant->id_user, 'id_menu' => $daftar, 'aksi' => $grant->aksi]);
            }
        }
        // Menu lama dinonaktifkan, bukan dihapus: baris hak dan riwayat tetap utuh.
        DB::table('ms_menu')->where('id', $old->id)->update(['aktif' => 0]);
    }

    public function down(): void
    {
        throw new RuntimeException('Rollback Refund approval memerlukan snapshot status dan hak; pulihkan dari cadangan.');
    }
};

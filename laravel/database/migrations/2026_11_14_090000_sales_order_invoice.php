<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection();
        if (! in_array($db->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration hanya untuk salinan Laravel atau database test terisolasi.');
        }
        if (! Schema::hasColumn('master_customer', 'perlakuan_ppn')) {
            Schema::table('master_customer', fn (Blueprint $t) => $t->enum('perlakuan_ppn', ['PKP', 'WAPU', 'KB'])->nullable());
        }
        if (! Schema::hasColumn('surat_kerja_trainer', 'snapshot')) {
            Schema::table('surat_kerja_trainer', fn (Blueprint $t) => $t->longText('snapshot')->nullable());
        }
        if (! Schema::hasTable('invoice')) {
            Schema::create('invoice', function (Blueprint $t) {
                $t->increments('id');
                $t->string('nomor', 30)->unique();
                $t->date('tanggal');
                $t->unsignedInteger('id_sales_order')->index();
                $t->foreign('id_sales_order')->references('id')->on('sales_order')->restrictOnDelete();
                $t->integer('id_quotation')->index();
                $t->integer('id_penjualan')->index();
                $t->enum('cara_tagih', ['satu']);
                $t->enum('perlakuan_ppn', ['PKP', 'WAPU', 'KB']);
                $t->string('no_faktur', 100)->nullable();
                foreach (['penjualan', 'dpp', 'ppn', 'biaya_lain', 'total_tagihan', 'piutang'] as $column) {
                    $t->decimal($column, 18, 0);
                }
                // coa_master masih MyISAM; referensi PK diperiksa server dan nama akun disnapshot.
                $t->integer('coa_penjualan')->index();
                $t->enum('status', ['invoiced', 'batal'])->default('invoiced');
                $t->unsignedInteger('so_aktif')->nullable()->storedAs("CASE WHEN status = 'invoiced' THEN id_sales_order ELSE NULL END");
                $t->unique('so_aktif', 'uk_invoice_so_aktif');
                $t->longText('snapshot');
                $t->date('tanggal_bayar')->nullable();
                $t->string('keterangan_bayar', 100)->nullable();
                $t->integer('bayar_by')->nullable();
                $t->dateTime('bayar_date')->nullable();
                $this->audit($t);
            });
        }
        // MySQL DDL tidak atomic; lengkapi indeks bila percobaan sebelumnya berhenti setelah CREATE.
        foreach (['invoice_nomor_unique' => ['nomor', true], 'uk_invoice_so_aktif' => ['so_aktif', true],
            'invoice_id_sales_order_index' => ['id_sales_order', false], 'invoice_id_quotation_index' => ['id_quotation', false],
            'invoice_id_penjualan_index' => ['id_penjualan', false], 'invoice_coa_penjualan_index' => ['coa_penjualan', false]] as $name => [$column, $unique]) {
            if (! Schema::hasIndex('invoice', $name)) {
                Schema::table('invoice', fn (Blueprint $t) => $unique ? $t->unique($column, $name) : $t->index($column, $name));
            }
        }
        if (! Schema::hasTable('invoice_peserta')) {
            Schema::create('invoice_peserta', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('id_invoice');
                $t->foreign('id_invoice')->references('id')->on('invoice')->restrictOnDelete();
                $t->integer('id_quotation_peserta')->index();
                $t->decimal('nominal', 18, 0);
                $t->string('nama', 150);
                $t->unique(['id_invoice', 'id_quotation_peserta'], 'uk_invoice_peserta');
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('invoice_jurnal')) {
            Schema::create('invoice_jurnal', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('id_invoice');
                $t->foreign('id_invoice')->references('id')->on('invoice')->restrictOnDelete();
                $t->enum('jenis', ['invoice', 'bukti_setor']);
                $t->integer('urutan');
                $t->date('tanggal');
                $t->string('company', 150);
                $t->integer('coa')->index();
                $t->string('no_coa', 10);
                $t->string('nama_coa', 255);
                $t->string('deskripsi', 500);
                $t->decimal('debit', 18, 0);
                $t->decimal('credit', 18, 0);
                $t->unique(['id_invoice', 'jenis', 'urutan'], 'uk_invoice_jurnal');
                $this->audit($t);
            });
        }
        if (! Schema::hasTable('invoice_bukti_setor')) {
            Schema::create('invoice_bukti_setor', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('id_invoice')->unique();
                $t->foreign('id_invoice')->references('id')->on('invoice')->restrictOnDelete();
                $t->date('tanggal');
                $t->string('nomor', 100)->nullable();
                $this->audit($t);
            });
        }
        foreach (['sales_order' => ['Sales Order', '/sales-order', ['lihat', 'ubah']], 'invoicing' => ['Invoicing', '/invoicing', ['lihat', 'tambah', 'ubah']]] as $code => [$title, $link, $actions]) {
            if (! DB::table('ms_menu')->where('kode', $code)->exists()) {
                DB::table('ms_menu')->insert(['kode' => $code, 'judul' => $title, 'link' => $link,
                    'parent_id' => DB::table('ms_menu')->where('kode', 'm_quotation')->value('id') ?? 0, 'kelompok' => 1, 'urutan' => $code === 'sales_order' ? 42 : 43, 'aktif' => 1, 'sistem' => 0]);
            }
            $id = DB::table('ms_menu')->where('kode', $code)->value('id');
            foreach ($actions as $action) {
                DB::table('ms_menu_aksi')->insertOrIgnore(['id_menu' => $id, 'aksi' => $action]);
            }
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

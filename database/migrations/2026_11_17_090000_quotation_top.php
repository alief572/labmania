<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cara pembayaran quotation: Full payment atau TOP (QTN-FR-44, SO-FR-24).
 * ERD to be: quotation.cara_bayar, tabel quotation_termin, invoice.termin_ke.
 */
return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection();
        if (! in_array($db->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration hanya untuk salinan Laravel atau database test terisolasi.');
        }
        if (! Schema::hasColumn('quotation', 'cara_bayar')) {
            Schema::table('quotation', fn (Blueprint $t) => $t->enum('cara_bayar', ['full', 'top'])->default('full'));
        }
        if (! Schema::hasTable('quotation_termin')) {
            // Nominal tidak disimpan: dihitung dari persen x Grand total, termin terakhir menampung selisih pembulatan.
            Schema::create('quotation_termin', function (Blueprint $t) {
                $t->increments('id');
                $t->integer('id_quotation')->index();
                $t->unsignedTinyInteger('urutan');
                $t->decimal('persen', 5, 2);
                $t->string('keterangan', 150);
                $t->unique(['id_quotation', 'urutan'], 'uk_quotation_termin');
                $t->integer('created_by')->nullable();
                $t->dateTime('created_date')->nullable();
                $t->integer('updated_by')->nullable();
                $t->dateTime('updated_date')->nullable();
            });
        }
        if (! Schema::hasColumn('invoice', 'termin_ke')) {
            Schema::table('invoice', fn (Blueprint $t) => $t->unsignedTinyInteger('termin_ke')->nullable());
            // Satu invoice aktif per SO (Full) menjadi satu invoice aktif per SO dan termin (TOP). Nama kolom dan indeks dipertahankan.
            if (Schema::hasIndex('invoice', 'uk_invoice_so_aktif')) {
                Schema::table('invoice', fn (Blueprint $t) => $t->dropUnique('uk_invoice_so_aktif'));
            }
            Schema::table('invoice', fn (Blueprint $t) => $t->dropColumn('so_aktif'));
            Schema::table('invoice', function (Blueprint $t) {
                $t->unsignedBigInteger('so_aktif')->nullable()->storedAs("CASE WHEN status = 'invoiced' THEN id_sales_order * 100 + COALESCE(termin_ke, 0) ELSE NULL END");
                $t->unique('so_aktif', 'uk_invoice_so_aktif');
            });
        }
    }
};

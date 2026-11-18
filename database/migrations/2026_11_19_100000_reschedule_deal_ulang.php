<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deal ulang setelah reschedule (SO-FR-16/21, QTN-FR-42/43, PJ-FR-27 [RDU]): quotation Deal kembali ke Terkirim, SO lama menjadi Batal dan
 * SO -rev terbit saat Deal ulang, invoice diberi penanda reschedule. Satu quotation kini boleh punya beberapa SO (satu aktif, sisanya batal),
 * jadi unique id_quotation diganti kolom generated so_aktif (pola sama dengan invoice.so_aktif). Idempoten; hanya menambah kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration hanya untuk salinan Laravel atau database test terisolasi.');
        }
        $add = function (string $table, string $column, Closure $define) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::table($table, $define);
            }
        };
        foreach ([
            'batal_sebab' => fn (Blueprint $t) => $t->enum('batal_sebab', ['reschedule', 'program'])->nullable(),
            'batal_alasan' => fn (Blueprint $t) => $t->string('batal_alasan', 500)->nullable(),
            'batal_waktu' => fn (Blueprint $t) => $t->dateTime('batal_waktu')->nullable(),
            'batal_revisi' => fn (Blueprint $t) => $t->integer('batal_revisi')->nullable(),
            'batal_peserta' => fn (Blueprint $t) => $t->integer('batal_peserta')->nullable(),
            'batal_nilai' => fn (Blueprint $t) => $t->decimal('batal_nilai', 18, 0)->nullable(),
            'digantikan_oleh' => fn (Blueprint $t) => $t->unsignedInteger('digantikan_oleh')->nullable(),
        ] as $column => $define) {
            $add('sales_order', $column, $define);
        }
        foreach ([
            'deal_ulang_revisi' => fn (Blueprint $t) => $t->integer('deal_ulang_revisi')->nullable(),
            'deal_ulang_waktu' => fn (Blueprint $t) => $t->dateTime('deal_ulang_waktu')->nullable(),
            'deal_ulang_selesai_revisi' => fn (Blueprint $t) => $t->integer('deal_ulang_selesai_revisi')->nullable(),
            'deal_ulang_selesai_date' => fn (Blueprint $t) => $t->dateTime('deal_ulang_selesai_date')->nullable(),
        ] as $column => $define) {
            $add('quotation', $column, $define);
        }
        $add('invoice', 'resched_revisi', fn (Blueprint $t) => $t->integer('resched_revisi')->nullable());
        $add('invoice', 'resched_waktu', fn (Blueprint $t) => $t->dateTime('resched_waktu')->nullable());

        if (! Schema::hasColumn('sales_order', 'so_aktif')) {
            if (Schema::hasIndex('sales_order', 'uk_so_quotation')) {
                Schema::table('sales_order', fn (Blueprint $t) => $t->dropUnique('uk_so_quotation'));
            }
            Schema::table('sales_order', function (Blueprint $t) {
                $t->integer('so_aktif')->nullable()->storedAs("CASE WHEN status <> 'batal' THEN id_quotation ELSE NULL END");
                $t->unique('so_aktif', 'uk_so_aktif');
            });
            // Indeks biasa menggantikan unique lama untuk pencarian SO per quotation.
            if (! Schema::hasIndex('sales_order', 'idx_so_quotation')) {
                Schema::table('sales_order', fn (Blueprint $t) => $t->index('id_quotation', 'idx_so_quotation'));
            }
        }
        // SO Deal ulang memakai nomor dasar SO lama + -rev{n} (SO-FR-21): nomor yang sama boleh muncul lagi dengan revisi berbeda.
        if (Schema::hasIndex('sales_order', 'uk_so_nomor')) {
            Schema::table('sales_order', fn (Blueprint $t) => $t->dropUnique('uk_so_nomor'));
        }
        if (! Schema::hasIndex('sales_order', 'uk_so_nomor_revisi')) {
            Schema::table('sales_order', fn (Blueprint $t) => $t->unique(['nomor', 'revisi'], 'uk_so_nomor_revisi'));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Migrasi Deal ulang tidak dapat dibalik: SO batal dan penanda reschedule sudah dipakai data.');
    }
};

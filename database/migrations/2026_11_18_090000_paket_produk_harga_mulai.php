<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga "start from" per Paket Produk (FR-69, [MMB-5]): perkiraan untuk promosi di Katalog Produk dan PDF katalog,
 * bukan harga final. Tidak dipakai di Master Program, Penjualan Program, quotation, atau invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection();
        if (! in_array($db->getDatabaseName(), ['labmania_laravel', 'labmania_laravel_test', ':memory:'], true)) {
            throw new RuntimeException('Migration hanya untuk salinan Laravel atau database test terisolasi.');
        }
        if (! Schema::hasColumn('paket_produk', 'harga_mulai')) {
            Schema::table('paket_produk', fn (Blueprint $t) => $t->decimal('harga_mulai', 18, 0)->nullable());
        }
    }
};

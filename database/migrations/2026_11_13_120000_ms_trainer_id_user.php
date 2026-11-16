<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * FR-59: akun login trainer (ms_trainer.id_user, opsional, satu akun untuk satu trainer).
 * Hanya untuk salinan terisolasi. Unique index mengizinkan banyak NULL (trainer lepas tanpa akun).
 */
return new class extends Migration
{
    public function up(): void
    {
        abort_if(DB::getDatabaseName() === 'db_labmania', 500, 'Migrasi ini tidak boleh dijalankan pada database CI3.');
        if (! Schema::hasColumn('ms_trainer', 'id_user')) {
            Schema::table('ms_trainer', function (Blueprint $t) {
                $t->integer('id_user')->nullable()->after('aktif');
                $t->unique('id_user', 'uk_ms_trainer_id_user');
            });
        }
    }

    public function down(): void
    {
        // Salinan terisolasi: pulihkan dengan menyalin ulang dari sumber.
    }
};

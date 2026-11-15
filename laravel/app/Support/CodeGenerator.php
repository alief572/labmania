<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class CodeGenerator
{
    /**
     * Kode berikutnya "PREFIX-NNN". Panggil di dalam DB::transaction: baris berprefix yang sama dikunci
     * (lockForUpdate) sehingga dua permintaan bersamaan tidak mendapat nomor sama; unique index tetap jaring pengaman.
     */
    public static function next(string $table, string $prefix, string $field = 'kode', int $digits = 3): string
    {
        $max = 0;
        $codes = DB::table($table)->where($field, 'like', $prefix.'-%')->lockForUpdate()->pluck($field);
        foreach ($codes as $code) {
            if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return sprintf('%s-%0'.$digits.'d', $prefix, $max + 1);
    }
}

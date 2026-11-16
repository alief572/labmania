<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Coa
{
    public static function query(): Builder
    {
        return DB::connection(config('labmania.coa_connection'))->table('coa_master')->where('level', '5');
    }
}

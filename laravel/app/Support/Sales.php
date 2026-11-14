<?php

namespace App\Support;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Sales
{
    public static function display(object|array $row): string
    {
        $r = (array) $row;
        $suffix = ! empty($r['revisi']) ? '-rev'.$r['revisi'] : '';

        return $suffix && ! str_ends_with($r['nomor'], $suffix) ? $r['nomor'].$suffix : $r['nomor'];
    }

    public static function transaction(Closure $work): mixed
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction($work, 5);
            } catch (QueryException $e) {
                if ($attempt === 4 || ! in_array($e->errorInfo[1] ?? null, [1062, 19], true)) {
                    throw $e;
                }
            }
        }
    }

    public static function number(string $table, string $prefix): string
    {
        return CodeGenerator::next($table, $prefix, 'nomor');
    }

    public static function program(int $id, bool $lock = false): object
    {
        return DB::table('penjualan_program')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first() ?? abort(404);
    }

    public static function open(object $program): void
    {
        if ($program->closed_date !== null) {
            throw ValidationException::withMessages(['program' => 'Program sudah Closed; perubahan ditolak.']);
        }
    }

    public static function costs(int $id): array
    {
        return DB::table('penjualan_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')
            ->where('id_penjualan', $id)->select('b.*', 's.nama as nama_satuan')->get()->map(fn ($b) => (array) $b)->all();
    }

    public static function title(int $master): string
    {
        return DB::table('master_program_produk as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')
            ->leftJoin('ms_varian_pelaksanaan as v', 'v.id', '=', 'p.id_varian_pelaksanaan')
            ->where('m.id_master_program', $master)->orderBy('m.urutan')->get(['p.judul', 'v.nama as varian'])
            ->map(fn ($p) => $p->judul.($p->varian ? ' · '.$p->varian : ''))->implode(' + ');
    }

    public static function filters($request): array
    {
        return $request->validate(['q' => 'nullable|string|max:100', 'jenis' => 'nullable|in:public,b2b', 'status' => 'nullable|string|max:20',
            'tahun' => 'nullable|integer|min:1900|max:2200', 'bulan' => 'nullable|integer|min:1|max:12', 'tab' => 'nullable|in:ongoing,selesai,data']);
    }

    public static function filter($query, array $filters, string $alias = 'p'): void
    {
        if (! empty($filters['jenis'])) {
            $query->where($alias.'.jenis', $filters['jenis']);
        }
        if (! empty($filters['tahun'])) {
            $query->whereYear($alias.'.tgl_mulai', $filters['tahun']);
        }
        if (! empty($filters['bulan'])) {
            $query->whereMonth($alias.'.tgl_mulai', $filters['bulan']);
        }
    }

    public static function linkState(object $q, object $p): ?string
    {
        if ($p->closed_date) {
            return 'Program sudah Closed.';
        }
        if (! in_array($q->status, ['draft', 'terkirim'], true)) {
            return 'Quotation sudah '.ucfirst($q->status).'.';
        }
        if (Carbon::today()->gt(Carbon::parse($p->tgl_mulai)->subDays(14))) {
            return 'Batas H-14 sebelum acara sudah lewat.';
        }
        if (Carbon::today()->gt(Carbon::parse($q->berlaku_sampai))) {
            return 'Masa berlaku quotation berakhir.';
        }
        if ($p->jenis === 'b2b') {
            return DB::table('quotation_konfirmasi_company')->where('id_quotation', $q->id)->where('revisi', $q->revisi)->exists() ? 'Daftar peserta sudah dikirim PIC company.' : null;
        }

        return DB::table('quotation_peserta')->where('id_quotation', $q->id)->whereIn('status', ['menunggu', 'deal'])->count() >= $q->kuota_peserta ? 'Kuota peserta sudah terisi.' : null;
    }
}

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

    /**
     * Master Program dalam satu Penjualan Program (PJ-FR-32), urut tampil. Tiap baris memuat jadwal, tim, harga, dan biayanya sendiri.
     * Penjualan tanpa baris master (data sebelum migrasi) dibaca dari header sebagai satu Master Program.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function masters(object $program): array
    {
        $rows = DB::table('penjualan_program_master')->where('id_penjualan', $program->id)->orderBy('urutan')->get();
        if ($rows->isEmpty()) {
            return [self::headerAsMaster($program)];
        }
        $costs = DB::table('penjualan_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')->where('b.id_penjualan', $program->id)
            ->select('b.*', 's.nama as nama_satuan')->orderBy('b.id')->get()->groupBy('id_penjualan_master');

        return $rows->map(fn ($m) => (array) $m + ['biaya' => ($costs->get($m->id, collect()))->map(fn ($b) => (array) $b)->all()])->values()->all();
    }

    private static function headerAsMaster(object $p): array
    {
        return ['id' => null, 'id_penjualan' => $p->id, 'id_master_program' => $p->id_master_program, 'urutan' => 1, 'tgl_mulai' => $p->tgl_mulai, 'tgl_selesai' => $p->tgl_selesai,
            'durasi_hari' => $p->durasi_hari, 'jam_pembelajaran' => $p->jam_pembelajaran, 'id_trainer' => $p->id_trainer, 'id_asisten' => $p->id_asisten,
            'harga_jual_peserta' => $p->harga_jual_peserta, 'target_profit' => $p->target_profit ?? 0, 'biaya' => self::costs($p->id)];
    }

    /**
     * Nilai program dari Master Program-nya (PJ-FR-32): tanggal = mulai paling awal s.d. selesai paling akhir, Jam Pembelajaran dan harga jual = jumlah,
     * trainer dan asisten program = milik Master Program pertama (dipakai tampilan lama), durasi = rentang tanggal.
     *
     * @param  array<int, array<string, mixed>>  $masters
     */
    public static function aggregate(array $masters): array
    {
        $start = array_values(array_filter(array_column($masters, 'tgl_mulai')));
        $end = array_values(array_filter(array_column($masters, 'tgl_selesai')));
        sort($start);
        sort($end);
        $from = $start[0] ?? null;
        $to = $end ? $end[count($end) - 1] : null;
        $sum = fn (string $key) => array_sum(array_map(fn ($m) => (float) ($m[$key] ?? 0), $masters));
        $first = $masters[0];

        return ['tgl_mulai' => $from, 'tgl_selesai' => $to,
            'durasi_hari' => $from && $to && $to >= $from ? (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1 : (int) array_sum(array_column($masters, 'durasi_hari')),
            'jam_pembelajaran' => (int) $sum('jam_pembelajaran') ?: null, 'id_trainer' => $first['id_trainer'], 'id_asisten' => $first['id_asisten'],
            'harga_jual_peserta' => $sum('harga_jual_peserta'), 'target_profit' => $sum('target_profit'), 'id_master_program' => $first['id_master_program']];
    }

    /**
     * Costing seluruh program (PJ-FR-32): biaya per hari memakai durasi Master Program masing-masing, bukan rentang program; sisanya dijumlah.
     * Satu Master Program menghasilkan angka yang sama dengan Costing::calculate biasa.
     */
    public static function costing(array $masters, int|string $target): array
    {
        $fixed = 0.0;
        $variable = 0.0;
        foreach ($masters as $m) {
            $part = Costing::calculate($m['durasi_hari'], 1, 0, $m['biaya']);
            $fixed += $part['total_fixed'];
            $variable += $part['var_per_pax'];
        }
        $target = (int) $target;
        $price = array_sum(array_map(fn ($m) => (float) ($m['harga_jual_peserta'] ?? 0), $masters));
        $totalVar = $variable * $target;
        $hpp = $fixed + $totalVar;
        $income = $price * $target;
        $contribution = $price - $variable;

        return ['total_fixed' => $fixed, 'var_per_pax' => $variable, 'total_var' => $totalVar, 'total_hpp' => $hpp, 'hpp_per_pax' => $target > 0 ? $hpp / $target : 0,
            'pendapatan' => $income, 'profit' => $income - $hpp, 'margin' => $income > 0 ? round((($income - $hpp) / $income) * 100, 1) : 0,
            'bep_peserta' => $contribution > 0 ? max(1, (int) ceil(round($fixed / $contribution, 6))) : null];
    }

    public static function costs(int $id): array
    {
        return DB::table('penjualan_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')
            ->where('id_penjualan', $id)->select('b.*', 's.nama as nama_satuan')->get()->map(fn ($b) => (array) $b)->all();
    }

    public static function titles(array $ids): array
    {
        return DB::table('master_program_produk as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')
            ->leftJoin('ms_varian_pelaksanaan as v', 'v.id', '=', 'p.id_varian_pelaksanaan')
            ->whereIn('m.id_master_program', $ids)->orderBy('m.urutan')->get(['m.id_master_program', 'p.judul', 'v.nama as varian'])
            ->groupBy('id_master_program')->map(fn ($rows) => $rows->map(fn ($p) => $p->judul.($p->varian ? ' · '.$p->varian : ''))->implode(' + '))->all();
    }

    public static function title(int $master): string
    {
        return self::titles([$master])[$master] ?? '';
    }

    public static function costsFor(array $ids)
    {
        return DB::table('penjualan_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')
            ->whereIn('b.id_penjualan', $ids)->select('b.*', 's.nama as nama_satuan')->get()->groupBy('id_penjualan');
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

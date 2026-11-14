<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class Expenses
{
    public static function role(string $name): bool
    {
        return Auth::user()?->active() && DB::table('user_groups as u')->join('groups as g', 'g.id_group', '=', 'u.id_group')
            ->where('u.id_user', Auth::id())->where('g.nm_group', $name)->where('g.id_group', '<>', 1)->exists();
    }

    public static function team($query, string $alias = 'p'): void
    {
        $query->where(function ($w) use ($alias) {
            $w->whereExists(fn ($q) => $q->selectRaw('1')->from('quotation as tq')->join('ms_user_karyawan as u', 'u.id', '=', 'tq.id_sales')
                ->whereColumn('tq.id_penjualan', $alias.'.id')->where('tq.status', 'deal')->where('u.id_user', Auth::id()))
                ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('ms_trainer as t')->whereColumn('t.id', $alias.'.id_trainer')->where('t.id_user', Auth::id()));
        });
    }

    public static function scope($query, string $alias = 'p'): void
    {
        if (! Permissions::superadmin(Auth::user()) && ! self::role('Admin') && ! self::role('Direktur')) {
            self::team($query, $alias);
        }
    }

    public static function access(int $id, string $action = 'View', bool $approval = false): object
    {
        Gate::authorize('menu', [$approval ? 'approval_pengajuan_biaya' : 'pengajuan_biaya', $action]);
        if ($approval && $action === 'Approve') {
            abort_unless(Permissions::superadmin(Auth::user()) || self::role('Direktur'), 403);
        }
        $q = DB::table('penjualan_program as p')->where('p.id', $id)->where('p.status', 'final');
        self::scope($q);
        abort_unless($q->exists(), 403);

        return Sales::program($id);
    }

    public static function money(string|int|float|BigDecimal $v): string
    {
        return (string) BigDecimal::of((string) $v)->toScale(2, RoundingMode::HalfUp);
    }

    public static function budget(int $id, string $category): array
    {
        $p = Sales::program($id);
        $items = [];
        foreach (Sales::costs($id) as $b) {
            if ($b['tipe'] !== $category) {
                continue;
            }
            $qty = match ($b['nama_satuan']) {
                'Day' => $p->durasi_hari, 'Peserta/Event' => $p->target_peserta,
                'Peserta/Day' => $p->target_peserta * $p->durasi_hari, default => 1,
            };
            $items['c'.$b['id']] = ['key' => 'c'.$b['id'], 'id_penjualan_biaya' => $b['id'], 'id_overbudget_detail' => null,
                'item' => $b['item'], 'qty' => self::money($qty), 'harga' => self::money($b['harga']),
                'estimasi' => self::money(BigDecimal::of((string) $b['harga'])->multipliedBy($qty)), 'overbudget' => '0.00', 'qty_overbudget' => '0.00', 'terpakai' => '0.00', 'qty_terpakai' => '0.00'];
        }
        $extra = DB::table('pengajuan_overbudget_detail as d')->join('pengajuan_overbudget as h', 'h.id', '=', 'd.id_overbudget')
            ->where('h.id_penjualan', $id)->where('h.tipe_biaya', $category)->where('h.status', 'disetujui')->get(['d.*']);
        foreach ($extra as $d) {
            $key = $d->id_penjualan_biaya ? 'c'.$d->id_penjualan_biaya : 'o'.$d->id;
            if (! isset($items[$key])) {
                $items[$key] = ['key' => $key, 'id_penjualan_biaya' => null, 'id_overbudget_detail' => $d->id, 'item' => $d->item,
                    'qty' => '0.00', 'harga' => self::money($d->harga), 'estimasi' => '0.00', 'overbudget' => '0.00', 'qty_overbudget' => '0.00', 'terpakai' => '0.00', 'qty_terpakai' => '0.00'];
            }
            $items[$key]['overbudget'] = self::money(BigDecimal::of($items[$key]['overbudget'])->plus((string) $d->total));
            $items[$key]['qty_overbudget'] = self::money(BigDecimal::of($items[$key]['qty_overbudget'])->plus((string) $d->qty));
        }
        $used = DB::table('pengajuan_biaya_detail as d')->join('pengajuan_biaya as h', 'h.id', '=', 'd.id_pengajuan')
            ->where('h.id_penjualan', $id)->where('h.tipe_biaya', $category)->whereIn('h.status', ['menunggu', 'disetujui', 'paid'])->get(['d.*']);
        foreach ($used as $d) {
            $key = $d->id_penjualan_biaya ? 'c'.$d->id_penjualan_biaya : 'o'.$d->id_overbudget_detail;
            if (isset($items[$key])) {
                $items[$key]['terpakai'] = self::money(BigDecimal::of($items[$key]['terpakai'])->plus((string) $d->total));
                $items[$key]['qty_terpakai'] = self::money(BigDecimal::of($items[$key]['qty_terpakai'])->plus((string) $d->qty));
            }
        }
        $budget = BigDecimal::zero();
        $spent = BigDecimal::zero();
        foreach ($items as &$i) {
            $i['budget'] = self::money(BigDecimal::of($i['estimasi'])->plus($i['overbudget']));
            $i['sisa'] = self::money(BigDecimal::of($i['budget'])->minus($i['terpakai']));
            $budget = $budget->plus($i['budget']);
            $spent = $spent->plus($i['terpakai']);
        }

        return ['items' => array_values($items), 'budget' => self::money($budget), 'aktual' => self::money($spent), 'sisa' => self::money($budget->minus($spent))];
    }

    public static function save(int $program, array $input, bool $overbudget, array $files = []): int
    {
        return Sales::transaction(function () use ($program, $input, $overbudget, $files) {
            $p = Sales::program($program, true);
            self::access($program, 'Add');
            Sales::open($p);
            $available = collect(self::budget($program, $input['tipe_biaya'])['items'])->keyBy('key');
            $details = [];
            $sum = BigDecimal::zero();
            foreach ($input['items'] as $i) {
                if (BigDecimal::of((string) $i['qty'])->isZero()) {
                    continue;
                }
                $source = $available->get($i['key'] ?? '');
                if (! $source && (! $overbudget || ! empty($i['key']) || trim($i['item'] ?? '') === '')) {
                    throw ValidationException::withMessages(['items' => 'Item tidak tersedia pada kategori program ini.']);
                }
                $total = self::money(BigDecimal::of((string) $i['qty'])->multipliedBy((string) $i['harga']));
                if (BigDecimal::of($total)->isLessThanOrEqualTo(0)) {
                    throw ValidationException::withMessages(['items' => 'Total item harus positif.']);
                }
                if ($overbudget && $source && ! $source['id_penjualan_biaya']) {
                    throw ValidationException::withMessages(['items' => 'Tambahan item baru diajukan sebagai baris baru Overbudget.']);
                }
                $d = ['id_penjualan_biaya' => $source['id_penjualan_biaya'] ?? null, 'item' => $source['item'] ?? $i['item'], 'qty' => $i['qty'], 'harga' => $i['harga'], 'total' => $total];
                if (! $overbudget) {
                    $d['id_overbudget_detail'] = $source['id_overbudget_detail'];
                }
                $details[] = $d;
                $sum = $sum->plus($total);
            }
            if ($sum->isLessThanOrEqualTo(0) || $sum->isGreaterThan('9999999999999.99')) {
                throw ValidationException::withMessages(['items' => 'Total pengajuan tidak valid.']);
            }
            if (! $overbudget && $sum->isGreaterThan(self::budget($program, $input['tipe_biaya'])['sisa'])) {
                throw ValidationException::withMessages(['items' => 'Sisa kategori tidak mencukupi. Ajukan Overbudget.']);
            }
            $table = $overbudget ? 'pengajuan_overbudget' : 'pengajuan_biaya';
            $code = $overbudget ? 'OB' : ($input['jenis'] === 'kasbon' ? 'KS' : 'DP');
            $data = ['nomor' => Sales::number($table, 'LMI/'.$code.'/'.now()->format('m/y')), 'id_penjualan' => $program,
                'tipe_biaya' => $input['tipe_biaya'], 'tanggal' => today()->toDateString(), 'total' => (string) $sum, 'status' => 'menunggu', 'request_by' => Auth::id(),
                $overbudget ? 'alasan' : 'deskripsi' => $input[$overbudget ? 'alasan' : 'deskripsi'], ...Invoice::audit()];
            if (! $overbudget) {
                $data += array_intersect_key($input, array_flip(['jenis', 'bank', 'no_rekening', 'nama_rekening']));
            }
            $id = DB::table($table)->insertGetId($data);
            foreach ($details as $d) {
                DB::table($table.'_detail')->insert([$overbudget ? 'id_overbudget' : 'id_pengajuan' => $id, ...$d, ...Invoice::audit()]);
            }
            foreach ($files as $f) {
                DB::table('pengajuan_biaya_lampiran')->insert(['id_pengajuan' => $id, 'nama_file' => $f['name'], 'file_path' => $f['path'], ...Invoice::audit()]);
            }
            unset($data['no_rekening']);
            write_log('Pengajuan Biaya', 'Ajukan', 'Ajukan '.$data['nomor'], ['new' => $data, 'detail_changes' => ['Item' => ['old' => [], 'new' => $details]]], $table, $id, $data['nomor']);

            return $id;
        });
    }

    public static function transition(int $id, bool $overbudget, string $action, array $input = [], array $files = []): void
    {
        Sales::transaction(function () use ($id, $overbudget, $action, $input, $files) {
            $table = $overbudget ? 'pengajuan_overbudget' : 'pengajuan_biaya';
            $read = DB::table($table)->where('id', $id)->first() ?? abort(404);
            $approval = in_array($action, ['setujui', 'tolak'], true);
            Sales::program($read->id_penjualan, true);
            self::access($read->id_penjualan, $approval ? 'Approve' : ($action === 'paid' ? 'Manage' : 'Add'), $approval);
            $old = DB::table($table)->where('id', $id)->lockForUpdate()->first();
            if ($old->status !== ($action === 'paid' ? 'disetujui' : 'menunggu') || ($overbudget && $action === 'paid')) {
                throw ValidationException::withMessages(['pengajuan' => 'Status pengajuan tidak dapat diproses ulang.']);
            }
            if ($approval) {
                abort_if((int) $old->request_by === (int) Auth::id(), 403, 'Tidak boleh memproses pengajuan sendiri.');
            }
            if ($action === 'batal') {
                abort_unless((int) $old->request_by === (int) Auth::id(), 403);
            }
            $new = match ($action) {
                'setujui' => ['status' => 'disetujui', 'approved_by' => Auth::id(), 'approved_date' => now()],
                'tolak' => ['status' => 'ditolak', 'approved_by' => Auth::id(), 'approved_date' => now(), 'alasan_tolak' => $input['alasan_tolak']],
                'batal' => ['status' => 'batal', 'closed_by' => Auth::id(), 'closed_date' => now()],
                'paid' => ['status' => 'paid', 'paid_by' => Auth::id(), 'paid_date' => $input['paid_date'], 'paid_keterangan' => $input['paid_keterangan'] ?? null],
            };
            if ($action === 'paid' && ($input['paid_date'] < $old->tanggal || $input['paid_date'] > today()->toDateString())) {
                throw ValidationException::withMessages(['paid_date' => 'Tanggal bayar di luar rentang pengajuan sampai hari ini.']);
            }
            DB::table($table)->where('id', $id)->update($new + ['updated_by' => Auth::id(), 'updated_date' => now()]);
            foreach ($files as $f) {
                DB::table('pengajuan_biaya_lampiran')->insert(['id_pengajuan' => $id, 'nama_file' => $f['name'], 'file_path' => $f['path'], ...Invoice::audit()]);
            }
            $safe = (array) $old;
            unset($safe['no_rekening']);
            write_log('Pengajuan Biaya', $action === 'paid' ? 'Tandai Paid' : ucfirst($action), ucfirst($action).' '.$old->nomor, ['old' => $safe, 'new' => array_merge($safe, $new)], $table, $id, $old->nomor);
        });
    }
}

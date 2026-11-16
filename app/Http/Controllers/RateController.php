<?php

namespace App\Http\Controllers;

use App\Support\Coa;
use App\Support\CodeGenerator;
use App\Support\ServerDataTable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Master Rate: tab Fixed Cost dan Variable Cost (FR-18..20, FR-25, FR-34..35). Rate tidak dihapus, hanya dinonaktifkan. */
class RateController extends Controller
{
    private const MENU = 'master_data.rate';

    /** Satuan yang boleh per tab (kode ms_satuan) dan kategorinya. */
    private const SATUAN = ['fixed' => ['category' => 'rate_fixed', 'codes' => ['event', 'day']], 'variable' => ['category' => 'rate_variable', 'codes' => ['peserta_event', 'peserta_day']]];

    private const TITLE = ['fixed' => 'Fixed Cost', 'variable' => 'Variable Cost'];

    public static function satuans(string $tipe)
    {
        $cfg = self::SATUAN[$tipe] ?? abort(404);

        return DB::table('ms_satuan')->where('category', $cfg['category'])->whereIn('code', $cfg['codes'])->orderBy('id')->get(['id', 'nama']);
    }

    public function index(Request $request, string $tipe)
    {
        abort_unless(isset(self::TITLE[$tipe]), 404);
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:all,on,off']);
        if ($request->route('datatable')) {
            $base = DB::table('ms_rate as r')->leftJoin('ms_satuan as s', 's.id', '=', 'r.id_satuan')
                ->where('r.tipe', $tipe)
                ->orderBy('r.id')->select('r.*', 's.nama as nama_satuan');
            $query = (clone $base)
                ->when(($f['status'] ?? 'all') === 'on', fn ($q) => $q->where('r.aktif', 1))
                ->when(($f['status'] ?? 'all') === 'off', fn ($q) => $q->where('r.aktif', 0));

            return ServerDataTable::response($request, $base, $query, [null, 'kode', 'item', 'nama_satuan', 'default_cost', 'aktif', null], ['id', 'kode', 'item', 'keterangan', 'id_satuan', 'nama_satuan', 'default_cost', 'coa', 'aktif'], 'id', ['kode', 'item', 'keterangan', 'nama_satuan'], null);
        }

        return Inertia::render('Master/Rate', [
            'tipe' => $tipe, 'title' => self::TITLE[$tipe], 'filters' => ['q' => $f['q'] ?? '', 'status' => $f['status'] ?? 'all'],
            'counts' => DB::table('ms_rate')->selectRaw('tipe, COUNT(*) as n')->groupBy('tipe')->pluck('n', 'tipe')->map(fn ($n) => (int) $n)->all() + ['fixed' => 0, 'variable' => 0],
            'satuans' => self::satuans($tipe),
            'coa' => Coa::query()->selectRaw('no_perkiraan, MAX(nama) as nama')->groupBy('no_perkiraan')->orderBy('no_perkiraan')->get(),
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage'])],
        ]);
    }

    public function store(Request $request, string $tipe)
    {
        abort_unless(isset(self::TITLE[$tipe]), 404);
        Gate::authorize('menu', [self::MENU, 'Add']);
        $data = $this->validated($request, $tipe);
        try {
            $kode = DB::transaction(function () use ($data, $tipe) {
                $kode = CodeGenerator::next('ms_rate', config('labmania.code_prefix.rate_'.$tipe));
                $row = ['kode' => $kode, 'tipe' => $tipe] + $data + ['aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
                $id = DB::table('ms_rate')->insertGetId($row);
                write_log('Master Rate', 'Tambah', 'Tambah Rate '.$kode, $row, 'ms_rate', $id, $kode);

                return $kode;
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['item' => 'Kode bentrok dengan simpanan lain. Ulangi.']);
        }

        return back()->with('success', self::TITLE[$tipe].' '.$kode.' ditambahkan.');
    }

    /** Kategori (tipe) tidak berubah setelah tersimpan; satuan divalidasi terhadap tipe tersimpan, bukan kiriman klien. */
    public function update(Request $request, string $tipe, int $id)
    {
        abort_unless(isset(self::TITLE[$tipe]), 404);
        Gate::authorize('menu', [self::MENU, 'Manage']);
        DB::transaction(function () use ($request, $tipe, $id) {
            $old = (array) (DB::table('ms_rate')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
            abort_if($old['tipe'] !== $tipe, 404);
            $data = $this->validated($request, $tipe);
            $data += ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('ms_rate')->where('id', $id)->update($data);
            write_log('Master Rate', 'Edit', 'Update Rate '.$old['kode'], ['old' => $old, 'new' => array_merge($old, $data)], 'ms_rate', $id, $old['kode']);
        });

        return back()->with('success', 'Rate diperbarui.');
    }

    public function toggle(string $tipe, int $id)
    {
        abort_unless(isset(self::TITLE[$tipe]), 404);
        Gate::authorize('menu', [self::MENU, 'Manage']);
        DB::transaction(function () use ($tipe, $id) {
            $old = (array) (DB::table('ms_rate')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
            abort_if($old['tipe'] !== $tipe, 404);
            $aktif = $old['aktif'] ? 0 : 1;
            DB::table('ms_rate')->where('id', $id)->update(['aktif' => $aktif, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            $action = $aktif ? 'Aktifkan' : 'Nonaktifkan';
            write_log('Master Rate', $action, $action.' Rate '.$old['kode'], ['old' => $old, 'new' => array_merge($old, ['aktif' => $aktif])], 'ms_rate', $id, $old['kode']);
        });

        return back()->with('success', 'Status Rate diperbarui.');
    }

    private function validated(Request $request, string $tipe): array
    {
        $in = $request->validate([
            'item' => 'required|string|max:150', 'id_satuan' => 'required|integer', 'default_cost' => ['required', 'regex:/^\d{1,13}$/'],
            'keterangan' => 'nullable|string|max:150', 'coa' => 'required|string|max:50',
        ], ['item.required' => 'Item wajib diisi.', 'default_cost.required' => 'Default Cost wajib diisi.', 'default_cost.regex' => 'Default Cost berupa angka rupiah ≥ 0 tanpa desimal.', 'coa.required' => 'COA wajib dipilih.']);
        if (! self::satuans($tipe)->contains('id', (int) $in['id_satuan'])) {
            throw ValidationException::withMessages(['id_satuan' => 'Satuan tidak sesuai dengan kategori '.self::TITLE[$tipe].'.']);
        }
        if (! Coa::query()->where('no_perkiraan', $in['coa'])->exists()) {
            throw ValidationException::withMessages(['coa' => 'Pilih COA level 5 dari master GL.']);
        }

        return ['item' => trim($in['item']), 'id_satuan' => (int) $in['id_satuan'], 'default_cost' => (float) $in['default_cost'], 'keterangan' => trim((string) ($in['keterangan'] ?? '')), 'coa' => $in['coa']];
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\CodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan (FR-03..09, FR-21). */
class MasterAtributController extends Controller
{
    private function cfg(string $slug): array
    {
        return config("labmania.atribut.$slug") ?? abort(404);
    }

    public function index(Request $request, string $atribut)
    {
        $cfg = $this->cfg($atribut);
        Gate::authorize('menu', [$cfg['menu'], 'View']);
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:all,on,off']);
        $rows = DB::table($cfg['table'])
            ->when(($filters['status'] ?? 'all') === 'on', fn ($q) => $q->where('aktif', 1))
            ->when(($filters['status'] ?? 'all') === 'off', fn ($q) => $q->where('aktif', 0))
            ->when(($filters['q'] ?? null) !== null, fn ($q) => $q->where(fn ($w) => $w->where('kode', 'like', '%'.$filters['q'].'%')->orWhere('nama', 'like', '%'.$filters['q'].'%')))
            ->orderBy('id')->paginate(25)->withQueryString();

        return Inertia::render('Master/Atribut', [
            'slug' => $atribut, 'label' => $cfg['label'], 'rows' => $rows, 'filters' => ['q' => $filters['q'] ?? '', 'status' => $filters['status'] ?? 'all'],
            'can' => ['add' => Gate::allows('menu', [$cfg['menu'], 'Add']), 'manage' => Gate::allows('menu', [$cfg['menu'], 'Manage'])],
        ]);
    }

    public function store(Request $request, string $atribut)
    {
        $cfg = $this->cfg($atribut);
        Gate::authorize('menu', [$cfg['menu'], 'Add']);
        $nama = $this->nama($request, $cfg);
        try {
            $kode = DB::transaction(function () use ($cfg, $nama) {
                $this->assertUnique($cfg, $nama, null);
                $kode = CodeGenerator::next($cfg['table'], $cfg['prefix']);
                $data = ['kode' => $kode, 'nama' => $nama, 'aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
                $id = DB::table($cfg['table'])->insertGetId($data);
                write_log($cfg['label'], 'Tambah', 'Tambah '.$cfg['label'].' '.$kode, $data, $cfg['table'], $id, $kode);

                return $kode;
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['nama' => 'Nama atau kode bentrok dengan simpanan lain. Ulangi.']);
        }

        return back()->with('success', $cfg['label'].' '.$kode.' ditambahkan.');
    }

    public function update(Request $request, string $atribut, int $id)
    {
        $cfg = $this->cfg($atribut);
        Gate::authorize('menu', [$cfg['menu'], 'Manage']);
        $nama = $this->nama($request, $cfg);
        try {
            DB::transaction(function () use ($cfg, $id, $nama) {
                $old = (array) (DB::table($cfg['table'])->where('id', $id)->lockForUpdate()->first() ?? abort(404));
                $this->assertUnique($cfg, $nama, $id);
                $data = ['nama' => $nama, 'updated_by' => Auth::id(), 'updated_date' => now()];
                DB::table($cfg['table'])->where('id', $id)->update($data);
                write_log($cfg['label'], 'Edit', 'Update '.$cfg['label'].' '.$old['kode'], ['old' => $old, 'new' => array_merge($old, $data)], $cfg['table'], $id, $old['kode']);
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['nama' => 'Nama sudah dipakai.']);
        }

        return back()->with('success', $cfg['label'].' diperbarui.');
    }

    public function toggle(string $atribut, int $id)
    {
        $cfg = $this->cfg($atribut);
        Gate::authorize('menu', [$cfg['menu'], 'Manage']);
        DB::transaction(function () use ($cfg, $id) {
            $old = (array) (DB::table($cfg['table'])->where('id', $id)->lockForUpdate()->first() ?? abort(404));
            $aktif = $old['aktif'] ? 0 : 1;
            DB::table($cfg['table'])->where('id', $id)->update(['aktif' => $aktif, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            $action = $aktif ? 'Aktifkan' : 'Nonaktifkan';
            write_log($cfg['label'], $action, $action.' '.$cfg['label'].' '.$old['kode'], ['old' => $old, 'new' => array_merge($old, ['aktif' => $aktif])], $cfg['table'], $id, $old['kode']);
        });

        return back()->with('success', 'Status diperbarui.');
    }

    private function nama(Request $request, array $cfg): string
    {
        return trim($request->validate(['nama' => 'required|string|max:100'], ['nama.required' => 'Nama '.$cfg['label'].' wajib diisi.'])['nama']);
    }

    private function assertUnique(array $cfg, string $nama, ?int $id): void
    {
        $dup = DB::table($cfg['table'])->where('nama', $nama)->when($id, fn ($q) => $q->where('id', '!=', $id))->lockForUpdate()->exists();
        if ($dup) {
            throw ValidationException::withMessages(['nama' => $cfg['label'].' "'.$nama.'" sudah ada.']);
        }
    }
}

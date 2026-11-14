<?php

namespace App\Http\Controllers;

use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Master Role = tabel groups (FR-64). Hak per menu diatur di halaman Role & Permission (fase 1). Superadmin (id 1) terkunci. */
class RoleController extends Controller
{
    private const MENU = 'permission_group';

    private const ADMIN = 1;

    public function index()
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $rows = DB::table('groups as g')->orderBy('g.id_group')
            ->select('g.*', DB::raw('(SELECT COUNT(*) FROM user_groups ug WHERE ug.id_group = g.id_group) AS n_user'), DB::raw('(SELECT COUNT(*) FROM ms_group_akses a WHERE a.id_group = g.id_group) AS n_hak'))
            ->paginate(50);

        return Inertia::render('Master/Role', ['rows' => $rows, 'admin' => self::ADMIN,
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage']), 'delete' => Gate::allows('menu', [self::MENU, 'Delete'])]]);
    }

    private function data(Request $request, ?int $id): array
    {
        $in = $request->validate(['nama' => 'required|string|max:50', 'ket' => 'nullable|string|max:100'], ['nama.required' => 'Nama Role wajib diisi.', 'nama.max' => 'Nama Role maksimal 50 karakter.', 'ket.max' => 'Keterangan maksimal 100 karakter.']);
        $nama = trim($in['nama']);
        if (! Permissions::superadmin(Auth::user()) && (in_array(mb_strtolower($nama), ['admin', 'direktur'], true)
            || ($id && DB::table('groups')->where('id_group', $id)->whereIn('nm_group', ['Admin', 'Direktur'])->exists()))) {
            abort(403, 'Role dengan akses data seluruh program hanya dikelola Superadmin.');
        }
        // Role 1 legacy bernama admin tetap terpisah dari Admin bisnis fase 6.
        if (DB::table('groups')->whereRaw('LOWER(nm_group) = ?', [mb_strtolower($nama)])
            ->when(mb_strtolower($nama) === 'admin', fn ($q) => $q->where('id_group', $id === self::ADMIN ? '=' : '<>', self::ADMIN))
            ->when($id, fn ($q) => $q->where('id_group', '!=', $id))->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['nama' => 'Nama Role sudah dipakai.']);
        }

        return [$nama, trim((string) ($in['ket'] ?? ''))];
    }

    /** Aktor non-Superadmin tidak boleh menyentuh role yang ia miliki sendiri (aturan delegasi fase 1). */
    private function guardOwn(int $id): void
    {
        $actor = Auth::user();
        if (! Permissions::superadmin($actor) && DB::table('user_groups')->where('id_user', $actor->id_user)->where('id_group', $id)->exists()) {
            abort(403);
        }
    }

    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $nama = DB::transaction(function () use ($request) {
            [$nama, $ket] = $this->data($request, null);
            $row = ['nm_group' => $nama, 'ket' => $ket, 'st_default' => 0, 'created_by' => Auth::id(), 'created_date' => now()];
            $id = DB::table('groups')->insertGetId($row, 'id_group');
            write_log('Permission Group', 'Tambah', 'Tambah Permission Group '.$nama, $row, 'groups', $id, $nama);

            return $nama;
        });

        return back()->with('success', 'Role '.$nama.' ditambahkan. Atur hak di Role & Permission.');
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $this->guardOwn($id);
        DB::transaction(function () use ($request, $id) {
            $old = (array) (DB::table('groups')->where('id_group', $id)->lockForUpdate()->first() ?? abort(404));
            [$nama, $ket] = $this->data($request, $id);
            if ($id === self::ADMIN && $nama !== $old['nm_group']) {
                throw ValidationException::withMessages(['nama' => 'Nama role Superadmin tidak dapat diubah.']);
            }
            $data = ['nm_group' => $nama, 'ket' => $ket, 'updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('groups')->where('id_group', $id)->update($data);
            write_log('Permission Group', 'Edit', 'Update Permission Group '.$old['nm_group'], ['old' => $old, 'new' => array_merge($old, $data)], 'groups', $id, $old['nm_group']);
        });

        return back()->with('success', 'Role diperbarui.');
    }

    public function destroy(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Delete']);
        $this->guardOwn($id);
        DB::transaction(function () use ($id) {
            $old = (array) (DB::table('groups')->where('id_group', $id)->lockForUpdate()->first() ?? abort(404));
            if ($id === self::ADMIN || ! empty($old['st_default'])) {
                throw ValidationException::withMessages(['hapus' => 'Role '.$old['nm_group'].' adalah role sistem dan tidak dapat dihapus.']);
            }
            $n = DB::table('user_groups')->where('id_group', $id)->count();
            if ($n > 0) {
                throw ValidationException::withMessages(['hapus' => 'Role '.$old['nm_group'].' masih dipakai '.$n.' user. Pindahkan user lebih dulu.']);
            }
            DB::table('ms_group_akses')->where('id_group', $id)->delete();
            DB::table('groups')->where('id_group', $id)->delete();
            write_log('Permission Group', 'Hapus', 'Hapus Permission Group '.$old['nm_group'], $old, 'groups', $id, $old['nm_group']);
        });

        return back()->with('success', 'Role dihapus.');
    }
}

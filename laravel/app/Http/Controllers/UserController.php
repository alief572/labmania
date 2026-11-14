<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CodeGenerator;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Master User = ms_user_karyawan + akun login (users) + role (user_groups) (FR-36..39, FR-65).
 * Override Permission tetap di halaman fase 1; Superadmin (role 1) terkunci. Password tidak pernah masuk log.
 */
class UserController extends Controller
{
    private const MENU = 'master_data.users';

    private const SUPERADMIN = 1;

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $request->validate(['q' => 'nullable|string|max:100', 'role' => 'nullable|integer', 'status' => 'nullable|in:all,on,off']);
        $rows = DB::table('ms_user_karyawan as e')
            ->leftJoin('users as u', fn ($j) => $j->on('u.id_user', '=', 'e.id_user')->whereRaw('IFNULL(u.deleted, 0) = 0'))
            ->leftJoin('groups as g', 'g.id_group', '=', 'e.id_group')
            ->when(($f['status'] ?? 'all') === 'on', fn ($q) => $q->where('e.aktif', 1))
            ->when(($f['status'] ?? 'all') === 'off', fn ($q) => $q->where('e.aktif', 0))
            ->when(($f['role'] ?? null) !== null, fn ($q) => $q->where('e.id_group', (int) $f['role']))
            ->when(($f['q'] ?? null) !== null, fn ($q) => $q->where(fn ($w) => $w->where('e.kode', 'like', '%'.$f['q'].'%')->orWhere('e.nama', 'like', '%'.$f['q'].'%')->orWhere('u.username', 'like', '%'.$f['q'].'%')))
            ->orderBy('e.id')->select('e.*', 'u.username', 'g.nm_group', DB::raw('(SELECT COUNT(*) FROM ms_user_akses o WHERE o.id_user = e.id_user) AS n_override'),
                DB::raw("(u.ga_secret IS NOT NULL AND u.ga_secret <> '') AS has_2fa"))
            ->paginate(25)->withQueryString();
        $actor = $request->user();

        return Inertia::render('Master/User', [
            'rows' => $rows, 'filters' => ['q' => $f['q'] ?? '', 'role' => $f['role'] ?? '', 'status' => $f['status'] ?? 'all'],
            'roles' => DB::table('groups')->orderBy('nm_group')->get(['id_group', 'nm_group']), 'superadmin' => self::SUPERADMIN,
            'options' => config('labmania.user_options'), 'me' => $actor->id_user,
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage'])],
        ]);
    }

    private function data(Request $request, ?object $old, ?int $accountId): array
    {
        $o = config('labmania.user_options');
        $request->merge(['telp' => preg_replace('/[\s-]/', '', (string) $request->input('telp'))]);
        $in = $request->validate([
            'nama' => 'required|string|max:100', 'id_group' => 'required|integer', 'nik' => ['nullable', 'regex:/^\d{16}$/'], 'tmp_lahir' => 'nullable|string|max:50',
            'tgl_lahir' => 'nullable|date_format:Y-m-d', 'tgl_masuk' => 'nullable|date_format:Y-m-d|after_or_equal:tgl_lahir', 'gender' => ['nullable', Rule::in($o['gender'])],
            'agama' => ['nullable', Rule::in($o['agama'])], 'tipe' => ['nullable', Rule::in($o['tipe'])], 'telp' => ['nullable', 'regex:/^\+?\d{9,15}$/'],
            'email' => 'nullable|email|max:100', 'alamat' => 'nullable|string|max:255', 'npwp' => 'nullable|string|max:25', 'jobdesc' => 'nullable|string|max:500',
            'akun' => 'sometimes|boolean', 'username' => ['nullable', 'regex:/^[a-z0-9._]{3,45}$/'], 'password' => 'nullable|string|min:8|max:200|confirmed',
        ], [
            'nama.required' => 'Employee Name wajib diisi.', 'id_group.required' => 'Role wajib diisi.', 'nik.regex' => 'NIK 16 digit angka.', 'telp.regex' => 'Nomor telepon hanya angka (boleh diawali +), 9–15 digit.',
            'tgl_masuk.after_or_equal' => 'Join Date sebelum Birth Date.', 'username.regex' => 'Username: huruf kecil, angka, titik, atau garis bawah; 3–45 karakter.',
            'password.min' => 'Password minimal 8 karakter.', 'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);
        if (($in['npwp'] ?? '') !== '' && ! preg_match('/^(\d{15}|\d{16})$/', preg_replace('/[.\-\s]/', '', $in['npwp']))) {
            throw ValidationException::withMessages(['npwp' => 'NPWP 15 atau 16 digit angka.']);
        }
        $group = (int) $in['id_group'];
        $oldGroup = $old ? (int) $old->id_group : 0;
        // Superadmin bawaan sistem: tidak dipilih untuk user lain, dan rolenya tidak dapat diganti.
        if ($oldGroup === self::SUPERADMIN) {
            $group = self::SUPERADMIN;
        } elseif ($group === self::SUPERADMIN) {
            throw ValidationException::withMessages(['id_group' => 'Role Superadmin tidak dapat dipilih.']);
        }
        if (! DB::table('groups')->where('id_group', $group)->exists()) {
            throw ValidationException::withMessages(['id_group' => 'Role tidak ditemukan.']);
        }
        $actor = Auth::user();
        if ($group !== $oldGroup) {
            if ($old && $old->id_user && (int) $old->id_user === $actor->id_user) {
                throw ValidationException::withMessages(['id_group' => 'Anda tidak dapat mengubah role Anda sendiri.']);
            }
            if (! Permissions::coversRole($actor, $group)) {
                throw ValidationException::withMessages(['id_group' => 'Anda tidak dapat memberi role yang haknya melebihi hak Anda.']);
            }
        }
        $withAccount = ! empty($in['akun']) || $accountId !== null;
        if ($withAccount) {
            if (empty($in['username'])) {
                throw ValidationException::withMessages(['username' => 'Username wajib diisi.']);
            }
            $dup = DB::table('users')->whereRaw('LOWER(username) = ?', [mb_strtolower($in['username'])])->when($accountId, fn ($q) => $q->where('id_user', '!=', $accountId))->lockForUpdate()->exists();
            if ($dup) {
                throw ValidationException::withMessages(['username' => 'Username sudah dipakai.']);
            }
            if ($accountId === null && empty($in['password'])) {
                throw ValidationException::withMessages(['password' => 'Password minimal 8 karakter.']);
            }
        }
        $n = fn ($k) => ($in[$k] ?? '') !== '' ? trim((string) $in[$k]) : null;

        return [$in, $group, $withAccount, [
            'nama' => trim($in['nama']), 'nik' => $n('nik'), 'tmp_lahir' => $n('tmp_lahir'), 'tgl_lahir' => $n('tgl_lahir'), 'id_group' => $group, 'gender' => $n('gender'), 'agama' => $n('agama'),
            'telp' => $n('telp'), 'email' => $n('email'), 'alamat' => $n('alamat'), 'npwp' => $n('npwp'), 'tgl_masuk' => $n('tgl_masuk'), 'tipe' => $n('tipe'), 'jobdesc' => $n('jobdesc'),
        ]];
    }

    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $kode = DB::transaction(function () use ($request) {
            [$in, $group, $withAccount, $emp] = $this->data($request, null, null);
            $kode = CodeGenerator::next('ms_user_karyawan', 'USR');
            $row = ['kode' => $kode] + $emp + ['aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
            $id = DB::table('ms_user_karyawan')->insertGetId($row);
            $log = [];
            if ($withAccount) {
                $idUser = $this->saveAccount(null, $in, $emp, 1, $group);
                DB::table('ms_user_karyawan')->where('id', $id)->update(['id_user' => $idUser]);
                $log = ['username' => $in['username'], 'sandi_dibuat' => true];
            }
            write_log('Master User', 'Tambah', 'Tambah User '.$kode, $row + $log, 'ms_user_karyawan', $id, $kode);

            return $kode;
        });

        return back()->with('success', 'User '.$kode.' ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        DB::transaction(function () use ($request, $id) {
            $old = DB::table('ms_user_karyawan')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            $this->guardSuperadminTarget($old);
            [$in, $group, $withAccount, $emp] = $this->data($request, $old, $old->id_user ? (int) $old->id_user : null);
            $emp += ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('ms_user_karyawan')->where('id', $id)->update($emp);
            $log = [];
            if ($withAccount) {
                $idUser = $this->saveAccount($old->id_user ? (int) $old->id_user : null, $in, $emp, (int) $old->aktif, $group);
                if (! $old->id_user) {
                    DB::table('ms_user_karyawan')->where('id', $id)->update(['id_user' => $idUser]);
                }
                $log = ['username' => $in['username'], 'sandi_diganti' => ! empty($in['password'])];
            }
            $oldArr = (array) $old;
            write_log('Master User', 'Edit', 'Update User '.$old->kode, ['old' => $oldArr, 'new' => array_merge($oldArr, $emp, $log)], 'ms_user_karyawan', $id, $old->kode);
        });

        return back()->with('success', 'User diperbarui.');
    }

    /** Sinkron akun login dan role. Panggil di dalam transaksi. Kembalikan id_user. */
    private function saveAccount(?int $idUser, array $in, array $emp, int $aktif, int $group): int
    {
        $account = ['username' => $in['username'], 'nm_lengkap' => $emp['nama'], 'email' => $emp['email'], 'hp' => $emp['telp'], 'alamat' => $emp['alamat'], 'st_aktif' => $aktif];
        if (! empty($in['password'])) {
            $account['password'] = password_hash($in['password'], PASSWORD_DEFAULT);
        }
        if ($idUser) {
            DB::table('users')->where('id_user', $idUser)->update($account);
        } else {
            $idUser = (int) DB::table('users')->insertGetId($account + ['created_on' => now(), 'deleted' => 0]);
        }
        DB::table('user_groups')->where('id_user', $idUser)->delete();
        DB::table('user_groups')->insert(['id_user' => $idUser, 'id_group' => $group]);

        return $idUser;
    }

    /** User Superadmin hanya boleh diubah/dinonaktifkan/direset oleh Superadmin; mencegah pengambilalihan akun. */
    private function guardSuperadminTarget(object $row): void
    {
        if ((int) $row->id_group === self::SUPERADMIN && ! Permissions::superadmin(Auth::user())) {
            abort(403);
        }
    }

    public function toggle(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        DB::transaction(function () use ($id) {
            $old = DB::table('ms_user_karyawan')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            $this->guardSuperadminTarget($old);
            if ($old->id_user && (int) $old->id_user === Auth::id()) {
                throw ValidationException::withMessages(['status' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
            }
            $aktif = $old->aktif ? 0 : 1;
            DB::table('ms_user_karyawan')->where('id', $id)->update(['aktif' => $aktif, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            if ($old->id_user) {
                DB::table('users')->where('id_user', $old->id_user)->update(['st_aktif' => $aktif]);
            }
            $action = $aktif ? 'Aktifkan' : 'Nonaktifkan';
            write_log('Master User', $action, $action.' User '.$old->kode, ['old' => ['aktif' => (int) $old->aktif], 'new' => ['aktif' => $aktif]], 'ms_user_karyawan', $id, $old->kode);
        });

        return back()->with('success', 'Status User diperbarui.');
    }

    /**
     * Reset autentikator oleh admin (bukan mandiri): hapus secret TOTP target, batalkan sesi terverifikasinya
     * (VerifiedAccount membandingkan hash secret), lalu terbitkan token enroll sekali pakai 15 menit.
     * Tidak boleh untuk diri sendiri: sesi yang dibajak tidak boleh mencopot 2FA-nya sendiri.
     */
    public function resetTwoFactor(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $token = strtoupper(bin2hex(random_bytes(6)));
        DB::transaction(function () use ($id, $token) {
            $old = DB::table('ms_user_karyawan')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            $this->guardSuperadminTarget($old);
            if (! $old->id_user) {
                throw ValidationException::withMessages(['status' => 'User tidak memiliki akun login.']);
            }
            if ((int) $old->id_user === Auth::id()) {
                throw ValidationException::withMessages(['status' => 'Anda tidak dapat mereset autentikator Anda sendiri; minta admin lain.']);
            }
            $account = DB::table('users')->where('id_user', $old->id_user)->lockForUpdate()->first() ?? abort(404);
            if (! $account->ga_secret) {
                throw ValidationException::withMessages(['status' => 'Autentikator akun ini belum aktif; buat token enroll saja.']);
            }
            DB::table('users')->where('id_user', $old->id_user)->update(['ga_secret' => null]);
            Cache::put('auth.enroll.'.$old->id_user, hash('sha256', $token), now()->addMinutes(15));
            write_log('Master User', 'Reset Autentikator', 'Reset autentikator User '.$old->kode, ['sub_module' => 'Master User'], 'ms_user_karyawan', $id, $old->kode);
        });

        return back()->with('success', 'Autentikator direset. Token enroll berlaku 15 menit dan hanya tampil sekali.')->with('enroll_token', $token);
    }

    public function resetPassword(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $in = $request->validate(['password' => 'required|string|min:8|max:200|confirmed'], ['password.required' => 'Password minimal 8 karakter.', 'password.min' => 'Password minimal 8 karakter.', 'password.confirmed' => 'Konfirmasi password tidak sama.']);
        DB::transaction(function () use ($id, $in) {
            $old = DB::table('ms_user_karyawan')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            $this->guardSuperadminTarget($old);
            if (! $old->id_user) {
                throw ValidationException::withMessages(['password' => 'User tidak memiliki akun login.']);
            }
            DB::table('users')->where('id_user', $old->id_user)->update(['password' => password_hash($in['password'], PASSWORD_DEFAULT)]);
            write_log('Master User', 'Reset Password', 'Reset Password User '.$old->kode, null, 'ms_user_karyawan', $id, $old->kode);
        });

        return back()->with('success', 'Password diganti.');
    }
}

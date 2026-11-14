<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('menu', ['permission_group', 'View']);
        $roles = DB::table('groups')->orderBy('nm_group')->get(['id_group', 'nm_group']);
        $role = $roles->firstWhere('id_group', $request->integer('role')) ?? $roles->first();

        return $this->page('role', $role?->id_group, $roles);
    }

    public function user(User $user)
    {
        Gate::authorize('menu', ['master_data.users', 'Manage']);

        return $this->page('user', $user->id_user, []);
    }

    private function page(string $kind, ?int $id, $roles)
    {
        $table = $kind === 'role' ? 'ms_group_akses' : 'ms_user_akses';
        $key = $kind === 'role' ? 'id_group' : 'id_user';
        $grants = DB::table($table)->where($key, $id)->get(['id_menu', 'aksi']);
        $target = $kind === 'user' ? User::findOrFail($id) : null;
        $inherited = $target ? Permissions::map($target, false) : [];
        $locked = $kind === 'role' ? $id === 1 : Permissions::superadmin($target);
        $menus = DB::table('ms_menu')->where('aktif', 1)->orderBy('urutan')->get(['id', 'kode', 'judul']);
        $catalogue = Permissions::catalogue($menus);
        $selected = [];
        $base = [];
        foreach ($menus as $menu) {
            $menu->actions = $catalogue[$menu->id];
            foreach ($menu->actions as $action) {
                $value = $menu->id.':'.$action;
                if ($locked || $grants->contains(fn ($grant) => (int) $grant->id_menu === (int) $menu->id && in_array($grant->aksi, Permissions::ACTIONS[$action], true))) {
                    $selected[] = $value;
                }
                if (in_array($action, $inherited[$menu->kode] ?? [], true)) {
                    $base[] = $value;
                }
            }
        }

        $actor = request()->user();
        $manage = Gate::allows('menu', [$kind === 'role' ? 'permission_group' : 'master_data.users', 'Manage']);

        return Inertia::render('Permissions', [
            'kind' => $kind, 'targetId' => $id, 'targetName' => $target?->nm_lengkap,
            'roles' => $roles, 'menus' => $menus, 'selected' => $selected, 'inherited' => $base,
            'editable' => ! $locked && $manage && ! $this->ownsTarget($actor, $kind, (int) $id),
            'canEnroll' => $target !== null && ! $target->ga_secret && Gate::allows('menu', ['master_data.users', 'Manage']) && $this->mayEnroll($actor, $target),
            'users' => Gate::allows('menu', ['master_data.users', 'Manage']) ? User::orderBy('nm_lengkap')->get(['id_user', 'nm_lengkap']) : [],
        ]);
    }

    public function roleUpdate(Request $request, int $role)
    {
        Gate::authorize('menu', ['permission_group', 'Manage']);
        abort_if($role === 1 || $this->ownsTarget($request->user(), 'role', $role), 403);

        return $this->save($request, 'role', $role);
    }

    public function userUpdate(Request $request, User $user)
    {
        Gate::authorize('menu', ['master_data.users', 'Manage']);
        abort_if(Permissions::superadmin($user) || $this->ownsTarget($request->user(), 'user', $user->id_user), 403);

        return $this->save($request, 'user', $user->id_user);
    }

    public function enrollToken(Request $request, User $user)
    {
        Gate::authorize('menu', ['master_data.users', 'Manage']);
        abort_unless($this->mayEnroll($request->user(), $user), 403);
        if ($user->ga_secret) {
            throw ValidationException::withMessages(['enroll' => 'Autentikator akun ini sudah aktif.']);
        }
        $token = strtoupper(bin2hex(random_bytes(6)));
        Cache::put('auth.enroll.'.$user->id_user, hash('sha256', $token), now()->addMinutes(15));
        write_log('Master Data', 'Edit', 'Buat token enroll autentikator', ['sub_module' => 'Master User'], 'users', $user->id_user, $user->username);

        return back()->with('success', 'Token enroll dibuat, berlaku 15 menit dan hanya tampil sekali.')->with('enroll_token', $token);
    }

    /** Aktor non-Superadmin tidak boleh mengubah hak dirinya sendiri atau role yang ia miliki. */
    private function ownsTarget(?User $actor, string $kind, int $id): bool
    {
        if (! $actor || Permissions::superadmin($actor)) {
            return false;
        }

        return $kind === 'user'
            ? $actor->id_user === $id
            : DB::table('user_groups')->where('id_user', $actor->id_user)->where('id_group', $id)->exists();
    }

    private function mayEnroll(?User $actor, User $target): bool
    {
        return $actor && $actor->id_user !== $target->id_user
            && (! Permissions::superadmin($target) || Permissions::superadmin($actor));
    }

    private function save(Request $request, string $kind, int $id)
    {
        $input = $request->validate(['grants' => 'present|array|max:5000', 'grants.*' => 'required|string|distinct|max:80']);
        $menus = DB::table('ms_menu')->where('aktif', 1)->get(['id', 'kode']);
        $allowed = [];
        $catalogue = Permissions::catalogue($menus);
        foreach ($catalogue as $menuId => $actions) {
            foreach ($actions as $action) {
                $allowed[] = $menuId.':'.$action;
            }
        }
        if (array_diff($input['grants'], $allowed)) {
            throw ValidationException::withMessages(['grants' => 'Menu atau aksi tidak tersedia.']);
        }
        $selected = $input['grants'];
        foreach ($selected as $grant) {
            [$menu, $action] = explode(':', $grant);
            if ($action !== 'View') {
                $selected[] = $menu.':View';
            }
        }
        $selected = array_values(array_unique($selected));
        $table = $kind === 'role' ? 'ms_group_akses' : 'ms_user_akses';
        $key = $kind === 'role' ? 'id_group' : 'id_user';
        $actor = $request->user();
        $actorMap = Permissions::superadmin($actor) ? null : Permissions::map($actor);
        $raw = Permissions::rawActions();
        DB::transaction(function () use ($kind, $id, $table, $key, $menus, $selected, $actorMap, $catalogue, $raw) {
            $owner = DB::table($kind === 'role' ? 'groups' : 'users')->where($key, $id)->lockForUpdate()->first();
            abort_unless($owner, 404);
            $inherited = $kind === 'user' ? Permissions::map(User::findOrFail($id), false) : [];
            $query = DB::table($table)->where($key, $id);
            $old = (clone $query)->orderBy('id_menu')->orderBy('aksi')->get()->map(fn ($row) => (array) $row)->all();
            $wanted = array_fill_keys($selected, true);
            foreach ($menus as $menu) {
                foreach ($catalogue[$menu->id] as $action) {
                    $has = collect($old)->contains(fn ($row) => (int) $row['id_menu'] === (int) $menu->id && in_array($row['aksi'], Permissions::ACTIONS[$action], true));
                    $want = isset($wanted[$menu->id.':'.$action]);
                    if ($has && ! $want) {
                        (clone $query)->where('id_menu', $menu->id)->whereIn('aksi', Permissions::ACTIONS[$action])->delete();
                    } elseif ($want && ! $has && ! in_array($action, $inherited[$menu->kode] ?? [], true)) {
                        if ($actorMap !== null && ! in_array($action, $actorMap[$menu->kode] ?? [], true)) {
                            throw ValidationException::withMessages(['grants' => 'Anda tidak dapat memberi hak yang tidak Anda miliki: '.$menu->kode.' - '.$action.'.']);
                        }
                        $name = $action === 'Manage' && ! in_array('ubah', $raw[$menu->id] ?? [], true) ? 'aktif' : Permissions::ACTIONS[$action][0];
                        DB::table($table)->insert([$key => $id, 'id_menu' => (int) $menu->id, 'aksi' => $name]);
                    }
                }
            }
            $new = (clone $query)->orderBy('id_menu')->orderBy('aksi')->get()->map(fn ($row) => (array) $row)->all();
            $before = array_fill_keys(array_map(fn ($row) => $row['id_menu'].':'.$row['aksi'], $old), true);
            $after = array_fill_keys(array_map(fn ($row) => $row['id_menu'].':'.$row['aksi'], $new), true);
            $changes = [];
            foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $grant) {
                if (isset($before[$grant]) === isset($after[$grant])) {
                    continue;
                }
                [$menuId, $action] = explode(':', $grant);
                $label = $menus->firstWhere('id', (int) $menuId)?->kode ?? $menuId;
                $changes[$label.' - '.$action] = ['old' => isset($before[$grant]), 'new' => isset($after[$grant])];
            }
            write_log('Master Data', 'Edit', $kind === 'role' ? 'Update Role & Permission' : 'Update Override Permission', [
                'sub_module' => $kind === 'role' ? 'Role & Permission' : 'Master User',
                'old' => ['grants' => $old], 'new' => ['grants' => $new],
                'detail_changes' => $changes,
            ], $table, $id, $kind === 'role' ? $owner->nm_group : $owner->username);
        });

        return back()->with('success', 'Hak akses tersimpan.');
    }
}

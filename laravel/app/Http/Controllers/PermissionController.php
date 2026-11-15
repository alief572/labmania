<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('menu', ['permission_group', 'View']);
        if ($request->route('datatable')) {
            return $this->roleList($request);
        }
        $menus = DB::table('ms_menu')->where('aktif', 1)->get(['id', 'kode']);
        $catalogue = Permissions::catalogue($menus);

        return Inertia::render('Permissions', [
            'kind' => 'role', 'targetId' => null, 'targetName' => null, 'roles' => [], 'users' => [], 'menus' => [], 'selected' => [], 'inherited' => [],
            'editable' => false, 'superadmin' => 1,
            'kpi' => [
                'roles' => DB::table('groups')->count(),
                'users' => DB::table('ms_user_karyawan')->whereNotNull('id_user')->count(),
                'menus' => $menus->filter(fn ($m) => $catalogue[$m->id] !== [])->count(),
                'hak' => collect($catalogue)->sum(fn ($a) => count($a)),
            ],
            'can' => ['manage' => Gate::allows('menu', ['permission_group', 'Manage'])],
        ]);
    }

    /** Daftar role untuk tabel server-side: jumlah user, user dengan override, total hak, dan cuplikan kode hak. */
    private function roleList(Request $request)
    {
        $base = DB::table('groups as g')->orderBy('g.id_group')->select('g.id_group', 'g.nm_group',
            DB::raw('(SELECT COUNT(*) FROM user_groups ug WHERE ug.id_group = g.id_group) AS n_user'),
            DB::raw('(SELECT COUNT(DISTINCT ug2.id_user) FROM user_groups ug2 JOIN ms_user_akses o ON o.id_user = ug2.id_user WHERE ug2.id_group = g.id_group) AS n_override'),
            DB::raw('(SELECT COUNT(*) FROM ms_group_akses a WHERE a.id_group = g.id_group) AS n_hak'));
        $menus = DB::table('ms_menu')->where('aktif', 1)->get(['id', 'kode']);
        $codes = $menus->pluck('kode', 'id');
        $catalogue = Permissions::catalogue($menus);
        $total = collect($catalogue)->sum(fn ($a) => count($a));

        return ServerDataTable::response($request, $base, clone $base, [null, 'nm_group', 'n_user', 'n_hak', null, null], ['id_group', 'nm_group', 'n_user', 'n_override', 'n_hak', 'hak', 'sistem', 'total'], 'id_group', ['nm_group'],
            function ($row) use ($codes, $total) {
                $sistem = (int) $row->id_group === 1;
                $grants = $sistem ? collect() : DB::table('ms_group_akses')->where('id_group', $row->id_group)->orderBy('id_menu')->orderBy('aksi')->get(['id_menu', 'aksi']);
                $row->hak = $grants->map(function ($g) use ($codes) {
                    $ui = collect(Permissions::ACTIONS)->filter(fn ($names) => in_array($g->aksi, $names, true))->keys()->first();

                    return $ui ? ($codes[$g->id_menu] ?? $g->id_menu).'.'.strtolower($ui) : null;
                })->filter()->unique()->values()->all();
                $row->n_hak = $sistem ? $total : count($row->hak);
                $row->sistem = $sistem;
                $row->total = $total;

                return $row;
            });
    }

    public function user(User $user)
    {
        Gate::authorize('menu', ['master_data.users', 'Manage']);

        return $this->page('user', $user->id_user, []);
    }

    private function page(string $kind, ?int $id, $roles, bool $json = false)
    {
        $table = $kind === 'role' ? 'ms_group_akses' : 'ms_user_akses';
        $key = $kind === 'role' ? 'id_group' : 'id_user';
        $grants = DB::table($table)->where($key, $id)->get(['id_menu', 'aksi']);
        $target = $kind === 'user' ? User::findOrFail($id) : null;
        $inherited = $target ? Permissions::map($target, false) : [];
        $locked = $kind === 'role' ? $id === 1 : Permissions::superadmin($target);
        $menus = DB::table('ms_menu')->where('aktif', 1)->orderBy('urutan')->get(['id', 'kode', 'judul', 'parent_id']);
        $parents = $menus->where('parent_id', 0)->pluck('judul', 'id');
        $catalogue = Permissions::catalogue($menus);
        $selected = [];
        $base = [];
        foreach ($menus as $menu) {
            $menu->actions = $catalogue[$menu->id];
            $menu->modul = $parents[$menu->parent_id] ?? 'Umum';
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

        $props = [
            'kind' => $kind, 'targetId' => $id, 'targetName' => $target?->nm_lengkap,
            'roles' => $roles, 'menus' => $menus, 'selected' => $selected, 'inherited' => $base,
            'editable' => ! $locked && $manage && ! $this->ownsTarget($actor, $kind, (int) $id),
            'users' => Gate::allows('menu', ['master_data.users', 'Manage']) ? User::orderBy('nm_lengkap')->get(['id_user', 'nm_lengkap']) : [],
        ];

        return $json ? response()->json($props) : Inertia::render($kind === 'user' ? 'PermissionOverride' : 'Permissions', $props);
    }

    /** Data matriks satu role untuk modal "Atur Akses Role" (JSON); simpan tetap lewat PUT /permissions/roles/{id}. */
    public function role(int $role)
    {
        Gate::authorize('menu', ['permission_group', 'View']);
        $row = DB::table('groups')->where('id_group', $role)->first(['id_group', 'nm_group']);
        abort_unless($row, 404);
        $json = $this->page('role', $role, [$row], true)->getData(true);
        $json['users'] = [];
        $json['n_user'] = DB::table('user_groups')->where('id_group', $role)->count();

        return response()->json($json);
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

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Permissions
{
    public const ACTIONS = ['View' => ['lihat'], 'Add' => ['tambah'], 'Manage' => ['ubah', 'aktif'], 'Delete' => ['hapus'], 'Approve' => ['approve']];

    public static function superadmin(User $user): bool
    {
        return $user->active() && DB::table('user_groups')->where('id_user', $user->id_user)->where('id_group', 1)->exists();
    }

    public static function map(User $user, bool $withOverride = true): array
    {
        if (! $user->active()) {
            return [];
        }
        $menus = DB::table('ms_menu')->where('aktif', 1)->get(['id', 'kode']);
        $catalogue = self::catalogue($menus);
        $grants = DB::table('ms_group_akses as a')->join('user_groups as ug', 'ug.id_group', '=', 'a.id_group')
            ->where('ug.id_user', $user->id_user)->get(['a.id_menu', 'a.aksi']);
        if ($withOverride) {
            $grants = $grants->concat(DB::table('ms_user_akses')->where('id_user', $user->id_user)->get(['id_menu', 'aksi']));
        }
        $superadmin = self::superadmin($user);
        $logEligible = $superadmin || DB::table('user_groups')->where('id_user', $user->id_user)
            ->whereIn('id_group', config('labmania.activity_log_role_ids'))->exists();
        $map = [];
        foreach ($menus as $menu) {
            if ($menu->kode === 'activity_log' && ! $logEligible) {
                continue;
            }
            $available = $catalogue[$menu->id];
            foreach (self::ACTIONS as $action => $legacy) {
                if (! $superadmin && ! in_array($action, $available, true)) {
                    continue;
                }
                if ($superadmin || $grants->contains(fn ($grant) => (int) $grant->id_menu === (int) $menu->id && in_array($grant->aksi, $legacy, true))) {
                    $map[$menu->kode][] = $action;
                }
            }
        }

        return $map;
    }

    public static function allows(User $user, string $menu, string $action): bool
    {
        return in_array($action, self::map($user)[$menu] ?? [], true);
    }

    /** Nama aksi legacy per menu dari ms_menu_aksi, satu query: [id_menu => [aksi, ...]]. */
    public static function rawActions(): array
    {
        return DB::table('ms_menu_aksi')->get(['id_menu', 'aksi'])->groupBy('id_menu')
            ->map(fn ($rows) => $rows->pluck('aksi')->all())->all();
    }

    /** Aksi UI tersedia per menu: [id_menu => ['View', ...]] tanpa query per menu. */
    public static function catalogue(iterable $menus): array
    {
        $raw = self::rawActions();
        $result = [];
        foreach ($menus as $menu) {
            $result[$menu->id] = self::available($menu, $raw[$menu->id] ?? []);
        }

        return $result;
    }

    public static function available(object $menu, ?array $raw = null): array
    {
        $raw ??= DB::table('ms_menu_aksi')->where('id_menu', $menu->id)->pluck('aksi')->all();
        $actions = array_keys(array_filter(self::ACTIONS, fn ($names) => array_intersect($names, $raw)));
        if (in_array($menu->kode, ['master_program', 'penjualan_program'], true) && ! in_array('Approve', $actions, true)) {
            $actions[] = 'Approve';
        }

        return $actions;
    }

    /**
     * Simpan hak akses satu role (kind=role) atau satu override user (kind=user). Dipakai Role & Permission dan form Master User.
     * Aksi selain View otomatis menyertakan View. Membuka transaksi sendiri.
     * Boleh memberi hak yang tidak dimiliki aktor; larangan mengubah hak diri sendiri dijaga pemanggil.
     * Override hanya menambah: hak yang sudah dimiliki role user tidak disimpan ulang.
     */
    public static function saveGrants(string $kind, int $id, array $grants, string $errorKey = 'grants'): void
    {
        $menus = DB::table('ms_menu')->where('aktif', 1)->get(['id', 'kode']);
        $catalogue = self::catalogue($menus);
        $allowed = [];
        foreach ($catalogue as $menuId => $actions) {
            foreach ($actions as $action) {
                $allowed[] = $menuId.':'.$action;
            }
        }
        if (array_diff($grants, $allowed)) {
            throw ValidationException::withMessages([$errorKey => 'Menu atau aksi tidak tersedia.']);
        }
        $selected = $grants;
        foreach ($grants as $grant) {
            [$menu, $action] = explode(':', $grant);
            if ($action !== 'View') {
                $selected[] = $menu.':View';
            }
        }
        $selected = array_values(array_unique($selected));
        $table = $kind === 'role' ? 'ms_group_akses' : 'ms_user_akses';
        $key = $kind === 'role' ? 'id_group' : 'id_user';
        $raw = self::rawActions();
        DB::transaction(function () use ($kind, $id, $table, $key, $menus, $selected, $catalogue, $raw) {
            $owner = DB::table($kind === 'role' ? 'groups' : 'users')->where($key, $id)->lockForUpdate()->first();
            abort_unless($owner, 404);
            $inherited = $kind === 'user' ? self::map(User::findOrFail($id), false) : [];
            $query = DB::table($table)->where($key, $id);
            $old = (clone $query)->orderBy('id_menu')->orderBy('aksi')->get()->map(fn ($row) => (array) $row)->all();
            $wanted = array_fill_keys($selected, true);
            foreach ($menus as $menu) {
                foreach ($catalogue[$menu->id] as $action) {
                    $has = collect($old)->contains(fn ($row) => (int) $row['id_menu'] === (int) $menu->id && in_array($row['aksi'], self::ACTIONS[$action], true));
                    $want = isset($wanted[$menu->id.':'.$action]);
                    if ($has && ! $want) {
                        (clone $query)->where('id_menu', $menu->id)->whereIn('aksi', self::ACTIONS[$action])->delete();
                    } elseif ($want && ! $has && ! in_array($action, $inherited[$menu->kode] ?? [], true)) {
                        $name = $action === 'Manage' && ! in_array('ubah', $raw[$menu->id] ?? [], true) ? 'aktif' : self::ACTIONS[$action][0];
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
    }

    /**
     * Buang override user yang sudah tercakup role-nya (FR-65: saat Role diganti, hak override yang sudah tercakup role baru dibuang).
     * Kembalian: jumlah baris override yang dibuang. Panggil setelah user_groups diperbarui, di dalam transaksi.
     */
    public static function pruneOverride(int $idUser): int
    {
        $user = User::find($idUser);
        if (! $user) {
            return 0;
        }
        $role = self::map($user, false);
        $removed = 0;
        foreach (DB::table('ms_menu')->get(['id', 'kode']) as $menu) {
            foreach ($role[$menu->kode] ?? [] as $action) {
                $removed += DB::table('ms_user_akses')->where('id_user', $idUser)->where('id_menu', $menu->id)->whereIn('aksi', self::ACTIONS[$action])->delete();
            }
        }

        return $removed;
    }
}

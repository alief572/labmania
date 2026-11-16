<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    /**
     * Apakah semua hak role ini sudah dimiliki aktor? Dipakai agar pemberi role tidak menaikkan hak lewat penugasan role
     * (aturan delegasi fase 1). Superadmin selalu boleh; hak legacy di luar lima aksi UI (mis. cetak) tidak dihitung.
     */
    public static function coversRole(User $actor, int $groupId): bool
    {
        if (self::superadmin($actor)) {
            return true;
        }
        // Role ini memberi cakupan data semua program meski grant menu kosong.
        if (DB::table('groups')->where('id_group', $groupId)->whereIn('nm_group', ['Admin', 'Direktur'])->exists()) {
            return false;
        }
        $map = self::map($actor);
        $codes = DB::table('ms_menu')->pluck('kode', 'id');
        foreach (DB::table('ms_group_akses')->where('id_group', $groupId)->get(['id_menu', 'aksi']) as $grant) {
            foreach (self::ACTIONS as $ui => $legacy) {
                if (in_array($grant->aksi, $legacy, true) && ! in_array($ui, $map[$codes[$grant->id_menu] ?? ''] ?? [], true)) {
                    return false;
                }
            }
        }

        return true;
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
}

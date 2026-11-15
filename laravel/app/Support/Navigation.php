<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class Navigation
{
    public static function url(string $link, string $code): ?string
    {
        $path = '/'.ltrim($link, '/');
        $mapped = match ($code) {
            'dashboard' => '/',
            'permission_group' => '/permissions',
            default => collect(config('navigation'))->firstWhere('menu', $code)['url'] ?? null,
        };

        $registered = collect(config('navigation'))->pluck('url')->all();
        foreach (array_unique([$path, $mapped]) as $candidate) {
            if ($candidate === null) {
                continue;
            }
            foreach (Route::getRoutes() as $route) {
                if (in_array('GET', $route->methods(), true) && in_array('auth', $route->gatherMiddleware(), true)
                    && (! str_contains($route->uri(), '{') || in_array($candidate, $registered, true))
                    && $route->matches(Request::create($candidate))) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /** Kode menu => tabel ERD untuk badge sidebar (mockup core.js countOf). Menu tanpa tabel sendiri tidak diberi badge. */
    private const COUNT_TABLES = [
        'master_data.jenis_produk' => 'ms_jenis_produk', 'master_data.jenis_sertifikasi' => 'ms_jenis_sertifikasi',
        'master_data.jenis_bidang' => 'ms_jenis_bidang', 'master_data.varian_pelaksanaan' => 'ms_varian_pelaksanaan',
        'master_data.paket_produk' => 'paket_produk', 'master_data.katalog' => 'paket_produk',
        'master_data.peserta' => 'ms_peserta', 'master_data.company' => 'master_customer', 'master_trainer' => 'ms_trainer',
        'master_data.template_keterangan' => 'ms_template_keterangan', 'master_data.template_konfirmasi' => 'ms_template_konfirmasi',
        'master_data.rate' => 'ms_rate', 'master_data.users' => 'ms_user_karyawan',
        'master_data.role' => 'groups', 'permission_group' => 'groups',
        'master_program' => 'master_program', 'penjualan_program' => 'penjualan_program', 'quotation_program' => 'quotation',
        'sales_order' => 'sales_order', 'activity_log' => 'logs',
    ];

    private const SOFT_DELETE = ['ms_peserta', 'master_customer'];

    private static function count(string $code): ?int
    {
        $table = self::COUNT_TABLES[$code] ?? null;
        if ($table === null) {
            return null;
        }
        try {
            $query = DB::table($table);
            if (in_array($table, self::SOFT_DELETE, true)) {
                $query->whereNull('deleted_date');
            }

            return $query->count();
        } catch (\Throwable) {
            return null; // tabel belum dimigrasi: badge menu itu kosong, menu lain tetap tampil
        }
    }

    public static function items(array $permissions): array
    {
        $rows = DB::table('ms_menu')->where('aktif', 1)->orderBy('urutan')->orderBy('id')->get()->groupBy('parent_id');
        $items = [];
        $append = function ($menu, $parent = null) use (&$items, $permissions) {
            $url = self::url($menu->link, $menu->kode);
            if (! in_array('View', $permissions[$menu->kode] ?? [], true) || $url === null) {
                return;
            }
            $item = ['label' => $menu->judul, 'url' => $url, 'menu' => $menu->kode,
                'group' => $parent?->judul, 'sectionKey' => (string) ($parent?->id ?? $menu->id),
                'subgroupKey' => (string) $menu->kelompok,
                'subgroupLabel' => config('navigation-groups')[$parent?->kode ?? ''][(int) $menu->kelompok] ?? '',
                'icon' => $parent?->ikon ?? $menu->ikon];
            if (($count = self::count($menu->kode)) !== null) {
                $item['count'] = $count;
            }
            $items[] = $item;
        };
        foreach ($rows[0] ?? [] as $top) {
            if ($top->link !== '#') {
                $append($top);

                continue;
            }
            foreach (collect($rows[$top->id] ?? [])->sortBy(fn ($menu) => [(int) $menu->kelompok, (int) $menu->urutan, (int) $menu->id]) as $child) {
                $append($child, $top);
            }
        }

        return $items;
    }

    public static function unmapped(): array
    {
        return DB::table('ms_menu')->where('aktif', 1)->where('link', '!=', '#')->orderBy('id')->get(['id', 'kode', 'judul', 'link'])
            ->filter(fn ($menu) => self::url($menu->link, $menu->kode) === null)->values()->all();
    }
}

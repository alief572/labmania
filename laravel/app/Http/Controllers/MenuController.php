<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * CRUD Menu Sidebar (struktur menu dan aksi di ms_menu / ms_menu_aksi). Aturan M-01..M-14 dari perilaku CI3
 * (Menu_labmania_model), lihat .artifacts/paritas_fitur_ci3/spesifikasi_crud_menu_14-11-2026.md.
 * Sidebar Laravel sendiri tetap dari config/navigation.php (D-02, menunggu keputusan BA).
 */
class MenuController extends Controller
{
    private const MENU = 'menu_labmania';

    private const AKSI = ['lihat' => 'Lihat', 'tambah' => 'Tambah', 'ubah' => 'Ubah', 'aktif' => 'Ubah Status', 'hapus' => 'Hapus', 'cetak' => 'Cetak', 'approve' => 'Approve'];

    private const ICONS = ['home', 'database', 'document', 'chat', 'clock', 'settings', 'users', 'tag', 'chart'];

    private function find(int $id, bool $lock = false): array
    {
        $m = (array) (DB::table('ms_menu')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first() ?? abort(404));
        $m['aksi'] = DB::table('ms_menu_aksi')->where('id_menu', $id)->orderBy('aksi')->pluck('aksi')->all();

        return $m;
    }

    private static function isModul(array $m): bool
    {
        return (int) $m['parent_id'] === 0 && $m['link'] === '#';
    }

    public function index()
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $aksi = DB::table('ms_menu_aksi')->get()->groupBy('id_menu')->map(fn ($g) => $g->pluck('aksi')->sort()->values()->all());
        $by = DB::table('ms_menu')->orderBy('urutan')->orderBy('id')->get()->map(fn ($r) => (array) $r + ['aksi' => $aksi[$r->id] ?? []])->groupBy('parent_id');
        $rows = [];
        foreach ($by[0] ?? [] as $top) {
            $rows[] = $top + ['level' => 0, 'tipe' => self::isModul($top) ? 'modul' : 'menu'];
            foreach (collect($by[$top['id']] ?? [])->sortBy(fn ($k) => [$k['kelompok'], $k['urutan'], $k['id']])->values() as $kid) {
                $rows[] = $kid + ['level' => 1, 'tipe' => 'menu'];
            }
        }

        return Inertia::render('Menu/Index', ['rows' => $rows, 'moduls' => DB::table('ms_menu')->where('parent_id', 0)->where('link', '#')->orderBy('urutan')->get(['id', 'judul']),
            'icons' => self::ICONS, 'aksi' => self::AKSI,
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage'])]]);
    }

    private function slug(string $s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($s)), '_') ?: 'menu';
    }

    private function uniqueKode(string $base): string
    {
        $kode = $base;
        for ($i = 2; DB::table('ms_menu')->where('kode', $kode)->exists(); $i++) {
            $kode = $base.'_'.$i;
        }

        return $kode;
    }

    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);

        return $this->save($request, null);
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);

        return $this->save($request, $id);
    }

    private function save(Request $request, ?int $id)
    {
        $in = $request->validate([
            'tipe' => 'nullable|in:modul,menu', 'judul' => 'required|string|max:100', 'link' => 'nullable|string', 'ikon' => 'nullable|string|in:'.implode(',', self::ICONS),
            'parent_id' => 'nullable|integer|min:0', 'kelompok' => 'nullable|integer', 'aksi' => 'nullable|array', 'aksi.*' => 'string|in:'.implode(',', array_keys(self::AKSI)),
        ], ['judul.required' => 'Judul wajib diisi.', 'judul.max' => 'Judul maksimal 100 karakter.', 'ikon.in' => 'Ikon tidak valid.', 'aksi.*.in' => 'Aksi tidak valid.']);

        $judul = trim($in['judul']);
        DB::transaction(function () use ($in, $id, $judul) {
            $old = $id ? $this->find($id, true) : null;
            $modul = $old ? self::isModul($old) : ($in['tipe'] ?? 'menu') === 'modul'; // tipe tidak dapat diubah (M-01)
            $sistem = $old ? (int) $old['sistem'] : 0;
            $ikon = trim((string) ($in['ikon'] ?? ''));
            $kelompok = max(1, min(99, (int) ($in['kelompok'] ?? 1)));
            $parent = $modul ? 0 : (int) ($in['parent_id'] ?? 0);
            $link = '#';
            $aksi = [];
            if (! $modul) {
                $link = $old && $sistem ? $old['link'] : trim((string) ($in['link'] ?? '')); // link menu sistem terkunci (M-04)
                if ($link === '' || $link === '#') {
                    throw ValidationException::withMessages(['link' => 'Link wajib diisi.']);
                }
                if (strlen($link) > 150 || ! preg_match('#^[a-z0-9_\-]+(/[a-z0-9_\-]+)*$#i', $link)) {
                    throw ValidationException::withMessages(['link' => 'Link tidak valid (huruf, angka, _ - dan /; tanpa awalan /).']);
                }
                if ($parent !== 0 && ! DB::table('ms_menu')->where('id', $parent)->where('parent_id', 0)->where('link', '#')->exists()) {
                    throw ValidationException::withMessages(['parent_id' => 'Modul induk tidak valid.']);
                }
                if (DB::table('ms_menu')->where('link', $link)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                    throw ValidationException::withMessages(['link' => 'Link sudah dipakai menu lain.']);
                }
                $aksi = array_values(array_unique([...($in['aksi'] ?? []), 'lihat'])); // setiap menu punya Lihat (M-08)
                if ($parent !== 0) {
                    $ikon = ''; // ikon hanya untuk tingkat atas (M-06)
                }
            }
            $now = now();
            if ($old) {
                $data = ['parent_id' => $parent, 'judul' => $judul, 'link' => $link, 'ikon' => $ikon !== '' ? $ikon : null, 'kelompok' => $kelompok, 'updated_by' => Auth::id(), 'updated_date' => $now];
                DB::table('ms_menu')->where('id', $id)->update($data);
                $menuId = $id;
                $kode = $old['kode'];
            } else {
                $kode = $this->uniqueKode($modul ? 'm_'.$this->slug($judul) : str_replace('/', '.', strtolower($link)));
                $data = ['parent_id' => $parent, 'kode' => $kode, 'judul' => $judul, 'link' => $link, 'ikon' => $ikon !== '' ? $ikon : null, 'kelompok' => $kelompok,
                    'urutan' => (int) DB::table('ms_menu')->where('parent_id', $parent)->max('urutan') + 1, 'aktif' => 1, 'sistem' => 0, 'created_by' => Auth::id(), 'created_date' => $now];
                $menuId = DB::table('ms_menu')->insertGetId($data);
            }
            if (! $modul) {
                $hapus = array_diff($old['aksi'] ?? [], $aksi);
                if ($hapus) {
                    DB::table('ms_menu_aksi')->where('id_menu', $menuId)->whereIn('aksi', $hapus)->delete();
                    DB::table('ms_group_akses')->where('id_menu', $menuId)->whereIn('aksi', $hapus)->delete(); // izin yang tidak berlaku dicabut (M-09)
                    DB::table('ms_user_akses')->where('id_menu', $menuId)->whereIn('aksi', $hapus)->delete();
                }
                foreach (array_diff($aksi, $old['aksi'] ?? []) as $a) {
                    DB::table('ms_menu_aksi')->insert(['id_menu' => $menuId, 'aksi' => $a]);
                }
            }
            if ($old) {
                $oldLog = array_diff_key($old, ['aksi' => 1]) + ['aksi' => implode(',', $old['aksi'])];
                write_log('Menu Sidebar', 'Edit', 'Update Menu '.$judul, ['old' => $oldLog, 'new' => array_merge($oldLog, $data, ['aksi' => implode(',', $aksi)])], 'ms_menu', $menuId, $kode);
            } else {
                write_log('Menu Sidebar', 'Tambah', 'Tambah Menu '.$judul, $data + ['aksi' => implode(',', $aksi)], 'ms_menu', $menuId, $kode);
            }
        });

        return back()->with('success', 'Menu '.$judul.($id ? ' berhasil diperbarui.' : ' berhasil ditambahkan.'));
    }

    public function toggle(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $m = DB::transaction(function () use ($id) {
            $m = $this->find($id, true);
            $baru = $m['aktif'] ? 0 : 1;
            if (! $baru) {
                if ($m['sistem']) {
                    throw ValidationException::withMessages(['aktif' => 'Menu '.$m['judul'].' adalah menu sistem dan tidak dapat dinonaktifkan.']);
                }
                if (DB::table('ms_menu')->where('parent_id', $id)->where('sistem', 1)->exists()) { // M-05
                    throw ValidationException::withMessages(['aktif' => 'Modul '.$m['judul'].' memuat menu sistem dan tidak dapat dinonaktifkan.']);
                }
            }
            DB::table('ms_menu')->where('id', $id)->update(['aktif' => $baru, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            $act = $baru ? 'Aktifkan' : 'Nonaktifkan';
            write_log('Menu Sidebar', $act, $act.' Menu '.$m['judul'], ['old' => ['aktif' => (int) $m['aktif']], 'new' => ['aktif' => $baru]], 'ms_menu', $id, $m['kode']);
            $m['aktif'] = $baru;

            return $m;
        });

        return back()->with('success', 'Menu '.$m['judul'].($m['aktif'] ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function move(int $id, string $dir)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        abort_unless(in_array($dir, ['up', 'down'], true), 404);
        $m = DB::transaction(function () use ($id, $dir) {
            $m = $this->find($id, true);
            $sib = DB::table('ms_menu')->where('parent_id', $m['parent_id'])->when((int) $m['parent_id'] !== 0, fn ($q) => $q->where('kelompok', $m['kelompok']))
                ->orderBy('urutan')->orderBy('id')->pluck('id')->map(fn ($v) => (int) $v)->all();
            $i = array_search($id, $sib, true);
            $to = $dir === 'up' ? $i - 1 : $i + 1;
            if ($i === false || $to < 0 || $to >= count($sib)) {
                throw ValidationException::withMessages(['aktif' => 'Menu sudah berada di posisi '.($dir === 'up' ? 'teratas' : 'terbawah').'.']);
            }
            [$sib[$i], $sib[$to]] = [$sib[$to], $sib[$i]];
            foreach ($sib as $n => $sid) {
                DB::table('ms_menu')->where('id', $sid)->update(['urutan' => ($n + 1) * 10]);
            }
            write_log('Menu Sidebar', 'Edit', 'Pindah menu '.$m['judul'].($dir === 'up' ? ' ke atas' : ' ke bawah'), null, 'ms_menu', $id, $m['kode']);

            return $m;
        });

        return back()->with('success', 'Menu '.$m['judul'].' dipindahkan.');
    }
}

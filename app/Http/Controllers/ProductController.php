<?php

namespace App\Http\Controllers;

use App\Support\CodeGenerator;
use App\Support\ProductLingkup;
use App\Support\ServerDataTable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Product = Produk + Bidang + Varian + Sertifikat (FR-10..13, FR-22..24, FR-43). */
class ProductController extends Controller
{
    private const MENU = 'master_data.paket_produk';

    private const FIELDS = ['produk' => 'id_jenis_produk', 'bidang' => 'id_jenis_bidang', 'varian' => 'id_varian_pelaksanaan', 'sertifikasi' => 'id_jenis_sertifikasi'];

    private const LABEL = ['produk' => 'Produk', 'bidang' => 'Bidang', 'varian' => 'Varian', 'sertifikasi' => 'Sertifikat'];

    private function base()
    {
        return DB::table('paket_produk as pp')
            ->leftJoin('ms_jenis_produk as jp', 'jp.id', '=', 'pp.id_jenis_produk')
            ->leftJoin('ms_jenis_sertifikasi as js', 'js.id', '=', 'pp.id_jenis_sertifikasi')
            ->leftJoin('ms_jenis_bidang as jb', 'jb.id', '=', 'pp.id_jenis_bidang')
            ->leftJoin('ms_varian_pelaksanaan as vp', 'vp.id', '=', 'pp.id_varian_pelaksanaan')
            ->select('pp.*', 'jp.nama as nama_produk', 'js.nama as nama_sertifikasi', 'jb.nama as nama_bidang', 'vp.nama as nama_varian',
                'jp.aktif as aktif_produk', 'js.aktif as aktif_sertifikasi', 'jb.aktif as aktif_bidang', 'vp.aktif as aktif_varian');
    }

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:all,on,off']);
        if ($request->route('datatable')) {
            $base = $this->base()
                ->orderBy('pp.id');
            $query = (clone $base)
                ->when(($f['status'] ?? 'all') === 'on', fn ($q) => $q->where('pp.aktif', 1))
                ->when(($f['status'] ?? 'all') === 'off', fn ($q) => $q->where('pp.aktif', 0));

            return ServerDataTable::response($request, $base, $query, [null, 'judul', 'nama_produk', 'nama_bidang', 'nama_varian', 'nama_sertifikasi', 'aktif', null], ['id', 'nomor', 'judul', 'aktif', 'id_jenis_produk', 'id_jenis_bidang', 'id_varian_pelaksanaan', 'id_jenis_sertifikasi', 'nama_produk', 'nama_bidang', 'nama_varian', 'nama_sertifikasi', 'aktif_produk', 'aktif_bidang', 'aktif_varian', 'aktif_sertifikasi'], 'id', ['nomor', 'judul', 'nama_produk', 'nama_bidang', 'nama_varian', 'nama_sertifikasi'], null);
        }

        return Inertia::render('Master/Product', [
            'filters' => ['q' => $f['q'] ?? '', 'status' => $f['status'] ?? 'all'],
            'options' => $this->options(true),
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage'])],
            'lingkupMax' => config('labmania.lingkup'),
        ]);
    }

    /** Pilihan dropdown: hanya yang aktif; atribut nonaktif yang masih dipakai Product tetap muncul lewat nama di daftar. */
    private function options(bool $withInactive = false): array
    {
        $opt = fn (string $t) => DB::table($t)->when(! $withInactive, fn ($q) => $q->where('aktif', 1))->orderBy('nama')->get(['id', 'nama', 'aktif']);

        return ['produk' => $opt('ms_jenis_produk'), 'bidang' => $opt('ms_jenis_bidang'), 'varian' => $opt('ms_varian_pelaksanaan'), 'sertifikasi' => $opt('ms_jenis_sertifikasi')];
    }

    /** Halaman "Tambah Product": judul + tabel kombinasi; simpan lewat store() (n baris = n Product, atomik). */
    public function create()
    {
        Gate::authorize('menu', [self::MENU, 'Add']);

        return Inertia::render('Master/ProductForm', ['options' => $this->options()]);
    }

    public function detail(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $row = $this->base()->where('pp.id', $id)->first() ?? abort(404);

        return response()->json([
            'product' => $row,
            'lingkup' => DB::table('paket_produk_lingkup')->where('id_paket_produk', $id)->orderBy('urutan')->pluck('lingkup'),
            'dipakai' => DB::table('master_program_produk')->where('id_paket_produk', $id)->count(),
        ]);
    }

    /** Tambah n baris kombinasi = n Product dalam satu transaksi; gagal semua bila ada yang bentrok (FR-13, NFR-03). */
    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $input = $request->validate([
            'judul' => 'required|string|max:150',
            'rows' => 'required|array|min:1|max:50',
            'rows.*.produk' => 'required|integer', 'rows.*.bidang' => 'required|integer',
            'rows.*.varian' => 'required|integer', 'rows.*.sertifikasi' => 'required|integer',
        ], ['judul.required' => 'Judul wajib diisi.', 'rows.*.*.required' => 'Pilih Produk, Bidang, Varian, dan Sertifikat.']);

        try {
            $nomors = DB::transaction(function () use ($input) {
                $errors = [];
                $seen = [];
                foreach ($input['rows'] as $i => $row) {
                    $this->assertActiveAttributes($row, $i, $errors);
                    $key = implode('|', array_map(fn ($f) => (int) $row[$f], array_keys(self::FIELDS)));
                    if (isset($seen[$key])) {
                        $errors["rows.$i"] = 'Kombinasi sama persis dengan baris '.($seen[$key] + 1).'.';

                        continue;
                    }
                    $seen[$key] = $i;
                    if ($dup = $this->activeDuplicate($row, null)) {
                        $errors["rows.$i"] = 'Kombinasi sama persis dengan Product aktif '.$dup->nomor.' — '.$dup->judul.'.';
                    }
                }
                if ($errors) {
                    throw ValidationException::withMessages($errors);
                }
                $nomors = [];
                foreach ($input['rows'] as $row) {
                    $nomor = CodeGenerator::next('paket_produk', config('labmania.code_prefix.product'), 'nomor');
                    $data = ['nomor' => $nomor, 'judul' => trim($input['judul'])] + $this->columns($row) + ['aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
                    $id = DB::table('paket_produk')->insertGetId($data);
                    write_log('Master Produk', 'Tambah', 'Tambah Product '.$nomor, $data, 'paket_produk', $id, $nomor);
                    $nomors[] = $nomor;
                }

                return $nomors;
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['rows' => 'Kombinasi bentrok dengan Product aktif lain. Tidak ada yang tersimpan.']);
        }

        return redirect('/master/product')->with('success', count($nomors).' Product tersimpan: '.implode(', ', $nomors).'.');
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $input = $request->validate([
            'judul' => 'required|string|max:150',
            'produk' => 'required|integer', 'bidang' => 'required|integer', 'varian' => 'required|integer', 'sertifikasi' => 'required|integer',
        ]);
        try {
            DB::transaction(function () use ($input, $id) {
                $old = (array) (DB::table('paket_produk')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
                $errors = [];
                $this->assertActiveAttributes($input, null, $errors, $old);
                if ($old['aktif'] && ($dup = $this->activeDuplicate($input, $id))) {
                    $errors['kombinasi'] = 'Kombinasi sama persis dengan Product aktif '.$dup->nomor.' — '.$dup->judul.'.';
                }
                if ($errors) {
                    throw ValidationException::withMessages($errors);
                }
                $data = ['judul' => trim($input['judul'])] + $this->columns($input) + ['updated_by' => Auth::id(), 'updated_date' => now()];
                DB::table('paket_produk')->where('id', $id)->update($data);
                write_log('Master Produk', 'Edit', 'Update Product '.$old['nomor'], ['old' => $old, 'new' => array_merge($old, $data)], 'paket_produk', $id, $old['nomor']);
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['kombinasi' => 'Kombinasi bentrok dengan Product aktif lain.']);
        }

        return back()->with('success', 'Product diperbarui.');
    }

    /** Reaktivasi ditolak bila kombinasinya sudah dipakai Product aktif lain ([USULAN] FR-22). */
    public function toggle(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        try {
            DB::transaction(function () use ($id) {
                $old = (array) (DB::table('paket_produk')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
                $aktif = $old['aktif'] ? 0 : 1;
                if ($aktif) {
                    $row = ['produk' => $old['id_jenis_produk'], 'bidang' => $old['id_jenis_bidang'], 'varian' => $old['id_varian_pelaksanaan'], 'sertifikasi' => $old['id_jenis_sertifikasi']];
                    if ($dup = $this->activeDuplicate($row, $id)) {
                        throw ValidationException::withMessages(['status' => 'Tidak dapat diaktifkan: kombinasi sudah dipakai Product aktif '.$dup->nomor.'.']);
                    }
                }
                DB::table('paket_produk')->where('id', $id)->update(['aktif' => $aktif, 'updated_by' => Auth::id(), 'updated_date' => now()]);
                $action = $aktif ? 'Aktifkan' : 'Nonaktifkan';
                write_log('Master Produk', $action, $action.' Product '.$old['nomor'], ['old' => $old, 'new' => array_merge($old, ['aktif' => $aktif])], 'paket_produk', $id, $old['nomor']);
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['status' => 'Tidak dapat diaktifkan: kombinasi bentrok dengan Product aktif lain.']);
        }

        return back()->with('success', 'Status Product diperbarui.');
    }

    /** Lingkup dari form Product maupun Katalog (G07). */
    public function lingkup(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $in = $request->validate(['items' => 'present|array|max:60', 'items.*' => 'nullable|string|max:1000', 'harga_mulai' => 'nullable|string|max:20']);
        // Harga hanya diproses bila dikirim, sehingga klien lama yang hanya mengirim lingkup tidak menghapus harga.
        $harga = $request->has('harga_mulai') ? ProductLingkup::hargaMulai($in['harga_mulai']) : false;
        $changed = DB::transaction(function () use ($id, $in, $harga) {
            $paket = DB::table('paket_produk')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            $lingkup = ProductLingkup::save($paket, $in['items']);
            $price = $harga === false ? false : ProductLingkup::saveHargaMulai($paket, $harga);

            return $lingkup || $price;
        });

        return back()->with('success', $changed ? 'Lingkup dan harga tersimpan.' : 'Tidak ada perubahan.');
    }

    private function columns(array $row): array
    {
        $out = [];
        foreach (self::FIELDS as $key => $column) {
            $out[$column] = (int) $row[$key];
        }

        return $out;
    }

    private function activeDuplicate(array $row, ?int $exceptId): ?object
    {
        return DB::table('paket_produk')->where('aktif', 1)
            ->where('id_jenis_produk', (int) $row['produk'])->where('id_jenis_bidang', (int) $row['bidang'])
            ->where('id_varian_pelaksanaan', (int) $row['varian'])->where('id_jenis_sertifikasi', (int) $row['sertifikasi'])
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->lockForUpdate()->first();
    }

    /** Atribut baru harus aktif; atribut lama yang tidak diubah boleh nonaktif. */
    private function assertActiveAttributes(array $row, ?int $index, array &$errors, ?array $old = null): void
    {
        $tables = ['produk' => 'ms_jenis_produk', 'bidang' => 'ms_jenis_bidang', 'varian' => 'ms_varian_pelaksanaan', 'sertifikasi' => 'ms_jenis_sertifikasi'];
        foreach ($tables as $key => $table) {
            $record = DB::table($table)->where('id', (int) $row[$key])->first();
            $unchanged = $old && (int) $old[self::FIELDS[$key]] === (int) $row[$key];
            if (! $record || (! $record->aktif && ! $unchanged)) {
                $errors[$index === null ? $key : "rows.$index"] = self::LABEL[$key].' tidak ditemukan atau nonaktif.';
            }
        }
    }
}

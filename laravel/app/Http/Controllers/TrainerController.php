<?php

namespace App\Http\Controllers;

use App\Support\CodeGenerator;
use App\Support\QuotationPdf;
use App\Support\ServerDataTable;
use App\Support\TrainerCvPdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Master Trainer (FR-44..47, FR-59). Tidak dihapus, hanya Aktifkan/Nonaktifkan. Lampiran disimpan di jalur dokumen privat. */
class TrainerController extends Controller
{
    private const MENU = 'master_trainer';

    /** Daftar riwayat: tabel => [kunci form, kolom pertama (wajib bila baris terisi), kolom lain => panjang maks]. */
    private const LISTS = [
        'ms_trainer_pelatihan' => ['pelatihan', 'judul', ['judul' => 255, 'penyelenggara' => 150, 'waktu' => 50]],
        'ms_trainer_pengalaman_kerja' => ['pengalaman_kerja', 'jabatan', ['jabatan' => 150, 'institusi' => 150, 'tahun' => 50]],
        'ms_trainer_pengalaman_mengajar' => ['pengalaman_mengajar', 'topik', ['topik' => 255, 'tempat' => 150, 'tahun' => 50]],
    ];

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:all,on,off']);
        $count = fn (string $t) => "(SELECT COUNT(*) FROM $t x WHERE x.id_trainer = t.id)";
        if ($request->route('datatable')) {
            $base = DB::table('ms_trainer as t')
                ->orderBy('t.id')
                ->select('t.id', 't.kode', 't.nama', 't.no_telp', 't.bidang_keahlian', 't.aktif', 't.id_user',
                    DB::raw($count('ms_trainer_pelatihan').' AS n_pelatihan'), DB::raw($count('ms_trainer_pengalaman_kerja').' AS n_kerja'),
                    DB::raw($count('ms_trainer_pengalaman_mengajar').' AS n_mengajar'), DB::raw($count('ms_trainer_lampiran').' AS n_lampiran'),
                    DB::raw('(SELECT COUNT(*) FROM penjualan_program pj WHERE pj.id_trainer = t.id) AS n_penjualan'));
            $query = (clone $base)
                ->when(($f['status'] ?? 'all') === 'on', fn ($q) => $q->where('t.aktif', 1))
                ->when(($f['status'] ?? 'all') === 'off', fn ($q) => $q->where('t.aktif', 0));

            return ServerDataTable::response($request, $base, $query, [null, 'kode', 'nama', 'bidang_keahlian', null, 'n_penjualan', 'aktif', null], ['id', 'kode', 'nama', 'no_telp', 'bidang_keahlian', 'aktif', 'id_user', 'n_pelatihan', 'n_kerja', 'n_mengajar', 'n_lampiran', 'n_penjualan'], 'id', ['kode', 'nama', 'no_telp', 'bidang_keahlian'], null);
        }

        return Inertia::render('Master/Trainer', [
            'filters' => ['q' => $f['q'] ?? '', 'status' => $f['status'] ?? 'all'],
            'users' => DB::table('users')->where('st_aktif', 1)->where('deleted', 0)->orderBy('nm_lengkap')->get(['id_user', 'nm_lengkap', 'username']),
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage'])],
        ]);
    }

    /** Seluruh data satu trainer untuk form Edit dan Lihat CV (FR-47). */
    public function show(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $t = DB::table('ms_trainer')->where('id', $id)->first() ?? abort(404);
        $list = fn (string $table) => DB::table($table)->where('id_trainer', $id)->orderBy('urutan')->get();

        return response()->json(['trainer' => $t, 'pelatihan' => $list('ms_trainer_pelatihan'), 'pengalaman_kerja' => $list('ms_trainer_pengalaman_kerja'),
            'pengalaman_mengajar' => $list('ms_trainer_pengalaman_mengajar'),
            'lampiran' => $list('ms_trainer_lampiran')->map(fn ($l) => ['id' => $l->id, 'dokumen' => $l->dokumen, 'tahun' => $l->tahun, 'ada_file' => (bool) $l->file_path])]);
    }

    /** CV Trainer sebagai PDF A4 dengan lampiran tersisip (FR-47); hak Lihat, dicatat sebagai unduhan. */
    public function cvPdf(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $t = DB::table('ms_trainer')->where('id', $id)->first() ?? abort(404);
        write_log('Master Trainer', 'Unduh', 'Cetak CV Trainer '.$t->kode, null, 'ms_trainer', $id, $t->kode);

        return QuotationPdf::response(TrainerCvPdf::make($t), TrainerCvPdf::filename($t));
    }

    private function rules(): array
    {
        $rules = [
            'nama' => 'required|string|max:150', 'tempat_lahir' => 'nullable|string|max:100', 'tgl_lahir' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'alamat' => 'nullable|string|max:2000', 'no_telp' => 'nullable|string|max:50', 'pend_smu' => 'nullable|string|max:255', 'pend_diploma' => 'nullable|string|max:255',
            'pend_sarjana' => 'nullable|string|max:255', 'pend_pascasarjana' => 'nullable|string|max:255', 'bidang_keahlian' => 'nullable|string|max:4000',
            'pengalaman_lain' => 'nullable|string|max:4000', 'cv_tempat' => 'nullable|string|max:100', 'cv_tanggal' => 'nullable|date_format:Y-m-d',
            'nama_ttd' => 'nullable|string|max:150', 'id_user' => 'nullable|integer',
            'lampiran' => 'nullable|array|max:50', 'lampiran.*.dokumen' => 'nullable|string|max:255', 'lampiran.*.tahun' => 'nullable|string|max:20',
            'lampiran.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', 'lampiran.*.file_id' => 'nullable|integer',
        ];
        foreach (self::LISTS as [$key, , $cols]) {
            $rules[$key] = 'nullable|array|max:100';
            foreach ($cols as $col => $max) {
                $rules["$key.*.$col"] = 'nullable|string|max:'.$max;
            }
        }

        return $rules;
    }

    private function validated(Request $request, ?int $trainerId): array
    {
        $in = $request->validate($this->rules(), ['nama.required' => 'Nama Lengkap wajib diisi.', 'tgl_lahir.before_or_equal' => 'Tanggal Lahir tidak boleh di masa depan.', 'lampiran.*.file.mimes' => 'Lampiran harus PDF, JPG, JPEG, atau PNG.', 'lampiran.*.file.max' => 'Lampiran maksimal 5 MB.']);
        foreach (self::LISTS as [$key, $first]) {
            foreach ($in[$key] ?? [] as $i => $row) {
                $filled = collect($row)->contains(fn ($v) => trim((string) $v) !== '');
                if ($filled && trim((string) ($row[$first] ?? '')) === '') {
                    throw ValidationException::withMessages(["$key.$i.$first" => 'Kolom pertama wajib diisi pada baris yang terisi.']);
                }
            }
        }
        if (! empty($in['id_user'])) {
            $ok = DB::table('users')->where('id_user', $in['id_user'])->where('st_aktif', 1)->where('deleted', 0)->exists();
            $taken = DB::table('ms_trainer')->where('id_user', $in['id_user'])->when($trainerId, fn ($q) => $q->where('id', '!=', $trainerId))->exists();
            if (! $ok || $taken) {
                throw ValidationException::withMessages(['id_user' => $ok ? 'Akun ini sudah menjadi akun trainer lain.' : 'Akun login tidak ditemukan atau nonaktif.']);
            }
        }

        return $in;
    }

    private function header(array $in): array
    {
        $keahlian = array_values(array_filter(array_map('trim', preg_split('/[\r\n;]+/', (string) ($in['bidang_keahlian'] ?? ''))), 'strlen'));
        $t = fn ($k) => trim((string) ($in[$k] ?? ''));

        return ['nama' => $t('nama'), 'tempat_lahir' => $t('tempat_lahir'), 'tgl_lahir' => $t('tgl_lahir') ?: null, 'alamat' => $t('alamat'), 'no_telp' => $t('no_telp'),
            'pend_smu' => $t('pend_smu'), 'pend_diploma' => $t('pend_diploma'), 'pend_sarjana' => $t('pend_sarjana'), 'pend_pascasarjana' => $t('pend_pascasarjana'),
            'bidang_keahlian' => implode("\n", $keahlian), 'pengalaman_lain' => $t('pengalaman_lain'), 'cv_tempat' => $t('cv_tempat'), 'cv_tanggal' => $t('cv_tanggal') ?: null,
            'nama_ttd' => $t('nama_ttd'), 'id_user' => ! empty($in['id_user']) ? (int) $in['id_user'] : null];
    }

    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $in = $this->validated($request, null);
        $newFiles = [];
        try {
            $kode = DB::transaction(function () use ($in, $request, &$newFiles) {
                $kode = CodeGenerator::next('ms_trainer', config('labmania.code_prefix.trainer'));
                $header = ['kode' => $kode] + $this->header($in) + ['aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
                $id = DB::table('ms_trainer')->insertGetId($header);
                $this->writeChildren($id, $in, $request, [], $newFiles);
                write_log('Master Trainer', 'Tambah', 'Tambah Trainer '.$kode, $header, 'ms_trainer', $id, $kode);

                return $kode;
            });
        } catch (\Throwable $e) {
            $this->discard($newFiles);
            throw $e;
        }

        return back()->with('success', 'Trainer '.$kode.' ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $in = $this->validated($request, $id);
        $newFiles = [];
        $obsolete = [];
        try {
            DB::transaction(function () use ($in, $request, $id, &$newFiles, &$obsolete) {
                $old = (array) (DB::table('ms_trainer')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
                $header = $this->header($in) + ['updated_by' => Auth::id(), 'updated_date' => now()];
                DB::table('ms_trainer')->where('id', $id)->update($header);
                $oldLampiran = DB::table('ms_trainer_lampiran')->where('id_trainer', $id)->get()->keyBy('id');
                $keep = $this->writeChildren($id, $in, $request, $oldLampiran->all(), $newFiles);
                $obsolete = $oldLampiran->pluck('file_path')->filter()->diff($keep)->all();
                write_log('Master Trainer', 'Edit', 'Update Trainer '.$old['kode'], ['old' => $old, 'new' => array_merge($old, $header)], 'ms_trainer', $id, $old['kode']);
            });
        } catch (\Throwable $e) {
            $this->discard($newFiles);
            throw $e;
        }
        $this->discard($obsolete);

        return back()->with('success', 'Trainer diperbarui.');
    }

    /** Ganti seluruh riwayat dan lampiran; kembalikan daftar nama berkas yang dipertahankan. Panggil di dalam transaksi. */
    private function writeChildren(int $id, array $in, Request $request, array $oldLampiran, array &$newFiles): array
    {
        foreach (self::LISTS as $table => [$key, $first, $cols]) {
            DB::table($table)->where('id_trainer', $id)->delete();
            $n = 1;
            foreach ($in[$key] ?? [] as $row) {
                if (trim((string) ($row[$first] ?? '')) === '') {
                    continue;
                }
                DB::table($table)->insert(['id_trainer' => $id, 'urutan' => $n++] + array_map(fn ($col) => trim((string) ($row[$col] ?? '')), array_combine(array_keys($cols), array_keys($cols))));
            }
        }
        DB::table('ms_trainer_lampiran')->where('id_trainer', $id)->delete();
        $keep = [];
        $n = 1;
        foreach ($in['lampiran'] ?? [] as $i => $row) {
            if (trim((string) ($row['dokumen'] ?? '')) === '') {
                continue;
            }
            $file = null;
            $upload = $request->file("lampiran.$i.file");
            if ($upload instanceof UploadedFile) {
                $file = $this->store_file($upload);
                $newFiles[] = $file;
            } elseif (! empty($row['file_id']) && isset($oldLampiran[(int) $row['file_id']])) {
                // Berkas lama hanya dipertahankan bila milik trainer ini; nilai dari klien tidak dipercaya.
                $file = $oldLampiran[(int) $row['file_id']]->file_path;
            }
            if ($file) {
                $keep[] = $file;
            }
            DB::table('ms_trainer_lampiran')->insert(['id_trainer' => $id, 'urutan' => $n++, 'dokumen' => trim($row['dokumen']), 'tahun' => trim((string) ($row['tahun'] ?? '')), 'file_path' => $file, 'created_date' => now()]);
        }

        return $keep;
    }

    private function directory(): string
    {
        $dir = rtrim((string) config('labmania.document_root'), '/\\').DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'trainers';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function store_file(UploadedFile $file): string
    {
        $name = bin2hex(random_bytes(16)).'.'.strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $file->move($this->directory(), $name);

        return $name;
    }

    /** Hapus hanya berkas di root privat baru; berkas di root legacy CI3 tidak pernah dihapus dari sini. */
    private function discard(array $names): void
    {
        foreach ($names as $name) {
            $path = $this->directory().DIRECTORY_SEPARATOR.basename((string) $name);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function toggle(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        DB::transaction(function () use ($id) {
            $old = (array) (DB::table('ms_trainer')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
            $aktif = $old['aktif'] ? 0 : 1;
            DB::table('ms_trainer')->where('id', $id)->update(['aktif' => $aktif, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            $action = $aktif ? 'Aktifkan' : 'Nonaktifkan';
            write_log('Master Trainer', $action, $action.' Trainer '.$old['kode'], ['old' => $old, 'new' => array_merge($old, ['aktif' => $aktif])], 'ms_trainer', $id, $old['kode']);
        });

        return back()->with('success', 'Status Trainer diperbarui.');
    }
}

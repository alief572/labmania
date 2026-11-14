<?php

namespace App\Http\Controllers;

use App\Support\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Master Peserta (FR-15..17). Hapus = soft delete (deleted_date) dan diblokir bila peserta sudah Deal. */
class PesertaController extends Controller
{
    private const MENU = 'master_data.peserta';

    /** Field wajib untuk status "lengkap" (kolom => label). Peserta dari Deal boleh belum lengkap sampai diedit. */
    public const WAJIB = ['nama' => 'Nama Peserta', 'gender' => 'Gender', 'instansi' => 'Instansi/Perusahaan', 'id_jenis_bidang' => 'Bidang', 'id_prov' => 'Provinsi', 'id_kab' => 'Kota/Kabupaten', 'no_telp' => 'No Telp/WhatsApp', 'email' => 'Email'];

    private function belumSql(): string
    {
        return '('.implode(' OR ', array_map(fn ($k) => "(p.$k IS NULL OR TRIM(p.$k) = '')", array_keys(self::WAJIB))).')';
    }

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $request->validate(['q' => 'nullable|string|max:100', 'kelengkapan' => 'nullable|in:all,lengkap,belum']);
        $rows = DB::table('ms_peserta as p')->leftJoin('ms_jenis_bidang as jb', 'jb.id', '=', 'p.id_jenis_bidang')->whereNull('p.deleted_date')
            ->when(($f['q'] ?? null) !== null, fn ($q) => $q->where(fn ($w) => $w->where('p.kode', 'like', '%'.$f['q'].'%')->orWhere('p.nama', 'like', '%'.$f['q'].'%')
                ->orWhere('p.instansi', 'like', '%'.$f['q'].'%')->orWhere('p.nik', 'like', '%'.$f['q'].'%')->orWhere('jb.nama', 'like', '%'.$f['q'].'%')
                ->orWhere('p.kabupaten', 'like', '%'.$f['q'].'%')->orWhere('p.provinsi', 'like', '%'.$f['q'].'%')))
            ->when(($f['kelengkapan'] ?? 'all') === 'belum', fn ($q) => $q->whereRaw($this->belumSql()))
            ->when(($f['kelengkapan'] ?? 'all') === 'lengkap', fn ($q) => $q->whereRaw('NOT '.$this->belumSql()))
            ->orderBy('p.id')
            ->select('p.*', 'jb.nama as nama_bidang', DB::raw('(SELECT q.nomor FROM quotation_peserta qp JOIN quotation q ON q.id = qp.id_quotation WHERE qp.id_peserta = p.id ORDER BY qp.id LIMIT 1) AS asal_deal'))
            ->paginate(25)->withQueryString();
        $rows->through(function ($r) {
            $r->kurang = array_values(array_filter(self::WAJIB, fn ($label, $k) => trim((string) ($r->$k ?? '')) === '', ARRAY_FILTER_USE_BOTH));

            return $r;
        });

        return Inertia::render('Master/Peserta', [
            'rows' => $rows, 'filters' => ['q' => $f['q'] ?? '', 'kelengkapan' => $f['kelengkapan'] ?? 'all'],
            'bidang' => DB::table('ms_jenis_bidang')->where('aktif', 1)->orderBy('nama')->get(['id', 'nama']),
            'companies' => $this->activeCompanies(),
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage']), 'delete' => Gate::allows('menu', [self::MENU, 'Delete'])],
        ]);
    }

    private function activeCompanies()
    {
        return DB::table('master_customer')->whereNull('deleted_date')
            ->where(fn ($q) => $q->whereNull('activation')->orWhereNotIn('activation', ['nonaktif', 'inactive']))
            ->orderBy('id_customer')->get(['id_customer', 'name_customer', 'npwp']);
    }

    private function data(Request $request): array
    {
        // Spasi dan tanda hubung dibuang sebelum validasi (sama dengan CI3): "0812-3456-7890" sah.
        $request->merge(['no_telp' => preg_replace('/[\s-]/', '', (string) $request->input('no_telp'))]);
        $in = $request->validate([
            'nama' => 'required|string|max:100', 'gender' => ['required', Rule::in(['L', 'P'])], 'instansi' => 'nullable|string|max:150',
            'bidang' => ['required', 'integer', Rule::exists('ms_jenis_bidang', 'id')], 'id_prov' => 'required|string|max:20', 'provinsi' => 'nullable|string|max:100',
            'id_kab' => 'required|string|max:20', 'kabupaten' => 'nullable|string|max:100',
            'no_telp' => ['required', 'regex:/^\+?\d{9,15}$/'], 'email' => 'required|email|max:150', 'nik' => 'nullable|string|max:20',
            'npwp_instansi' => 'nullable|string|max:30', 'id_customer' => 'nullable|string|max:20',
        ], [
            'nama.required' => 'Nama Peserta wajib diisi.', 'gender.required' => 'Gender wajib diisi.', 'gender.in' => 'Gender wajib diisi.', 'bidang.required' => 'Bidang wajib diisi.',
            'id_prov.required' => 'Provinsi wajib diisi.', 'id_kab.required' => 'Kota/Kabupaten wajib diisi.', 'no_telp.required' => 'No Telp/WhatsApp wajib diisi.',
            'no_telp.regex' => 'Nomor hanya angka, 9–15 digit.', 'email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.', 'nik.max' => 'NIK maksimal 20 karakter.',
        ]);
        $instansi = trim((string) ($in['instansi'] ?? ''));
        $npwp = trim((string) ($in['npwp_instansi'] ?? ''));
        // Company dipilih: Instansi dan NPWP Instansi mengikuti Company (FR-16); server yang menentukan.
        if (! empty($in['id_customer'])) {
            $co = DB::table('master_customer')->where('id_customer', $in['id_customer'])->whereNull('deleted_date')->first();
            if (! $co) {
                throw ValidationException::withMessages(['id_customer' => 'Company tidak ditemukan.']);
            }
            $instansi = $co->name_customer;
            $npwp = (string) $co->npwp;
        }
        if ($instansi === '') {
            throw ValidationException::withMessages(['instansi' => 'Instansi/Perusahaan wajib diisi.']);
        }

        return [
            'nama' => trim($in['nama']), 'gender' => $in['gender'], 'instansi' => $instansi, 'id_jenis_bidang' => (int) $in['bidang'],
            'id_prov' => $in['id_prov'], 'provinsi' => $in['provinsi'] ?? null, 'id_kab' => $in['id_kab'], 'kabupaten' => $in['kabupaten'] ?? null,
            'no_telp' => $in['no_telp'], 'email' => trim($in['email']), 'npwp_instansi' => $npwp,
            'nik' => trim((string) ($in['nik'] ?? '')) !== '' ? trim($in['nik']) : null, 'id_customer' => $in['id_customer'] ?? null,
        ];
    }

    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $data = $this->data($request);
        $kode = DB::transaction(function () use ($data) {
            $kode = CodeGenerator::next('ms_peserta', config('labmania.code_prefix.peserta'));
            $row = ['kode' => $kode] + $data + ['aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
            $id = DB::table('ms_peserta')->insertGetId($row);
            write_log('Master Peserta', 'Tambah', 'Tambah Peserta '.$kode, $row, 'ms_peserta', $id, $kode);

            return $kode;
        });

        return back()->with('success', 'Peserta '.$kode.' ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $data = $this->data($request);
        DB::transaction(function () use ($data, $id) {
            $old = (array) (DB::table('ms_peserta')->where('id', $id)->whereNull('deleted_date')->lockForUpdate()->first() ?? abort(404));
            $data += ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('ms_peserta')->where('id', $id)->update($data);
            write_log('Master Peserta', 'Edit', 'Update Peserta '.$old['kode'], ['old' => $old, 'new' => array_merge($old, $data)], 'ms_peserta', $id, $old['kode']);
        });

        return back()->with('success', 'Peserta diperbarui.');
    }

    public function destroy(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Delete']);
        DB::transaction(function () use ($id) {
            $old = (array) (DB::table('ms_peserta')->where('id', $id)->whereNull('deleted_date')->lockForUpdate()->first() ?? abort(404));
            $used = DB::table('quotation_peserta as qp')->leftJoin('quotation as q', 'q.id', '=', 'qp.id_quotation')->where('qp.id_peserta', $id)->value('q.nomor');
            if ($used !== null || DB::table('quotation_peserta')->where('id_peserta', $id)->exists()) {
                throw ValidationException::withMessages(['hapus' => 'Peserta '.$old['kode'].' sudah Deal di Quotation '.($used ?? '—').', tidak dapat dihapus.']);
            }
            DB::table('ms_peserta')->where('id', $id)->update(['deleted_by' => Auth::id(), 'deleted_date' => now()]);
            write_log('Master Peserta', 'Hapus', 'Hapus Peserta '.$old['kode'], $old, 'ms_peserta', $id, $old['kode']);
        });

        return back()->with('success', 'Peserta dihapus.');
    }
}

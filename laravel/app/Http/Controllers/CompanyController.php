<?php

namespace App\Http\Controllers;

use App\Support\CodeGenerator;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Master Company = tabel master_customer (FR-30..32). NPWP wajib saat simpan (FR-31, JPT-41);
 * data lama tanpa NPWP tidak diubah dan baru diminta saat diedit. Hapus = soft delete, ditolak bila dipakai.
 */
class CompanyController extends Controller
{
    private const MENU = 'master_data.company';

    /** Nilai activation lama yang berarti nonaktif; NULL/aktif/active dianggap aktif. */
    private const NONAKTIF = ['nonaktif', 'inactive'];

    public static function isActive(?string $activation): bool
    {
        return ! in_array($activation, self::NONAKTIF, true);
    }

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $request->validate(['q' => 'nullable|string|max:100', 'jenis' => ['nullable', Rule::in(config('labmania.jenis_instansi'))]]);
        if ($request->route('datatable')) {
            $base = DB::table('master_customer')->whereNull('deleted_date')
                ->orderBy('id_customer')->select('*', DB::raw("CASE WHEN activation IN ('nonaktif', 'inactive') THEN 0 ELSE 1 END AS aktif"));
            $query = (clone $base)
                ->when(($f['jenis'] ?? null) !== null, fn ($q) => $q->where('jenis_instansi', $f['jenis']));

            return ServerDataTable::response($request, $base, $query, [null, 'id_customer', 'name_customer', 'jenis_instansi', 'kota', 'aktif', null], ['id_customer', 'name_customer', 'jenis_instansi', 'address_office', 'id_prov', 'provinsi', 'id_city', 'kota', 'telephone', 'email', 'pic_name', 'pic_jabatan', 'pic_hp', 'pic_email', 'npwp', 'perlakuan_ppn', 'aktif'], 'id_customer', ['id_customer', 'name_customer', 'jenis_instansi', 'kota', 'provinsi', 'pic_name'], function ($r) {
                $r->aktif = self::isActive($r->activation) ? 1 : 0;

                return $r;
            });
        }

        return Inertia::render('Master/Company', [
            'filters' => ['q' => $f['q'] ?? '', 'jenis' => $f['jenis'] ?? ''], 'jenis' => config('labmania.jenis_instansi'),
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage']), 'delete' => Gate::allows('menu', [self::MENU, 'Delete'])],
        ]);
    }

    private function data(Request $request): array
    {
        $request->merge(['telp' => preg_replace('/[\s-]/', '', (string) $request->input('telp')), 'picTelp' => preg_replace('/[\s-]/', '', (string) $request->input('picTelp'))]);
        $in = $request->validate([
            'nama' => 'required|string|max:150', 'jenis' => ['required', Rule::in(config('labmania.jenis_instansi'))], 'alamat' => 'required|string|max:255',
            'id_prov' => 'required|string|max:20', 'provinsi' => 'nullable|string|max:100', 'id_kab' => 'required|string|max:20', 'kabupaten' => 'nullable|string|max:100',
            'telp' => ['required', 'regex:/^\+?\d{9,15}$/'], 'email' => 'required|email|max:150', 'picNama' => 'required|string|max:100', 'picJabatan' => 'required|string|max:100',
            'picTelp' => ['required', 'regex:/^\+?\d{9,15}$/'], 'picEmail' => 'required|email|max:150', 'npwp' => 'required|string|max:40',
            'perlakuan_ppn' => 'required|in:PKP,WAPU,KB',
        ], [
            'nama.required' => 'Nama Company wajib diisi.', 'jenis.required' => 'Jenis Instansi wajib diisi.', 'jenis.in' => 'Jenis Instansi tidak valid.', 'alamat.required' => 'Alamat Lengkap wajib diisi.',
            'id_prov.required' => 'Provinsi wajib diisi.', 'id_kab.required' => 'Kota/Kabupaten wajib diisi.', 'telp.required' => 'Telepon Kantor wajib diisi.', 'telp.regex' => 'Nomor hanya angka, 9–15 digit.',
            'email.required' => 'Email Kantor wajib diisi.', 'email.email' => 'Format email tidak valid.', 'picNama.required' => 'Nama PIC wajib diisi.', 'picJabatan.required' => 'Jabatan PIC wajib diisi.',
            'picTelp.required' => 'Telp/WA PIC wajib diisi.', 'picTelp.regex' => 'Nomor hanya angka, 9–15 digit.', 'picEmail.required' => 'Email PIC wajib diisi.', 'picEmail.email' => 'Format email tidak valid.',
            'npwp.required' => 'NPWP Instansi wajib diisi.',
        ]);
        $npwp = preg_replace('/[.\-\s]/', '', $in['npwp']);
        if (! preg_match('/^(\d{15}|\d{16})$/', $npwp)) {
            throw ValidationException::withMessages(['npwp' => 'NPWP Instansi 15 atau 16 digit angka.']);
        }

        return [
            'name_customer' => trim($in['nama']), 'jenis_instansi' => $in['jenis'], 'address_office' => trim($in['alamat']),
            'id_prov' => $in['id_prov'], 'provinsi' => $in['provinsi'] ?? null, 'id_city' => $in['id_kab'], 'kota' => $in['kabupaten'] ?? null,
            'telephone' => $in['telp'], 'email' => trim($in['email']),
            'pic_name' => trim($in['picNama']), 'pic_jabatan' => trim($in['picJabatan']), 'pic_hp' => $in['picTelp'], 'pic_email' => trim($in['picEmail']),
            'npwp' => $npwp, 'perlakuan_ppn' => $in['perlakuan_ppn'],
        ];
    }

    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $data = $this->data($request);
        $kode = DB::transaction(function () use ($data) {
            $kode = CodeGenerator::next('master_customer', config('labmania.code_prefix.company'), 'id_customer');
            $row = ['id_customer' => $kode] + $data + ['activation' => 'aktif', 'created_by' => Auth::id(), 'created_on' => now()];
            DB::table('master_customer')->insert($row);
            write_log('Master Company', 'Tambah', 'Tambah Company '.$kode, $row, 'master_customer', null, $kode);

            return $kode;
        });

        return back()->with('success', 'Company '.$kode.' ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $data = $this->data($request);
        DB::transaction(function () use ($data, $id) {
            $old = (array) (DB::table('master_customer')->where('id_customer', $id)->whereNull('deleted_date')->lockForUpdate()->first() ?? abort(404));
            $data += ['modified_by' => Auth::id(), 'modified_on' => now()];
            DB::table('master_customer')->where('id_customer', $id)->update($data);
            write_log('Master Company', 'Edit', 'Update Company '.$id, ['old' => $old, 'new' => array_merge($old, $data)], 'master_customer', null, $id);
        });

        return back()->with('success', 'Company diperbarui.');
    }

    public function toggle(string $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        DB::transaction(function () use ($id) {
            $old = (array) (DB::table('master_customer')->where('id_customer', $id)->whereNull('deleted_date')->lockForUpdate()->first() ?? abort(404));
            $new = self::isActive($old['activation']) ? 'nonaktif' : 'aktif';
            $upd = ['activation' => $new, 'modified_by' => Auth::id(), 'modified_on' => now()];
            DB::table('master_customer')->where('id_customer', $id)->update($upd);
            $action = $new === 'aktif' ? 'Aktifkan' : 'Nonaktifkan';
            write_log('Master Company', $action, $action.' Company '.$id, ['old' => $old, 'new' => array_merge($old, $upd)], 'master_customer', null, $id);
        });

        return back()->with('success', 'Status Company diperbarui.');
    }

    /** Ditolak bila dipakai Penjualan Program atau Quotation ([USULAN] FR-32; CI3 hanya mengecek Penjualan Program). */
    public function destroy(string $id)
    {
        Gate::authorize('menu', [self::MENU, 'Delete']);
        DB::transaction(function () use ($id) {
            $old = (array) (DB::table('master_customer')->where('id_customer', $id)->whereNull('deleted_date')->lockForUpdate()->first() ?? abort(404));
            foreach (['penjualan_program' => 'Penjualan Program', 'quotation' => 'Quotation'] as $table => $label) {
                if (DB::table($table)->where('id_customer', $id)->exists()) {
                    throw ValidationException::withMessages(['hapus' => 'Company '.$id.' sudah dipakai pada '.$label.', tidak dapat dihapus.']);
                }
            }
            if (DB::table('ms_peserta')->where('id_customer', $id)->whereNull('deleted_date')->exists()) {
                throw ValidationException::withMessages(['hapus' => 'Company '.$id.' masih terhubung ke Peserta, tidak dapat dihapus.']);
            }
            DB::table('master_customer')->where('id_customer', $id)->update(['deleted_by' => Auth::id(), 'deleted_date' => now()]);
            write_log('Master Company', 'Hapus', 'Hapus Company '.$id, $old, 'master_customer', null, $id);
        });

        return back()->with('success', 'Company dihapus.');
    }
}

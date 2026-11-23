<?php

namespace App\Http\Controllers;

use App\Support\CodeGenerator;
use App\Support\QuotationPdf;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Template Keterangan Quotation (FR-48..50) dan Template Formulir Konfirmasi (FR-51..53). */
class TemplateController extends Controller
{
    private const KATEGORI = ['syarat', 'termasuk', 'tidak', 'dokumen', 'klausul'];

    private const UNTUK = ['semua', 'public', 'b2b'];

    /** Isian Formulir Konfirmasi: kunci => label (urutan tetap). */
    public const KF_FIELDS = [
        'nama' => 'Nama Lengkap Peserta*', 'nik' => 'NIK', 'jabatan' => 'Jabatan', 'instansi' => 'Nama Instansi', 'alamat' => 'Alamat Instansi',
        'hp' => 'Nomor Hp', 'wa' => 'Nomor WhatsApp', 'email' => 'Alamat Email Aktif', 'pengalaman' => 'Masa Pengalaman Bekerja Sesuai Skema',
        'lsp' => 'LSP', 'habis' => 'Tanggal Habis Sertifikat',
    ];

    // ---- Keterangan ----

    public function keterangan(Request $request)
    {
        Gate::authorize('menu', ['master_data.template_keterangan', 'View']);
        $f = $request->validate(['kategori' => ['nullable', Rule::in(self::KATEGORI)], 'status' => 'nullable|in:all,on,off']);
        if ($request->route('datatable')) {
            $base = DB::table('ms_template_keterangan')
                ->orderByRaw("CASE kategori WHEN 'syarat' THEN 1 WHEN 'termasuk' THEN 2 WHEN 'tidak' THEN 3 WHEN 'dokumen' THEN 4 ELSE 5 END")->orderBy('id');
            $query = (clone $base)
                ->when(($f['kategori'] ?? null) !== null, fn ($q) => $q->where('kategori', $f['kategori']))
                ->when(($f['status'] ?? 'all') === 'on', fn ($q) => $q->where('aktif', 1))
                ->when(($f['status'] ?? 'all') === 'off', fn ($q) => $q->where('aktif', 0));

            return ServerDataTable::response($request, $base, $query, [null, 'kode', 'kategori', 'teks', 'berlaku_untuk', 'is_default', 'aktif', null], ['id', 'kode', 'kategori', 'teks', 'berlaku_untuk', 'is_default', 'aktif'], 'id', ['kode', 'kategori', 'teks', 'berlaku_untuk'], null);
        }

        return Inertia::render('Master/TemplateKeterangan', [
            'filters' => ['kategori' => $f['kategori'] ?? '', 'status' => $f['status'] ?? 'all'],
            'kategori' => self::KATEGORI, 'untuk' => self::UNTUK,
            'can' => ['add' => Gate::allows('menu', ['master_data.template_keterangan', 'Add']), 'manage' => Gate::allows('menu', ['master_data.template_keterangan', 'Manage'])],
        ]);
    }

    private function keteranganData(Request $request): array
    {
        $in = $request->validate([
            'kategori' => ['required', Rule::in(self::KATEGORI)], 'teks' => 'required|string|max:500',
            'berlaku_untuk' => ['required', Rule::in(self::UNTUK)], 'is_default' => 'sometimes|boolean',
        ], ['kategori.required' => 'Kategori wajib dipilih.', 'teks.required' => 'Teks wajib diisi.', 'teks.max' => 'Teks maksimal 500 karakter.']);

        return ['kategori' => $in['kategori'], 'teks' => trim($in['teks']), 'berlaku_untuk' => $in['berlaku_untuk'], 'is_default' => ! empty($in['is_default']) ? 1 : 0];
    }

    public function storeKeterangan(Request $request)
    {
        Gate::authorize('menu', ['master_data.template_keterangan', 'Add']);
        $data = $this->keteranganData($request);
        $kode = DB::transaction(function () use ($data) {
            $kode = CodeGenerator::next('ms_template_keterangan', config('labmania.code_prefix.template_keterangan'));
            $row = ['kode' => $kode] + $data + ['aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
            $id = DB::table('ms_template_keterangan')->insertGetId($row);
            write_log('Template Keterangan', 'Tambah', 'Tambah Template '.$kode, $row, 'ms_template_keterangan', $id, $kode);

            return $kode;
        });

        return back()->with('success', 'Butir '.$kode.' ditambahkan.');
    }

    public function updateKeterangan(Request $request, int $id)
    {
        Gate::authorize('menu', ['master_data.template_keterangan', 'Manage']);
        $data = $this->keteranganData($request);
        DB::transaction(function () use ($data, $id) {
            $old = (array) (DB::table('ms_template_keterangan')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
            $data += ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('ms_template_keterangan')->where('id', $id)->update($data);
            write_log('Template Keterangan', 'Edit', 'Update Template '.$old['kode'], ['old' => $old, 'new' => array_merge($old, $data)], 'ms_template_keterangan', $id, $old['kode']);
        });

        return back()->with('success', 'Butir diperbarui.');
    }

    public function toggleKeterangan(int $id)
    {
        return $this->toggle('ms_template_keterangan', 'Template Keterangan', 'master_data.template_keterangan', $id);
    }

    // ---- Formulir Konfirmasi ----

    public function konfirmasi(Request $request)
    {
        Gate::authorize('menu', ['master_data.template_konfirmasi', 'View']);
        if ($request->route('datatable')) {
            $base = DB::table('ms_template_konfirmasi as k')->leftJoin('ms_jenis_produk as jp', 'jp.id', '=', 'k.id_jenis_produk')
                ->orderBy('k.id')->select('k.*', 'jp.nama as nama_produk');
            $status = $request->validate(['status' => 'nullable|in:all,on,off'])['status'] ?? 'all';
            $query = (clone $base)
                ->when($status === 'on', fn ($q) => $q->where('k.aktif', 1))
                ->when($status === 'off', fn ($q) => $q->where('k.aktif', 0));

            return ServerDataTable::response($request, $base, $query, [null, 'kode', 'nama', 'nama_produk', null, null, 'aktif', null], ['id', 'kode', 'nama', 'label_kegiatan', 'id_jenis_produk', 'nama_produk', 'isian', 'pernyataan', 'catatan', 'aktif'], 'id', ['kode', 'nama', 'label_kegiatan', 'nama_produk'], function ($r) {
                $r->isian = json_decode((string) $r->isian, true) ?: [];

                return $r;
            });
        }

        return Inertia::render('Master/TemplateKonfirmasi', [
            'fields' => self::KF_FIELDS, 'produk' => DB::table('ms_jenis_produk')->orderBy('nama')->get(['id', 'nama']),
            'can' => ['add' => Gate::allows('menu', ['master_data.template_konfirmasi', 'Add']), 'manage' => Gate::allows('menu', ['master_data.template_konfirmasi', 'Manage'])],
        ]);
    }

    private function konfirmasiData(Request $request): array
    {
        $in = $request->validate([
            'nama' => 'required|string|max:80', 'label_kegiatan' => 'required|string|max:80', 'pernyataan' => 'required|string|max:2000',
            'isian' => 'required|array|min:1', 'isian.*' => ['string', Rule::in(array_keys(self::KF_FIELDS))],
            'id_jenis_produk' => 'nullable|integer|exists:ms_jenis_produk,id', 'catatan' => 'nullable|string|max:2000',
        ], ['nama.required' => 'Nama template wajib diisi.', 'label_kegiatan.required' => 'Label kegiatan wajib diisi.', 'pernyataan.required' => 'Kalimat pernyataan wajib diisi.', 'isian.required' => 'Pilih minimal 1 isian.', 'isian.min' => 'Pilih minimal 1 isian.']);
        $isian = array_values(array_filter(array_keys(self::KF_FIELDS), fn ($k) => in_array($k, $in['isian'], true)));
        $catatan = implode("\n", array_filter(array_map('trim', preg_split('/\R/u', (string) ($in['catatan'] ?? '')))));

        return ['nama' => trim($in['nama']), 'label_kegiatan' => trim($in['label_kegiatan']), 'id_jenis_produk' => $in['id_jenis_produk'] ?? null,
            'isian' => json_encode($isian), 'pernyataan' => trim($in['pernyataan']), 'catatan' => $catatan];
    }

    public function storeKonfirmasi(Request $request)
    {
        Gate::authorize('menu', ['master_data.template_konfirmasi', 'Add']);
        $data = $this->konfirmasiData($request);
        $kode = DB::transaction(function () use ($data) {
            $kode = CodeGenerator::next('ms_template_konfirmasi', config('labmania.code_prefix.template_konfirmasi'));
            $row = ['kode' => $kode] + $data + ['aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
            $id = DB::table('ms_template_konfirmasi')->insertGetId($row);
            write_log('Template Konfirmasi', 'Tambah', 'Tambah Formulir Konfirmasi '.$kode, $row, 'ms_template_konfirmasi', $id, $kode);

            return $kode;
        });

        return back()->with('success', 'Template '.$kode.' ditambahkan.');
    }

    public function updateKonfirmasi(Request $request, int $id)
    {
        Gate::authorize('menu', ['master_data.template_konfirmasi', 'Manage']);
        $data = $this->konfirmasiData($request);
        DB::transaction(function () use ($data, $id) {
            $old = (array) (DB::table('ms_template_konfirmasi')->where('id', $id)->lockForUpdate()->first() ?? abort(404));
            $data += ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('ms_template_konfirmasi')->where('id', $id)->update($data);
            write_log('Template Konfirmasi', 'Edit', 'Update Formulir Konfirmasi '.$old['kode'], ['old' => $old, 'new' => array_merge($old, $data)], 'ms_template_konfirmasi', $id, $old['kode']);
        });

        return back()->with('success', 'Template diperbarui.');
    }

    public function toggleKonfirmasi(int $id)
    {
        return $this->toggle('ms_template_konfirmasi', 'Template Formulir Konfirmasi', 'master_data.template_konfirmasi', $id);
    }

    /**
     * Contoh formulir: Penjualan Program Final terbaru dibaca lewat master_program.id_paket_produk. Preview tidak menyimpan data atau mengambil identitas peserta.
     */
    public function contoh(int $id)
    {
        Gate::authorize('menu', ['master_data.template_konfirmasi', 'View']);
        $t = DB::table('ms_template_konfirmasi')->where('id', $id)->first() ?? abort(404);
        $pj = DB::table('penjualan_program as pj')->where('pj.status', 'final')->orderByDesc('pj.id')->first();
        $judul = $pj ? DB::table('master_program as mpp')->join('paket_produk as pp', 'pp.id', '=', 'mpp.id_paket_produk')
            ->where('mpp.id', $pj->id_master_program)->value('pp.judul') : null;

        return response()->json([
            'template' => ['nama' => $t->nama, 'label_kegiatan' => $t->label_kegiatan, 'isian' => json_decode((string) $t->isian, true) ?: [], 'pernyataan' => $t->pernyataan, 'catatan' => $t->catatan],
            'fields' => self::KF_FIELDS, 'program' => $judul,
            'document' => QuotationPdf::confirmationPreview((array) $t, $pj, $judul),
        ]);
    }

    private function toggle(string $table, string $module, string $menu, int $id)
    {
        Gate::authorize('menu', [$menu, 'Manage']);
        DB::transaction(function () use ($table, $module, $id) {
            $old = (array) (DB::table($table)->where('id', $id)->lockForUpdate()->first() ?? abort(404));
            $aktif = $old['aktif'] ? 0 : 1;
            DB::table($table)->where('id', $id)->update(['aktif' => $aktif, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            $action = $aktif ? 'Aktifkan' : 'Nonaktifkan';
            write_log($module, $action, $action.' '.$module.' '.$old['kode'], ['old' => $old, 'new' => array_merge($old, ['aktif' => $aktif])], $table, $id, $old['kode']);
        });

        return back()->with('success', 'Status diperbarui.');
    }
}

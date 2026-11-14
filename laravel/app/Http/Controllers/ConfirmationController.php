<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use App\Support\QuotationFiles;
use App\Support\QuotationPdf;
use App\Support\Reconfirmation;
use App\Support\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConfirmationController extends Controller
{
    private function quotation(string $token, bool $lock = false): object
    {
        abort_unless(preg_match('/^[a-f0-9]{32}$/', $token), 404);

        return DB::table('quotation')->where('link_token', $token)->when($lock, fn ($q) => $q->lockForUpdate())->first() ?? abort(404);
    }

    public function show(Request $request, string $token)
    {
        if (! DB::table('quotation')->where('link_token', $token)->exists() && DB::table('quotation_revisi')->where('link_token_hash', hash('sha256', $token))->exists()) {
            return Inertia::render('ConfirmationClosed', ['message' => 'Link ini sudah diganti. Jadwal program berubah. Hubungi Sales untuk link terbaru.'])->toResponse($request)->setStatusCode(410);
        }
        $q = $this->quotation($token);
        $p = Sales::program($q->id_penjualan);
        if ($q->revisi && ! $request->boolean('daftar_baru') && Reconfirmation::targets($q)->exists()) {
            return redirect('/konfirmasi/'.$token.'/ulang');
        }
        $closed = Sales::linkState($q, $p);
        $company = $q->id_customer ? DB::table('master_customer')->where('id_customer', $q->id_customer)->first() : null;
        $sales = DB::table('ms_user_karyawan')->where('id', $q->id_sales)->first();

        return Inertia::render('Confirmation', ['token' => $token, 'closed' => $closed,
            'info' => ['nomor' => Sales::display($q), 'judul' => Sales::title($p->id_master_program), 'jenis' => $p->jenis, 'tgl_mulai' => $p->tgl_mulai, 'tgl_selesai' => $p->tgl_selesai, 'waktu' => $p->waktu, 'lokasi' => $p->lokasi, 'kuota' => $q->kuota_peserta,
                'sisa' => max(0, $q->kuota_peserta - DB::table('quotation_peserta')->where('id_quotation', $q->id)->whereIn('status', ['menunggu', 'deal'])->count()), 'kepada' => $company?->name_customer ?? $q->kepada_nama,
                'berlaku_sampai' => $q->berlaku_sampai, 'pernyataan' => $q->konfirmasi_pernyataan, 'fields' => json_decode($q->konfirmasi_isian ?: '[]', true), 'kolom' => json_decode($q->kolom_peserta_b2b ?: '[]', true),
                'paket_maks' => $q->paket_maks_peserta, 'tambahan' => $q->tambahan_per_peserta, 'sales_nama' => $sales?->nama, 'sales_telp' => $sales?->telp],
            'fieldLabels' => TemplateController::KF_FIELDS,
            'company' => $closed ? null : ['nama_perusahaan' => $company?->name_customer ?? '', 'alamat_perusahaan' => $company?->address_office ?? '', 'pic_nama' => $company?->pic_name ?? '', 'pic_jabatan' => $company?->pic_jabatan ?? '', 'pic_hp' => $company?->pic_hp ?? '', 'pic_email' => $company?->pic_email ?? ''],
        ]);
    }

    private function participantRules(string $prefix = ''): array
    {
        $rules = ['nama' => 'required|string|max:150', 'nik' => 'nullable|string|max:20', 'jabatan' => 'nullable|string|max:100', 'instansi' => 'nullable|string|max:150', 'alamat' => 'nullable|string|max:3000',
            'hp' => ['nullable', 'regex:/^[0-9]{9,15}$/'], 'wa' => ['nullable', 'regex:/^[0-9]{9,15}$/'], 'email' => 'nullable|email|max:100', 'pengalaman' => 'nullable|string|max:100', 'lsp' => 'nullable|string|max:100', 'habis' => 'nullable|date_format:Y-m-d'];

        return array_combine(array_map(fn ($k) => $prefix.$k, array_keys($rules)), array_values($rules));
    }

    private function validated(Request $r, object $q, object $p): array
    {
        if ($p->jenis === 'public') {
            $in = $r->validate($this->participantRules() + ['ex' => 'nullable|array|max:30', 'ex.*' => 'nullable|string|max:1000']);
            $allowed = json_decode($q->konfirmasi_isian ?: '[]', true) ?: ['nama'];
            $in['ex'] = array_intersect_key($in['ex'] ?? [], array_flip(array_diff($allowed, array_keys(TemplateController::KF_FIELDS))));

            return array_intersect_key($in, array_flip([...$allowed, 'nama', 'ex']));
        }
        if (is_array($r->input('peserta'))) {
            $r->merge(['peserta' => array_values(array_filter($r->input('peserta'), fn ($row) => ! is_array($row) || collect($row)->contains(fn ($v) => ! is_scalar($v) && $v !== null || trim((string) $v) !== '')))]);
        }
        // ponytail: 1.000 baris menjaga ukuran satu request/PDF; pindah ke impor bertahap bila daftar lebih besar diperlukan.
        $in = $r->validate(['nama_perusahaan' => 'required|string|max:150', 'alamat_perusahaan' => 'required|string|max:3000', 'pic_nama' => 'required|string|max:100', 'pic_jabatan' => 'required|string|max:100',
            'pic_hp' => ['required', 'regex:/^[0-9]{9,15}$/'], 'pic_email' => 'required|email|max:100', 'peserta' => 'required|array|min:10|max:1000', ...$this->participantRules('peserta.*.')]);
        $columns = json_decode($q->kolom_peserta_b2b ?: '[]', true) ?: [];
        $in['peserta'] = array_map(fn ($row) => array_intersect_key($row, array_flip(['nama', ...$columns])), $in['peserta']);

        return $in;
    }

    private function rowData(array $in): array
    {
        $map = ['nama' => 'nama', 'nik' => 'nik', 'jabatan' => 'jabatan', 'instansi' => 'instansi', 'alamat' => 'alamat_instansi', 'hp' => 'no_hp', 'wa' => 'no_wa', 'email' => 'email', 'pengalaman' => 'pengalaman', 'lsp' => 'lsp', 'habis' => 'tgl_habis_sertifikat'];
        $out = [];
        foreach ($map as $source => $dest) {
            $out[$dest] = isset($in[$source]) && trim((string) $in[$source]) !== '' ? trim((string) $in[$source]) : null;
        }
        $out['isian_tambahan'] = json_encode($in['ex'] ?? [], JSON_THROW_ON_ERROR);

        return $out;
    }

    public function preview(Request $r, string $token)
    {
        $q = $this->quotation($token);
        $p = Sales::program($q->id_penjualan);
        abort_if(Sales::linkState($q, $p) !== null, 410, 'Formulir sudah ditutup.');
        $in = $this->validated($r, $q, $p);

        return QuotationPdf::response(QuotationPdf::confirmation($q, $p, $in), 'Surat-Konfirmasi.pdf');
    }

    public function store(Request $r, string $token)
    {
        $read = $this->quotation($token);
        $p = Sales::program($read->id_penjualan);
        $r->validate(['setuju' => 'accepted', 'surat' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png']);
        $in = $this->validated($r, $read, $p);
        $file = QuotationFiles::store($r->file('surat'));
        try {
            DB::transaction(function () use ($token, $p, $in, $file) {
                $program = Sales::program($p->id, true);
                $q = $this->quotation($token, true);
                abort_if(Sales::linkState($q, $program) !== null, 410, 'Formulir sudah ditutup; hubungi Sales.');
                $base = ['id_quotation' => $q->id, 'tgl_isi' => now(), 'status' => 'menunggu', 'input_by' => null];
                if ($program->jenis === 'b2b') {
                    $company = array_intersect_key($in, array_flip(['nama_perusahaan', 'alamat_perusahaan', 'pic_nama', 'pic_jabatan', 'pic_hp', 'pic_email']));
                    DB::table('quotation_konfirmasi_company')->insert($company + ['id_quotation' => $q->id, 'revisi' => $q->revisi, 'jumlah_peserta' => count($in['peserta']), 'surat_konfirmasi' => $file['path'], 'surat_konfirmasi_nama' => $file['name'], 'tgl_kirim' => now(), 'created_date' => now()]);
                    foreach ($in['peserta'] as $row) {
                        DB::table('quotation_peserta')->insert($base + ['sumber' => 'company'] + array_replace($this->rowData($row), ['instansi' => $in['nama_perusahaan']]));
                    }
                    $n = count($in['peserta']);
                } else {
                    $data = $this->rowData($in);
                    if ($q->tujuan === 'company') {
                        $data['instansi'] = DB::table('master_customer')->where('id_customer', $q->id_customer)->value('name_customer');
                    }
                    DB::table('quotation_peserta')->insert($base + ['sumber' => 'link', 'surat_konfirmasi' => $file['path'], 'surat_konfirmasi_nama' => $file['name']] + $data);
                    $n = 1;
                }
                Audit::publicConfirmation('Tambah', 'Konfirmasi kehadiran '.$q->nomor, ['jumlah_peserta' => $n, 'sumber' => $program->jenis === 'b2b' ? 'company' : 'link', 'nama_berkas' => $file['name']], $q->id, $q->nomor);
            });
        } catch (\Throwable $e) {
            QuotationFiles::discard([$file]);
            throw $e;
        }

        return redirect('/konfirmasi/'.$token)->with('success', 'Konfirmasi diterima. Berkas: '.$file['name'].'.');
    }
}

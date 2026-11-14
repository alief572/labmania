<?php

namespace App\Http\Controllers;

use App\Support\QuotationFiles;
use App\Support\QuotationPdf;
use App\Support\Reconfirmation;
use App\Support\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReconfirmationController extends Controller
{
    private function source(string $token): array
    {
        $q = DB::table('quotation')->where('link_token', $token)->first() ?? abort(410, 'Link ini sudah diganti. Jadwal program berubah.');

        return [$q, Sales::program($q->id_penjualan)];
    }

    public function show(string $token)
    {
        [$q,$p] = $this->source($token);
        $deadline = Reconfirmation::deadline($p);
        $people = Reconfirmation::targets($q)->get()->map(function ($a) use ($q, $p) {
            $i = Reconfirmation::invoice($q, $p->jenis === 'b2b' ? null : $a->id_quotation_peserta);

            return ['id' => $a->id_quotation_peserta, 'nama' => $a->nama, 'keputusan' => $a->keputusan, 'answered' => $a->tgl_isi !== null, 'paid' => $i?->tanggal_bayar !== null];
        });
        $old = DB::table('penjualan_program_reschedule')->where('id_penjualan', $p->id)->whereColumn('tgl_mulai_lama', '!=', 'tgl_mulai_baru')->orderByDesc('id')->first();

        return Inertia::render('Reconfirmation', ['token' => $token, 'revisi' => $q->revisi, 'nomor' => Sales::display($q), 'judul' => Sales::title($p->id_master_program), 'jenis' => $p->jenis,
            'oldDate' => $old?->tgl_mulai_lama, 'newDate' => $p->tgl_mulai, 'endDate' => $p->tgl_selesai, 'deadline' => $deadline->format('d-m-Y H:i').' WIB', 'people' => $people,
            'closed' => $p->closed_date !== null || $q->status === 'batal' || ! $q->revisi || now('Asia/Jakarta')->gte($deadline), 'canRegister' => Sales::linkState($q, $p) === null]);
    }

    public function preview(Request $r, string $token)
    {
        [$q,$p] = $this->source($token);
        abort_if($p->closed_date || $q->status === 'batal' || now('Asia/Jakarta')->gte(Reconfirmation::deadline($p)), 410, 'Konfirmasi ulang ditutup.');
        $in = $r->validate(['revisi' => 'required|integer', 'id_peserta' => 'nullable|integer', 'peserta' => 'nullable|array', 'peserta.*' => 'integer']);
        abort_unless((int) $q->revisi === (int) $in['revisi'], 422);
        $ids = Reconfirmation::targets($q)->pluck('a.id_quotation_peserta')->all();
        if ($p->jenis === 'b2b') {
            $selected = array_values(array_unique($in['peserta'] ?? []));
            abort_unless(count($selected) >= 10 && ! array_diff($selected, $ids), 422, 'Pilih minimal 10 nama terdaftar.');
            $co = DB::table('quotation_konfirmasi_company')->where('id_quotation', $q->id)->orderByDesc('revisi')->first() ?? abort(422, 'Konfirmasi Company perlu rekonsiliasi.');
            $data = (array) $co;
            $data['peserta'] = DB::table('quotation_peserta')->whereIn('id', $selected)->orderBy('id')->get()->map(fn ($x) => ['nama' => $x->nama, 'jabatan' => $x->jabatan, 'email' => $x->email, 'wa' => $x->no_wa, 'nik' => $x->nik])->all();
        } else {
            abort_unless(in_array((int) ($in['id_peserta'] ?? 0), $ids), 404);
            $x = DB::table('quotation_peserta')->where('id', $in['id_peserta'])->first();
            $data = ['nama' => $x->nama, 'jabatan' => $x->jabatan, 'email' => $x->email, 'wa' => $x->no_wa, 'hp' => $x->no_hp, 'nik' => $x->nik, 'instansi' => $x->instansi, 'alamat' => $x->alamat_instansi, 'pengalaman' => $x->pengalaman, 'lsp' => $x->lsp, 'habis' => $x->tgl_habis_sertifikat, 'ex' => json_decode($x->isian_tambahan ?: '[]', true)];
        }

        return QuotationPdf::response(QuotationPdf::confirmation($q, $p, $data), 'Konfirmasi-jadwal-baru.pdf');
    }

    public function store(Request $r, string $token)
    {
        $in = $r->validate(['revisi' => 'required|integer|min:1', 'id_peserta' => 'nullable|integer', 'peserta' => 'nullable|array', 'peserta.*' => 'integer',
            'keputusan' => 'required|in:pindah_jadwal,batal,refund', 'catatan' => 'nullable|string|max:500', 'setuju' => 'accepted',
            'surat' => 'required_if:keputusan,pindah_jadwal|nullable|file|max:5120|mimes:pdf,jpg,jpeg,png']);
        $file = $r->hasFile('surat') ? QuotationFiles::store($r->file('surat')) : null;
        try {
            Reconfirmation::answer($token, $in, $file);
        } catch (\Throwable $e) {
            if ($file) {
                QuotationFiles::discard([$file]);
            }throw $e;
        }

        return redirect('/konfirmasi/'.$token.'/ulang')->with('success', 'Jawaban diterima. Perubahan jawaban melalui Sales.');
    }

    public function process(Request $r, int $id)
    {
        Gate::authorize('menu', ['quotation_program', 'Manage']);
        $in = $r->validate(['revisi' => 'required|integer|min:1', 'keputusan' => 'required|in:pindah_jadwal,batal,refund,tidak', 'alasan' => 'nullable|string|max:500']);
        Reconfirmation::process($id, $in);

        return back()->with('success', 'Konfirmasi ulang diproses.');
    }
}

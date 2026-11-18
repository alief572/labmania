<?php

namespace App\Http\Controllers;

use App\Support\QuotationFiles;
use App\Support\QuotationPdf;
use App\Support\Reschedule;
use App\Support\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RescheduleController extends Controller
{
    public function show(int $id)
    {
        Gate::authorize('menu', ['penjualan_program', 'View']);
        $p = Sales::program($id);
        $quotes = DB::table('quotation')->where('id_penjualan', $id)->where('status', '!=', 'batal')->pluck('id');
        $history = DB::table('penjualan_program_reschedule as r')->leftJoin('users as u', 'u.id_user', '=', 'r.created_by')->where('r.id_penjualan', $id)->orderByDesc('r.id')
            ->get(['r.id', 'r.tgl_mulai_lama', 'r.tgl_selesai_lama', 'r.tgl_mulai_baru', 'r.tgl_selesai_baru', 'r.id_trainer_lama', 'r.id_trainer_baru', 'r.id_asisten_lama', 'r.id_asisten_baru', 'r.alasan', 'r.created_date', 'u.nm_lengkap']);
        foreach ($history as $h) {
            $h->evidence = DB::table('penjualan_program_reschedule_evidence')->where('id_reschedule', $h->id)->get(['id', 'nama_file']);
            $h->revisions = DB::table('quotation_revisi')->where('id_reschedule', $h->id)->get(['id', 'id_quotation', 'revisi']);
            $snap = json_decode(DB::table('penjualan_program_reschedule')->where('id', $h->id)->value('snapshot'), true, 512, JSON_THROW_ON_ERROR);
            $h->surat_lama = $snap['surat_lama'] ?? null;
            $h->surat_baru = $snap['surat_baru'] ?? null;
            $h->team = $snap['team'] ?? null;
        }

        return Inertia::render('Sales/Reschedule', ['program' => $p, 'history' => $history, 'version' => Reschedule::version($p), 'requestId' => (string) Str::uuid(),
            'reason' => Reschedule::reason($p), 'canManage' => Gate::allows('menu', ['penjualan_program', 'Manage']),
            'minimum' => today()->addDays(7)->toDateString(), 'trainers' => DB::table('ms_trainer')->where('aktif', 1)->get(['id', 'nama']),
            'assistants' => DB::table('ms_user_karyawan')->where('aktif', 1)->get(['id', 'nama']),
            'impact' => ['quotation' => $quotes->count(), 'peserta' => DB::table('quotation_peserta')->whereIn('id_quotation', $quotes)->whereIn('status', ['deal', 'menunggu'])->count(),
                'so' => DB::table('sales_order')->where('id_penjualan', $id)->where('status', '!=', 'batal')->count(), 'surat' => DB::table('surat_kerja_trainer')->where('id_penjualan', $id)->where('status', 'berlaku')->count(),
                'lunas' => DB::table('invoice_peserta as x')->join('invoice as i', 'i.id', '=', 'x.id_invoice')->where('i.id_penjualan', $id)->where('i.status', 'invoiced')->whereNotNull('i.tanggal_bayar')->count()]]);
    }

    public function store(Request $r, int $id)
    {
        Gate::authorize('menu', ['penjualan_program', 'Manage']);
        $input = $r->validate(['request_id' => 'required|uuid', 'version' => 'required|string|size:64', 'tgl_mulai' => 'required|date_format:Y-m-d|after_or_equal:'.today()->addDays(7)->toDateString(),
            'id_trainer' => 'required|integer|exists:ms_trainer,id,aktif,1', 'id_asisten' => 'required|integer|exists:ms_user_karyawan,id,aktif,1',
            'alasan' => 'required|string|max:500', 'evidence' => 'required|array|min:1', 'evidence.*' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx']);
        unset($input['evidence']);
        $files = [];
        try {
            foreach ($r->file('evidence') as $file) {
                $files[] = QuotationFiles::store($file, true, 'uploads/penjualan_program_reschedule/');
            }
            $rid = Reschedule::save($id, $input, $files);
        } catch (\Throwable $e) {
            QuotationFiles::discard($files);
            throw $e;
        }
        $used = DB::table('penjualan_program_reschedule_evidence')->where('id_reschedule', $rid)->pluck('path_file')->all();
        QuotationFiles::discard(array_values(array_filter($files, fn ($f) => ! in_array($f['path'], $used, true))));

        return redirect('/penjualan-program/'.$id.'/reschedule')->with('success', 'Reschedule dan seluruh dokumen terkait tersimpan. Kirim ulang PDF dan link kepada customer.');
    }

    public function historyPdf(int $id, int $history)
    {
        Gate::authorize('menu', ['penjualan_program', 'View']);
        $row = DB::table('penjualan_program_reschedule')->where('id', $history)->where('id_penjualan', $id)->first() ?? abort(404);
        $snapshot = json_decode($row->snapshot, true, 512, JSON_THROW_ON_ERROR);
        write_log('Penjualan Program', 'Cetak', 'Cetak versi lama program #'.$id, ['reschedule_id' => $history], 'penjualan_program', $id, null);

        return QuotationPdf::response(base64_decode($snapshot['pdf_program'], true), 'Program-riwayat-'.$history.'.pdf');
    }

    public function revision(int $id, int $revision, string $kind)
    {
        Gate::authorize('menu', [$kind === 'so' ? 'sales_order' : 'quotation_program', 'View']);
        abort_unless(in_array($kind, ['so', 'quotation'], true), 404);
        $row = DB::table('quotation_revisi')->where('id_quotation', $id)->where('revisi', $revision)->first() ?? abort(404);
        $snapshot = json_decode($row->snapshot, true, 512, JSON_THROW_ON_ERROR);
        abort_unless(isset($snapshot['pdf_'.$kind]), 404);
        write_log('Quotation', 'Cetak', 'Cetak revisi lama quotation #'.$id, ['revisi' => $revision], 'quotation', $id, null);

        return QuotationPdf::response(base64_decode($snapshot['pdf_'.$kind], true), 'Riwayat-'.$kind.'-'.$revision.'.pdf');
    }
}

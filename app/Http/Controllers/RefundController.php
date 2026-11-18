<?php

namespace App\Http\Controllers;

use App\Support\QuotationFiles;
use App\Support\Refund;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/** Modul Refund: Daftar Refund (refund_daftar) dan Approval Refund (refund_approval). RF-FR-01 sampai RF-FR-07. */
class RefundController extends Controller
{
    private const LIST = 'refund_daftar';

    private const APPROVAL = 'refund_approval';

    /** Urutan Daftar Refund (RF-FR-03): ditolak (perlu diperbaiki) dan disetujui (perlu ditransfer) di atas, lalu menunggu, lalu selesai. */
    private const ORDER = "CASE status WHEN 'ditolak' THEN 0 WHEN 'disetujui' THEN 1 WHEN 'menunggu' THEN 2 ELSE 3 END";

    private function page(Request $r, bool $approval)
    {
        Gate::authorize('menu', [$approval ? self::APPROVAL : self::LIST, 'View']);
        ServerDataTable::prepare($r);
        $f = $r->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:menunggu,disetujui,ditolak,selesai', 'tab' => 'nullable|in:menunggu,riwayat']);
        $tab = $f['tab'] ?? 'menunggu';
        $base = fn () => DB::table('sales_order_refund');
        ServerDataTable::scope($r, $base());
        // Filter status hanya di Daftar Refund. when() menerima nilai kondisi sebagai argumen callback, jadi status dipisah dari !$approval.
        $status = $approval ? null : ($f['status'] ?? null);
        $query = $base()->when($status, fn ($q, $v) => $q->where('status', $v))
            ->when($approval, fn ($q) => $tab === 'menunggu' ? $q->where('status', 'menunggu') : $q->whereExists(fn ($x) => $x->selectRaw('1')->from('sales_order_refund_riwayat as h')->whereColumn('h.id_refund', 'sales_order_refund.id')->whereIn('h.jenis', ['setujui', 'tolak'])))
            ->when(($f['q'] ?? '') !== '', fn ($q) => $q->where(fn ($w) => $w->where('nomor', 'like', '%'.$f['q'].'%')->orWhere('snapshot', 'like', '%'.$f['q'].'%')->orWhere('alasan', 'like', '%'.$f['q'].'%')));
        $rows = $query->orderByRaw($approval ? 'id DESC' : self::ORDER.', id DESC')
            ->pipe(fn ($builder) => ServerDataTable::page($r, $builder, [null, 'nomor', null, null, 'nominal_tagihan', 'status', null], 'id'));
        $ids = $rows->pluck('id')->all();
        $latest = DB::table('sales_order_refund_riwayat')->whereIn('id_refund', $ids)->whereIn('jenis', ['setujui', 'tolak'])->orderBy('id')->get()->groupBy('id_refund')->map->last();
        $users = DB::table('users')->whereIn('id_user', $latest->pluck('oleh')->merge($rows->pluck('selesai_by'))->filter()->unique())->pluck('nm_lengkap', 'id_user');
        $rows->through(function ($row) use ($latest, $users) {
            $s = json_decode($row->snapshot, true);
            $row->siapa = $s['peserta'] ?: $s['company'];
            $row->invoice = $s['invoice'];
            $row->so = $s['nomor_so'];
            $row->nomor_quotation = $s['nomor_quotation'] ?? null;
            $a = $latest->get($row->id);
            $row->keputusan = $a ? ['jenis' => $a->jenis, 'oleh' => $users[$a->oleh] ?? null, 'waktu' => $a->waktu, 'catatan' => $a->catatan] : null;
            $row->selesai_oleh = $users[$row->selesai_by] ?? null;
            unset($row->snapshot, $row->no_rekening, $row->bukti_transfer);

            return $row;
        });
        $counts = DB::table('sales_order_refund')->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status');
        $props = ['rows' => $rows, 'filters' => $f, 'tab' => $tab, 'mode' => $approval ? 'approval' : 'daftar',
            'counts' => ['menunggu' => (int) ($counts['menunggu'] ?? 0), 'disetujui' => (int) ($counts['disetujui'] ?? 0), 'ditolak' => (int) ($counts['ditolak'] ?? 0), 'selesai' => (int) ($counts['selesai'] ?? 0),
                'riwayat' => DB::table('sales_order_refund_riwayat')->whereIn('jenis', ['setujui', 'tolak'])->distinct()->count('id_refund')],
            'can' => ['proses' => Gate::allows('menu', [self::LIST, 'Manage']), 'ajukan' => Gate::allows('menu', [self::LIST, 'Add']), 'approve' => Gate::allows('menu', [self::APPROVAL, 'Approve']),
                'approval' => Gate::allows('menu', [self::APPROVAL, 'View']), 'daftar' => Gate::allows('menu', [self::LIST, 'View'])]];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Refund/Index', $props);
    }

    public function index(Request $r)
    {
        return $this->page($r, false);
    }

    public function approval(Request $r)
    {
        return $this->page($r, true);
    }

    public function show(Request $request, int $id)
    {
        abort_unless(Gate::allows('menu', [self::LIST, 'View']) || Gate::allows('menu', [self::APPROVAL, 'View']), 403);
        $row = DB::table('sales_order_refund')->where('id', $id)->first() ?? abort(404);
        $s = json_decode($row->snapshot, true);
        unset($row->snapshot, $row->bukti_transfer);
        $p = DB::table('sales_order as s')->join('penjualan_program as p', 'p.id', '=', 's.id_penjualan')->where('s.id', $row->id_sales_order)->first(['p.closed_date']);
        $history = DB::table('sales_order_refund_riwayat as h')->leftJoin('users as u', 'u.id_user', '=', 'h.oleh')->where('h.id_refund', $id)->orderBy('h.id')
            ->get(['h.jenis', 'h.catatan', 'h.nominal_lama', 'h.nominal_baru', 'h.alasan_lama', 'h.alasan_baru', 'h.waktu', 'u.nm_lengkap as oleh']);
        $invoice = DB::table('invoice')->where('id', DB::table('sales_order_refund')->where('id', $id)->value('id_invoice'))->first(['piutang']);
        $open = $p->closed_date === null;

        $props = ['refund' => $row, 'snapshot' => $s, 'riwayat' => $history, 'maks' => (string) ($invoice->piutang ?? $row->nominal_tagihan),
            'canManage' => Gate::allows('menu', [self::LIST, 'Manage']) && $open, 'canAjukan' => Gate::allows('menu', [self::LIST, 'Add']) && $open,
            'canApprove' => Gate::allows('menu', [self::APPROVAL, 'Approve']) && $open, 'closed' => ! $open];

        return $request->boolean('modal') ? response()->json($props) : Inertia::render('Refund/Show', $props);
    }

    public function decide(Request $r, int $id)
    {
        Gate::authorize('menu', [self::APPROVAL, 'Approve']);
        $in = $r->validate(['keputusan' => 'required|in:setujui,tolak', 'catatan' => 'nullable|string|max:300']);
        Refund::decide($id, $in['keputusan'], $in['catatan'] ?? null);

        return redirect('/refund/approval')->with('success', $in['keputusan'] === 'setujui' ? 'Refund disetujui dan lanjut ke proses transfer.' : 'Refund ditolak dan kembali ke Sales/Marketing untuk diperbaiki.');
    }

    public function resubmit(Request $r, int $id)
    {
        Gate::authorize('menu', [self::LIST, 'Add']);
        $in = $r->validate(['alasan' => 'required|string|max:500', 'nominal' => ['required', 'regex:/^[0-9]{1,18}$/'], 'catatan' => 'required|string|max:300']);
        Refund::resubmit($id, $in['alasan'], $in['nominal'], $in['catatan']);

        return redirect('/refund')->with('success', 'Refund diajukan ulang ke Approval Refund.');
    }

    public function process(Request $r, int $id)
    {
        Gate::authorize('menu', [self::LIST, 'Manage']);
        $in = $r->validate(['nominal_refund' => ['required', 'regex:/^[0-9]{1,18}$/'], 'bank' => 'required|string|max:50', 'no_rekening' => ['required', 'regex:/^[0-9 -]{3,30}$/'], 'atas_nama' => 'required|string|max:100', 'catatan' => 'nullable|string|max:500', 'bukti' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        unset($in['bukti']);
        $file = QuotationFiles::store($r->file('bukti'), false, 'uploads/refund/');
        try {
            $keep = Refund::finish($id, $in, $file);
        } catch (\Throwable $e) {
            QuotationFiles::discard([$file]);
            throw $e;
        }
        if (! $keep) {
            QuotationFiles::discard([$file]);
        }

        return redirect($r->boolean('modal') ? '/refund' : '/refund/'.$id)->with('success', 'Penyelesaian Refund tercatat.');
    }
}

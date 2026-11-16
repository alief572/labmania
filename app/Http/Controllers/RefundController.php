<?php

namespace App\Http\Controllers;

use App\Support\QuotationFiles;
use App\Support\Refund;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('menu', ['sales_order_refund', 'View']);
        ServerDataTable::prepare($r);
        $f = $r->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:menunggu,selesai']);
        ServerDataTable::scope($r, DB::table('sales_order_refund'));
        $query = DB::table('sales_order_refund')->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when(($f['q'] ?? '') !== '', fn ($q) => $q->where(fn ($w) => $w->where('nomor', 'like', '%'.$f['q'].'%')->orWhere('snapshot', 'like', '%'.$f['q'].'%')));
        $rows = $query->orderByRaw("CASE WHEN status='menunggu' THEN 0 ELSE 1 END")->orderByDesc('id')->pipe(fn ($builder) => ServerDataTable::page($r, $builder, [null, 'nomor', null, null, null, 'nominal_tagihan', 'status', null], 'id'));
        $actors = DB::table('users')->whereIn('id_user', $rows->pluck('selesai_by')->filter()->unique())->pluck('nm_lengkap', 'id_user');
        $rows->through(function ($row) use ($actors) {
            $s = json_decode($row->snapshot, true);
            $row->siapa = $s['peserta'] ?: $s['company'];
            $row->invoice = $s['invoice'];
            $row->so = $s['nomor_so'];
            $row->nomor_quotation = $s['nomor_quotation'] ?? null;
            $row->selesai_oleh = $actors[$row->selesai_by] ?? null;
            unset($row->snapshot,$row->no_rekening,$row->bukti_transfer);

            return $row;
        });

        $props = ['rows' => $rows, 'filters' => $f, 'pending' => DB::table('sales_order_refund')->where('status', 'menunggu')->count(),
            'canManage' => Gate::allows('menu', ['sales_order_refund', 'Manage']),
            'canSalesOrder' => Gate::allows('menu', ['sales_order', 'View']),
            'counts' => Gate::allows('menu', ['sales_order', 'View']) ? ['so' => DB::table('sales_order')->count(), 'surat' => DB::table('surat_kerja_trainer')->count()] : []];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Refund/Index', $props);
    }

    public function show(Request $request, int $id)
    {
        Gate::authorize('menu', ['sales_order_refund', 'View']);
        $row = DB::table('sales_order_refund')->where('id', $id)->first() ?? abort(404);
        $s = json_decode($row->snapshot, true);
        unset($row->snapshot,$row->bukti_transfer);
        $p = DB::table('sales_order as s')->join('penjualan_program as p', 'p.id', '=', 's.id_penjualan')->where('s.id', $row->id_sales_order)->first(['p.closed_date']);

        $props = ['refund' => $row, 'snapshot' => $s, 'canManage' => Gate::allows('menu', ['sales_order_refund', 'Manage']) && ! $p->closed_date, 'closed' => $p->closed_date !== null];
        return $request->boolean('modal') ? response()->json($props) : Inertia::render('Refund/Show', $props);
    }

    public function process(Request $r, int $id)
    {
        Gate::authorize('menu', ['sales_order_refund', 'Manage']);
        $in = $r->validate(['nominal_refund' => ['required', 'regex:/^[0-9]{1,18}$/'], 'bank' => 'required|string|max:50', 'no_rekening' => ['required', 'regex:/^[0-9 -]{3,30}$/'], 'atas_nama' => 'required|string|max:100', 'catatan' => 'nullable|string|max:500', 'bukti' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png']);
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

        return redirect($r->boolean('modal') ? '/sales-order/refund' : '/sales-order/refund/'.$id)->with('success', 'Penyelesaian Refund tercatat.');
    }
}

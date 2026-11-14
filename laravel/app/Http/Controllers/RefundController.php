<?php

namespace App\Http\Controllers;

use App\Support\QuotationFiles;
use App\Support\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('menu', ['sales_order_refund', 'View']);
        $f = $r->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:menunggu,selesai']);
        $query = DB::table('sales_order_refund')->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('nomor', 'like', '%'.$v.'%')->orWhere('snapshot', 'like', '%'.$v.'%')));
        $rows = $query->orderByRaw("CASE WHEN status='menunggu' THEN 0 ELSE 1 END")->orderByDesc('id')->paginate(25)->withQueryString()->through(function ($row) {
            $s = json_decode($row->snapshot, true);
            $row->siapa = $s['peserta'] ?: $s['company'];
            $row->invoice = $s['invoice'];
            $row->so = $s['nomor_so'];
            unset($row->snapshot,$row->no_rekening,$row->bukti_transfer);

            return $row;
        });

        return Inertia::render('Refund/Index', ['rows' => $rows, 'filters' => $f, 'pending' => DB::table('sales_order_refund')->where('status', 'menunggu')->count()]);
    }

    public function show(int $id)
    {
        Gate::authorize('menu', ['sales_order_refund', 'View']);
        $row = DB::table('sales_order_refund')->where('id', $id)->first() ?? abort(404);
        $s = json_decode($row->snapshot, true);
        unset($row->snapshot,$row->bukti_transfer);
        $p = DB::table('sales_order as s')->join('penjualan_program as p', 'p.id', '=', 's.id_penjualan')->where('s.id', $row->id_sales_order)->first(['p.closed_date']);

        return Inertia::render('Refund/Show', ['refund' => $row, 'snapshot' => $s, 'canManage' => Gate::allows('menu', ['sales_order_refund', 'Manage']) && ! $p->closed_date, 'closed' => $p->closed_date !== null]);
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

        return redirect('/sales-order/refund/'.$id)->with('success', 'Penyelesaian Refund tercatat.');
    }
}

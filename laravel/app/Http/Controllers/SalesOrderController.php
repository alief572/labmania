<?php

namespace App\Http\Controllers;

use App\Support\Quotation;
use App\Support\QuotationPdf;
use App\Support\Sales;
use App\Support\SalesOrder;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SalesOrderController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        $f = $r->validate(['tab' => 'nullable|in:so,surat', 'q' => 'nullable|string|max:100', 'jenis' => 'nullable|in:public,b2b', 'tahun' => 'nullable|integer|min:1900|max:2200', 'bulan' => 'nullable|integer|min:1|max:12']);
        $tab = $f['tab'] ?? 'so';
        $query = $tab === 'so' ? DB::table('penjualan_program as p')->whereExists(fn ($q) => $q->selectRaw('1')->from('sales_order as so')->whereColumn('so.id_penjualan', 'p.id'))
            : DB::table('surat_kerja_trainer as st')->join('penjualan_program as p', 'p.id', '=', 'st.id_penjualan');
        Sales::filter($query, $f);
        if (! empty($f['q'])) {
            $term = '%'.$f['q'].'%';
            $query->where(function ($w) use ($term, $tab) {
                $w->where('p.nomor', 'like', $term)->orWhereExists(fn ($q) => $q->selectRaw('1')->from('sales_order as s')->join('quotation as q', 'q.id', '=', 's.id_quotation')->whereColumn('s.id_penjualan', 'p.id')->where(fn ($x) => $x->where('s.nomor', 'like', $term)->orWhere('q.nomor', 'like', $term)->orWhere('q.kepada_nama', 'like', $term)))
                    ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->whereColumn('m.id_master_program', 'p.id_master_program')->where('k.judul', 'like', $term));
                if ($tab === 'surat') {
                    $w->orWhere('st.nomor', 'like', $term)->orWhere('st.snapshot', 'like', $term);
                }
            });
        }
        $rows = $query->select($tab === 'so' ? 'p.*' : ['st.*', 'p.jenis', 'p.nomor as program_nomor'])->orderByDesc($tab === 'so' ? 'p.id' : 'st.id')->paginate(25)->withQueryString();
        $rows->through(function ($p) use ($tab) {
            if ($tab === 'surat') {
                $p->facts = json_decode($p->snapshot ?? '', true);
                $p->peserta_deal = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('q.id_penjualan', $p->id_penjualan)->where('x.status', 'deal')->count();
                unset($p->snapshot);

                return $p;
            }
            $p->judul = Sales::title($p->id_master_program);
            $p->nomor = Sales::display($p);
            $orders = $this->orders($p->id);
            $p->jumlah_so = $orders->count();
            $p->peserta_deal = $orders->sum('jumlah_peserta');
            $p->nilai_so = (string) $orders->reduce(fn ($sum, $so) => $sum->plus($so->calc['grand_total']), BigDecimal::zero());
            $p->statuses = $orders->countBy('status_tampil');

            return $p;
        });

        return Inertia::render('SalesOrder/Index', ['rows' => $rows, 'filters' => $f, 'tab' => $tab,
            'canRefund' => Gate::allows('menu', ['sales_order_refund', 'View']),
            'refundPending' => Gate::allows('menu', ['sales_order_refund', 'View']) ? DB::table('sales_order_refund')->where('status', 'menunggu')->count() : 0,
            'counts' => ['so' => DB::table('sales_order')->count(), 'surat' => DB::table('surat_kerja_trainer')->count()]]);
    }

    private function orders(int $id)
    {
        return DB::table('sales_order')->where('id_penjualan', $id)->orderByDesc('id')->get()->map(function ($so) {
            $q = Quotation::row($so->id_quotation);
            $ps = DB::table('quotation_peserta')->where('id_quotation', $q->id)->where('status', 'deal')->orderBy('id')->pluck('nama');
            $so->nomor_quotation = Sales::display($q);
            $so->nomor = Sales::display($so);
            $so->kepada_nama = $q->kepada_nama;
            $so->tujuan = $q->tujuan;
            $so->up_nama = $q->up_nama;
            $so->jumlah_peserta = $ps->count();
            $so->nama_peserta = $ps->take(2)->implode(', ');
            $so->calc = Quotation::calculate($q, $ps->count());
            $so->invoice = DB::table('invoice')->where('id_sales_order', $so->id)->where('status', 'invoiced')->first(['id', 'nomor']);
            $so->status_tampil = $so->invoice ? 'Invoiced' : ($so->status === 'siap' ? 'Siap invoice' : 'Belum siap invoice');
            $so->siap_nama = $so->siap_by ? DB::table('users')->where('id_user', $so->siap_by)->value('nm_lengkap') : null;

            return $so;
        });
    }

    public function program(int $id)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        $p = Sales::program($id);
        $p->nomor = Sales::display($p);

        return Inertia::render('SalesOrder/Program', ['program' => (array) $p + ['judul' => Sales::title($p->id_master_program), 'name_customer' => $p->id_customer ? DB::table('master_customer')->where('id_customer', $p->id_customer)->value('name_customer') : null], 'rows' => $this->orders($id), 'canManage' => ! $p->closed_date && Gate::allows('menu', ['sales_order', 'Manage'])]);
    }

    public function show(int $id)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        $data = SalesOrder::detail(SalesOrder::row($id));

        return Inertia::render('SalesOrder/Show', $data + ['canManage' => ! $data['closed'] && Gate::allows('menu', ['sales_order', 'Manage']), 'canInvoice' => ! $data['closed'] && Gate::allows('menu', ['invoicing', 'Add'])]);
    }

    public function ready(int $id)
    {
        Gate::authorize('menu', ['sales_order', 'Manage']);
        SalesOrder::ready($id);

        return back()->with('success', 'SO Siap dibuatkan invoice.');
    }

    public function pdf(int $id)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        $data = SalesOrder::detail(SalesOrder::row($id));
        $pdf = QuotationPdf::document('Sales Order '.$data['so']['nomor'], view('pdf.sales-order', $data)->render());
        write_log('Quotation', 'Cetak', 'Cetak '.$data['so']['nomor'], ['sub_module' => 'Sales Order'], 'sales_order', $id, $data['so']['nomor']);

        return QuotationPdf::response($pdf, 'Sales-Order-'.$id.'.pdf');
    }

    public function letters(int $id)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        $so = SalesOrder::row($id);
        abort_unless(Quotation::row($so->id_quotation)->status === 'deal', 404);
        $pdf = QuotationPdf::batch(Sales::program($so->id_penjualan), $so->id_quotation, true);
        write_log('Quotation', 'Cetak', 'Confirmation Letter Deal '.$so->nomor, ['sub_module' => 'Sales Order'], 'sales_order', $id, $so->nomor);

        return QuotationPdf::response($pdf, 'Confirmation-Letter-SO-'.$id.'.pdf');
    }

    public function assignment(int $id)
    {
        Gate::authorize('menu', ['sales_order', 'View']);

        return Inertia::render('SalesOrder/Assignment', SalesOrder::assignment($id));
    }

    public function assignmentPdf(int $id)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        $data = SalesOrder::assignment($id);
        $pdf = QuotationPdf::document('Surat Tugas '.$data['surat']->nomor, view('pdf.assignment', $data)->render(), 'FR/LMI-TR-05/01/Ed.Rev-1.0 · {PAGENO} dari {nbpg} · Berlaku 01/08/2023');
        write_log('Quotation', 'Cetak', 'Cetak Surat Tugas '.$data['surat']->nomor, ['sub_module' => 'Surat Kerja Trainer'], 'surat_kerja_trainer', $id, $data['surat']->nomor);

        return QuotationPdf::response($pdf, 'Surat-Tugas-'.$id.'.pdf');
    }
}

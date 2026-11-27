<?php

namespace App\Http\Controllers;

use App\Support\Invoice;
use App\Support\Quotation;
use App\Support\QuotationPdf;
use App\Support\Sales;
use App\Support\SalesOrder;
use App\Support\ServerDataTable;
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
        ServerDataTable::prepare($r);
        $f = $r->validate(['tab' => 'nullable|in:so,batal,surat', 'q' => 'nullable|string|max:100', 'jenis' => 'nullable|in:public,b2b', 'tahun' => 'nullable|integer|min:1900|max:2200', 'bulan' => 'nullable|integer|min:1|max:12']);
        $tab = $f['tab'] ?? 'so';
        $query = $tab === 'so' ? DB::table('penjualan_program as p')->whereExists(fn ($q) => $q->selectRaw('1')->from('sales_order as so')->whereColumn('so.id_penjualan', 'p.id')->where('so.status', '!=', 'batal'))
            : ($tab === 'batal' ? DB::table('sales_order as so')->join('penjualan_program as p', 'p.id', '=', 'so.id_penjualan')->join('quotation as q', 'q.id', '=', 'so.id_quotation')->where('so.status', 'batal')
                : DB::table('surat_kerja_trainer as st')->join('penjualan_program as p', 'p.id', '=', 'st.id_penjualan'));
        ServerDataTable::scope($r, $query);
        Sales::filter($query, $f);
        // Tab batal mencari per baris SO (di bawah); dua tab lain per Penjualan Program.
        if (($f['q'] ?? '') !== '' && $tab !== 'batal') {
            $term = '%'.$f['q'].'%';
            $query->where(function ($w) use ($term, $tab) {
                $w->where('p.nomor', 'like', $term)->orWhereExists(fn ($q) => $q->selectRaw('1')->from('sales_order as s')->join('quotation as q', 'q.id', '=', 's.id_quotation')->whereColumn('s.id_penjualan', 'p.id')->where(fn ($x) => $x->where('s.nomor', 'like', $term)->orWhere('q.nomor', 'like', $term)->orWhere('q.kepada_nama', 'like', $term)))
                    ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('master_program as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->whereColumn('m.id', 'p.id_master_program')->where('k.judul', 'like', $term));
                if ($tab === 'surat') {
                    $w->orWhere('st.nomor', 'like', $term)->orWhere('st.snapshot', 'like', $term);
                }
            });
        }
        if ($tab === 'batal') {
            if (($f['q'] ?? '') !== '') {
                $term = '%'.$f['q'].'%';
                $query->where(fn ($w) => $w->where('so.nomor', 'like', $term)->orWhere('q.nomor', 'like', $term)->orWhere('p.nomor', 'like', $term)->orWhere('q.kepada_nama', 'like', $term)
                    ->orWhereExists(fn ($x) => $x->selectRaw('1')->from('master_program as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->whereColumn('m.id', 'p.id_master_program')->where('k.judul', 'like', $term)));
            }
            $rows = $query->select('so.id', 'so.nomor', 'so.revisi', 'so.tanggal', 'so.updated_date as batal_date', 'so.batal_sebab', 'so.batal_revisi', 'so.batal_waktu', 'so.batal_peserta', 'so.batal_nilai', 'so.digantikan_oleh', 'p.id as id_penjualan', 'p.nomor as program_nomor', 'p.jenis', 'p.tgl_mulai', 'p.tgl_selesai', 'p.status as program_status', 'q.nomor as quotation_nomor', 'q.kepada_nama', 'q.status as quotation_status')
                ->orderByDesc('so.updated_date')->orderByDesc('so.id')->pipe(fn ($builder) => ServerDataTable::page($r, $builder, [null, 'so.nomor', 'q.nomor', 'p.nomor', 'so.updated_date', null, null, null], 'so.id'));
            $rows->through(function ($so) {
                $so->nomor = Sales::display($so);
                $so->sebab = ($so->batal_sebab ?? null) === 'program' || (($so->batal_sebab ?? null) === null && $so->program_status === 'batal') ? 'Program batal' : 'Reschedule rev'.($so->batal_revisi ?? $so->revisi);
                $so->batal_date = $so->batal_waktu ?? $so->batal_date;
                $next = $so->digantikan_oleh ? DB::table('sales_order')->where('id', $so->digantikan_oleh)->first(['id', 'nomor', 'revisi', 'tanggal']) : null;
                $so->diganti = $next ? ['id' => $next->id, 'nomor' => Sales::display($next), 'tanggal' => $next->tanggal] : null;
                $so->menunggu_deal_ulang = ! $next && ($so->batal_sebab ?? null) === 'reschedule';

                return $so;
            });
        } else {
            $rows = $query->select($tab === 'so' ? 'p.*' : ['st.*', 'p.jenis', 'p.nomor as program_nomor'])->orderByDesc($tab === 'so' ? 'p.id' : 'st.id')->pipe(fn ($builder) => ServerDataTable::page($r, $builder, ($tab === 'so' ? [null, 'p.nomor', null, null, null, null] : [null, 'st.nomor', 'st.status', 'p.nomor', null, null, null]), $tab === 'so' ? 'p.id' : 'st.id'));
        }
        $ids = $rows->pluck($tab === 'so' ? 'id' : 'id_penjualan')->all();
        $titles = $tab === 'so' ? Sales::titles($rows->pluck('id_master_program')->unique()->all()) : [];
        $deals = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->whereIn('q.id_penjualan', $ids)->where('x.status', 'deal')->selectRaw('q.id_penjualan, COUNT(*) as jumlah')->groupBy('q.id_penjualan')->pluck('jumlah', 'id_penjualan');
        $summaries = [];
        if ($tab === 'so') {
            DB::table('quotation as q')->join('sales_order as so', 'so.id_quotation', '=', 'q.id')->whereIn('so.id_penjualan', $ids)
                ->select('q.*', 'so.status as so_status', 'so.id as so_id')->selectRaw("(SELECT COUNT(*) FROM invoice i WHERE i.id_sales_order = so.id AND i.status = 'invoiced') as n_invoice")
                ->orderBy('q.id')->chunkById(250, function ($quotes) use (&$summaries) {
                    $counts = DB::table('quotation_peserta')->whereIn('id_quotation', $quotes->pluck('id'))->where('status', 'deal')->selectRaw('id_quotation, COUNT(*) as jumlah')->groupBy('id_quotation')->pluck('jumlah', 'id_quotation');
                    foreach ($quotes as $quote) {
                        $id = $quote->id_penjualan;
                        $summary = $summaries[$id] ?? ['jumlah' => 0, 'nilai' => BigDecimal::zero(), 'statuses' => []];
                        $summary['jumlah']++;
                        $summary['nilai'] = $summary['nilai']->plus(Quotation::calculate($quote, (int) ($counts[$quote->id] ?? 0))['grand_total']);
                        $status = $quote->n_invoice > 0 && Invoice::complete($quote, $quote->so_id) ? 'Invoiced' : ($quote->so_status === 'siap' ? 'Siap invoice' : 'Belum siap invoice');
                        $summary['statuses'][$status] = ($summary['statuses'][$status] ?? 0) + 1;
                        $summaries[$id] = $summary;
                    }
                }, 'q.id', 'id');
        }
        $tab === 'batal' ?: $rows->through(function ($p) use ($tab, $titles, $deals, $summaries) {
            $p->peserta_deal = (int) ($deals[$tab === 'so' ? $p->id : $p->id_penjualan] ?? 0);
            if ($tab === 'surat') {
                $p->facts = json_decode($p->snapshot ?? '', true);
                unset($p->snapshot);

                return $p;
            }
            $p->judul = $titles[$p->id_master_program] ?? '';
            $p->nomor = Sales::display($p);
            $summary = $summaries[$p->id] ?? ['jumlah' => 0, 'nilai' => BigDecimal::zero(), 'statuses' => []];
            $p->jumlah_so = $summary['jumlah'];
            $p->nilai_so = (string) $summary['nilai'];
            $p->statuses = $summary['statuses'];

            return $p;
        });

        $props = ['rows' => $rows, 'filters' => $f, 'tab' => $tab,
                                    'counts' => ['so' => DB::table('sales_order')->where('status', '!=', 'batal')->count(), 'batal' => DB::table('sales_order')->where('status', 'batal')->count(), 'surat' => DB::table('surat_kerja_trainer')->count()],
            'years' => DB::table('penjualan_program')->whereNotNull('tgl_mulai')->pluck('tgl_mulai')->map(fn ($d) => (int) substr((string) $d, 0, 4))->unique()->sortDesc()->values()];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('SalesOrder/Index', $props);
    }

    private function orders(int $id, ?array $ids = null)
    {
        $orders = DB::table('sales_order')->where('id_penjualan', $id)->where('status', '!=', 'batal')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderByDesc('id')->get();
        $quotes = DB::table('quotation')->whereIn('id', $orders->pluck('id_quotation'))->get()->keyBy('id');
        $people = DB::table('quotation_peserta')->whereIn('id_quotation', $orders->pluck('id_quotation'))->where('status', 'deal')->orderBy('id')->get(['id_quotation', 'nama'])->groupBy('id_quotation');
        $invoices = DB::table('invoice')->whereIn('id_sales_order', $orders->pluck('id'))->where('status', 'invoiced')->orderBy('id')->get(['id', 'nomor', 'id_sales_order'])->groupBy('id_sales_order');
        $users = DB::table('users')->whereIn('id_user', $orders->pluck('siap_by')->filter())->pluck('nm_lengkap', 'id_user');

        return $orders->map(function ($so) use ($quotes, $people, $invoices, $users) {
            $q = $quotes[$so->id_quotation];
            $ps = $people->get($q->id, collect());
            $so->nomor_quotation = Sales::display($q);
            $so->nomor = Sales::display($so);
            $so->kepada_nama = $q->kepada_nama;
            $so->tujuan = $q->tujuan;
            $so->up_nama = $q->up_nama;
            $so->jumlah_peserta = $ps->count();
            $so->nama_peserta = $ps->take(2)->pluck('nama')->implode(', ');
            $so->calc = Quotation::calculate($q, $ps->count());
            $so->invoice = $invoices->get($so->id)?->first();
            $so->status_tampil = $so->invoice && Invoice::complete($q, $so->id) ? 'Invoiced' : ($so->status === 'siap' ? 'Siap invoice' : 'Belum siap invoice');
            $so->siap_nama = $users[$so->siap_by] ?? null;

            return $so;
        });
    }

    public function program(Request $request, int $id)
    {
        Gate::authorize('menu', ['sales_order', 'View']);
        $p = Sales::program($id);
        $p->nomor = Sales::display($p);

        if ($request->route('datatable')) {
            ServerDataTable::prepare($request);
            $f = $request->validate(['q' => 'nullable|string|max:100']);
            $query = DB::table('sales_order as so')->join('quotation as q', 'q.id', '=', 'so.id_quotation')->where('so.id_penjualan', $id);
            ServerDataTable::scope($request, $query);
            if (($f['q'] ?? '') !== '') {
                $like = '%'.$f['q'].'%';
                $query->where(fn ($w) => $w->where('so.nomor', 'like', $like)->orWhere('q.nomor', 'like', $like)->orWhere('q.kepada_nama', 'like', $like));
            }
            $rows = ServerDataTable::page($request, $query->select('so.*')->orderByDesc('so.id'), [null, 'so.nomor', 'q.nomor', 'q.kepada_nama', null, null, 'so.status', null], 'so.id');
            $details = $this->orders($id, $rows->pluck('id')->all())->keyBy('id');
            $rows->through(fn ($row) => $details[$row->id]);

            return ServerDataTable::result($request, $rows);
        }

        return Inertia::render('SalesOrder/Program', ['program' => (array) $p + ['judul' => Sales::title($p->id_master_program), 'name_customer' => $p->id_customer ? DB::table('master_customer')->where('id_customer', $p->id_customer)->value('name_customer') : null], 'rows' => [], 'canManage' => ! $p->closed_date && Gate::allows('menu', ['sales_order', 'Manage'])]);
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

<?php

namespace App\Http\Controllers;

use App\Support\Coa;
use App\Support\Invoice;
use App\Support\Quotation;
use App\Support\QuotationPdf;
use App\Support\Reconfirmation;
use App\Support\Sales;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    private function filters(Request $r): array
    {
        return $r->validate(['tab' => 'nullable|in:uninvoiced,invoiced', 'q' => 'nullable|string|max:100', 'jenis' => 'nullable|in:public,b2b',
            'tahun' => 'nullable|integer|min:1900|max:2200', 'program' => 'nullable|integer|min:1', 'perlakuan_ppn' => 'nullable|in:PKP,WAPU,KB', 'pembayaran' => 'nullable|in:lunas,belum', 'status' => 'nullable|in:invoiced,batal']);
    }

    private function query(array $f)
    {
        $done = ($f['tab'] ?? 'uninvoiced') === 'invoiced';
        $query = $done ? DB::table('invoice as i')->join('sales_order as s', 's.id', '=', 'i.id_sales_order')->leftJoin('invoice_bukti_setor as b', 'b.id_invoice', '=', 'i.id')
            : Invoice::uninvoiced();
        $query->join('quotation as q', 'q.id', '=', 's.id_quotation')->join('penjualan_program as p', 'p.id', '=', 's.id_penjualan')->leftJoin('ms_user_karyawan as u', 'u.id', '=', 'q.id_sales');
        $query->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('p.jenis', $v))->when($f['program'] ?? null, fn ($q, $v) => $q->where('p.id', $v))
            ->when($f['tahun'] ?? null, fn ($q, $v) => $q->whereYear($done ? 'i.tanggal' : 'q.tanggal', $v));
        if (($f['q'] ?? '') !== '') {
            $term = '%'.$f['q'].'%';
            $query->where(function ($w) use ($term, $done) {
                $w->where('s.nomor', 'like', $term)->orWhere('p.nomor', 'like', $term)->orWhere('q.nomor', 'like', $term)->orWhere('q.kepada_nama', 'like', $term)->orWhere('u.nama', 'like', $term);
                if ($done) {
                    $w->orWhere('i.nomor', 'like', $term)->orWhere('i.snapshot', 'like', $term);
                } else {
                    $w->orWhereExists(fn ($q) => $q->selectRaw('1')->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->whereColumn('m.id_master_program', 'p.id_master_program')->where('k.judul', 'like', $term));
                }
            });
        }
        if ($done) {
            $query->when($f['status'] ?? null, fn ($q, $v) => $q->where('i.status', $v));
            $query->when($f['perlakuan_ppn'] ?? null, fn ($q, $v) => $q->where('i.perlakuan_ppn', $v));
            if (! empty($f['pembayaran'])) {
                $f['pembayaran'] === 'lunas' ? $query->whereNotNull('i.tanggal_bayar') : $query->whereNull('i.tanggal_bayar');
            }
        }

        return $query->select($done ? ['i.*', 'p.closed_date', 'b.id as setor_id', 'b.tanggal as setor_tanggal'] : ['s.*', 'q.kepada_nama', 'q.nomor as nomor_quotation', 'q.tanggal as tanggal_quotation', 'p.nomor as nomor_program', 'p.revisi as revisi_program', 'q.revisi as revisi_quotation', 'p.jenis', 'p.closed_date', 'p.id_master_program', 'p.lokasi', 'p.tgl_mulai', 'p.tgl_selesai', 'u.nama as sales_nama'])->orderByDesc($done ? 'i.id' : 's.id');
    }

    private function listing(object $r, bool $done): object
    {
        if ($done) {
            $s = json_decode($r->snapshot, true, 512, JSON_THROW_ON_ERROR);
            $r->nomor_so = $s['so']['nomor'];
            $r->nomor_quotation = $s['quotation']['nomor'];
            $r->nomor_program = $s['program']['nomor'];
            $r->jenis = $s['program']['jenis'];
            $r->kepada_nama = $s['quotation']['kepada_nama'];
            $r->judul = $s['judul'];
            $r->sales_nama = $s['sales'];
            $r->jumlah_peserta = count($s['peserta']);
            $r->skema_harga = $s['quotation']['skema_harga'];
            $r->tujuan = $s['quotation']['tujuan'];
            $r->tgl_mulai = $s['program']['tgl_mulai'];
            $r->tgl_selesai = $s['program']['tgl_selesai'];
            $r->canManage = $r->status === 'invoiced' && ! $r->closed_date && Gate::allows('menu', ['invoicing', 'Manage']);
            unset($r->snapshot);
        } else {
            $r->nomor_so = Sales::display($r);
            $r->nomor_quotation = Sales::display((object) ['nomor' => $r->nomor_quotation, 'revisi' => $r->revisi_quotation]);
            $r->nomor_program = Sales::display((object) ['nomor' => $r->nomor_program, 'revisi' => $r->revisi_program]);
            $r->judul = Sales::title($r->id_master_program);
            $q = Quotation::row($r->id_quotation);
            $r->skema_harga = $q->skema_harga;
            $r->tujuan = $q->tujuan;
            $r->jumlah_peserta = Quotation::count($q);
            $r->pending = Reconfirmation::pending($q);
            $r->total_tagihan = Quotation::calculate($q, $r->jumlah_peserta)['grand_total'];
        }

        return $r;
    }

    public function index(Request $r)
    {
        Gate::authorize('menu', ['invoicing', 'View']);
        ServerDataTable::prepare($r);
        $f = $this->filters($r);
        $done = ($f['tab'] ?? 'uninvoiced') === 'invoiced';
        ServerDataTable::scope($r, $this->query(['tab' => $done ? 'invoiced' : 'uninvoiced']));
        $rows = $this->query($f)->pipe(fn ($builder) => ServerDataTable::page($r, $builder, ($done ? [null, 'p.nomor', 'i.nomor', null, 'i.perlakuan_ppn', 'i.total_tagihan', 'i.status', null] : [null, 'p.nomor', 's.nomor', 'q.nomor', 'q.kepada_nama', 'p.nomor', 'u.nama', null, null, 's.status', null]), $done ? 'i.id' : 's.id'))->through(fn ($r) => $this->listing($r, $done));

        $choices = ['tab' => $done ? 'invoiced' : 'uninvoiced', 'jenis' => $f['jenis'] ?? null];
        $programs = $this->query($choices)->reorder()->select('p.id', 'p.nomor')->distinct()->orderBy('p.nomor')->get();
        $dateField = $done ? 'i.tanggal' : 'q.tanggal';
        $years = $this->query($choices)->reorder()->select($dateField)->distinct()->pluck($dateField)->filter()->map(fn ($date) => (int) substr($date, 0, 4))->unique()->sortDesc()->values();
        $props = ['rows' => $rows, 'filters' => $f, 'tab' => $done ? 'invoiced' : 'uninvoiced',
            'counts' => ['uninvoiced' => $this->query([])->count(), 'invoiced' => DB::table('invoice')->count()],
            'programs' => $programs, 'years' => $years,
            'canAdd' => Gate::allows('menu', ['invoicing', 'Add'])];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Invoice/Index', $props);
    }

    public function create(Request $r)
    {
        Gate::authorize('menu', ['invoicing', 'Add']);
        $r->validate(['id_sales_order' => 'required|integer|min:1', 'coa_penjualan' => 'nullable|integer|min:1', 'tanggal' => 'nullable|date_format:Y-m-d']);
        $source = Invoice::source($r->integer('id_sales_order'));
        if ($source['invoice']) {
            return redirect('/invoicing/'.$source['invoice']->id);
        }
        $coa = $r->integer('coa_penjualan');
        $journal = [];
        $errors = [];
        try {
            $coa = $coa ?: Invoice::account($source['program']['jenis'])->id;
            $rows = Invoice::journal($source['amounts'], $source['perlakuan_ppn']);
            $journal = Invoice::journals($rows, Invoice::accounts($rows, $coa), '(nomor saat Save)', $source['quotation']->nomor, $r->input('tanggal', today()->toDateString()), $source['perlakuan_ppn']);
        } catch (ValidationException $e) {
            $errors = $e->errors();
        }

        return Inertia::render('Invoice/Form', ['source' => array_intersect_key($source, array_flip(['so', 'quotation', 'program', 'judul', 'company', 'sales', 'peserta', 'amounts', 'perlakuan_ppn'])),
            'coa' => $coa ?: '', 'accounts' => Coa::query()->orderBy('no_perkiraan')->orderBy('id')->get(['id', 'no_perkiraan', 'nama', 'kdcab']), 'journal' => $journal, 'configurationErrors' => $errors, 'tanggal' => $r->input('tanggal', today()->toDateString())]);
    }

    public function store(Request $r)
    {
        Gate::authorize('menu', ['invoicing', 'Add']);
        $input = $r->validate(['id_sales_order' => 'required|integer|min:1', 'tanggal' => 'required|date_format:Y-m-d', 'no_faktur' => 'nullable|string|max:100', 'coa_penjualan' => 'required|integer|min:1']);
        $id = Invoice::save($input['id_sales_order'], $input);

        return redirect('/invoicing?tab=invoiced')->with('success', 'Invoice '.Invoice::row($id)->nomor.' tersimpan.');
    }

    public function show(Request $request, int $id)
    {
        Gate::authorize('menu', ['invoicing', 'View']);
        $i = Invoice::row($id);
        $s = json_decode($i->snapshot, true, 512, JSON_THROW_ON_ERROR);
        unset($i->snapshot);

        $props = ['invoice' => $i, 'snapshot' => $s,
            'journal' => DB::table('invoice_jurnal')->where('id_invoice', $id)->orderBy('jenis')->orderBy('urutan')->get(),
            'setor' => DB::table('invoice_bukti_setor')->where('id_invoice', $id)->first(),
            'canManage' => $i->status === 'invoiced' && ! Sales::program($i->id_penjualan)->closed_date && Gate::allows('menu', ['invoicing', 'Manage']), 'today' => today()->toDateString()];
        return $request->boolean('modal') ? response()->json($props) : Inertia::render('Invoice/Show', $props);
    }

    public function payment(Request $r, int $id)
    {
        Gate::authorize('menu', ['invoicing', 'Manage']);
        $in = $r->validate(['tanggal' => 'required|date_format:Y-m-d|before_or_equal:today', 'keterangan' => 'nullable|string|max:100']);
        Invoice::record($id, $in);

        return back()->with('success', 'Pelunasan penuh tercatat.');
    }

    public function withholding(Request $r, int $id)
    {
        Gate::authorize('menu', ['invoicing', 'Manage']);
        $in = $r->validate(['tanggal' => 'required|date_format:Y-m-d|before_or_equal:today', 'nomor' => 'nullable|string|max:100']);
        Invoice::record($id, $in, true);

        return back()->with('success', 'Bukti setor WAPU dan jurnal pembalikan tersimpan.');
    }

    public function pdf(int $id)
    {
        Gate::authorize('menu', ['invoicing', 'View']);
        $i = Invoice::row($id);
        $config = config('labmania.invoice.print');
        foreach (['bank', 'rekening', 'atas_nama', 'penanda_tangan', 'jabatan', 'kota', 'kode_dokumen'] as $key) {
            if (! is_string($config[$key]) || trim($config[$key]) === '') {
                throw ValidationException::withMessages(['pdf' => 'Lengkapi konfigurasi cetak Invoice: '.$key.'.']);
            }
        }
        $snapshot = json_decode($i->snapshot, true, 512, JSON_THROW_ON_ERROR);
        $pdf = QuotationPdf::document('Invoice '.$i->nomor, view('pdf.invoice', ['invoice' => $i, 'snapshot' => $snapshot, 'issuer' => $config])->render(), $config['kode_dokumen'].' · {PAGENO} / {nbpg}', $i->status === 'batal' ? 'BATAL' : null);
        write_log('Invoice', 'Cetak', 'Cetak '.$i->nomor, null, 'invoice', $id, $i->nomor);

        return QuotationPdf::response($pdf, 'Invoice-'.$id.'.pdf');
    }

    public function excel(Request $r)
    {
        Gate::authorize('menu', ['invoicing', 'View']);
        $f = $this->filters($r);
        $done = ($f['tab'] ?? 'uninvoiced') === 'invoiced';
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'Workbook', 'urn:schemas-microsoft-com:office:spreadsheet');
        $xml->writeAttribute('xmlns:ss', 'urn:schemas-microsoft-com:office:spreadsheet');
        $xml->startElement('Worksheet');
        $xml->writeAttribute('ss:Name', 'Invoicing');
        $xml->startElement('Table');
        $write = function (array $values) use ($xml) {
            $xml->startElement('Row');
            foreach ($values as $v) {
                $xml->startElement('Cell');
                $xml->startElement('Data');
                $xml->writeAttribute('ss:Type', 'String');
                $xml->text((string) ($v ?? ''));
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        };
        $write(['No. Program', 'Jenis', 'No. SO', 'No. Quotation', 'Invoice', 'Tanggal', 'Customer', 'Sales', 'Program', 'Peserta Deal', 'Total Tagihan', 'Piutang', 'PPN', 'Status pembayaran', 'Status Invoice']);
        foreach ($this->query($f)->cursor() as $row) {
            $v = $this->listing($row, $done);
            $write([$v->nomor_program, $v->jenis, $v->nomor_so, $v->nomor_quotation, $done ? $v->nomor : '', $done ? $v->tanggal : $v->tanggal_quotation,
                $v->kepada_nama, $v->sales_nama, $v->judul, $v->jumlah_peserta, $v->total_tagihan, $done ? $v->piutang : '', $done ? $v->perlakuan_ppn : '', $done && $v->tanggal_bayar ? 'Lunas' : 'Belum dibayar', $done ? $v->status : '']);
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        write_log('Invoice', 'Export Excel', 'Export daftar Invoicing '.($f['tab'] ?? 'uninvoiced'), ['filters' => $f], null, null, null);

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="Invoicing.xml"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}

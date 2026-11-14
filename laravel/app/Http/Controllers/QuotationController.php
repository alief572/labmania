<?php

namespace App\Http\Controllers;

use App\Support\Costing;
use App\Support\Quotation;
use App\Support\QuotationFiles;
use App\Support\QuotationPdf;
use App\Support\Reconfirmation;
use App\Support\Sales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class QuotationController extends Controller
{
    private const MENU = 'quotation_program';

    public function index(Request $r)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = Sales::filters($r);
        $tab = $f['tab'] ?? 'ongoing';
        $query = DB::table('penjualan_program as p')->where('p.status', 'final');
        Sales::filter($query, $f);
        if ($tab !== 'data') {
            $tab === 'selesai' ? $query->whereNotNull('p.closed_date') : $query->whereNull('p.closed_date');
        }
        if (! empty($f['q'])) {
            $like = '%'.$f['q'].'%';
            $query->where(fn ($w) => $w->where('p.nomor', 'like', $like)
                ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('master_program_produk as mp')->join('paket_produk as pr', 'pr.id', '=', 'mp.id_paket_produk')->whereColumn('mp.id_master_program', 'p.id_master_program')->where('pr.judul', 'like', $like))
                ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('quotation as q')->leftJoin('master_customer as c', 'c.id_customer', '=', 'q.id_customer')->leftJoin('quotation_peserta as x', 'x.id_quotation', '=', 'q.id')->whereColumn('q.id_penjualan', 'p.id')->where(fn ($s) => $s->where('q.nomor', 'like', $like)->orWhere('q.kepada_nama', 'like', $like)->orWhere('c.name_customer', 'like', $like)->orWhere('x.nama', 'like', $like))));
        }
        $statistics = DB::table('quotation')->whereIn('id_penjualan', (clone $query)->select('p.id'))->get();
        $stats = collect(['draft', 'terkirim', 'deal', 'batal'])->map(function ($status) use ($statistics) {
            $quotes = $statistics->where('status', $status);

            return ['status' => $status, 'jumlah' => $quotes->count(), 'peserta' => $quotes->sum(fn ($q) => Quotation::count($q)), 'nilai' => $quotes->sum(fn ($q) => (float) Quotation::calculate($q, Quotation::count($q))['grand_total'])];
        });
        if (in_array($f['status'] ?? '', ['draft', 'terkirim', 'deal', 'batal'], true)) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('quotation as q')->whereColumn('q.id_penjualan', 'p.id')->where('q.status', $f['status']));
        }
        $rows = $query->orderByDesc('p.id')->paginate(25)->withQueryString();
        $rows->through(function ($p) {
            $p->nomor = Sales::display($p);
            $p->judul = Sales::title($p->id_master_program);
            $p->quotation_status = DB::table('quotation')->where('id_penjualan', $p->id)->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
            $p->jumlah_quotation = $p->quotation_status->sum();
            $p->deal = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('q.id_penjualan', $p->id)->where('x.status', 'deal')->count();
            $p->menunggu = (int) DB::table('quotation')->where('id_penjualan', $p->id)->where('status', 'terkirim')->sum('kuota_peserta');
            $p->bep = Costing::calculate($p->durasi_hari, $p->target_peserta, $p->harga_jual_peserta, Sales::costs($p->id))['bep_peserta'];
            $p->can_add = ! $p->closed_date && Gate::allows('menu', [self::MENU, 'Add']) && ($p->jenis !== 'b2b' || ! DB::table('quotation')->where('id_penjualan', $p->id)->where('status', '!=', 'batal')->exists());

            return $p;
        });

        return Inertia::render('Quotation/Index', ['rows' => $rows, 'filters' => $f, 'tab' => $tab, 'stats' => $stats,
            'years' => DB::table('penjualan_program')->whereNotNull('tgl_mulai')->pluck('tgl_mulai')->map(fn ($d) => (int) substr($d, 0, 4))->unique()->sortDesc()->values()]);
    }

    public function batch(Request $r, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $p = Sales::program($id);
        $f = Sales::filters($r);
        $r->validate(['data' => 'nullable|boolean', 'peserta_status' => 'nullable|in:aktif,batal,keluar,deal,menunggu', 'tujuan' => 'nullable|in:personal,company']);
        $f['tujuan'] = $r->input('tujuan', '');
        $data = $r->boolean('data');
        $qs = DB::table('quotation')->where('id_penjualan', $id)->when($f['tujuan'], fn ($q) => $q->where('tujuan', $f['tujuan']))->when(in_array($f['status'] ?? '', ['draft', 'terkirim', 'deal', 'batal'], true), fn ($q) => $q->where('status', $f['status']))
            ->when(! empty($f['q']), fn ($q) => $q->where(fn ($w) => $w->where('nomor', 'like', '%'.$f['q'].'%')->orWhere('kepada_nama', 'like', '%'.$f['q'].'%')))->orderByDesc('id')->get();
        foreach ($qs as $q) {
            $q->nomor = Sales::display($q);
            $q->calc = Quotation::calculate($q, Quotation::count($q));
            $q->link_closed = Reconfirmation::targets($q)->exists() ? ($p->closed_date || now('Asia/Jakarta')->gte(Reconfirmation::deadline($p)) ? 'Konfirmasi ulang ditutup' : null) : Sales::linkState($q, $p);
            $q->terisi = DB::table('quotation_peserta')->where('id_quotation', $q->id)->count();
            $q->name_customer = $q->id_customer ? DB::table('master_customer')->where('id_customer', $q->id_customer)->value('name_customer') : null;
        }
        $ps = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('q.id_penjualan', $id)
            ->when(($r->input('peserta_status') ?? 'aktif') === 'batal' || $r->input('peserta_status') === 'keluar', fn ($q) => $q->where('x.status', $r->input('peserta_status')), fn ($q) => $q->whereIn('x.status', ['deal', 'menunggu']))
            ->when(in_array($r->input('peserta_status'), ['deal', 'menunggu'], true), fn ($q) => $q->where('x.status', $r->input('peserta_status')))
            ->when(! empty($f['q']), fn ($q) => $q->where(fn ($w) => $w->where('x.nama', 'like', '%'.$f['q'].'%')->orWhere('x.instansi', 'like', '%'.$f['q'].'%')->orWhere('x.email', 'like', '%'.$f['q'].'%')->orWhere('q.nomor', 'like', '%'.$f['q'].'%')))
            ->orderBy('x.id')->select('x.*', 'q.nomor as nomor_quotation', 'q.revisi as revisi_quotation')->get();
        foreach ($ps as $person) {
            $person->nomor_quotation = Sales::display((object) ['nomor' => $person->nomor_quotation, 'revisi' => $person->revisi_quotation]);
        }
        $p->nomor = Sales::display($p);

        return Inertia::render('Quotation/Batch', ['program' => (array) $p + ['judul' => Sales::title($p->id_master_program)], 'quotations' => $qs, 'peserta' => Quotation::participantDocuments($ps), 'dataTab' => $data, 'filters' => $f, 'canManage' => Gate::allows('menu', [self::MENU, 'Manage']),
            'pesertaStatus' => $r->input('peserta_status', 'aktif'), 'canAdd' => $p->status === 'final' && ! $p->closed_date && Gate::allows('menu', [self::MENU, 'Add']) && ($p->jenis !== 'b2b' || ! DB::table('quotation')->where('id_penjualan', $id)->where('status', '!=', 'batal')->exists())]);
    }

    public function create(Request $r)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $r->validate(['id_penjualan' => 'nullable|integer|exists:penjualan_program,id']);

        return $this->form(null, $r->integer('id_penjualan') ?: null);
    }

    public function show(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);

        return $this->form(Quotation::row($id));
    }

    private function form(?object $q, ?int $programId = null)
    {
        $p = $q ? Sales::program($q->id_penjualan) : ($programId ? Sales::program($programId) : null);
        $masters = DB::table('penjualan_program')->where('status', 'final')->where(fn ($w) => $w->whereNull('closed_date')->when($q, fn ($w) => $w->orWhere('id', $q->id_penjualan)))->get();
        foreach ($masters as $master) {
            $master->nomor = Sales::display($master);
            $master->judul = Sales::title($master->id_master_program);
            $master->bep_harga = Costing::calculate($master->durasi_hari, $master->target_peserta, $master->harga_jual_peserta, Sales::costs($master->id))['hpp_per_pax'];
            $master->id_jenis_produk = DB::table('master_program_produk as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')->where('m.id_master_program', $master->id_master_program)->orderBy('m.urutan')->value('p.id_jenis_produk');
        }
        $form = $q ? (array) $q : ['id' => null, 'nomor' => '', 'status' => 'draft', 'id_penjualan' => $programId ?? '', 'tanggal' => today()->toDateString(), 'berlaku_sampai' => now()->format('Y').'-12-31', 'id_sales' => '', 'sumber_info' => 'Sales',
            'tujuan' => $p?->jenis === 'b2b' ? 'company' : 'personal', 'id_customer' => $p?->id_customer ?? '', 'up_nama' => '', 'kepada_nama' => '', 'alamat' => '', 'kuota_peserta' => $p?->jenis === 'b2b' ? 10 : 1,
            'harga_allin' => $p?->jenis === 'b2b' ? $p->harga_jual_peserta * 10 : 0, 'diskon' => 0, 'is_ppn' => 1, 'biaya_lain' => 0, 'id_template_konfirmasi' => '', 'konfirmasi_isian' => [], 'konfirmasi_pernyataan' => '', 'kolom_peserta_b2b' => []];
        foreach (['konfirmasi_isian', 'kolom_peserta_b2b'] as $key) {
            $form[$key] = is_array($form[$key] ?? null) ? $form[$key] : (json_decode($form[$key] ?? '[]', true) ?: []);
        }
        $form['keterangan'] = $q ? DB::table('quotation_keterangan')->where('id_quotation', $q->id)->orderBy('urutan')->get() : [];
        $templates = DB::table('ms_template_konfirmasi')->where('aktif', 1)->get();
        foreach ($templates as $t) {
            $t->isian = json_decode($t->isian ?? '[]', true) ?: [];
        }
        $programs = $masters->keyBy('id')->all();
        if ($p && ! isset($programs[$p->id])) {
            $p->judul = Sales::title($p->id_master_program);
            $programs[$p->id] = $p;
        }

        if ($q) {
            $form['nomor'] = Sales::display($q);
        }

        return Inertia::render('Quotation/Form', ['quotation' => $form, 'programs' => array_values($programs),
            'companies' => DB::table('master_customer')->whereIn('activation', ['aktif', 'active', '1'])->get(['id_customer', 'name_customer', 'pic_name', 'address_office', 'kota']),
            'sales' => DB::table('ms_user_karyawan')->where('aktif', 1)->get(['id', 'nama']), 'templates' => $templates, 'fields' => TemplateController::KF_FIELDS,
            'keteranganOptions' => DB::table('ms_template_keterangan')->where('aktif', 1)->orderBy('id')->get(),
            'peserta' => $q ? Quotation::participantDocuments(DB::table('quotation_peserta')->where('id_quotation', $q->id)->get()) : [],
            'companyConfirmation' => $q ? DB::table('quotation_konfirmasi_company')->where('id_quotation', $q->id)->where('revisi', $q->revisi)->first() : null,
            'revisions' => $q ? DB::table('quotation_revisi')->where('id_quotation', $q->id)->orderByDesc('revisi')->get(['revisi', 'created_date']) : [],
            'documents' => $q ? DB::table('quotation_dokumen')->where('id_quotation', $q->id)->get(['id', 'nama_file', 'ukuran_byte']) : [],
            'link' => $q ? url('/konfirmasi/'.$q->link_token) : null, 'linkClosed' => $q ? (Reconfirmation::targets($q)->exists() ? ($p->closed_date || now('Asia/Jakarta')->gte(Reconfirmation::deadline($p)) ? 'Konfirmasi ulang ditutup' : null) : Sales::linkState($q, $p)) : null,
            'calc' => $q ? Quotation::calculate($q, Quotation::count($q)) : null,
            'salesOrder' => $q ? DB::table('sales_order')->where('id_quotation', $q->id)->first(['id', 'nomor', 'revisi']) : null,
            'quotaOther' => $p ? DB::table('quotation')->where('id_penjualan', $p->id)->where('status', '!=', 'batal')->when($q, fn ($w) => $w->where('id', '!=', $q->id))->sum('kuota_peserta') : 0,
            'can' => ['edit' => (! $p || ! $p->closed_date) && Gate::allows('menu', [self::MENU, $q ? 'Manage' : 'Add']), 'cancel' => $p && ! $p->closed_date && Gate::allows('menu', [self::MENU, 'Delete'])]]);
    }

    private function validated(Request $r, object $p, ?object $old = null): array
    {
        foreach (['harga_allin', 'diskon', 'biaya_lain'] as $key) {
            if (is_string($r->input($key))) {
                $r->merge([$key => str_replace('.', '', $r->input($key))]);
            }
        }
        $in = $r->validate(['id_penjualan' => 'required|integer', 'tanggal' => 'required|date_format:Y-m-d', 'berlaku_sampai' => 'required|date_format:Y-m-d|after_or_equal:tanggal',
            'id_sales' => 'required|integer', 'sumber_info' => 'nullable|string|max:30', 'tujuan' => 'required|in:personal,company', 'id_customer' => 'nullable|string|max:20',
            'kepada_nama' => 'nullable|string|max:100', 'up_nama' => 'nullable|string|max:100', 'alamat' => 'nullable|string|max:3000', 'kuota_peserta' => 'required|integer|min:1|max:100000',
            'harga_allin' => 'required|integer|min:0|max:9999999999999', 'diskon' => 'required|integer|min:0|max:9999999999999', 'biaya_lain' => 'required|integer|min:0|max:9999999999999', 'is_ppn' => 'required|boolean',
            'id_template_konfirmasi' => 'nullable|integer', 'konfirmasi_isian' => 'required|array|min:1|max:30', 'konfirmasi_isian.*' => 'string|max:80', 'konfirmasi_pernyataan' => 'required|string|max:2000',
            'kolom_peserta_b2b' => 'present|array', 'kolom_peserta_b2b.*' => Rule::in(['jabatan', 'email', 'wa', 'nik']), 'keterangan' => 'present|array|max:100',
            'keterangan.*.kategori' => ['required', Rule::in(['syarat', 'termasuk', 'tidak', 'dokumen', 'klausul'])], 'keterangan.*.teks' => 'required|string|max:2000']);
        Sales::open($p);
        if ($p->status !== 'final' || (int) $in['id_penjualan'] !== (int) $p->id) {
            throw ValidationException::withMessages(['id_penjualan' => 'Pilih Penjualan Program Final.']);
        }
        if (! DB::table('ms_user_karyawan')->where('id', $in['id_sales'])->where('aktif', 1)->exists()) {
            throw ValidationException::withMessages(['id_sales' => 'Pilih sales aktif.']);
        }
        $b2b = $p->jenis === 'b2b';
        $in['tujuan'] = $b2b ? 'company' : $in['tujuan'];
        $in['id_customer'] = $b2b ? $p->id_customer : ($in['tujuan'] === 'company' ? ($in['id_customer'] ?? null) : null);
        if ($in['tujuan'] === 'company' && ! DB::table('master_customer')->where('id_customer', $in['id_customer'])->whereIn('activation', ['aktif', 'active', '1'])->exists()) {
            throw ValidationException::withMessages(['id_customer' => 'Pilih Company aktif.']);
        }
        if ($in['tujuan'] === 'personal' && trim($in['kepada_nama'] ?? '') === '') {
            throw ValidationException::withMessages(['kepada_nama' => 'Kepada wajib untuk Personal.']);
        }
        if ($b2b && ($in['kuota_peserta'] < 10 || $in['harga_allin'] <= 0)) {
            throw ValidationException::withMessages(['harga_allin' => 'B2B memerlukan harga paket > 0 dan kuota minimal 10.']);
        }
        if ($in['diskon'] > ($b2b ? $in['harga_allin'] : $p->harga_jual_peserta)) {
            throw ValidationException::withMessages(['diskon' => 'Diskon tidak boleh melebihi harga dasar.']);
        }
        if ($old && $in['kuota_peserta'] < DB::table('quotation_peserta')->where('id_quotation', $old->id)->where('status', '!=', 'batal')->count()) {
            throw ValidationException::withMessages(['kuota_peserta' => 'Kuota tidak boleh kurang dari peserta terisi.']);
        }
        if ($b2b && DB::table('quotation')->where('id_penjualan', $p->id)->where('status', '!=', 'batal')->when($old, fn ($q) => $q->where('id', '!=', $old->id))->exists()) {
            throw ValidationException::withMessages(['id_penjualan' => 'B2B hanya boleh satu quotation aktif.']);
        }
        $isian = array_values(array_unique(['nama', ...$in['konfirmasi_isian']]));
        $template = null;
        if (! $b2b) {
            $template = DB::table('ms_template_konfirmasi')->where('id', $in['id_template_konfirmasi'] ?? 0)->where('aktif', 1)->first();
            if (! $template) {
                throw ValidationException::withMessages(['id_template_konfirmasi' => 'Pilih template konfirmasi aktif.']);
            }
        }
        $details = $in['keterangan'];
        unset($in['keterangan']);
        foreach ($details as $d) {
            if ($d['kategori'] !== 'klausul') {
                continue;
            }
            $fromMaster = DB::table('ms_template_keterangan')->where('kategori', 'klausul')->where('teks', $d['teks'])->where('aktif', 1)->whereIn('berlaku_untuk', ['semua', $p->jenis])->exists();
            $unchanged = $old && DB::table('quotation_keterangan')->where('id_quotation', $old->id)->where('kategori', 'klausul')->where('teks', $d['teks'])->exists();
            if (! $fromMaster && ! $unchanged) {
                throw ValidationException::withMessages(['keterangan' => 'Klausul tanda tangan hanya boleh diambil dari Master Template Keterangan.']);
            }
        }
        $in['skema_harga'] = $b2b ? 'allin' : 'peserta';
        $in['harga_peserta'] = $p->harga_jual_peserta;
        $in['ppn_persen'] = 11;
        $in['paket_maks_peserta'] = $b2b ? ($old?->paket_maks_peserta ?? 25) : null;
        $in['tambahan_per_peserta'] = $b2b ? ($old?->tambahan_per_peserta ?? 300000) : null;
        $in['id_template_konfirmasi'] = $template?->id;
        $in['konfirmasi_isian'] = json_encode($isian, JSON_THROW_ON_ERROR);
        $in['kolom_peserta_b2b'] = $b2b ? json_encode(array_values(array_unique($in['kolom_peserta_b2b']))) : null;
        if ($in['tujuan'] === 'company') {
            $in['kepada_nama'] = null;
        }

        return [$in, $details];
    }

    public function store(Request $r)
    {
        return $this->save($r, null);
    }

    public function update(Request $r, int $id)
    {
        return $this->save($r, $id);
    }

    private function save(Request $r, ?int $id)
    {
        Gate::authorize('menu', [self::MENU, $id ? 'Manage' : 'Add']);
        $r->validate(['id_penjualan' => 'required|integer']);
        $id = Sales::transaction(function () use ($r, $id) {
            $p = Sales::program($id ? Quotation::row($id)->id_penjualan : $r->integer('id_penjualan'), true);
            $old = $id ? Quotation::row($id, true) : null;
            if ($old && $old->status !== 'draft') {
                throw ValidationException::withMessages(['quotation' => 'Hanya Draft dapat diedit.']);
            }
            [$data, $details] = $this->validated($r, $p, $old);
            $before = $old ? DB::table('quotation_keterangan')->where('id_quotation', $id)->get()->map(fn ($d) => (array) $d)->all() : [];
            if (! $old) {
                $data += ['nomor' => Sales::number('quotation', 'LMI/PW/'.Carbon::parse($data['tanggal'])->format('m/y')), 'status' => 'draft', 'link_token' => bin2hex(random_bytes(16)), 'link_dibuat' => now(), 'created_by' => Auth::id(), 'created_date' => now()];
                $id = DB::table('quotation')->insertGetId($data);
            } else {
                $data += ['updated_by' => Auth::id(), 'updated_date' => now()];
                DB::table('quotation')->where('id', $id)->update($data);
            }
            DB::table('quotation_keterangan')->where('id_quotation', $id)->delete();
            foreach ($details as $i => $d) {
                DB::table('quotation_keterangan')->insert(['id_quotation' => $id, 'kategori' => $d['kategori'], 'teks' => $d['teks'], 'urutan' => $i + 1]);
            }
            $new = Quotation::row($id);
            write_log('Quotation', $old ? 'Edit' : 'Tambah', ($old ? 'Update ' : 'Tambah ').$new->nomor, ['old' => $old ? (array) $old : [], 'new' => (array) $new, 'detail_changes' => ['Keterangan' => ['old' => array_map(fn ($x) => $x['kategori'].': '.$x['teks'], $before), 'new' => array_map(fn ($x) => $x['kategori'].': '.$x['teks'], $details)]]], 'quotation', $id, $new->nomor);

            return $id;
        });

        return redirect('/quotation/'.$id)->with('success', 'Quotation Draft disimpan; link konfirmasi tersedia.');
    }

    public function preview(Request $r)
    {
        $r->validate(['id' => 'nullable|integer', 'id_penjualan' => 'required|integer']);
        $id = $r->integer('id') ?: null;
        Gate::authorize('menu', [self::MENU, $id ? 'Manage' : 'Add']);
        $old = $id ? Quotation::row($id) : null;
        if ($old && $old->status !== 'draft') {
            throw ValidationException::withMessages(['quotation' => 'Hanya Draft dapat dipratinjau sebagai perubahan.']);
        }
        $p = Sales::program($old?->id_penjualan ?? $r->integer('id_penjualan'));
        [$data, $details] = $this->validated($r, $p, $old);

        return QuotationPdf::response(QuotationPdf::offer((object) ($data + ['id' => null, 'nomor' => $old?->nomor ?? 'Preview — belum disimpan', 'status' => 'draft', 'link_token' => null]), $p, $details), 'Preview-Quotation.pdf');
    }

    public function pdf(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $q = Quotation::row($id);
        $pdf = QuotationPdf::offer($q, Sales::program($q->id_penjualan));
        write_log('Quotation', 'Cetak', 'Cetak '.$q->nomor, null, 'quotation', $id, $q->nomor);

        return QuotationPdf::response($pdf, 'Quotation-'.$id.'.pdf');
    }

    public function send(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $pdf = DB::transaction(function () use ($id) {
            $read = Quotation::row($id);
            $p = Sales::program($read->id_penjualan, true);
            Sales::open($p);
            $old = Quotation::row($id, true);
            if ($old->status !== 'draft') {
                throw ValidationException::withMessages(['quotation' => 'Hanya Draft dapat dikirim.']);
            }
            $pdf = QuotationPdf::offer((object) array_replace((array) $old, ['status' => 'terkirim']), $p);
            $data = ['status' => 'terkirim', 'sent_by' => Auth::id(), 'sent_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('quotation')->where('id', $id)->update($data);
            write_log('Quotation', 'Kirim', 'Kirim dan Cetak '.$old->nomor, ['old' => (array) $old, 'new' => array_merge((array) $old, $data)], 'quotation', $id, $old->nomor);

            return $pdf;
        });

        return QuotationPdf::response($pdf, 'Quotation-'.$id.'.pdf');
    }

    public function cancel(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Delete']);
        DB::transaction(function () use ($id) {
            $read = Quotation::row($id);
            $p = Sales::program($read->id_penjualan, true);
            Sales::open($p);
            $old = Quotation::row($id, true);
            if (! in_array($old->status, ['draft', 'terkirim'], true)) {
                throw ValidationException::withMessages(['quotation' => 'Quotation Deal/Batal tidak dapat dibatalkan.']);
            }
            $before = DB::table('quotation_peserta')->where('id_quotation', $id)->get();
            $data = ['status' => 'batal', 'closed_by' => Auth::id(), 'closed_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('quotation')->where('id', $id)->update($data);
            DB::table('quotation_peserta')->where('id_quotation', $id)->update(['status' => 'batal']);
            $changes = [];
            foreach ($before as $x) {
                $changes['Peserta #'.$x->id] = ['old' => $x->status, 'new' => 'batal'];
            }
            write_log('Quotation', 'Batal', 'Batalkan '.$old->nomor, ['old' => (array) $old, 'new' => array_merge((array) $old, $data), 'detail_changes' => $changes], 'quotation', $id, $old->nomor);
        });

        return back()->with('success', 'Quotation dibatalkan.');
    }

    public function quota(Request $r, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $in = $r->validate(['kuota_peserta' => 'required|integer|min:1|max:100000']);
        DB::transaction(function () use ($id, $in) {
            $read = Quotation::row($id);
            $p = Sales::program($read->id_penjualan, true);
            Sales::open($p);
            $old = Quotation::row($id, true);
            if ($old->status !== 'terkirim' || $p->jenis !== 'public' || $old->tujuan !== 'company' || $in['kuota_peserta'] <= $old->kuota_peserta) {
                throw ValidationException::withMessages(['kuota_peserta' => 'Tambah kuota hanya untuk Public Company Terkirim, dan harus lebih besar.']);
            }
            $new = $in + ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('quotation')->where('id', $id)->update($new);
            write_log('Quotation', 'Edit', 'Tambah kuota '.$old->nomor, ['old' => (array) $old, 'new' => array_merge((array) $old, $new)], 'quotation', $id, $old->nomor);
        });

        return back()->with('success', 'Kuota bertambah. Batas H-14 dan masa berlaku tetap berlaku.');
    }

    public function documents(Request $r, int $id, bool $deal = false)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $in = $r->validate(['dokumen' => 'required|array|min:1|max:20', 'dokumen.*' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'dokumen_keterangan' => 'nullable|string|max:200', 'peserta' => $deal ? 'required|array|min:1' : 'nullable|array', 'peserta.*' => 'integer']);
        $files = [];
        try {
            foreach ($r->file('dokumen') as $file) {
                $files[] = QuotationFiles::store($file, true);
            }
            if ($deal) {
                $result = Quotation::deal($id, $in['peserta'], $files, $in['dokumen_keterangan'] ?? null);
                if (! empty($result['replay'])) {
                    QuotationFiles::discard($files);
                }
                $message = 'Deal tersimpan. SO '.$result['so'].'; Master Peserta '.$result['baru'].' baru, '.$result['cocok'].' dicocokkan.';
            } else {
                DB::transaction(function () use ($id, $files, $in) {
                    $read = Quotation::row($id);
                    $p = Sales::program($read->id_penjualan, true);
                    Sales::open($p);
                    $q = Quotation::row($id, true);
                    if ($q->status !== 'deal') {
                        throw ValidationException::withMessages(['quotation' => 'Tambahan dokumen hanya untuk quotation Deal.']);
                    }
                    $before = DB::table('quotation_dokumen')->where('id_quotation', $id)->pluck('nama_file')->all();
                    foreach ($files as $f) {
                        Quotation::customerDocument($id, $f);
                    }
                    $update = ['dokumen_keterangan' => $in['dokumen_keterangan'] ?? $q->dokumen_keterangan, 'updated_by' => Auth::id(), 'updated_date' => now()];
                    DB::table('quotation')->where('id', $id)->update($update);
                    write_log('Quotation', 'Edit', 'Tambah dokumen '.$q->nomor, ['old' => (array) $q, 'new' => array_merge((array) $q, $update), 'detail_changes' => ['Dokumen customer' => ['old' => $before, 'new' => [...$before, ...array_column($files, 'name')]]]], 'quotation', $id, $q->nomor);
                });
                $message = 'Dokumen customer ditambahkan.';
            }
        } catch (\Throwable $e) {
            QuotationFiles::discard($files);
            throw $e;
        }

        return back()->with('success', $message);
    }

    public function deal(Request $r, int $id)
    {
        return $this->documents($r, $id, true);
    }

    public function replaceLetter(Request $r, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $r->validate(['surat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', 'alasan' => 'required|string|max:200']);
        $file = QuotationFiles::store($r->file('surat'));
        try {
            DB::transaction(function () use ($r, $id, $file) {
                $read = DB::table('quotation_peserta')->where('id', $id)->first() ?? abort(404);
                $q = Quotation::row($read->id_quotation);
                $p = Sales::program($q->id_penjualan, true);
                Sales::open($p);
                $old = DB::table('quotation_peserta')->where('id', $id)->lockForUpdate()->first();
                if (! $old->surat_konfirmasi) {
                    throw ValidationException::withMessages(['surat' => 'Peserta ini tidak memiliki surat per orang.']);
                }
                DB::table('quotation_peserta_surat_riwayat')->insert(['id_quotation_peserta' => $id, 'surat_konfirmasi' => $old->surat_konfirmasi, 'surat_konfirmasi_nama' => $old->surat_konfirmasi_nama, 'sumber' => 'ganti_sales', 'alasan' => $r->input('alasan'), 'created_by' => Auth::id(), 'created_date' => now()]);
                $new = ['surat_konfirmasi' => $file['path'], 'surat_konfirmasi_nama' => $file['name']];
                DB::table('quotation_peserta')->where('id', $id)->update($new);
                write_log('Quotation', 'Edit', 'Ganti surat '.$q->nomor, ['sub_module' => 'Data Peserta', 'alasan' => $r->input('alasan'), 'old' => (array) $old, 'new' => array_merge((array) $old, $new)], 'quotation', $q->id, $q->nomor);
            });
        } catch (\Throwable $e) {
            QuotationFiles::discard([$file]);
            throw $e;
        }

        return back()->with('success', 'Surat diganti; berkas lama tetap tersimpan sebagai riwayat.');
    }

    public function batchPdf(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $p = Sales::program($id);
        $pdf = QuotationPdf::batch($p);
        write_log('Quotation', 'Export PDF', 'Export Data Peserta '.$p->nomor, ['sub_module' => 'Data Peserta'], 'penjualan_program', $id, $p->nomor);

        return QuotationPdf::response($pdf, 'Data-Peserta-'.$id.'.pdf');
    }

    public function letters(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $q = Quotation::row($id);
        $pdf = QuotationPdf::batch(Sales::program($q->id_penjualan), $id);
        write_log('Quotation', 'Export PDF', 'Export Confirmation Letter '.$q->nomor, ['sub_module' => 'Data Peserta'], 'quotation', $id, $q->nomor);

        return QuotationPdf::response($pdf, 'Confirmation-Letters-'.$id.'.pdf');
    }

    public function excel(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $q = Quotation::row($id);
        $rows = Quotation::participantDocuments(DB::table('quotation_peserta')->where('id_quotation', $id)->orderBy('id')->get());
        $fields = ['nama' => 'Nama Lengkap', 'nik' => 'NIK', 'jabatan' => 'Jabatan', 'instansi' => 'Instansi', 'alamat_instansi' => 'Alamat Instansi', 'no_hp' => 'Nomor HP', 'no_wa' => 'Nomor WhatsApp', 'email' => 'Email', 'pengalaman' => 'Pengalaman', 'lsp' => 'LSP', 'tgl_habis_sertifikat' => 'Tanggal Habis Sertifikat'];
        $extra = $rows->flatMap(fn ($p) => array_keys(json_decode($p->isian_tambahan ?: '[]', true) ?: []))->unique()->values()->all();
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'Workbook', 'urn:schemas-microsoft-com:office:spreadsheet');
        $xml->writeAttribute('xmlns:ss', 'urn:schemas-microsoft-com:office:spreadsheet');
        $xml->startElement('Worksheet');
        $xml->writeAttribute('ss:Name', 'Data Peserta');
        $xml->startElement('Table');
        $row = function (array $cells) use ($xml) {
            $xml->startElement('Row');
            foreach ($cells as $cell) {
                $xml->startElement('Cell');
                $xml->startElement('Data');
                $xml->writeAttribute('ss:Type', 'String');
                $xml->text((string) ($cell ?? ''));
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        };
        $row(['No', ...array_values($fields), ...$extra, 'Nama berkas surat konfirmasi', 'Masuk lewat', 'Waktu isi', 'Status']);
        $companyFile = DB::table('quotation_konfirmasi_company')->where('id_quotation', $id)->where('revisi', $q->revisi)->value('surat_konfirmasi_nama');
        foreach ($rows as $i => $p) {
            $additional = json_decode($p->isian_tambahan ?: '[]', true) ?: [];
            $row([$i + 1, ...array_map(fn ($field) => $p->$field, array_keys($fields)), ...array_map(fn ($field) => $additional[$field] ?? '', $extra), $p->surat_konfirmasi_nama ?: $companyFile, $p->sumber, $p->tgl_isi, $p->status]);
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        write_log('Quotation', 'Export Excel', 'Export Data Peserta '.$q->nomor, ['sub_module' => 'Data Peserta', 'jumlah' => $rows->count()], 'quotation', $id, $q->nomor);

        // Excel XML menjaga NIK/telepon sebagai teks dan tidak menafsirkan isian peserta sebagai formula.
        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="Data-Peserta-'.$id.'.xml"', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}

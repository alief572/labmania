<?php

namespace App\Http\Controllers;

use App\Support\Costing;
use App\Support\ProgramCancel;
use App\Support\QuotationPdf;
use App\Support\Reschedule;
use App\Support\Sales;
use App\Support\ServerDataTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SalesController extends Controller
{
    private const MENU = 'penjualan_program';

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        ServerDataTable::prepare($request);
        $f = Sales::filters($request);
        $query = DB::table('penjualan_program as p')->leftJoin('master_customer as c', 'c.id_customer', '=', 'p.id_customer')
            ->leftJoin('master_program as m', 'm.id', '=', 'p.id_master_program')
            ->leftJoin('ms_trainer as t', 't.id', '=', 'p.id_trainer')
            ->leftJoin('ms_user_karyawan as a', 'a.id', '=', 'p.id_asisten');
        ServerDataTable::scope($request, $query);
        Sales::filter($query, $f);
        if (! empty($f['status']) && in_array($f['status'], ['draft', 'final', 'batal'], true)) {
            $query->where('p.status', $f['status']);
        }
        if (($f['q'] ?? '') !== '') {
            $query->where(fn ($w) => $w->where('p.nomor', 'like', '%'.$f['q'].'%')->orWhere('c.name_customer', 'like', '%'.$f['q'].'%')->orWhere('p.lokasi', 'like', '%'.$f['q'].'%'));
        }
        $rows = $query->select('p.*', 'c.name_customer', 'm.nomor as master_nomor', 't.nama as trainer_nama', 'a.nama as asisten_nama')->orderByDesc('p.id')->pipe(fn ($builder) => ServerDataTable::page($request, $builder, [null, 'p.nomor', 'p.jenis', 'm.nomor', 'p.tgl_mulai', 'p.target_peserta', 'p.harga_jual_peserta', 'p.status', null], 'p.id'));
        $titles = Sales::titles($rows->pluck('id_master_program')->unique()->all());
        $reschedules = DB::table('penjualan_program_reschedule')->whereIn('id_penjualan', $rows->pluck('id'))->selectRaw('id_penjualan, COUNT(*) as total')->groupBy('id_penjualan')->pluck('total', 'id_penjualan');
        $deals = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->whereIn('q.id_penjualan', $rows->pluck('id'))->where('x.status', 'deal')->selectRaw('q.id_penjualan, COUNT(*) as total')->groupBy('q.id_penjualan')->pluck('total', 'id_penjualan');
        $costs = Sales::costsFor($rows->pluck('id')->all());
        $rows->through(function ($p) use ($titles, $reschedules, $deals, $costs) {
            $p->judul = $titles[$p->id_master_program] ?? '';
            $p->nomor = Sales::display($p);
            $p->reschedule_count = (int) ($reschedules[$p->id] ?? 0);
            $p->peserta_deal = (int) ($deals[$p->id] ?? 0);
            $p->reschedulable = Reschedule::reason($p) === null;
            $p->costing = Costing::calculate($p->durasi_hari, $p->target_peserta, $p->harga_jual_peserta, $costs->get($p->id, collect())->map(fn ($b) => (array) $b)->all());
            $p->warnings = Costing::warnings($p->costing, $p->target_peserta);

            return $p;
        });

        $props = ['rows' => $rows, 'filters' => $f, 'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage']), 'approve' => Gate::allows('menu', [self::MENU, 'Approve']), 'cancel' => Gate::allows('menu', [self::MENU, 'Manage']), 'quotation' => Gate::allows('menu', ['quotation_program', 'Add'])],
            'years' => DB::table('penjualan_program')->whereNotNull('tgl_mulai')->pluck('tgl_mulai')->map(fn ($d) => (int) substr($d, 0, 4))->unique()->sortDesc()->values()];
        if ($request->route('datatable')) {
            return ServerDataTable::result($request, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Sales/Index', $props);
    }

    /** Langkah awal (PJ-FR-02/03): pilih Master Program Final, lalu jenis Public atau B2B. ?master=<id> memilih master itu lebih dulu. */
    public function start(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $masters = DB::table('master_program')->where('status', 'final')->orderByDesc('id')->get(['id', 'nomor', 'durasi_hari', 'target_peserta', 'harga_jual_peserta']);
        $titles = Sales::titles($masters->pluck('id')->all());
        $costs = DB::table('master_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')->whereIn('b.id_master_program', $masters->pluck('id'))
            ->get(['b.*', 's.nama as nama_satuan'])->groupBy('id_master_program');
        foreach ($masters as $m) {
            $m->judul = $titles[$m->id] ?? '';
            $m->hpp = Costing::calculate($m->durasi_hari, $m->target_peserta, $m->harga_jual_peserta, ($costs[$m->id] ?? collect())->map(fn ($b) => (array) $b)->all())['total_hpp'];
        }
        $preset = $request->integer('master');

        return Inertia::render('Sales/Start', ['masters' => $masters, 'preset' => $masters->contains('id', $preset) ? $preset : null, 'minB2b' => 10]);
    }

    /** ?master=<id> memilih master itu lebih dulu (hanya yang Final); ?jenis=public|b2b mengunci jenis dari halaman Mulai. */
    public function create(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        $master = $request->integer('master') ?: null;
        $jenis = in_array($request->query('jenis'), ['public', 'b2b'], true) ? $request->query('jenis') : null;

        return $this->form(null, $master && DB::table('master_program')->where('id', $master)->where('status', 'final')->exists() ? $master : null, $jenis);
    }

    public function show(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);

        return $this->form(Sales::program($id));
    }

    public function pdf(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $p = Sales::program($id);
        abort_unless($p->status === 'final', 422, 'Surat penawaran hanya untuk Penjualan Program Final.');
        $pdf = QuotationPdf::program($p);
        write_log('Penjualan Program', 'Cetak', 'Cetak penawaran '.$p->nomor, null, 'penjualan_program', $id, $p->nomor);

        return QuotationPdf::response($pdf, 'Penawaran-Program-'.$id.'.pdf');
    }

    private function form(?object $p, ?int $presetMaster = null, ?string $presetJenis = null)
    {
        $masters = DB::table('master_program')->where('status', 'final')->get();
        foreach ($masters as $m) {
            $m->judul = Sales::title($m->id);
            $m->biaya = DB::table('master_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')->where('id_master_program', $m->id)->select('b.*', 's.nama as nama_satuan')->get();
        }
        $program = $p ? (array) $p : ['id' => null, 'nomor' => '', 'status' => 'draft', 'jenis' => 'public', 'id_master_program' => '', 'id_customer' => '', 'id_trainer' => '', 'id_asisten' => '',
            'tgl_mulai' => '', 'tgl_selesai' => '', 'waktu' => '', 'lokasi' => '', 'jam_pembelajaran' => '', 'target_peserta' => 10, 'harga_jual_peserta' => 0, 'target_profit' => 0, 'durasi_hari' => 1, 'closed_date' => null];
        if ($p) {
            $program['nomor'] = Sales::display($p);
        }
        if (! $p && $presetMaster) {
            $program['id_master_program'] = $presetMaster;
        }
        if (! $p && $presetJenis) {
            $program['jenis'] = $presetJenis;
        }
        $program['biaya'] = $p ? Sales::costs($p->id) : [];
        $actual = null;
        if ($p && $p->status === 'final') {
            $n = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('q.id_penjualan', $p->id)->where('x.status', 'deal')->count();
            $actual = ['peserta' => $n, 'rencana' => Costing::calculate($p->durasi_hari, $p->target_peserta, $p->harga_jual_peserta, $program['biaya']), 'aktual' => Costing::calculate($p->durasi_hari, $n, $p->harga_jual_peserta, $program['biaya'])];
        }

        return Inertia::render('Sales/Form', ['program' => $program, 'masters' => $masters,
            'companies' => DB::table('master_customer')->where(fn ($w) => $w->whereIn('activation', ['aktif', 'active', '1'])->when($p?->id_customer, fn ($w) => $w->orWhere('id_customer', $p->id_customer)))->get(['id_customer', 'name_customer']),
            'trainers' => DB::table('ms_trainer')->where(fn ($w) => $w->where('aktif', 1)->when($p?->id_trainer, fn ($w) => $w->orWhere('id', $p->id_trainer)))->get(['id', 'nama', 'bidang_keahlian']),
            'assistants' => DB::table('ms_user_karyawan as u')->leftJoin('groups as g', 'g.id_group', '=', 'u.id_group')->where(fn ($w) => $w->where('u.aktif', 1)->when($p?->id_asisten, fn ($w) => $w->orWhere('u.id', $p->id_asisten)))->get(['u.id', 'u.nama', 'u.id_group', 'g.nm_group as role']),
            'rates' => DB::table('ms_rate as r')->leftJoin('ms_satuan as s', 's.id', '=', 'r.id_satuan')->where('r.aktif', 1)->get(['r.*', 's.nama as nama_satuan']),
            'units' => DB::table('ms_satuan')->get(['id', 'nama']), 'actual' => $actual, 'jenisLocked' => (bool) $p || (bool) $presetJenis,
            'can' => ['edit' => Gate::allows('menu', [self::MENU, $p ? 'Manage' : 'Add']), 'approve' => Gate::allows('menu', [self::MENU, 'Approve']), 'cancel' => Gate::allows('menu', [self::MENU, 'Manage'])]]);
    }

    private function validated(Request $r, ?object $old): array
    {
        foreach (['harga_jual_peserta', 'target_profit'] as $key) {
            if (is_string($r->input($key))) {
                $r->merge([$key => str_replace('.', '', $r->input($key))]);
            }
        }
        $in = $r->validate(['jenis' => 'required|in:public,b2b', 'id_master_program' => 'required|integer|exists:master_program,id', 'id_customer' => 'nullable|string|max:20',
            'id_trainer' => 'required|integer', 'id_asisten' => 'required|integer', 'tgl_mulai' => 'required|date_format:Y-m-d', 'tgl_selesai' => 'required|date_format:Y-m-d|after_or_equal:tgl_mulai',
            'waktu' => 'nullable|string|max:50', 'lokasi' => 'nullable|string|max:150', 'jam_pembelajaran' => 'required|integer|min:1|max:10000',
            'target_peserta' => 'required|integer|min:1|max:100000', 'harga_jual_peserta' => 'required|integer|min:0|max:9999999999999', 'target_profit' => 'nullable|integer|min:0|max:9999999999999', 'biaya' => 'present|array|max:200']);
        $master = DB::table('master_program')->where('id', $in['id_master_program'])->first();
        if ($master->status !== 'final' || ($old && ($old->jenis !== $in['jenis'] || $old->id_master_program !== (int) $in['id_master_program']))) {
            throw ValidationException::withMessages(['id_master_program' => 'Pilih Master Program Final; jenis dan sumber terkunci setelah disimpan.']);
        }
        if (! DB::table('ms_trainer')->where('id', $in['id_trainer'])->where('aktif', 1)->exists()) {
            throw ValidationException::withMessages(['id_trainer' => 'Pilih trainer aktif.']);
        }
        if (! DB::table('ms_user_karyawan')->where('id', $in['id_asisten'])->where('aktif', 1)->exists()) {
            throw ValidationException::withMessages(['id_asisten' => 'Pilih asisten trainer aktif.']);
        }
        if ($in['jenis'] === 'b2b' && ($in['target_peserta'] < 10 || ! DB::table('master_customer')->where('id_customer', $in['id_customer'] ?? '')->whereIn('activation', ['aktif', 'active', '1'])->exists())) {
            throw ValidationException::withMessages(['id_customer' => 'B2B wajib Company aktif dan target minimal 10.']);
        }
        [$costs, $errors, $dropped] = Costing::cleanRows($in['biaya'], $old ? DB::table('penjualan_program_biaya')->where('id_penjualan', $old->id)->pluck('id_rate')->filter()->all() : []);
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        unset($in['biaya']);
        $in['id_customer'] = $in['jenis'] === 'b2b' ? $in['id_customer'] : null;
        $in['durasi_hari'] = (int) Carbon::parse($in['tgl_mulai'])->diffInDays(Carbon::parse($in['tgl_selesai'])) + 1;
        $in['target_profit'] = $in['target_profit'] ?? 0;

        return [$in, $costs, $dropped];
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
        [$id, $dropped, $nomor] = Sales::transaction(function () use ($r, $id) {
            $old = $id ? Sales::program($id, true) : null;
            if ($old) {
                Sales::open($old);
                if ($old->status !== 'draft') {
                    throw ValidationException::withMessages(['program' => 'Hanya Draft dapat diubah.']);
                }
            }
            [$data, $costs, $dropped] = $this->validated($r, $old);
            $before = $old ? DB::table('penjualan_program_biaya')->where('id_penjualan', $id)->get()->map(fn ($x) => (array) $x)->all() : [];
            if (! $old) {
                $data += ['nomor' => Sales::number('penjualan_program', ($data['jenis'] === 'b2b' ? 'PJB' : 'PJP').'-'.now()->format('ym')), 'status' => 'draft', 'created_by' => Auth::id(), 'created_date' => now()];
                $id = DB::table('penjualan_program')->insertGetId($data);
            } else {
                $data += ['updated_by' => Auth::id(), 'updated_date' => now()];
                DB::table('penjualan_program')->where('id', $id)->update($data);
            }
            DB::table('penjualan_program_biaya')->where('id_penjualan', $id)->delete();
            foreach ($costs as $cost) {
                DB::table('penjualan_program_biaya')->insert(['id_penjualan' => $id] + $cost);
            }
            $new = (array) Sales::program($id);
            write_log('Penjualan Program', $old ? 'Edit' : 'Tambah', ($old ? 'Update ' : 'Tambah ').$new['nomor'], ['old' => $old ? (array) $old : [], 'new' => $new, 'detail_changes' => Costing::changes($before, $costs)], 'penjualan_program', $id, $new['nomor']);

            return [$id, $dropped, Sales::display($new)];
        });

        // Pesan sama dengan Master Program dan mockup saveCost: nomor, status Draft, dan jumlah item bernilai 0 yang dibuang.
        return redirect('/penjualan-program/'.$id)->with('success', 'Penjualan Program '.$nomor.' tersimpan sebagai Draft'.($dropped ? ' · '.$dropped.' item bernilai 0 dibuang' : '').'.');
    }

    public function status(Request $r, int $id, string $action)
    {
        abort_unless(in_array($action, ['finalkan', 'batalkan'], true), 404);
        Gate::authorize('menu', [self::MENU, $action === 'finalkan' ? 'Approve' : 'Manage']);
        $reason = $action === 'batalkan' ? $r->validate(['alasan' => 'required|string|max:300'], ['alasan.required' => 'Isi alasan pembatalan.'])['alasan'] : null;
        $message = Sales::transaction(function () use ($id, $action, $reason) {
            $old = Sales::program($id, true);
            Sales::open($old);
            if ($action === 'batalkan') {
                $done = ProgramCancel::apply($old, trim($reason));

                return 'Penjualan Program '.Sales::display($old).' dibatalkan'.($done['quotation'] || $done['sales_order'] || $done['invoice_belum'] || $done['refund_dibuat']
                    ? '; '.$done['quotation'].' quotation, '.$done['sales_order'].' SO, '.$done['invoice_belum'].' invoice ikut batal'.($done['refund_dibuat'] ? ', '.$done['refund_dibuat'].' refund menunggu approval' : '') : '').'.';
            }
            if ($old->status !== 'draft') {
                throw ValidationException::withMessages(['program' => 'Hanya Draft dapat difinalkan.']);
            }
            if ($action === 'finalkan') {
                $input = (array) $old;
                foreach (['harga_jual_peserta', 'target_profit'] as $field) {
                    $input[$field] = (int) $old->$field;
                }
                $input['biaya'] = array_map(fn ($b) => array_replace($b, ['harga' => (int) $b['harga']]), Sales::costs($id));
                $this->validated(new Request($input), $old);
            }
            $new = ['status' => 'final', 'updated_by' => Auth::id(), 'updated_date' => now(), 'confirmed_by' => Auth::id(), 'confirmed_date' => now()];
            DB::table('penjualan_program')->where('id', $id)->update($new);
            write_log('Penjualan Program', 'Finalkan', 'Finalkan '.$old->nomor, ['old' => (array) $old, 'new' => array_merge((array) $old, $new)], 'penjualan_program', $id, $old->nomor);

            return 'Penjualan Program '.Sales::display($old).' Final: siap Quotation.';
        });

        return back()->with('success', $message);
    }

    /** Ringkasan dampak pembatalan untuk modal konfirmasi (mockup qtCancel). Hanya membaca. */
    public function cancelImpact(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        $p = Sales::program($id);

        return response()->json(['allowed' => ProgramCancel::allowed($p), 'nomor' => Sales::display($p)] + ProgramCancel::impact($p));
    }
}

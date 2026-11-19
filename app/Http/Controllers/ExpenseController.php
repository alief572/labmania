<?php

namespace App\Http\Controllers;

use App\Support\Expenses;
use App\Support\Permissions;
use App\Support\QuotationFiles;
use App\Support\Sales;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('menu', ['pengajuan_biaya', 'View']);
        ServerDataTable::prepare($r);
        $f = $r->validate(['tab' => 'nullable|in:program,pengajuan', 'q' => 'nullable|string|max:100', 'jenis' => 'nullable|in:public,b2b,kasbon,direct_payment', 'status' => 'nullable|in:menunggu,disetujui,paid,ditolak,batal']);
        $base = DB::table('penjualan_program as p')->where('p.status', 'final');
        Expenses::scope($base);
        $counts = ['program' => (clone $base)->count(), 'pengajuan' => (clone $base)->join('pengajuan_biaya as h', 'h.id_penjualan', '=', 'p.id')->count()];
        $done = ($f['tab'] ?? 'program') === 'pengajuan';
        if ($r->route('datatable')) {
            $total = clone $base;
            if ($done) {
                $total->join('pengajuan_biaya as h', 'h.id_penjualan', '=', 'p.id');
            }
            ServerDataTable::scope($r, $total);
        }
        if ($done) {
            $base->join('pengajuan_biaya as h', 'h.id_penjualan', '=', 'p.id')->join('users as u', 'u.id_user', '=', 'h.request_by');
            $base->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('h.jenis', $v))->when($f['status'] ?? null, fn ($q, $v) => $q->where('h.status', $v));
        } else {
            $base->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('p.jenis', $v));
        }
        if (($f['q'] ?? '') !== '') {
            $v = '%'.$f['q'].'%';
            $base->where(function ($w) use ($v, $done) {
                $w->where('p.nomor', 'like', $v)->orWhereExists(fn ($z) => $z->selectRaw('1')->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->whereColumn('m.id_master_program', 'p.id_master_program')->where('k.judul', 'like', $v))
                    ->orWhereExists(fn ($z) => $z->selectRaw('1')->from('master_customer as c')->whereColumn('c.id_customer', 'p.id_customer')->where('c.name_customer', 'like', $v))
                    ->orWhereExists(fn ($z) => $z->selectRaw('1')->from('quotation as q')->join('ms_user_karyawan as s', 's.id', '=', 'q.id_sales')->whereColumn('q.id_penjualan', 'p.id')->where('q.status', 'deal')->where('s.nama', 'like', $v))
                    ->orWhereExists(fn ($z) => $z->selectRaw('1')->from('ms_trainer as t')->whereColumn('t.id', 'p.id_trainer')->where('t.nama', 'like', $v));
                if ($done) {
                    $w->orWhere('h.nomor', 'like', $v)->orWhere('h.deskripsi', 'like', $v)->orWhere('u.nm_lengkap', 'like', $v);
                }
            });
        }
        $rows = $base->select($done ? ['h.id', 'h.nomor', 'h.id_penjualan', 'h.jenis', 'h.tipe_biaya', 'h.deskripsi', 'h.total', 'h.status', 'h.tanggal', 'u.nm_lengkap as pengaju', 'p.id_master_program', 'p.nomor as program_nomor'] : ['p.*'])
            ->orderByDesc($done ? 'h.id' : 'p.id')->pipe(fn ($builder) => ServerDataTable::page($r, $builder, ($done ? [null, 'h.nomor', 'p.nomor', 'h.deskripsi', 'h.total', 'u.nm_lengkap', 'h.status', null] : [null, 'p.nomor', null, null, null, null]), $done ? 'h.id' : 'p.id'));
        $titles = Sales::titles($rows->pluck('id_master_program')->unique()->all());
        $budgets = $done ? [] : Expenses::budgets($rows->pluck('id')->all());
        $rows->through(function ($x) use ($done, $titles, $budgets) {
            $x->judul = $titles[$x->id_master_program] ?? '';
            if (! $done) {
                $x->nomor = Sales::display($x);
                $x->budget = $budgets[$x->id];
                $x->waiting = DB::table('pengajuan_biaya')->where('id_penjualan', $x->id)->where('status', 'menunggu')->count();
                $x->pengajuan_count = DB::table('pengajuan_biaya')->where('id_penjualan', $x->id)->count();
                $x->tim = $this->team($x);
            }

            return $x;
        });

        $props = ['rows' => $rows, 'filters' => $f, 'counts' => $counts, 'tab' => $done ? 'pengajuan' : 'program'];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Expenses/Index', $props);
    }

    private function team(object $p): array
    {
        return ['sales' => DB::table('quotation as q')->join('ms_user_karyawan as s', 's.id', '=', 'q.id_sales')->where('q.id_penjualan', $p->id)->where('q.status', 'deal')->distinct()->pluck('s.nama'),
            'trainer' => DB::table('ms_trainer')->where('id', $p->id_trainer)->value('nama'),
            'company' => $p->id_customer ? DB::table('master_customer')->where('id_customer', $p->id_customer)->value('name_customer') : 'Public'];
    }

    public function program(Request $r, int $id)
    {
        $p = Expenses::access($id);
        if ($r->route('datatable')) {
            ServerDataTable::prepare($r);
            $f = $r->validate(['q' => 'nullable|string|max:100', 'kategori' => 'required|in:fixed,variable', 'kind' => 'required|in:pengajuan,overbudget']);
            $table = $f['kind'] === 'overbudget' ? 'pengajuan_overbudget' : 'pengajuan_biaya';
            $description = $f['kind'] === 'overbudget' ? 'alasan' : 'deskripsi';
            $query = DB::table($table.' as h')->join('users as u', 'u.id_user', '=', 'h.request_by')->where('h.id_penjualan', $id)->where('h.tipe_biaya', $f['kategori']);
            ServerDataTable::scope($r, $query);
            if (($f['q'] ?? '') !== '') {
                $like = '%'.$f['q'].'%';
                $query->where(fn ($w) => $w->where('h.nomor', 'like', $like)->orWhere('h.'.$description, 'like', $like)->orWhere('u.nm_lengkap', 'like', $like));
            }
            $query->select('h.id', 'h.nomor', 'h.total', 'h.status', 'h.tanggal', 'u.nm_lengkap as pengaju', 'h.'.$description)
                ->selectRaw($f['kind'] === 'overbudget' ? "'Overbudget' as jenis" : 'h.jenis')->orderByDesc('h.id');

            $columns = $f['kind'] === 'overbudget'
                ? [null, 'h.nomor', null, 'h.total', 'h.status', 'u.nm_lengkap', null]
                : [null, 'h.nomor', 'h.deskripsi', 'h.total', 'h.jenis', 'u.nm_lengkap', 'h.status', 'h.tanggal', null];
            $page = ServerDataTable::page($r, $query, $columns, 'h.id');
            if ($f['kind'] === 'overbudget') {
                $items = DB::table('pengajuan_overbudget_detail')->whereIn('id_overbudget', $page->getCollection()->pluck('id'))->orderBy('id')->get(['id_overbudget', 'item'])->groupBy('id_overbudget');
                $page->getCollection()->each(function ($row) use ($items) {
                    $row->items = $items->get($row->id, collect())->pluck('item')->all();
                });
            }

            return ServerDataTable::result($r, $page);
        }
        $p->judul = Sales::title($p->id_master_program);
        $p->nomor = Sales::display($p);
        $p->tim = $this->team($p);
        $category = $r->validate(['kategori' => 'nullable|in:fixed,variable'])['kategori'] ?? 'fixed';
        $creating = $r->validate(['buat' => 'nullable|in:pengajuan,overbudget'])['buat'] ?? null;
        if ($creating) {
            Expenses::access($id, 'Add');
            Sales::open($p);
        }
        $query = DB::table('penjualan_program as p')->where('p.id', $id);
        Expenses::team($query);
        $inTeam = Expenses::role('Direktur') || $query->exists();
        $headers = [];
        foreach (['pengajuan_biaya', 'pengajuan_overbudget'] as $t) {
            $headers[$t] = [];
        }
        $lastAccount = DB::table('pengajuan_biaya')->where('request_by', Auth::id())->where('jenis', 'kasbon')->orderByDesc('id')->first(['bank', 'no_rekening', 'nama_rekening']);

        return Inertia::render('Expenses/Program', ['program' => $p, 'category' => $category, 'budget' => Expenses::budget($id, $category), 'budgets' => Expenses::budgets([$id])[$id], 'headers' => $headers,
            'canAdd' => ! $p->closed_date && Gate::allows('menu', ['pengajuan_biaya', 'Add']), 'notTeam' => ! $inTeam, 'lastAccount' => $lastAccount, 'creating' => $creating, 'requestBy' => Auth::user()->nm_lengkap, 'today' => today()->toDateString()]);
    }

    public function store(Request $r, int $id, string $kind)
    {
        Expenses::access($id, 'Add');
        $over = $kind === 'overbudget';
        $rules = ['tipe_biaya' => 'required|in:fixed,variable', 'items' => 'required|array|min:1|max:100', 'items.*.key' => 'nullable|string|max:30', 'items.*.item' => 'nullable|string|max:150',
            'items.*.qty' => ['required', 'regex:/^[0-9]{1,8}(\.[0-9]{1,2})?$/'], 'items.*.harga' => ['required', 'regex:/^[0-9]{1,13}(\.[0-9]{1,2})?$/']];
        $rules += $over ? ['alasan' => 'required|string|max:2000'] : ['jenis' => 'required|in:kasbon,direct_payment', 'deskripsi' => 'required|string|max:2000', 'bank' => 'required|string|max:50', 'no_rekening' => ['required', 'regex:/^[0-9 -]{3,30}$/'], 'nama_rekening' => 'required|string|max:100', 'bukti' => 'required_if:jenis,direct_payment|array|max:10', 'bukti.*' => 'file|max:5120|mimes:pdf,jpg,jpeg,png'];
        $data = $r->validate($rules);
        unset($data['bukti']);
        $files = [];
        try {
            foreach ($over ? [] : ($r->file('bukti') ?? []) as $file) {
                $files[] = QuotationFiles::store($file, false, 'uploads/pengajuan_biaya/');
            }
            $newId = Expenses::save($id, $data, $over, $files);
        } catch (\Throwable $e) {
            QuotationFiles::discard($files);
            throw $e;
        }

        return redirect('/pengajuan-biaya/'.$kind.'/'.$newId)->with('success', 'Pengajuan tersimpan.');
    }

    public function show(string $kind, int $id)
    {
        $over = $kind === 'overbudget';
        $table = $over ? 'pengajuan_overbudget' : 'pengajuan_biaya';
        $h = DB::table($table)->where('id', $id)->first() ?? abort(404);
        // Approver tetap membutuhkan hak lihat Approval serta cakupan program.
        $approvalView = ! Gate::allows('menu', ['pengajuan_biaya', 'View']) && Gate::allows('menu', ['approval_pengajuan_biaya', 'View']);
        $p = Expenses::access($h->id_penjualan, 'View', $approvalView);
        $p->judul = Sales::title($p->id_master_program);
        $h->pengaju = DB::table('users')->where('id_user', $h->request_by)->value('nm_lengkap');

        return Inertia::render('Expenses/Show', ['header' => $h, 'program' => $p, 'kind' => $kind, 'details' => DB::table($table.'_detail')->where($over ? 'id_overbudget' : 'id_pengajuan', $id)->get(),
            'files' => $over ? [] : DB::table('pengajuan_biaya_lampiran')->where('id_pengajuan', $id)->get(['id', 'nama_file']), 'budget' => Expenses::budget($p->id, $h->tipe_biaya),
            'history' => DB::table('logs')->where('ref_table', $table)->where('ref_id', $id)->orderBy('id')->get(['action', 'description', 'created_date']),
            'canApprove' => (Permissions::superadmin(Auth::user()) || Expenses::role('Direktur')) && Gate::allows('menu', ['approval_pengajuan_biaya', 'Approve']) && (int) $h->request_by !== (int) Auth::id(),
            'canCancel' => (int) $h->request_by === (int) Auth::id() && Gate::allows('menu', ['pengajuan_biaya', 'Add']), 'canPay' => ! $over && Gate::allows('menu', ['pengajuan_biaya', 'Manage'])]);
    }

    public function process(Request $r, string $kind, int $id, string $action)
    {
        $data = $r->validate(['alasan_tolak' => 'required_if:action,tolak|nullable|string|max:2000', 'paid_date' => 'nullable|date_format:Y-m-d', 'paid_keterangan' => 'nullable|string|max:150', 'bukti' => 'nullable|array|max:10', 'bukti.*' => 'file|max:5120|mimes:pdf,jpg,jpeg,png']);
        if ($action === 'tolak') {
            $data = $r->validate(['alasan_tolak' => 'required|string|max:2000']);
        }
        if ($action === 'paid') {
            $data += $r->validate(['paid_date' => 'required|date_format:Y-m-d']);
        }
        $files = [];
        try {
            if ($action === 'paid') {
                foreach ($r->file('bukti') ?? [] as $f) {
                    $files[] = QuotationFiles::store($f, false, 'uploads/pengajuan_biaya/');
                }
            }
            Expenses::transition($id, $kind === 'overbudget', $action, $data, $files);
        } catch (\Throwable $e) {
            QuotationFiles::discard($files);
            throw $e;
        }

        return back()->with('success', 'Status pengajuan diperbarui.');
    }

    public function approval(Request $r)
    {
        Gate::authorize('menu', ['approval_pengajuan_biaya', 'View']);
        ServerDataTable::prepare($r);
        $f = $r->validate(['tab' => 'nullable|in:pengajuan,overbudget', 'q' => 'nullable|string|max:100']);
        $kind = $f['tab'] ?? 'pengajuan';
        $base = fn (string $table) => DB::table($table.' as h')->join('penjualan_program as p', 'p.id', '=', 'h.id_penjualan')
            ->join('users as u', 'u.id_user', '=', 'h.request_by')->where('h.status', 'menunggu')->where('p.status', 'final');
        $counts = [];
        foreach (['pengajuan' => 'pengajuan_biaya', 'overbudget' => 'pengajuan_overbudget'] as $key => $table) {
            $query = $base($table);
            Expenses::scope($query);
            $counts[$key] = $query->count();
            if ($key === $kind) {
                $q = $query;
            }
        }
        ServerDataTable::scope($r, $q);
        $description = $kind === 'overbudget' ? 'alasan' : 'deskripsi';
        $q->when($f['q'] ?? null, fn ($w, $v) => $w->where(fn ($z) => $z->where('h.nomor', 'like', '%'.$v.'%')->orWhere('p.nomor', 'like', '%'.$v.'%')->orWhere('h.'.$description, 'like', '%'.$v.'%')->orWhere('u.nm_lengkap', 'like', '%'.$v.'%')));
        $canApprove = (Permissions::superadmin(Auth::user()) || Expenses::role('Direktur')) && Gate::allows('menu', ['approval_pengajuan_biaya', 'Approve']);
        $rows = $q->select('h.id', 'h.nomor', 'h.request_by', 'h.total', 'h.tanggal', 'h.tipe_biaya', 'h.'.$description, 'p.id_master_program', 'p.jenis as jenis_program', 'p.nomor as program_nomor', 'u.nm_lengkap as pengaju')
            ->selectRaw($kind === 'overbudget' ? "'Overbudget' as jenis" : 'h.jenis')->orderByDesc('h.id')
            ->pipe(fn ($builder) => ServerDataTable::page($r, $builder, [null, 'h.nomor', 'p.nomor', 'h.tipe_biaya', 'h.'.$description, 'h.total', 'u.nm_lengkap', null], 'h.id'));
        $titles = Sales::titles($rows->pluck('id_master_program')->unique()->all());
        $rows->through(function ($row) use ($canApprove, $titles) {
            $row->judul = $titles[$row->id_master_program] ?? '';
            $row->canApprove = $canApprove && (int) $row->request_by !== (int) Auth::id();
            return $row;
        });
        $props = ['rows' => $rows, 'kind' => $kind, 'filters' => $f, 'counts' => $counts, 'canApprove' => $canApprove];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $rows, collect($props)->except('rows')->all());
        }
        return Inertia::render('Expenses/Approval', $props);
    }
}

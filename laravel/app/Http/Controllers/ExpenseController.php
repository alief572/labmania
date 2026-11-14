<?php

namespace App\Http\Controllers;

use App\Support\Expenses;
use App\Support\Permissions;
use App\Support\QuotationFiles;
use App\Support\Sales;
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
        $f = $r->validate(['tab' => 'nullable|in:program,pengajuan', 'q' => 'nullable|string|max:100', 'jenis' => 'nullable|in:public,b2b,kasbon,direct_payment', 'status' => 'nullable|in:menunggu,disetujui,paid,ditolak,batal']);
        $base = DB::table('penjualan_program as p')->where('p.status', 'final');
        Expenses::scope($base);
        $counts = ['program' => (clone $base)->count(), 'pengajuan' => (clone $base)->join('pengajuan_biaya as h', 'h.id_penjualan', '=', 'p.id')->count()];
        $done = ($f['tab'] ?? 'program') === 'pengajuan';
        if ($done) {
            $base->join('pengajuan_biaya as h', 'h.id_penjualan', '=', 'p.id')->join('users as u', 'u.id_user', '=', 'h.request_by');
            $base->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('h.jenis', $v))->when($f['status'] ?? null, fn ($q, $v) => $q->where('h.status', $v));
        } else {
            $base->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('p.jenis', $v));
        }
        if (! empty($f['q'])) {
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
            ->orderByDesc($done ? 'h.id' : 'p.id')->paginate(25)->withQueryString()->through(function ($x) use ($done) {
                $x->judul = Sales::title($x->id_master_program);
                if (! $done) {
                    $x->nomor = Sales::display($x);
                    $x->budget = ['fixed' => Expenses::budget($x->id, 'fixed'), 'variable' => Expenses::budget($x->id, 'variable')];
                    $x->waiting = DB::table('pengajuan_biaya')->where('id_penjualan', $x->id)->where('status', 'menunggu')->count();
                    $x->pengajuan_count = DB::table('pengajuan_biaya')->where('id_penjualan', $x->id)->count();
                    $x->tim = $this->team($x);
                }

                return $x;
            });

        return Inertia::render('Expenses/Index', ['rows' => $rows, 'filters' => $f, 'counts' => $counts, 'tab' => $done ? 'pengajuan' : 'program']);
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
            $headers[$t] = DB::table($t.' as h')->join('users as u', 'u.id_user', '=', 'h.request_by')->where('h.id_penjualan', $id)->where('h.tipe_biaya', $category)->orderByDesc('h.id')->get(['h.id', 'h.nomor', 'h.total', 'h.status', 'h.tanggal', 'h.request_by', 'u.nm_lengkap as pengaju', $t === 'pengajuan_biaya' ? 'h.deskripsi' : 'h.alasan']);
        }
        $lastAccount = DB::table('pengajuan_biaya')->where('request_by', Auth::id())->where('jenis', 'kasbon')->orderByDesc('id')->first(['bank', 'no_rekening', 'nama_rekening']);

        return Inertia::render('Expenses/Program', ['program' => $p, 'category' => $category, 'budget' => Expenses::budget($id, $category), 'headers' => $headers,
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
        $f = $r->validate(['tab' => 'nullable|in:pengajuan,overbudget', 'q' => 'nullable|string|max:100']);
        $kind = $f['tab'] ?? 'pengajuan';
        $table = $kind === 'overbudget' ? 'pengajuan_overbudget' : 'pengajuan_biaya';
        $q = DB::table($table.' as h')->join('penjualan_program as p', 'p.id', '=', 'h.id_penjualan')->join('users as u', 'u.id_user', '=', 'h.request_by')->where('h.status', 'menunggu')->where('p.status', 'final');
        Expenses::scope($q);
        $q->when($f['q'] ?? null, fn ($w, $v) => $w->where(fn ($z) => $z->where('h.nomor', 'like', '%'.$v.'%')->orWhere('p.nomor', 'like', '%'.$v.'%')->orWhere('u.nm_lengkap', 'like', '%'.$v.'%')));

        return Inertia::render('Expenses/Approval', ['rows' => $q->select('h.id', 'h.nomor', 'h.total', 'h.tanggal', 'h.tipe_biaya', 'p.nomor as program_nomor', 'u.nm_lengkap as pengaju')->orderByDesc('h.id')->paginate(25)->withQueryString(), 'kind' => $kind, 'filters' => $f]);
    }
}

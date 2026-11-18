<?php

namespace App\Http\Controllers;

use App\Support\Costing;
use App\Support\Sales;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Dashboard halaman depan (FR-40..42). Semua angka adalah total seluruh data tanpa filter periode dan berasal dari
 * Penjualan Program berstatus Final (rencana). Rumus memakai Costing::calculate yang identik dengan CI3
 * (Dashboard_labmania_model); tidak ada rumus baru di sini. Hak lihat lewat menu 'dashboard'.
 */
class DashboardController extends Controller
{
    private const MENU = 'dashboard';

    private const KPI = ['final', 'pend', 'prof', 'draft'];

    /** Penjualan Program pada status tertentu beserta hasil costing, urut tanggal mulai. */
    private function penjualan(array $statuses, ?array $ids = null): array
    {
        $rows = DB::table('penjualan_program as pj')->leftJoin('master_customer as c', 'c.id_customer', '=', 'pj.id_customer')
            ->whereIn('pj.status', $statuses)->when($ids !== null, fn ($q) => $q->whereIn('pj.id', $ids))->orderBy('pj.tgl_mulai')->orderBy('pj.id')->get(['pj.*', 'c.name_customer']);
        $costs = $this->costsFor($rows->pluck('id')->all());
        $durations = Sales::durationsFor($rows->pluck('id')->all());

        return $rows->map(function ($r) use ($costs, $durations) {
            $r->judul = $this->title((int) $r->id_master_program);
            $r->tampil = Sales::display($r);
            $r->c = Sales::costingOf($r, $costs[$r->id] ?? [], $durations);

            return $r;
        })->all();
    }

    /** Satu query untuk seluruh biaya, dikelompokkan per Penjualan Program (tanpa N+1). */
    private function costsFor(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return DB::table('penjualan_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')
            ->whereIn('b.id_penjualan', $ids)->get(['b.*', 's.nama as nama_satuan'])->groupBy('id_penjualan')
            ->map(fn ($g) => $g->map(fn ($b) => (array) $b)->all())->all();
    }

    private array $titles = [];

    private function title(int $master): string
    {
        return $this->titles[$master] ??= Sales::title($master);
    }

    private function draftMaster(): array
    {
        return DB::table('master_program')->where('status', 'draft')->orderBy('id')->get()
            ->map(function ($m) {
                $m->judul = $this->title((int) $m->id);

                return $m;
            })->all();
    }

    private function pj(object $r): array
    {
        return ['id' => $r->id, 'nomor' => $r->tampil, 'judul' => $r->judul, 'jenis' => $r->jenis, 'lokasi' => $r->lokasi, 'company' => $r->name_customer,
            'tgl_mulai' => $r->tgl_mulai, 'tgl_selesai' => $r->tgl_selesai, 'target' => (int) $r->target_peserta, 'status' => $r->status];
    }

    public function index()
    {
        // Halaman depan tetap terbuka bagi semua akun terverifikasi (perilaku sebelumnya); angka hanya untuk pemegang hak.
        if (Gate::denies('menu', [self::MENU, 'View'])) {
            return Inertia::render('Home');
        }
        $finals = $this->penjualan(['final']);
        $drafts = $this->penjualan(['draft']);
        $masters = $this->draftMaster();

        $pendapatan = array_sum(array_map(fn ($f) => $f->c['pendapatan'], $finals));
        $profit = array_sum(array_map(fn ($f) => $f->c['profit'], $finals));
        $public = count(array_filter($finals, fn ($f) => $f->jenis === 'public'));

        // Jadwal terdekat: maksimal 5 Final yang tanggal selesainya belum lewat, urut tanggal mulai (FR-42).
        $today = now()->format('Y-m-d');
        $jadwal = array_slice(array_values(array_filter($finals, fn ($f) => $f->tgl_selesai !== null && $f->tgl_selesai >= $today)), 0, 5);

        $todo = [];
        foreach ($masters as $m) {
            $todo[] = ['type' => 'mp', 'id' => $m->id, 'nomor' => $m->nomor, 'judul' => $m->judul, 'msg' => 'Master Program masih Draft — finalkan agar bisa dijual', 'warn' => false];
        }
        foreach ([...$drafts, ...$finals] as $p) {
            $base = ['type' => 'pj', 'id' => $p->id, 'nomor' => $p->tampil, 'judul' => $p->judul];
            if ($p->status === 'draft') {
                $todo[] = $base + ['msg' => 'Penjualan Program masih Draft', 'warn' => false];
            }
            $c = $p->c;
            if ($c['pendapatan'] && $c['profit'] < 0) {
                $todo[] = $base + ['msg' => 'Rugi Rp '.number_format(abs($c['profit']), 0, ',', '.'), 'warn' => true];
            } elseif ($c['bep_peserta'] === null || $c['bep_peserta'] > (int) $p->target_peserta) {
                $todo[] = $base + ['msg' => $c['bep_peserta'] === null ? 'Titik impas tidak tercapai' : 'Titik impas '.$c['bep_peserta'].' peserta, rencana '.(int) $p->target_peserta, 'warn' => true];
            }
        }

        return Inertia::render('Dashboard/Index', [
            'kpi' => [
                'final' => count($finals), 'public' => $public, 'b2b' => count($finals) - $public,
                'pendapatan' => $pendapatan, 'peserta' => array_sum(array_map(fn ($f) => (int) $f->target_peserta, $finals)),
                'profit' => $profit, 'margin' => $pendapatan > 0 ? round($profit / $pendapatan * 100, 1) : 0,
                'draft' => count($masters) + count($drafts),
            ],
            'jadwal' => array_map(fn ($f) => $this->pj($f), $jadwal),
            'todo' => array_slice($todo, 0, 8), 'todoTotal' => count($todo),
            'can' => ['master' => Gate::allows('menu', ['master_program', 'View']), 'sales' => Gate::allows('menu', ['penjualan_program', 'View'])],
        ]);
    }

    public function data(Request $request, string $kpi)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        abort_unless(in_array($kpi, self::KPI, true), 404);
        ServerDataTable::prepare($request);
        $input = $request->validate(['q' => 'nullable|string|max:100', 'source' => 'nullable|in:master,penjualan']);
        $master = $kpi === 'draft' && ($input['source'] ?? 'master') === 'master';
        $query = DB::table($master ? 'master_program as p' : 'penjualan_program as p')->where('p.status', $kpi === 'draft' ? 'draft' : 'final');
        ServerDataTable::scope($request, $query);
        if (($input['q'] ?? '') !== '') {
            $term = '%'.$input['q'].'%';
            $query->where(fn ($q) => $q->where('p.nomor', 'like', $term)->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                ->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')
                ->whereColumn('m.id_master_program', $master ? 'p.id' : 'p.id_master_program')->where('k.judul', 'like', $term)));
        }
        $filtered = clone $query;
        $columns = array_fill(0, $kpi === 'draft' ? 2 : ($kpi === 'final' ? 3 : ($kpi === 'pend' ? 5 : 9)), null);
        $columns[1] = 'p.nomor';
        $page = ServerDataTable::page($request, $query->select('p.*')->orderBy('p.id'), $columns, 'p.id');
        if ($master) {
            $page->through(fn ($r) => ['id' => $r->id, 'nomor' => $r->nomor, 'judul' => $this->title((int) $r->id)]);
            return ServerDataTable::result($request, $page);
        }
        $rows = collect($this->penjualan([$kpi === 'draft' ? 'draft' : 'final'], $page->pluck('id')->all()))->keyBy('id');
        $page->through(fn ($r) => $this->pj($rows[$r->id]) + ['harga' => (float) $r->harga_jual_peserta,
            'pendapatan' => $rows[$r->id]->c['pendapatan'], 'fixed' => $rows[$r->id]->c['total_fixed'],
            'variable' => $rows[$r->id]->c['total_var'], 'profit' => $rows[$r->id]->c['profit'], 'margin' => $rows[$r->id]->c['margin']]);
        // ponytail: totals use the existing costing engine across matching rows; SQL aggregation needs parity checks first.
        $all = $this->penjualan([$kpi === 'draft' ? 'draft' : 'final'], $filtered->pluck('p.id')->all());
        $totals = ['count' => count($all), 'target' => array_sum(array_column($all, 'target_peserta'))];
        foreach (['pendapatan' => 'pendapatan', 'fixed' => 'total_fixed', 'variable' => 'total_var', 'profit' => 'profit'] as $key => $field) {
            $totals[$key] = array_sum(array_map(fn ($r) => $r->c[$field], $all));
        }
        return ServerDataTable::result($request, $page, ['totals' => $totals]);
    }

    /** Rincian satu kartu KPI (FR-41): sumber data, rumus, dan baris penyusun angka. */
    public function detail(string $kpi)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        abort_unless(in_array($kpi, self::KPI, true), 404);
        if ($kpi === 'draft') {
            return response()->json([
                'master' => array_map(fn ($m) => ['id' => $m->id, 'nomor' => $m->nomor, 'judul' => $m->judul], $this->draftMaster()),
                'penjualan' => array_map(fn ($p) => $this->pj($p), $this->penjualan(['draft'])),
            ]);
        }

        return response()->json(['finals' => array_map(function ($f) {
            return $this->pj($f) + ['harga' => (float) $f->harga_jual_peserta, 'pendapatan' => $f->c['pendapatan'], 'fixed' => $f->c['total_fixed'],
                'variable' => $f->c['total_var'], 'hpp' => $f->c['total_hpp'], 'profit' => $f->c['profit'], 'margin' => $f->c['margin']];
        }, $this->penjualan(['final']))]);
    }
}

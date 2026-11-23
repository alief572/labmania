<?php

namespace App\Http\Controllers;

use App\Support\CodeGenerator;
use App\Support\Costing;
use App\Support\ServerDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Master Program (MP-FR-01..15): satu costing untuk satu atau lebih Product. Hanya Draft yang bisa diubah;
 * Finalkan memakai hak Approve, Batalkan memakai Manage. Costing dihitung ulang di server (MP-NFR-06).
 */
class MasterProgramController extends Controller
{
    private const MENU = 'master_program';

    private const STATUS = ['draft', 'final', 'batal'];

    // ---- Daftar ----

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        ServerDataTable::prepare($request);
        $f = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:all,draft,final,batal']);
        $query = DB::table('master_program as mp');
        ServerDataTable::scope($request, $query);
        $rows = $query
            ->when(in_array($f['status'] ?? 'all', self::STATUS, true), fn ($q) => $q->where('mp.status', $f['status']))
            ->when(($f['q'] ?? null) !== null, fn ($q) => $q->where(fn ($w) => $w->where('mp.nomor', 'like', '%'.$f['q'].'%')->orWhereExists(
                fn ($e) => $e->select(DB::raw(1))->from('paket_produk as p')->whereColumn('p.id', 'mp.id_paket_produk')
                    ->where(fn ($m) => $m->where('p.judul', 'like', '%'.$f['q'].'%')->orWhere('p.nomor', 'like', '%'.$f['q'].'%')))))
            ->orderByDesc('mp.id')->select('mp.*')->pipe(fn ($builder) => ServerDataTable::page($request, $builder, [null, 'mp.nomor', null, 'mp.durasi_hari', 'mp.target_peserta', 'mp.harga_jual_peserta', null, 'mp.status', null], 'mp.id'));

        $ids = $rows->pluck('id');
        $products = DB::table('paket_produk as p')
            ->leftJoin('ms_varian_pelaksanaan as v', 'v.id', '=', 'p.id_varian_pelaksanaan')->leftJoin('ms_jenis_sertifikasi as s', 's.id', '=', 'p.id_jenis_sertifikasi')
            ->whereIn('p.id', $rows->pluck('id_paket_produk'))->get(['p.id', 'p.nomor', 'p.judul', 'v.nama as nama_varian', 's.nama as nama_sertifikasi'])->keyBy('id');
        $biaya = $this->biayaFor($ids->all())->groupBy('id_master_program');
        $rows->through(function ($r) use ($products, $biaya) {
            $p = $products[$r->id_paket_produk] ?? null;
            $r->judul = $p->judul ?? '';
            $r->nomor_product = $p->nomor ?? '';
            $r->nama_varian = $p->nama_varian ?? null;
            $r->nama_sertifikasi = $p->nama_sertifikasi ?? null;
            $r->hpp = Costing::calculate($r->durasi_hari, $r->target_peserta, $r->harga_jual_peserta, ($biaya[$r->id] ?? collect())->map(fn ($b) => (array) $b)->all())['total_hpp'];

            return $r;
        });

        $props = [
            'rows' => $rows, 'filters' => ['q' => $f['q'] ?? '', 'status' => $f['status'] ?? 'all'],
            'can' => ['add' => Gate::allows('menu', [self::MENU, 'Add']), 'manage' => Gate::allows('menu', [self::MENU, 'Manage']), 'approve' => Gate::allows('menu', [self::MENU, 'Approve'])],
        ];
        if ($request->route('datatable')) {
            return ServerDataTable::result($request, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Master/Program/Index', $props);
    }

    private function biayaFor(array $programIds)
    {
        return DB::table('master_program_biaya as b')->leftJoin('ms_satuan as s', 's.id', '=', 'b.id_satuan')
            ->whereIn('b.id_master_program', $programIds)->orderBy('b.tipe')->orderBy('b.id')->get(['b.*', 's.nama as nama_satuan']);
    }

    // ---- Form ----

    public function create()
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        // Program baru: semua Fixed Cost aktif terisi Default Cost (MP-FR-06); yang tak dipakai dihapus pengguna.
        $fixed = DB::table('ms_rate as r')->leftJoin('ms_satuan as s', 's.id', '=', 'r.id_satuan')->where('r.tipe', 'fixed')->where('r.aktif', 1)->orderBy('r.id')->get(['r.id', 'r.item', 'r.id_satuan', 'r.default_cost', 's.nama as nama_satuan']);
        $biaya = $fixed->map(fn ($r) => ['id_rate' => (int) $r->id, 'tipe' => 'fixed', 'item' => $r->item, 'satuan' => $r->nama_satuan, 'id_satuan' => (int) $r->id_satuan, 'harga' => (int) round($r->default_cost), 'is_manual' => false])->all();

        return $this->form(['id' => null, 'nomor' => null, 'status' => 'draft', 'paket' => '', 'durasi_hari' => 1, 'jam_pembelajaran' => '', 'target_peserta' => 10, 'harga_jual_peserta' => '', 'target_profit' => '', 'biaya' => $biaya], 0);
    }

    public function show(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $mp = DB::table('master_program')->where('id', $id)->first() ?? abort(404);
        $biaya = $this->biayaFor([$id])->map(fn ($b) => ['id_rate' => $b->id_rate ? (int) $b->id_rate : null, 'tipe' => $b->tipe, 'item' => $b->item, 'satuan' => $b->nama_satuan, 'id_satuan' => (int) $b->id_satuan, 'harga' => (int) round($b->harga), 'is_manual' => (bool) $b->is_manual])->all();
        $paket = (int) $mp->id_paket_produk;

        return $this->form(['id' => $mp->id, 'nomor' => $mp->nomor, 'status' => $mp->status, 'paket' => $paket, 'durasi_hari' => (int) $mp->durasi_hari, 'jam_pembelajaran' => $mp->jam_pembelajaran, 'target_peserta' => (int) $mp->target_peserta,
            'harga_jual_peserta' => (int) round($mp->harga_jual_peserta), 'target_profit' => $mp->target_profit > 0 ? (int) round($mp->target_profit) : '', 'biaya' => $biaya], $paket);
    }

    private function form(array $program, int $usedProduct)
    {
        $satuan = ['fixed' => RateController::satuans('fixed')->pluck('id', 'nama'), 'variable' => RateController::satuans('variable')->pluck('id', 'nama')];
        $products = DB::table('paket_produk as pp')->leftJoin('ms_jenis_produk as jp', 'jp.id', '=', 'pp.id_jenis_produk')->leftJoin('ms_jenis_bidang as jb', 'jb.id', '=', 'pp.id_jenis_bidang')
            ->leftJoin('ms_varian_pelaksanaan as vp', 'vp.id', '=', 'pp.id_varian_pelaksanaan')->leftJoin('ms_jenis_sertifikasi as js', 'js.id', '=', 'pp.id_jenis_sertifikasi')
            ->where(fn ($q) => $q->where('pp.aktif', 1)->orWhere('pp.id', $usedProduct))->orderBy('pp.id')
            ->get(['pp.id', 'pp.nomor', 'pp.judul', 'pp.aktif', 'jp.nama as produk', 'jb.nama as bidang', 'vp.nama as varian', 'js.nama as sertifikat']);
        $rates = DB::table('ms_rate as r')->leftJoin('ms_satuan as s', 's.id', '=', 'r.id_satuan')->orderBy('r.id')->get(['r.id', 'r.tipe', 'r.item', 'r.id_satuan', 'r.default_cost', 'r.aktif', 's.nama as nama_satuan']);

        return Inertia::render('Master/Program/Form', [
            'program' => $program, 'products' => $products, 'rates' => $rates, 'satuan' => $satuan,
            'can' => ['edit' => $program['id'] ? Gate::allows('menu', [self::MENU, 'Manage']) : Gate::allows('menu', [self::MENU, 'Add']),
                'approve' => Gate::allows('menu', [self::MENU, 'Approve']), 'cancel' => Gate::allows('menu', [self::MENU, 'Manage'])],
        ]);
    }

    private function productLabel(int $id): string
    {
        return DB::table('paket_produk')->where('id', $id)->value('nomor') ?? '#'.$id;
    }

    // ---- Validasi ----

    private function whole(Request $request, array $keys): void
    {
        $request->merge(collect($keys)->mapWithKeys(fn ($k) => [$k => is_string($request->input($k)) ? str_replace('.', '', trim($request->input($k))) : $request->input($k)])->all());
    }

    /** @return array{0: array, 1: array, 2: int} header (termasuk id_paket_produk), biaya bersih, jumlah item bernilai 0 yang dibuang */
    private function validated(Request $request, ?object $old): array
    {
        // Titik pemisah ribuan hanya dibuang untuk kolom rupiah; durasi, JP, dan target peserta harus bilangan bulat apa adanya
        // (JP pecahan belum diputuskan BA, Q-PJ-25; "1.5" tidak boleh diam-diam menjadi 15).
        $this->whole($request, ['harga_jual_peserta', 'target_profit']);
        $in = $request->validate([
            'paket' => 'required|integer', 'durasi_hari' => 'required|integer|min:1|max:3650', 'jam_pembelajaran' => 'required|integer|min:1|max:10000',
            'target_peserta' => 'required|integer|min:1|max:100000', 'harga_jual_peserta' => 'required|integer|min:0|max:9999999999999', 'target_profit' => 'nullable|integer|min:0|max:9999999999999',
            'biaya' => 'nullable|array|max:200',
        ], [
            'paket.required' => 'Pilih Product.', 'paket.integer' => 'Pilih Product.', 'durasi_hari.required' => 'Durasi bilangan bulat > 0.', 'durasi_hari.min' => 'Durasi bilangan bulat > 0.', 'durasi_hari.integer' => 'Durasi bilangan bulat > 0.',
            'jam_pembelajaran.required' => 'Jam Pembelajaran wajib diisi (bilangan bulat > 0).', 'jam_pembelajaran.min' => 'Jam Pembelajaran bilangan bulat > 0.', 'jam_pembelajaran.integer' => 'Jam Pembelajaran bilangan bulat > 0.',
            'target_peserta.required' => 'Target peserta bilangan bulat > 0.', 'target_peserta.min' => 'Target peserta bilangan bulat > 0.', 'target_peserta.integer' => 'Target peserta bilangan bulat > 0.',
            'harga_jual_peserta.required' => 'Harga jual / peserta wajib diisi.', 'target_profit.integer' => 'Target profit berupa angka rupiah ≥ 0.',
        ]);
        $errors = [];
        $paket = (int) $in['paket'];
        $product = DB::table('paket_produk')->where('id', $paket)->first();
        if (! $product) {
            $errors['paket'] = 'Product tidak ditemukan.';
        } elseif (! $product->aktif && (int) ($old->id_paket_produk ?? 0) !== $paket) {
            $errors['paket'] = 'Product '.$product->nomor.' harus berstatus Aktif.';
        }
        [$biaya, $biayaErrors, $dropped] = Costing::cleanRows($in['biaya'] ?? [], $old ? DB::table('master_program_biaya')->where('id_master_program', $old->id)->pluck('id_rate')->filter()->map(fn ($v) => (int) $v)->all() : []);
        $errors += $biayaErrors;
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [['id_paket_produk' => $paket, 'durasi_hari' => (int) $in['durasi_hari'], 'jam_pembelajaran' => (int) $in['jam_pembelajaran'], 'target_peserta' => (int) $in['target_peserta'],
            'harga_jual_peserta' => (float) $in['harga_jual_peserta'], 'target_profit' => (float) ($in['target_profit'] ?? 0)], $biaya, $dropped];
    }

    // ---- Simpan ----

    private function writeBiaya(int $id, array $biaya): void
    {
        DB::table('master_program_biaya')->where('id_master_program', $id)->delete();
        foreach ($biaya as $b) {
            DB::table('master_program_biaya')->insert(['id_master_program' => $id] + $b);
        }
    }

    private function savedMessage(string $nomor, int $dropped): string
    {
        return 'Master Program '.$nomor.' tersimpan sebagai Draft'.($dropped ? ' · '.$dropped.' item bernilai 0 dibuang' : '').'.';
    }

    public function store(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'Add']);
        [$id, $nomor, $dropped] = DB::transaction(function () use ($request) {
            [$header, $biaya, $dropped] = $this->validated($request, null);
            $nomor = CodeGenerator::next('master_program', 'MP-'.now()->format('ym'), 'nomor');
            $row = ['nomor' => $nomor, 'status' => 'draft', 'created_by' => Auth::id(), 'created_date' => now()] + $header;
            $id = DB::table('master_program')->insertGetId($row);
            $this->writeBiaya($id, $biaya);
            write_log('Master Program', 'Tambah', 'Tambah Master Program '.$nomor, $header + ['product' => $this->productLabel($header['id_paket_produk']), 'jumlah_biaya' => count($biaya)], 'master_program', $id, $nomor);

            return [$id, $nomor, $dropped];
        });

        return to_route('master-program.show', $id)->with('success', $this->savedMessage($nomor, $dropped));
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);
        [$nomor, $dropped] = DB::transaction(function () use ($request, $id) {
            // Status dibaca ulang dengan kunci baris: Final/Batal tidak dapat ditimpa (MP-NFR-02/03).
            $old = DB::table('master_program')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            if ($old->status !== 'draft') {
                throw ValidationException::withMessages(['program' => 'Hanya Master Program berstatus Draft yang dapat diubah.']);
            }
            [$header, $biaya, $dropped] = $this->validated($request, $old);
            $oldBiaya = DB::table('master_program_biaya')->where('id_master_program', $id)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $header += ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('master_program')->where('id', $id)->update($header);
            $this->writeBiaya($id, $biaya);
            $oldArr = (array) $old;
            write_log('Master Program', 'Edit', 'Update Master Program '.$old->nomor, [
                'old' => array_merge($oldArr, ['product' => $this->productLabel((int) $old->id_paket_produk)]),
                'new' => array_merge($oldArr, $header, ['product' => $this->productLabel($header['id_paket_produk'])]),
                'detail_changes' => Costing::changes($oldBiaya, $biaya),
            ], 'master_program', $id, $old->nomor);

            return [$old->nomor, $dropped];
        });

        return back()->with('success', $this->savedMessage($nomor, $dropped));
    }

    // ---- Status ----

    private function summary(object $mp): array
    {
        $biaya = $this->biayaFor([$mp->id])->map(fn ($b) => (array) $b)->all();
        $calc = Costing::calculate($mp->durasi_hari, $mp->target_peserta, $mp->harga_jual_peserta, $biaya);

        return ['id' => $mp->id, 'nomor' => $mp->nomor, 'status' => $mp->status, 'judul' => DB::table('paket_produk')->where('id', $mp->id_paket_produk)->value('judul') ?? '',
            'durasi' => (int) $mp->durasi_hari, 'target' => (int) $mp->target_peserta, 'harga' => (float) $mp->harga_jual_peserta, 'jp' => $mp->jam_pembelajaran, 'hpp' => $calc['total_hpp'], 'profit' => $calc['profit'],
            'bep_peserta' => $calc['bep_peserta'], 'warnings' => Costing::warnings($calc, $mp->target_peserta), 'jumlah_biaya' => count($biaya),
            'n_penjualan' => DB::table('penjualan_program')->where('id_master_program', $mp->id)->count()];
    }

    public function ringkasan(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'View']);

        return response()->json($this->summary(DB::table('master_program')->where('id', $id)->first() ?? abort(404)));
    }

    /** Finalkan butuh hak Approve terpisah (Manage tidak cukup). Isian wajib diperiksa di dalam transaksi; peringatan costing tidak memblokir (MP-FR-12). */
    public function finalkan(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Approve']);

        return $this->transition($id, 'final');
    }

    public function batalkan(int $id)
    {
        Gate::authorize('menu', [self::MENU, 'Manage']);

        return $this->transition($id, 'batal');
    }

    private function transition(int $id, string $to)
    {
        $message = DB::transaction(function () use ($id, $to) {
            $old = DB::table('master_program')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
            if ($old->status !== 'draft') {
                throw ValidationException::withMessages(['program' => $old->status === 'final'
                    ? 'Master Program '.$old->nomor.' sudah Final: tidak dapat difinalkan ulang, dibatalkan, atau diubah.'
                    : 'Master Program '.$old->nomor.' sudah dibatalkan.']);
            }
            $warnings = [];
            if ($to === 'final') {
                $summary = $this->summary($old);
                $missing = [];
                if (! $old->jam_pembelajaran || (int) $old->jam_pembelajaran < 1) {
                    $missing[] = 'Jam Pembelajaran';
                }
                if ((float) $old->harga_jual_peserta <= 0) {
                    $missing[] = 'Harga jual / peserta';
                }
                if ($summary['jumlah_biaya'] < 1) {
                    $missing[] = 'minimal satu item biaya';
                }
                if (! $old->id_paket_produk) {
                    $missing[] = 'Product';
                }
                if ($missing) {
                    throw ValidationException::withMessages(['program' => 'Belum dapat difinalkan, lengkapi: '.implode(', ', $missing).'.']);
                }
                $warnings = $summary['warnings'];
            }
            $now = now();
            $update = $to === 'final' ? ['status' => 'final', 'confirmed_by' => Auth::id(), 'confirmed_date' => $now] : ['status' => 'batal', 'closed_by' => Auth::id(), 'closed_date' => $now];
            $update += ['updated_by' => Auth::id(), 'updated_date' => $now];
            DB::table('master_program')->where('id', $id)->update($update);
            $action = $to === 'final' ? 'Finalkan' : 'Batalkan';
            write_log('Master Program', $action, $action.' Master Program '.$old->nomor, ['old' => (array) $old, 'new' => array_merge((array) $old, $update)], 'master_program', $id, $old->nomor);

            return 'Master Program '.$old->nomor.($to === 'final' ? ' Final: tersedia di pilihan Penjualan Program.' : ' dibatalkan.').($warnings ? ' Peringatan: '.implode(' ', $warnings) : '');
        });

        return back()->with('success', $message);
    }
}

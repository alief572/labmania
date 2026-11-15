<?php

namespace App\Http\Controllers;

use App\Support\ParticipantPdf;
use App\Support\Participants;
use App\Support\QuotationFiles;
use App\Support\QuotationPdf;
use App\Support\Sales;
use App\Support\ServerDataTable;
use App\Support\Survey;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ParticipantController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('menu', ['manage_peserta', 'View']);
        ServerDataTable::prepare($r);
        $f = Sales::filters($r);
        $q = DB::table('penjualan_program as p')->where('p.status', 'final');
        ($f['tab'] ?? 'ongoing') === 'selesai' ? $q->whereNotNull('p.closed_date') : $q->whereNull('p.closed_date');
        ServerDataTable::scope($r, $q);
        Sales::filter($q, $f);
        $this->searchProgram($q, $f['q'] ?? '');
        $rows = $q->orderBy('p.tgl_mulai')->pipe(fn ($builder) => ServerDataTable::page($r, $builder, [null, 'p.nomor', null, 'p.jenis', 'p.tgl_mulai', null, 'p.target_peserta', null, null], 'p.id'))->through(function ($p) {
            $p->nomor = Sales::display($p);
            $p->judul = Sales::title($p->id_master_program);
            $p->trainer = DB::table('ms_trainer')->where('id', $p->id_trainer)->value('nama');
            $p->company = $p->id_customer ? DB::table('master_customer')->where('id_customer', $p->id_customer)->value('name_customer') : 'Public';
            $p->peserta = Participants::query($p->id)->count();
            $p->blockers = $p->closed_date ? [] : Participants::blockers($p);
            $p->operational_status = $p->closed_date ? 'Closed' : ($p->tgl_selesai < today()->toDateString() ? 'Acara selesai' : 'On going');

            return $p;
        });

        $props = ['rows' => $rows, 'filters' => $f, 'canManage' => Gate::allows('menu', ['manage_peserta', 'Manage'])];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Participants/Index', $props);
    }

    private function searchProgram($query, string $term): void
    {
        if ($term !== '') {
            $query->where(fn ($w) => $w->where('p.nomor', 'like', '%'.$term.'%')->orWhere('p.lokasi', 'like', '%'.$term.'%')
                ->orWhereExists(fn ($z) => $z->selectRaw('1')->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->whereColumn('m.id_master_program', 'p.id_master_program')->where('k.judul', 'like', '%'.$term.'%'))
                ->orWhereExists(fn ($z) => $z->selectRaw('1')->from('ms_trainer as t')->whereColumn('t.id', 'p.id_trainer')->where('t.nama', 'like', '%'.$term.'%'))
                ->orWhereExists(fn ($z) => $z->selectRaw('1')->from('master_customer as c')->whereColumn('c.id_customer', 'p.id_customer')->where('c.name_customer', 'like', '%'.$term.'%')));
        }
    }

    public function show(Request $r, int $id)
    {
        Gate::authorize('menu', ['manage_peserta', 'View']);
        ServerDataTable::prepare($r);
        $p = Sales::program($id);
        abort_unless($p->status === 'final', 404);
        $p->judul = Sales::title($p->id_master_program);
        $p->nomor = Sales::display($p);
        $p->trainer = DB::table('ms_trainer')->where('id', $p->id_trainer)->value('nama');
        $p->company = $p->id_customer ? DB::table('master_customer')->where('id_customer', $p->id_customer)->value('name_customer') : 'Public';
        $f = $r->validate(['q' => 'nullable|string|max:100', 'tab' => 'nullable|in:peserta,sertifikat,survey']);
        $q = Participants::query($id);
        ServerDataTable::scope($r, $q);
        $this->searchPerson($q, $f['q'] ?? '');
        $warnings = [];
        if (DB::table('quotation')->where('id_penjualan', $id)->whereIn('status', ['draft', 'terkirim'])->exists()) {
            $warnings[] = 'Masih ada quotation Draft/Terkirim; setelah Close tidak dapat diproses.';
        }
        if (DB::table('pengajuan_biaya')->where('id_penjualan', $id)->where('status', 'menunggu')->exists() || DB::table('pengajuan_overbudget')->where('id_penjualan', $id)->where('status', 'menunggu')->exists()) {
            $warnings[] = 'Masih ada biaya/overbudget Menunggu Approval; pengajuan existing tetap dapat diselesaikan setelah Close.';
        }

        $props = ['program' => $p, 'rows' => $this->peoplePage($q),
            'filters' => $f, 'blockers' => Participants::blockers($p), 'warnings' => $warnings, 'canManage' => Gate::allows('menu', ['manage_peserta', 'Manage']), 'survey' => Survey::summary($id)];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Participants/Show', $props);
    }

    private function peoplePage($query)
    {
        $columns = request()->is('manage-peserta/monitoring/data')
            ? [null, 'x.nama', 'p.nomor', null, 's.no_sertifikat', 's.no_sertifikat_bnsp', 's.berlaku_sampai', 's.catatan_monitoring', null]
            : (request('tab') === 'sertifikat' ? [null, 'x.nama', 's.nama_sertifikat', 's.no_sertifikat', 's.no_sertifikat_bnsp', 's.berlaku_sampai', null]
                : (request('tab') === 'survey' ? [null, 'x.nama', 'q.nomor', null, null, null] : [null, 'x.nama', 'x.instansi', 'x.no_wa', 'x.email', 'q.nomor', null]));
        $page = ServerDataTable::page(request(), $query->orderBy('x.id'), $columns, 'x.id');

        return $page->setCollection(Participants::enrichRows($page->getCollection()));
    }

    private function searchPerson($query, string $term): void
    {
        if ($term !== '') {
            $query->where(function ($w) use ($term) {
                foreach (['x.nama', 'x.instansi', 'x.no_wa', 'x.email', 'q.nomor', 'p.nomor', 'p.lokasi', 's.no_sertifikat', 's.no_sertifikat_bnsp', 's.lsp', 'u.nama'] as $col) {
                    $w->orWhere($col, 'like', '%'.$term.'%');
                }
                $w->orWhereExists(fn ($z) => $z->selectRaw('1')->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->whereColumn('m.id_master_program', 'p.id_master_program')->where('k.judul', 'like', '%'.$term.'%'));
            });
        }
    }

    private function monitoringQuery(array $f)
    {
        $q = Participants::query();
        Sales::filter($q, $f);
        $this->searchPerson($q, $f['q'] ?? '');
        $cert = fn ($w) => $w->selectRaw('1')->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->join('ms_jenis_produk as j', 'j.id', '=', 'k.id_jenis_produk')->whereColumn('m.id_master_program', 'p.id_master_program')->where('j.nama', 'like', '%Sertifikasi%');
        $training = fn ($w) => $w->selectRaw('1')->from('master_program_produk as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')->join('ms_jenis_produk as j', 'j.id', '=', 'k.id_jenis_produk')->whereColumn('m.id_master_program', 'p.id_master_program')->where('j.nama', 'not like', '%Sertifikasi%');
        match ($f['tipe'] ?? '') {
            'Pelatihan' => $q->whereNotExists($cert), 'Sertifikasi' => $q->whereExists($cert)->whereNotExists($training),
            'Training & Sertifikasi' => $q->whereExists($cert)->whereExists($training), default => null,
        };
        match ($f['tindak'] ?? '') {
            'uninvoiced' => $q->whereNotExists(fn ($w) => $w->selectRaw('1')->from('invoice_peserta as d')->join('invoice as i', 'i.id', '=', 'd.id_invoice')->whereColumn('d.id_quotation_peserta', 'x.id')->where('i.status', 'invoiced')),
            'pelatihan' => $q->where('p.tgl_selesai', '<', today()->toDateString())->where(fn ($w) => $w->whereNull('s.status_kirim')->orWhere('s.status_kirim', 'belum')),
            'bnsp' => $q->where('s.hasil', 'kompeten')->whereNull('s.tgl_terima_bnsp'),
            'expired' => $q->whereNotNull('s.berlaku_sampai')->where('s.berlaku_sampai', '<=', today()->addDays(90)->toDateString()), default => null,
        };

        return $q;
    }

    public function monitoring(Request $r)
    {
        Gate::authorize('menu', ['monitoring_peserta', 'View']);
        ServerDataTable::prepare($r);
        $f = $r->validate(['q' => 'nullable|string|max:100', 'tahun' => 'nullable|integer|min:1900|max:2200', 'bulan' => 'nullable|integer|min:1|max:12', 'tipe' => 'nullable|in:Pelatihan,Sertifikasi,Training & Sertifikasi', 'tindak' => 'nullable|in:uninvoiced,pelatihan,bnsp,expired', 'export' => 'nullable|boolean']);
        ServerDataTable::scope($r, $this->monitoringQuery([]));
        $q = $this->monitoringQuery($f);
        if ($r->boolean('export')) {
            return $this->export($q, 'Monitoring_Peserta', true);
        }
        $counts = [];
        foreach (['uninvoiced', 'pelatihan', 'bnsp', 'expired'] as $key) {
            $counts[$key] = $this->monitoringQuery(array_replace($f, ['tindak' => $key]))->count();
        }

        foreach (['Pelatihan', 'Sertifikasi', 'Training & Sertifikasi'] as $type) {
            $counts[$type] = $this->monitoringQuery(array_replace($f, ['tipe' => $type]))->count();
        }

        $props = ['rows' => $this->peoplePage($q), 'filters' => $f, 'counts' => $counts,
            'canManage' => Gate::allows('menu', ['monitoring_peserta', 'Manage']), 'canCertificates' => Gate::allows('menu', ['manage_peserta', 'View']), 'canQuotation' => Gate::allows('menu', ['quotation_program', 'View'])];
        if ($r->route('datatable')) {
            return ServerDataTable::result($r, $props['rows'], collect($props)->except('rows')->all());
        }

        return Inertia::render('Participants/Monitoring', $props);
    }

    private function export($query, string $name, bool $monitoring = false, ?object $program = null)
    {
        write_log('Manage Peserta', 'Export', 'Export '.$name, null, null, null, $name);
        $rows = (function () use ($query, $monitoring, $program) {
            if ($program) {
                yield [Sales::display($program), Sales::title($program->id_master_program), $program->tgl_mulai.' s.d. '.$program->tgl_selesai, $program->lokasi];
            }
            yield $monitoring ? ['No', 'Nama', 'Instansi', 'Jabatan', 'NIK KTP', 'Email', 'WA Aktif', 'Judul Program', 'No. Program', 'Tanggal Pelaksanaan', 'Tipe', 'Lokasi', 'PIC', 'Nama PIC Customer', 'No. Quotation', 'No. Invoice', 'Status pembayaran', 'No. Sertifikat Pelatihan', 'Status Kirim', 'Tanggal Kirim', 'Ukom/Porto', 'LSP', 'Skema', 'Hasil', 'No. BNSP', 'Penerimaan BNSP', 'Pengiriman BNSP', 'ID Card', 'Berlaku Sampai', 'Catatan'] : ['No', 'Nama', 'Instansi', 'No. WA', 'Email', 'No. Quotation'];
            $n = 0;
            foreach ($query->orderBy('x.id')->lazy(250)->chunk(250) as $chunk) {
                foreach (Participants::enrichRows(collect($chunk->all())) as $x) {
                    yield $monitoring ? [++$n, $x->nama, $x->instansi, $x->jabatan, $x->nik, $x->email, $x->no_wa, $x->judul, $x->nomor_program, $x->tgl_mulai.' s.d. '.$x->tgl_selesai, $x->tipe, $x->lokasi, $x->sales, $x->pic_customer, $x->nomor_quotation, $x->invoice, $x->pembayaran, $x->no_sertifikat, $x->status_kirim, $x->tgl_kirim, $x->metode, $x->sertifikat_lsp, $x->skema, $x->hasil, $x->no_sertifikat_bnsp, $x->tgl_terima_bnsp, $x->tgl_kirim_bnsp, $x->id_card, $x->sertifikat_berlaku, $x->catatan_monitoring] : [++$n, $x->nama, $x->instansi, $x->no_wa, $x->email, $x->nomor_quotation];
                }
            }
        })();

        return Xlsx::response($name, $rows);
    }

    public function excel(int $id)
    {
        Gate::authorize('menu', ['manage_peserta', 'View']);
        $p = Sales::program($id);
        abort_unless($p->status === 'final', 404);

        return $this->export(Participants::query($id), 'Peserta_'.Sales::display($p), false, $p);
    }

    public function pdf(Request $r, int $id, string $kind)
    {
        Gate::authorize('menu', ['manage_peserta', 'View']);
        $f = $r->validate(['peserta' => 'nullable|integer|min:1']);
        $p = Sales::program($id);
        abort_unless($p->status === 'final', 404);
        $q = Participants::query($id)->when($f['peserta'] ?? null, fn ($w, $v) => $w->where('x.id', $v))->orderBy('x.id');
        $people = $q->get();
        abort_if($people->isEmpty(), 404);
        $bytes = ParticipantPdf::make($p, $people, $kind);
        write_log('Manage Peserta', 'Cetak', 'Cetak '.$kind.' '.Sales::display($p), null, 'penjualan_program', $id, Sales::display($p));

        return QuotationPdf::response($bytes, $kind.'-'.$id.'.pdf');
    }

    public function update(Request $r, int $id, string $action)
    {
        Gate::authorize('menu', [$action === 'catatan' ? 'monitoring_peserta' : 'manage_peserta', 'Manage']);
        $x = Participants::person($id);
        $data = match ($action) {
            'nama' => $r->validate(['nama_sertifikat' => 'required|string|max:150']),
            'draft-kirim' => $r->validate(['draft_kirim_date' => 'required|date_format:Y-m-d|before_or_equal:today']),
            'catatan' => $r->validate(['catatan_monitoring' => 'nullable|string|max:500']),
            'bnsp' => $r->validate(['lsp' => 'nullable|string|max:100', 'skema' => 'nullable|string|max:150', 'metode' => 'nullable|in:ukom,porto', 'hasil' => 'required|in:belum,kompeten,belum_kompeten', 'no_sertifikat_bnsp' => 'nullable|string|max:60', 'berlaku_sampai' => 'nullable|date_format:Y-m-d', 'tgl_terima_bnsp' => 'nullable|date_format:Y-m-d|before_or_equal:today', 'tgl_kirim_bnsp' => 'nullable|date_format:Y-m-d|required_with:tgl_kirim_bnsp|after_or_equal:tgl_terima_bnsp|before_or_equal:today', 'id_card' => 'required|in:belum,sudah', 'bukti' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png']),
            'pengiriman' => $r->validate(['status_kirim' => 'required|in:belum,soft,hard', 'tgl_kirim' => 'nullable|required_unless:status_kirim,belum|date_format:Y-m-d|before_or_equal:today', 'kirim_via' => 'nullable|required_unless:status_kirim,belum|string|max:100']),
            'terbit' => throw ValidationException::withMessages(['sertifikat' => 'Penerbitan resmi menunggu format, penandatangan, dan aturan BA.']),
        };
        $note = $action === 'nama' ? ($r->validate(['sumber_konfirmasi' => 'nullable|string|max:150'])['sumber_konfirmasi'] ?? null) : null;
        if ($action === 'nama') {
            $data += ['nama_konfirmasi_by' => Auth::id(), 'nama_konfirmasi_date' => now()];
        }
        if ($action === 'bnsp') {
            abort_unless(Participants::type($x->id_master_program) !== 'Pelatihan', 422, 'Program tidak termasuk Sertifikasi.');
            if (! empty($data['tgl_kirim_bnsp']) && empty($data['tgl_terima_bnsp'])) {
                throw ValidationException::withMessages(['tgl_terima_bnsp' => 'Tanggal diterima wajib sebelum pengiriman.']);
            }
        }
        $file = null;
        unset($data['bukti']);
        try {
            if ($r->hasFile('bukti')) {
                $file = QuotationFiles::store($r->file('bukti'), false, 'uploads/sertifikat/');
                $data['file_sertifikat_bnsp'] = $file['path'];
            }
            Participants::updateCertificate($id, $data, $action === 'nama' ? 'Konfirmasi Nama' : ucfirst($action), $note);
        } catch (\Throwable $e) {
            if ($file) {
                QuotationFiles::discard([$file]);
            }
            throw $e;
        }

        return back()->with('success', 'Data peserta tersimpan.');
    }

    public function close(int $id)
    {
        Gate::authorize('menu', ['manage_peserta', 'Manage']);
        Participants::close($id);

        return redirect('/manage-peserta/'.$id)->with('success', 'Program Closed.');
    }
}

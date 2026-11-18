<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/** Katalog Produk disusun dari Product aktif setiap dibuka (FR-14, FR-60..63, FR-67..68). */
class KatalogController extends Controller
{
    private const MENU = 'master_data.katalog';

    private function filters(Request $request): array
    {
        return $request->validate(['bidang' => 'nullable|integer', 'sert' => 'nullable|integer', 'q' => 'nullable|string|max:100']);
    }

    /** Product aktif + atribut + Lingkup, difilter, dikelompokkan per Bidang (urut nama), isi urut Judul lalu Sertifikat. */
    public static function groups(array $f): array
    {
        // Kolom harga_mulai baru ada setelah migrasi FR-69; sebelum itu Katalog tetap tampil tanpa harga.
        $hasPrice = Schema::hasColumn('paket_produk', 'harga_mulai');
        $rows = DB::table('paket_produk as pp')
            ->leftJoin('ms_jenis_produk as jp', 'jp.id', '=', 'pp.id_jenis_produk')
            ->leftJoin('ms_jenis_bidang as jb', 'jb.id', '=', 'pp.id_jenis_bidang')
            ->leftJoin('ms_varian_pelaksanaan as vp', 'vp.id', '=', 'pp.id_varian_pelaksanaan')
            ->leftJoin('ms_jenis_sertifikasi as js', 'js.id', '=', 'pp.id_jenis_sertifikasi')
            ->where('pp.aktif', 1)
            ->when(($f['bidang'] ?? null) !== null, fn ($q) => $q->where('pp.id_jenis_bidang', (int) $f['bidang']))
            ->when(($f['sert'] ?? null) !== null, fn ($q) => $q->where('pp.id_jenis_sertifikasi', (int) $f['sert']))
            ->get(array_merge(['pp.id', 'pp.nomor', 'pp.judul', 'pp.id_jenis_bidang'], $hasPrice ? ['pp.harga_mulai'] : [], ['jp.nama as nama_produk', 'jb.nama as nama_bidang', 'vp.nama as nama_varian', 'js.nama as nama_sertifikasi']));
        $lingkup = DB::table('paket_produk_lingkup')->whereIn('id_paket_produk', $rows->pluck('id'))->orderBy('id_paket_produk')->orderBy('urutan')->get()->groupBy('id_paket_produk');
        $q = mb_strtolower(trim((string) ($f['q'] ?? '')));
        $rows = $rows->map(function ($r) use ($lingkup) {
            $r->lingkup = ($lingkup[$r->id] ?? collect())->pluck('lingkup')->all();
            $r->harga_mulai ??= null;

            return $r;
        })->filter(function ($r) use ($q) {
            if ($q === '') {
                return true;
            }

            return str_contains(mb_strtolower(implode(' ', array_merge([$r->nomor, $r->judul, $r->nama_produk, $r->nama_bidang, $r->nama_varian, $r->nama_sertifikasi], $r->lingkup))), $q);
        });

        return $rows->groupBy('id_jenis_bidang')->map(function ($items, $bidang) {
            return [
                'id' => (int) $bidang, 'nama' => $items->first()->nama_bidang ?? '—',
                'items' => $items->sort(fn ($a, $b) => strcasecmp((string) $a->judul, (string) $b->judul) ?: strcasecmp((string) $a->nama_sertifikasi, (string) $b->nama_sertifikasi))->values()->all(),
            ];
        })->sort(fn ($a, $b) => strcasecmp($a['nama'], $b['nama']))->values()->all();
    }

    private function filterOptions(): array
    {
        $one = fn (string $table, string $col) => DB::table('paket_produk as pp')->join("$table as m", 'm.id', '=', "pp.$col")->where('pp.aktif', 1)
            ->groupBy('m.id', 'm.nama')->orderBy('m.nama')->get(['m.id', 'm.nama', DB::raw('COUNT(*) as n')]);

        return ['bidang' => $one('ms_jenis_bidang', 'id_jenis_bidang'), 'sert' => $one('ms_jenis_sertifikasi', 'id_jenis_sertifikasi')];
    }

    public function index(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $this->filters($request);

        return Inertia::render('Master/Katalog', [
            'groups' => self::groups($f), 'filters' => ['bidang' => $f['bidang'] ?? '', 'sert' => $f['sert'] ?? '', 'q' => $f['q'] ?? ''],
            'options' => $this->filterOptions(),
            'can' => ['edit' => Gate::allows('menu', [self::MENU, 'Manage']) && Gate::allows('menu', ['master_data.paket_produk', 'Manage']), 'print' => $this->mayPrint()],
            'lingkupMax' => config('labmania.lingkup'),
        ]);
    }

    private function mayPrint(): bool
    {
        return Gate::allows('menu', [self::MENU, 'View']);
    }

    /** Cetak PDF: ditolak bila hasil filter kosong (FR-63); tiap cetak dicatat di Activity Log (FR-62). */
    public function pdf(Request $request)
    {
        Gate::authorize('menu', [self::MENU, 'View']);
        $f = $this->filters($request);
        $groups = self::groups($f);
        if (! $groups) {
            return back()->withErrors(['pdf' => 'Tidak ada Product aktif sesuai filter; tidak ada yang dicetak.']);
        }
        $html = view('pdf.katalog', ['groups' => $groups, 'tahun' => now()->format('Y')])->render();
        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 14, 'margin_bottom' => 14, 'default_font' => 'dejavusans', 'tempDir' => storage_path('app/mpdf')]);
        $mpdf->SetTitle('Katalog Produk '.now()->format('Y'));
        $mpdf->SetAuthor('Labmania');
        $mpdf->WriteHTML($html);
        write_log('Master Produk', 'Cetak', 'Cetak Katalog Produk', ['filter' => $f, 'jumlah_product' => collect($groups)->sum(fn ($g) => count($g['items']))], null, null, 'Katalog Produk');

        return response($mpdf->Output('Katalog-Produk-'.now()->format('d-m-Y').'.pdf', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Katalog-Produk-'.now()->format('d-m-Y').'.pdf"', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

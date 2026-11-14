<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Participants
{
    public static function query(?int $id = null)
    {
        return DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')
            ->join('penjualan_program as p', 'p.id', '=', 'q.id_penjualan')
            ->leftJoin('quotation_peserta_sertifikat as s', 's.id_quotation_peserta', '=', 'x.id')
            ->leftJoin('ms_user_karyawan as u', 'u.id', '=', 'q.id_sales')
            ->where('x.status', 'deal')->where('q.status', 'deal')->where('p.status', 'final')
            ->when($id !== null, fn ($w) => $w->where('p.id', $id))
            ->select('x.*', 'q.nomor as nomor_quotation', 'q.revisi as revisi_quotation', 'p.id as id_penjualan', 'p.nomor as nomor_program', 'p.revisi as revisi_program',
                'p.id_master_program', 'p.jenis', 'p.tgl_mulai', 'p.tgl_selesai', 'p.lokasi', 'p.closed_date', 'u.nama as sales', 'q.kepada_nama as pic_customer',
                's.id as sertifikat_id', 's.nama_sertifikat', 's.no_sertifikat', 's.tgl_terbit', 's.status_kirim', 's.tgl_kirim', 's.kirim_via', 's.lsp as sertifikat_lsp', 's.skema', 's.metode', 's.hasil', 's.no_sertifikat_bnsp', 's.tgl_terima_bnsp', 's.tgl_kirim_bnsp', 's.berlaku_sampai as sertifikat_berlaku', 's.id_card', 's.catatan_monitoring', 's.draft_kirim_date', 's.nama_konfirmasi_by', 's.nama_konfirmasi_date')
            ->selectRaw("CASE WHEN s.file_sertifikat_bnsp IS NOT NULL AND s.file_sertifikat_bnsp <> '' THEN 1 ELSE 0 END AS has_bnsp_file");
    }

    public static function type(int $master): string
    {
        $names = DB::table('master_program_produk as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')
            ->join('ms_jenis_produk as j', 'j.id', '=', 'p.id_jenis_produk')->where('m.id_master_program', $master)->pluck('j.nama');
        $cert = $names->contains(fn ($n) => str_contains(mb_strtolower($n), 'sertifikasi'));
        $training = $names->contains(fn ($n) => ! str_contains(mb_strtolower($n), 'sertifikasi'));

        return $cert ? ($training ? 'Training & Sertifikasi' : 'Sertifikasi') : 'Pelatihan';
    }

    public static function enrichRows($rows)
    {
        $masters = DB::table('master_program_produk as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')
            ->join('ms_jenis_produk as j', 'j.id', '=', 'p.id_jenis_produk')->leftJoin('ms_varian_pelaksanaan as v', 'v.id', '=', 'p.id_varian_pelaksanaan')
            ->whereIn('m.id_master_program', $rows->pluck('id_master_program')->unique())->orderBy('m.urutan')
            ->get(['m.id_master_program', 'p.judul', 'v.nama as varian', 'j.nama as jenis'])->groupBy('id_master_program')->map(function ($products) {
                $cert = $products->contains(fn ($p) => str_contains(mb_strtolower($p->jenis), 'sertifikasi'));
                $training = $products->contains(fn ($p) => ! str_contains(mb_strtolower($p->jenis), 'sertifikasi'));

                return [$products->map(fn ($p) => $p->judul.($p->varian ? ' · '.$p->varian : ''))->implode(' + '), $cert ? ($training ? 'Training & Sertifikasi' : 'Sertifikasi') : 'Pelatihan'];
            });
        $invoices = DB::table('invoice_peserta as d')->join('invoice as i', 'i.id', '=', 'd.id_invoice')
            ->whereIn('d.id_quotation_peserta', $rows->pluck('id'))->where('i.status', 'invoiced')
            ->get(['d.id_quotation_peserta', 'i.nomor', 'i.tanggal_bayar'])->keyBy('id_quotation_peserta');

        return $rows->map(function ($x) use ($masters, $invoices) {
            [$x->judul, $x->tipe] = $masters->get($x->id_master_program, ['', 'Pelatihan']);
            $x->nomor_program = Sales::display((object) ['nomor' => $x->nomor_program, 'revisi' => $x->revisi_program]);
            $x->nomor_quotation = Sales::display((object) ['nomor' => $x->nomor_quotation, 'revisi' => $x->revisi_quotation]);
            $invoice = $invoices->get($x->id);
            $x->invoice = $invoice?->nomor;
            $x->pembayaran = $invoice ? ($invoice->tanggal_bayar ? 'Dibayar' : 'Belum dibayar') : 'Uninvoiced';

            return $x;
        });
    }

    public static function person(int $id): object
    {
        return self::query()->where('x.id', $id)->first() ?? abort(404);
    }

    public static function updateCertificate(int $id, array $data, string $action, ?string $note = null): void
    {
        Sales::transaction(function () use ($id, $data, $action, $note) {
            $x = self::person($id);
            Sales::program($x->id_penjualan, true);
            self::person($id);
            $old = DB::table('quotation_peserta_sertifikat')->where('id_quotation_peserta', $id)->lockForUpdate()->first();
            if (array_key_exists('nama_sertifikat', $data) && $old?->no_sertifikat) {
                throw ValidationException::withMessages(['nama_sertifikat' => 'Nama sertifikat yang sudah terbit terkunci.']);
            }
            if (isset($data['status_kirim']) && $data['status_kirim'] !== 'belum' && ! $old?->no_sertifikat) {
                throw ValidationException::withMessages(['status_kirim' => 'Sertifikat pelatihan belum terbit.']);
            }
            $new = $data + ['updated_by' => Auth::id(), 'updated_date' => now()];
            if ($old) {
                DB::table('quotation_peserta_sertifikat')->where('id', $old->id)->update($new);
            } else {
                DB::table('quotation_peserta_sertifikat')->insert(['id_quotation_peserta' => $id, ...$new, ...Invoice::audit()]);
            }
            $safe = (array) $old;
            unset($safe['file_sertifikat'], $safe['file_sertifikat_bnsp']);
            $log = $data;
            if ($note) {
                $log['sumber_konfirmasi'] = $note;
            }
            if (isset($log['file_sertifikat_bnsp'])) {
                $log['file_sertifikat_bnsp'] = 'Dokumen privat diunggah';
            }
            write_log('Manage Peserta', $action, $action.' '.$x->nama, ['sub_module' => $action === 'Catatan' ? 'Monitoring Peserta' : 'Sertifikat', 'old' => $safe, 'new' => array_merge($safe, $log)], 'quotation_peserta', $id, $x->nomor_program);
        });
    }

    public static function blockers(object $p): array
    {
        $reasons = [];
        if ($p->status !== 'final' || $p->closed_date) {
            $reasons[] = 'Program harus Final dan belum Closed.';
        }
        if (! $p->tgl_selesai || Carbon::parse($p->tgl_selesai, 'Asia/Jakarta')->startOfDay()->gte(Carbon::today('Asia/Jakarta'))) {
            $reasons[] = 'Tanggal selesai acara belum lewat.';
        }
        if (self::query($p->id)->whereNotExists(fn ($w) => $w->selectRaw('1')->from('invoice_peserta as ip')->join('invoice as i', 'i.id', '=', 'ip.id_invoice')
            ->whereColumn('ip.id_quotation_peserta', 'x.id')->where('i.status', 'invoiced')->whereColumn('i.id_penjualan', 'p.id'))->exists()) {
            $reasons[] = 'Masih ada peserta Deal belum ditagih.';
        }
        $invoices = DB::table('invoice')->where('id_penjualan', $p->id)->where('status', 'invoiced');
        if ((clone $invoices)->whereNull('tanggal_bayar')->exists()) {
            $reasons[] = 'Masih ada invoice aktif belum dibayar.';
        }
        if ((clone $invoices)->where('perlakuan_ppn', 'WAPU')->where('ppn', '>', 0)->whereNotExists(fn ($w) => $w->selectRaw('1')->from('invoice_bukti_setor as b')->whereColumn('b.id_invoice', 'invoice.id'))->exists()) {
            $reasons[] = 'Bukti Setor WAPU belum lengkap.';
        }
        foreach (DB::table('quotation')->where('id_penjualan', $p->id)->get() as $q) {
            if (Reconfirmation::targets($q)->whereNull('a.diproses_date')->exists()) {
                $reasons[] = 'Konfirmasi ulang belum seluruhnya diproses.';
                break;
            }
        }
        if (DB::table('sales_order_refund as r')->join('sales_order as s', 's.id', '=', 'r.id_sales_order')->where('s.id_penjualan', $p->id)->where('r.status', '<>', 'selesai')->exists()) {
            $reasons[] = 'Refund belum selesai.';
        }

        return $reasons;
    }

    public static function close(int $id): void
    {
        Sales::transaction(function () use ($id) {
            $p = Sales::program($id, true);
            if ($errors = self::blockers($p)) {
                throw ValidationException::withMessages(['program' => implode(' ', $errors)]);
            }
            $new = ['closed_by' => Auth::id(), 'closed_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('penjualan_program')->where('id', $id)->update($new);
            DB::table('program_survey')->where('id_penjualan', $id)->update(['status' => 'tutup', 'ditutup_by' => Auth::id(), 'ditutup_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()]);
            write_log('Manage Peserta', 'Close Program', 'Close Program '.Sales::display($p), ['old' => (array) $p, 'new' => array_merge((array) $p, $new)], 'penjualan_program', $id, Sales::display($p));
        });
    }
}

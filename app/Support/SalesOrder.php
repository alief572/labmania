<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrder
{
    public static function row(int $id, bool $lock = false): object
    {
        return DB::table('sales_order')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first() ?? abort(404);
    }

    public static function programSnapshot(object $p): array
    {
        $products = DB::table('master_program as m')->join('paket_produk as k', 'k.id', '=', 'm.id_paket_produk')
            ->leftJoin('ms_varian_pelaksanaan as v', 'v.id', '=', 'k.id_varian_pelaksanaan')
            ->leftJoin('ms_jenis_sertifikasi as s', 's.id', '=', 'k.id_jenis_sertifikasi')
            ->where('m.id', $p->id_master_program)->get(['k.nomor', 'k.judul', 'v.nama as varian', 's.nama as sertifikat'])->all();
        $trainer = DB::table('ms_trainer as t')->leftJoin('users as u', 'u.id_user', '=', 't.id_user')->where('t.id', $p->id_trainer)->first(['t.id', 't.nama', 't.bidang_keahlian', 't.no_telp', 't.alamat', 'u.email']);
        $assistant = DB::table('ms_user_karyawan')->where('id', $p->id_asisten)->first(['id', 'nama', 'jabatan', 'telp', 'email']);
        $company = $p->id_customer ? DB::table('master_customer')->where('id_customer', $p->id_customer)->first(['id_customer', 'name_customer', 'address_office', 'npwp', 'perlakuan_ppn']) : null;

        // Beberapa Master Program (SO-FR-25): satu surat per program berisi tim tiap Master Program. Hanya untuk program lebih dari satu Master Program,
        // sehingga snapshot dan cetakan program satu Master Program tidak berubah.
        $masters = Sales::masters($p);
        $team = count($masters) > 1 ? array_map(function ($m) {
            $t = DB::table('ms_trainer')->where('id', $m['id_trainer'])->first(['id', 'nama', 'bidang_keahlian']);
            $a = DB::table('ms_user_karyawan')->where('id', $m['id_asisten'])->first(['id', 'nama', 'jabatan']);

            return ['id_master_program' => (int) $m['id_master_program'], 'nomor' => DB::table('master_program')->where('id', $m['id_master_program'])->value('nomor'), 'judul' => Sales::title((int) $m['id_master_program']),
                'tgl_mulai' => $m['tgl_mulai'], 'tgl_selesai' => $m['tgl_selesai'], 'trainer' => $t, 'asisten' => $a];
        }, $masters) : null;

        return ['program' => array_replace(array_intersect_key((array) $p, array_flip(['id', 'nomor', 'jenis', 'id_master_program', 'tgl_mulai', 'tgl_selesai', 'waktu', 'lokasi', 'durasi_hari', 'jam_pembelajaran', 'id_trainer', 'id_asisten'])), ['nomor' => Sales::display($p)]),
            'judul' => Sales::title($p->id_master_program), 'produk' => $products, 'trainer' => $trainer, 'asisten' => $assistant, 'company' => $company] + ($team ? ['tim' => $team] : []);
    }

    public static function detail(object $so): array
    {
        $q = Quotation::row($so->id_quotation);
        unset($q->link_token);
        $q->nomor = Sales::display($q);
        $p = Sales::program($so->id_penjualan);
        $people = DB::table('quotation_peserta')->where('id_quotation', $q->id)->where('status', 'deal')->orderBy('id')->get();
        $invoice = DB::table('invoice')->where('id_sales_order', $so->id)->where('status', 'invoiced')->orderBy('id')->first(['id', 'nomor', 'tanggal_bayar']);
        $complete = $invoice && Invoice::complete($q, $so->id);

        $facts = self::programSnapshot($p);
        $facts['company'] = $q->id_customer ? DB::table('master_customer')->where('id_customer', $q->id_customer)->first(['id_customer', 'name_customer', 'address_office', 'npwp', 'perlakuan_ppn']) : null;

        return ['so' => array_replace((array) $so, ['nomor' => Sales::display($so)]) + ['status_tampil' => $so->status === 'batal' ? 'Batal' : ($complete ? 'Invoiced' : ($so->status === 'siap' ? 'Siap invoice' : 'Belum siap invoice')),
            'siap_nama' => $so->siap_by ? DB::table('users')->where('id_user', $so->siap_by)->value('nm_lengkap') : null],
            'quotation' => $q, ...$facts, 'closed' => $p->closed_date !== null,
            'pendingReconfirmation' => Reconfirmation::pending($q),
            'exited' => DB::table('quotation_peserta')->where('id_quotation', $q->id)->where('status', 'keluar')->get(['id', 'nama', 'keluar_jenis', 'keluar_alasan']),
            'revisions' => DB::table('quotation_revisi')->where('id_quotation', $q->id)->orderByDesc('revisi')->get(['revisi', 'created_date']),
            'sales' => DB::table('ms_user_karyawan')->where('id', $q->id_sales)->value('nama'),
            'peserta' => Quotation::participantDocuments($people), 'calc' => Quotation::calculate($q, $people->count()), 'invoice' => $invoice,
            'termin' => Quotation::termin($q), 'invoices' => DB::table('invoice')->where('id_sales_order', $so->id)->where('status', 'invoiced')->orderBy('termin_ke')->orderBy('id')->get(['id', 'nomor', 'termin_ke', 'total_tagihan', 'tanggal_bayar']),
            'documents' => DB::table('quotation_dokumen as d')->leftJoin('users as u', 'u.id_user', '=', 'd.created_by')->where('d.id_quotation', $q->id)->get(['d.id', 'd.nama_file', 'd.ukuran_byte', 'd.created_date', 'u.nm_lengkap']),
            'companyConfirmation' => DB::table('quotation_konfirmasi_company')->where('id_quotation', $q->id)->where('revisi', $q->revisi)->first(['id', 'surat_konfirmasi_nama']),
        ];
    }

    public static function ready(int $id): void
    {
        Sales::transaction(function () use ($id) {
            $read = self::row($id);
            $p = Sales::program($read->id_penjualan, true);
            Sales::open($p);
            $old = self::row($id, true);
            if ($old->status === 'batal') {
                throw ValidationException::withMessages(['so' => 'SO batal tidak dapat ditandai siap invoice.']);
            }
            if ($old->status === 'siap') {
                return;
            }
            $q = Quotation::row($old->id_quotation, true);
            if ($q->status !== 'deal' || ! DB::table('quotation_peserta')->where('id_quotation', $q->id)->where('status', 'deal')->exists()) {
                throw ValidationException::withMessages(['so' => 'SO memerlukan quotation dan peserta Deal.']);
            }
            $update = ['status' => 'siap', 'siap_by' => Auth::id(), 'siap_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('sales_order')->where('id', $id)->update($update);
            write_log('Quotation', 'Siap Invoice', 'Siap Invoice '.$old->nomor, ['sub_module' => 'Sales Order', 'old' => (array) $old, 'new' => array_merge((array) $old, $update)], 'sales_order', $id, $old->nomor);
        });
    }

    public static function assignment(int $id): array
    {
        $row = DB::table('surat_kerja_trainer')->where('id', $id)->first() ?? abort(404);
        $snapshot = json_decode($row->snapshot ?? '', true);
        if (! is_array($snapshot) || empty($snapshot['program']) || empty($snapshot['trainer']) || empty($snapshot['asisten'])) {
            throw ValidationException::withMessages(['surat' => 'Snapshot Surat Tugas belum tersedia; rekonsiliasi dokumen asal diperlukan sebelum ditampilkan atau dicetak.']);
        }
        $snapshot['jumlah_so'] = DB::table('sales_order')->where('id_penjualan', $row->id_penjualan)->where('status', '!=', 'batal')->count();
        $snapshot['peserta_deal'] = DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('q.id_penjualan', $row->id_penjualan)->where('x.status', 'deal')->count();

        return ['surat' => $row, ...$snapshot];
    }
}

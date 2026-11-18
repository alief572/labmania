<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembatalan Penjualan Program (mockup pjDampakBatal/pjBatal). Draft dan Final yang belum Closed boleh dibatalkan.
 * Quotation, Sales Order, invoice belum dibayar, Surat Tugas dan pengajuan biaya yang menunggu ikut Batal.
 * Invoice yang sudah dibayar tetap ada; dananya dikembalikan lewat Refund (perlu approval Finance).
 */
class ProgramCancel
{
    /** Ringkasan dampak untuk modal konfirmasi; hanya membaca. */
    public static function impact(object $p): array
    {
        $quotes = DB::table('quotation')->where('id_penjualan', $p->id)->where('status', '!=', 'batal')->get(['id', 'nomor', 'revisi']);
        $invoices = DB::table('invoice')->where('id_penjualan', $p->id)->where('status', 'invoiced')->get(['id', 'nomor', 'tanggal_bayar', 'piutang']);
        $paid = $invoices->whereNotNull('tanggal_bayar');

        return [
            'quotation' => $quotes->count(),
            'sales_order' => DB::table('sales_order')->where('id_penjualan', $p->id)->where('status', '!=', 'batal')->count(),
            'invoice_belum' => $invoices->whereNull('tanggal_bayar')->count(),
            'invoice_bayar' => $paid->count(),
            'refund' => (string) $paid->reduce(fn ($sum, $i) => $sum->plus((string) $i->piutang), \Brick\Math\BigDecimal::zero()),
            'surat_tugas' => DB::table('surat_kerja_trainer')->where('id_penjualan', $p->id)->where('status', 'berlaku')->count(),
            'pengajuan' => DB::table('pengajuan_biaya')->where('id_penjualan', $p->id)->where('status', 'menunggu')->count()
                + DB::table('pengajuan_overbudget')->where('id_penjualan', $p->id)->where('status', 'menunggu')->count(),
        ];
    }

    public static function allowed(object $p): bool
    {
        return $p->closed_date === null && in_array($p->status, ['draft', 'final'], true);
    }

    /**
     * Satu transaksi. $p harus dibaca dengan kunci baris. Mengembalikan jumlah yang terdampak untuk pesan sukses.
     */
    public static function apply(object $p, string $reason): array
    {
        if (! self::allowed($p)) {
            throw ValidationException::withMessages(['program' => $p->closed_date ? 'Program sudah Closed dan tidak dapat dibatalkan.' : 'Program sudah Batal.']);
        }
        $impact = self::impact($p);
        $now = now();
        $by = Auth::id();
        $text = 'Penjualan Program '.Sales::display($p).' dibatalkan: '.$reason;
        $refunds = 0;

        foreach (DB::table('quotation')->where('id_penjualan', $p->id)->where('status', '!=', 'batal')->orderBy('id')->lockForUpdate()->get() as $q) {
            $so = DB::table('sales_order')->where('id_quotation', $q->id)->first();
            if ($so && $so->status !== 'batal') {
                DB::table('sales_order')->where('id', $so->id)->update(['status' => 'batal', 'updated_by' => $by, 'updated_date' => $now]);
                write_log('Penjualan Program', 'Batal', 'Batal '.Sales::display($so).': '.$reason, ['sub_module' => 'Sales Order', 'old' => ['status' => $so->status], 'new' => ['status' => 'batal'], 'alasan' => $reason], 'sales_order', $so->id, $so->nomor);
            }
            foreach (DB::table('invoice')->where('id_quotation', $q->id)->where('status', 'invoiced')->orderBy('id')->lockForUpdate()->get() as $invoice) {
                if ($invoice->tanggal_bayar === null) {
                    DB::table('invoice')->where('id', $invoice->id)->update(['status' => 'batal', 'batal_by' => $by, 'batal_date' => $now, 'batal_alasan' => mb_substr($text, 0, 500), 'updated_by' => $by, 'updated_date' => $now]);
                    write_log('Invoice', 'Batal', 'Batal '.$invoice->nomor.': '.$text, ['old' => ['status' => 'invoiced'], 'new' => ['status' => 'batal'], 'alasan' => $text], 'invoice', $invoice->id, $invoice->nomor);

                    continue;
                }
                // Satu refund per invoice dibayar. Full payment: satu invoice untuk seluruh penerima (company); TOP: Refund::create menyatukan semua termin dibayar.
                if ($so) {
                    $people = DB::table('invoice_peserta')->where('id_invoice', $invoice->id)->pluck('id_quotation_peserta');
                    $single = $q->id_customer === null && $people->count() === 1;
                    Refund::create($q, $so, $invoice, null, $single ? (int) $people->first() : null, $text, 'batal');
                    $refunds++;
                }
            }
            DB::table('quotation')->where('id', $q->id)->update(['status' => 'batal', 'closed_by' => $by, 'closed_date' => $now, 'updated_by' => $by, 'updated_date' => $now]);
            DB::table('quotation_peserta')->where('id_quotation', $q->id)->whereNotIn('status', ['keluar', 'batal'])->update(['status' => 'batal', 'updated_by' => $by, 'updated_date' => $now]);
            write_log('Quotation', 'Batal', 'Batal '.Sales::display($q).': '.$reason, ['old' => ['status' => $q->status], 'new' => ['status' => 'batal'], 'alasan' => $reason], 'quotation', $q->id, $q->nomor);
        }
        foreach (DB::table('surat_kerja_trainer')->where('id_penjualan', $p->id)->where('status', 'berlaku')->get() as $letter) {
            DB::table('surat_kerja_trainer')->where('id', $letter->id)->update(['status' => 'batal', 'updated_by' => $by, 'updated_date' => $now]);
            write_log('Penjualan Program', 'Batal', 'Batal Surat Tugas '.$letter->nomor, ['sub_module' => 'Surat Kerja Trainer', 'old' => ['status' => 'berlaku'], 'new' => ['status' => 'batal'], 'alasan' => $reason], 'surat_kerja_trainer', $letter->id, $letter->nomor);
        }
        foreach (['pengajuan_biaya', 'pengajuan_overbudget'] as $table) {
            foreach (DB::table($table)->where('id_penjualan', $p->id)->where('status', 'menunggu')->get() as $request) {
                DB::table($table)->where('id', $request->id)->update(['status' => 'batal', 'closed_by' => $by, 'closed_date' => $now, 'updated_by' => $by, 'updated_date' => $now]);
                write_log('Pengajuan Biaya', 'Batal', 'Batal '.$request->nomor.': '.$text, ['old' => ['status' => 'menunggu'], 'new' => ['status' => 'batal'], 'alasan' => $text], $table, $request->id, $request->nomor);
            }
        }
        $update = ['status' => 'batal', 'alasan_batal' => $reason, 'closed_by' => $by, 'closed_date' => $now, 'updated_by' => $by, 'updated_date' => $now];
        DB::table('penjualan_program')->where('id', $p->id)->update($update);
        write_log('Penjualan Program', 'Batal', 'Batal '.Sales::display($p).': '.$reason, ['old' => (array) $p, 'new' => array_merge((array) $p, $update), 'alasan' => $reason, 'dampak' => $impact + ['refund_dibuat' => $refunds]], 'penjualan_program', $p->id, $p->nomor);

        return $impact + ['refund_dibuat' => $refunds];
    }
}

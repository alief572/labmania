<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Refund
{
    public static function allocate(string $paid, array $weights): array
    {
        ksort($weights, SORT_NUMERIC);
        $sum = BigDecimal::zero();
        foreach ($weights as $v) {
            $sum = $sum->plus($v);
        }
        if ($sum->isZero()) {
            $weights = array_fill_keys(array_keys($weights), '1');
            $sum = BigDecimal::of(count($weights));
        }
        $result = $remainders = [];
        $left = BigDecimal::of($paid);
        foreach ($weights as $id => $weight) {
            $product = BigDecimal::of($paid)->multipliedBy($weight);
            $part = $product->dividedBy($sum, 0, RoundingMode::Down);
            $result[$id] = (string) $part;
            $remainders[$id] = $product->minus($part->multipliedBy($sum));
            $left = $left->minus($part);
        }
        uksort($remainders, fn ($a, $b) => $remainders[$b]->compareTo($remainders[$a]) ?: $a <=> $b);
        foreach (array_slice(array_keys($remainders), 0, $left->toInt()) as $id) {
            $result[$id] = (string) BigDecimal::of($result[$id])->plus(1);
        }

        return $result;
    }

    public static function create(object $q, object $so, object $invoice, object $answer, ?int $participant, string $reason): int
    {
        $key = $participant ? 'peserta:'.$participant : 'company';
        $existing = DB::table('sales_order_refund')->where('id_invoice', $invoice->id)->where('kunci_penerima', $key)->first();
        if ($existing) {
            return $existing->id;
        }
        $weights = DB::table('invoice_peserta')->where('id_invoice', $invoice->id)->orderBy('id_quotation_peserta')->pluck('nominal', 'id_quotation_peserta')->all();
        $amount = $participant ? (self::allocate((string) $invoice->piutang, $weights)[$participant] ?? '0') : (string) $invoice->piutang;
        if (BigDecimal::of($amount)->isLessThanOrEqualTo(0)) {
            throw ValidationException::withMessages(['keputusan' => 'Tidak ada dana lunas teralokasi untuk Refund; rekonsiliasi diperlukan.']);
        }
        $snapshot = ['invoice' => $invoice->nomor, 'piutang' => (string) $invoice->piutang, 'tanggal_bayar' => $invoice->tanggal_bayar,
            'nomor_so' => Sales::display($so), 'nomor_quotation' => Sales::display($q), 'peserta' => $participant ? DB::table('quotation_peserta')->where('id', $participant)->value('nama') : null,
            'company' => $q->id_customer ? DB::table('master_customer')->where('id_customer', $q->id_customer)->value('name_customer') : null, 'bobot' => $weights, 'batas_dana' => $amount];
        $new = ['nomor' => Sales::number('sales_order_refund', 'LMI/RF/'.now()->format('m/y')), 'tanggal' => today()->toDateString(),
            'id_sales_order' => $so->id, 'id_quotation_peserta' => $participant, 'id_invoice' => $invoice->id, 'id_konfirmasi_ulang' => $answer->id,
            'kunci_penerima' => $key, 'revisi' => $q->revisi, 'nominal_tagihan' => $amount, 'alasan' => $reason, 'status' => 'menunggu',
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), ...Invoice::audit()];
        $id = DB::table('sales_order_refund')->insertGetId($new);
        write_log('Quotation', 'Tambah', 'Refund '.$new['nomor'].' dari '.$invoice->nomor, ['sub_module' => 'Refund', 'new' => $new], 'sales_order_refund', $id, $new['nomor']);

        return $id;
    }

    public static function finish(int $id, array $input, array $file): bool
    {
        return Sales::transaction(function () use ($id, $input, $file) {
            $read = DB::table('sales_order_refund')->where('id', $id)->first() ?? abort(404);
            $so = SalesOrder::row($read->id_sales_order);
            Sales::open(Sales::program($so->id_penjualan, true));
            $old = DB::table('sales_order_refund')->where('id', $id)->lockForUpdate()->first();
            $invoice = Invoice::row($old->id_invoice, true);
            if ($invoice->status !== 'invoiced' || ! $invoice->tanggal_bayar) {
                throw ValidationException::withMessages(['refund' => 'Invoice tidak lagi memenuhi syarat Refund.']);
            }
            $amount = BigDecimal::of($input['nominal_refund']);
            if ($amount->isLessThanOrEqualTo(0) || $amount->isGreaterThan((string) $old->nominal_tagihan)) {
                throw ValidationException::withMessages(['nominal_refund' => 'Nominal harus positif dan tidak melebihi dana teralokasi.']);
            }
            if ($amount->isLessThan((string) $old->nominal_tagihan) && trim($input['catatan'] ?? '') === '') {
                throw ValidationException::withMessages(['catatan' => 'Keterangan potongan wajib.']);
            }
            $data = ['nominal_refund' => (string) $amount, 'bank' => $input['bank'], 'no_rekening' => $input['no_rekening'], 'atas_nama' => $input['atas_nama'], 'catatan' => $input['catatan'] ?? null];
            if ($old->status === 'selesai') {
                foreach ($data as $key => $value) {
                    if ((string) $old->$key !== (string) $value) {
                        throw ValidationException::withMessages(['refund' => 'Refund sudah selesai dengan data berbeda.']);
                    }
                }
                if (! hash_equals(hash_file('sha256', QuotationFiles::storedPath($old->bukti_transfer)), hash_file('sha256', QuotationFiles::storedPath($file['path'])))) {
                    throw ValidationException::withMessages(['refund' => 'Bukti transfer berbeda dari penyelesaian sebelumnya.']);
                }

                return false;
            }
            $data += ['status' => 'selesai', 'bukti_transfer' => $file['path'], 'bukti_transfer_nama' => $file['name'], 'selesai_by' => Auth::id(), 'selesai_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('sales_order_refund')->where('id', $id)->update($data);
            $safe = fn ($row) => array_intersect_key((array) $row, array_flip(['id', 'nomor', 'status', 'nominal_tagihan', 'nominal_refund', 'bank', 'atas_nama', 'catatan', 'bukti_transfer_nama']));
            write_log('Quotation', 'Proses Refund', 'Selesai '.$old->nomor, ['sub_module' => 'Refund', 'old' => $safe($old), 'new' => $safe(array_merge((array) $old, $data))], 'sales_order_refund', $id, $old->nomor);

            return true;
        });
    }
}

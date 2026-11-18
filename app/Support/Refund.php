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
        write_log('Refund', 'Tambah', 'Refund '.$new['nomor'].' dari '.$invoice->nomor, ['sub_module' => 'Refund', 'new' => $new], 'sales_order_refund', $id, $new['nomor']);

        return $id;
    }

    /**
     * Keputusan Finance (RF-FR-05): Setujui atau Tolak. Hanya dari status menunggu. Tolak wajib beralasan.
     * Keputusan yang sama diulang (replay) tidak menggandakan riwayat; keputusan berbeda pada refund yang sudah diputuskan ditolak.
     */
    public static function decide(int $id, string $decision, ?string $note): void
    {
        $note = trim((string) $note);
        if (! in_array($decision, ['setujui', 'tolak'], true)) {
            throw ValidationException::withMessages(['keputusan' => 'Pilih Setujui atau Tolak.']);
        }
        if ($decision === 'tolak' && $note === '') {
            throw ValidationException::withMessages(['catatan' => 'Isi alasan penolakan (yang harus diperbaiki Sales/Marketing).']);
        }
        Sales::transaction(function () use ($id, $decision, $note) {
            $read = DB::table('sales_order_refund')->where('id', $id)->first() ?? abort(404);
            Sales::open(Sales::program(SalesOrder::row($read->id_sales_order)->id_penjualan, true));
            $old = DB::table('sales_order_refund')->where('id', $id)->lockForUpdate()->first();
            $to = $decision === 'setujui' ? 'disetujui' : 'ditolak';
            if ($old->status === $to) {
                return;
            }
            if ($old->status !== 'menunggu') {
                throw ValidationException::withMessages(['refund' => 'Refund sudah diputuskan atau tidak lagi menunggu approval; muat ulang.']);
            }
            DB::table('sales_order_refund')->where('id', $id)->update(['status' => $to, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            DB::table('sales_order_refund_riwayat')->insert(['id_refund' => $id, 'jenis' => $decision, 'catatan' => $note ?: null, 'oleh' => Auth::id(), 'waktu' => now()]);
            write_log('Refund', 'Approval Refund', ($decision === 'setujui' ? 'Setujui ' : 'Tolak ').$old->nomor, ['sub_module' => 'Approval Refund',
                'old' => ['status' => $old->status], 'new' => ['status' => $to], 'catatan' => $note ?: null], 'sales_order_refund', $id, $old->nomor);
        });
    }

    /**
     * Perbaiki dan ajukan ulang refund Ditolak (RF-FR-07): alasan, nominal tagihan (1 s.d. nilai invoice), dan catatan perbaikan wajib.
     * Kembali ke Menunggu Approval tanpa batas jumlah pengajuan ulang. Riwayat tetap tersimpan.
     */
    public static function resubmit(int $id, string $reason, string $nominal, string $note): void
    {
        $reason = trim($reason);
        $note = trim($note);
        $errors = [];
        if ($reason === '') {
            $errors['alasan'] = 'Isi alasan refund.';
        }
        if ($note === '') {
            $errors['catatan'] = 'Tulis apa yang diperbaiki.';
        }
        if (! preg_match('/^[0-9]{1,18}$/', $nominal)) {
            $errors['nominal'] = 'Nominal harus berupa angka rupiah.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        Sales::transaction(function () use ($id, $reason, $nominal, $note) {
            $read = DB::table('sales_order_refund')->where('id', $id)->first() ?? abort(404);
            Sales::open(Sales::program(SalesOrder::row($read->id_sales_order)->id_penjualan, true));
            $old = DB::table('sales_order_refund')->where('id', $id)->lockForUpdate()->first();
            if ($old->status !== 'ditolak') {
                throw ValidationException::withMessages(['refund' => 'Hanya refund yang ditolak yang dapat diajukan ulang; muat ulang.']);
            }
            $invoice = Invoice::row($old->id_invoice, true);
            if ($invoice->status !== 'invoiced' || ! $invoice->tanggal_bayar) {
                throw ValidationException::withMessages(['refund' => 'Invoice tidak lagi memenuhi syarat Refund.']);
            }
            $amount = BigDecimal::of($nominal);
            if ($amount->isLessThanOrEqualTo(0) || $amount->isGreaterThan((string) $invoice->piutang)) {
                throw ValidationException::withMessages(['nominal' => 'Nominal tagihan 1 sampai '.Invoice::rupiah((string) $invoice->piutang).'.']);
            }
            DB::table('sales_order_refund')->where('id', $id)->update(['status' => 'menunggu', 'alasan' => $reason, 'nominal_tagihan' => (string) $amount, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            DB::table('sales_order_refund_riwayat')->insert(['id_refund' => $id, 'jenis' => 'ajukan_ulang', 'catatan' => $note, 'nominal_lama' => $old->nominal_tagihan, 'nominal_baru' => (string) $amount,
                'alasan_lama' => $old->alasan, 'alasan_baru' => $reason, 'oleh' => Auth::id(), 'waktu' => now()]);
            write_log('Refund', 'Ajukan Ulang', 'Ajukan ulang '.$old->nomor, ['sub_module' => 'Daftar Refund',
                'old' => ['status' => 'ditolak', 'alasan' => $old->alasan, 'nominal_tagihan' => (string) $old->nominal_tagihan],
                'new' => ['status' => 'menunggu', 'alasan' => $reason, 'nominal_tagihan' => (string) $amount], 'catatan' => $note], 'sales_order_refund', $id, $old->nomor);
        });
    }

    public static function finish(int $id, array $input, array $file): bool
    {
        return Sales::transaction(function () use ($id, $input, $file) {
            $read = DB::table('sales_order_refund')->where('id', $id)->first() ?? abort(404);
            $so = SalesOrder::row($read->id_sales_order);
            Sales::open(Sales::program($so->id_penjualan, true));
            $old = DB::table('sales_order_refund')->where('id', $id)->lockForUpdate()->first();
            if (! in_array($old->status, ['disetujui', 'selesai'], true)) {
                throw ValidationException::withMessages(['refund' => $old->status === 'ditolak'
                    ? 'Refund ditolak dan perlu diperbaiki Sales/Marketing sebelum dapat diproses.' : 'Refund belum disetujui di Approval Refund.']);
            }
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
            write_log('Refund', 'Proses Refund', 'Selesai '.$old->nomor, ['sub_module' => 'Refund', 'old' => $safe($old), 'new' => $safe(array_merge((array) $old, $data))], 'sales_order_refund', $id, $old->nomor);

            return true;
        });
    }
}

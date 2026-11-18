<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Reconfirmation
{
    public static function targets(object $q)
    {
        return DB::table('quotation_peserta_konfirmasi_ulang as a')->join('quotation_peserta as x', 'x.id', '=', 'a.id_quotation_peserta')
            ->where('x.id_quotation', $q->id)->where('a.revisi', $q->revisi)->whereIn('x.status', ['menunggu', 'deal'])
            ->orderBy('x.id')->select('a.*', 'x.nama', 'x.status', 'x.id_quotation');
    }

    public static function pending(object $q, ?array $ids = null): bool
    {
        if (! $q->revisi || ! Schema::hasTable('quotation_peserta_konfirmasi_ulang')) {
            return false;
        }

        return self::targets($q)->whereNull('a.diproses_date')->when($ids !== null, fn ($w) => $w->whereIn('x.id', $ids), fn ($w) => $w->where('x.status', 'deal'))->exists();
    }

    public static function deadline(object $p): Carbon
    {
        return Carbon::parse($p->tgl_mulai, 'Asia/Jakarta')->subDay()->startOfDay();
    }

    public static function invoice(object $q, ?int $person = null): ?object
    {
        return DB::table('invoice as i')->where('i.id_quotation', $q->id)->where('i.status', 'invoiced')
            ->when($person !== null, fn ($w) => $w->whereExists(fn ($x) => $x->selectRaw('1')->from('invoice_peserta as ip')->whereColumn('ip.id_invoice', 'i.id')->where('ip.id_quotation_peserta', $person)))
            ->first(['i.*']);
    }

    private static function choice(string $decision, ?object $invoice, ?object $quotation = null): void
    {
        // ponytail: refund dan batal TOP menurut termin terbayar ([JMR-7]) belum dibangun; tolak jelas daripada salah menagih atau mengembalikan dana.
        // Pindah jadwal tidak menyentuh invoice sehingga tetap boleh. Tambahkan alokasi per termin sebelum membuka ini.
        if ($quotation && $decision !== 'pindah_jadwal' && $decision !== 'tidak' && Quotation::termin($quotation)) {
            throw ValidationException::withMessages(['keputusan' => 'Batal ikut atau Refund pada quotation TOP belum tersedia; hubungi Sales.']);
        }
        $paid = $invoice?->tanggal_bayar !== null;
        if (! in_array($decision, $paid ? ['pindah_jadwal', 'refund'] : ['pindah_jadwal', 'batal'], true)) {
            throw ValidationException::withMessages(['keputusan' => 'Status pembayaran berubah atau keputusan tidak sah; muat ulang.']);
        }
    }

    public static function answer(string $token, array $input, ?array $file): void
    {
        Sales::transaction(function () use ($token, $input, $file) {
            $read = DB::table('quotation')->where('link_token', $token)->first() ?? abort(410, 'Link sudah diganti.');
            $p = Sales::program($read->id_penjualan, true);
            $q = Quotation::row($read->id, true);
            abort_if($q->link_token !== $token, 410, 'Link sudah diganti.');
            Sales::open($p);
            if ($q->status === 'batal' || ! $q->revisi || now('Asia/Jakarta')->gte(self::deadline($p))) {
                throw ValidationException::withMessages(['konfirmasi' => 'Konfirmasi ulang sudah ditutup pada H-1 pukul 00.00 WIB.']);
            }
            if ((int) $input['revisi'] !== (int) $q->revisi) {
                throw ValidationException::withMessages(['konfirmasi' => 'Revisi sudah berubah; muat ulang link.']);
            }
            $b2b = $p->jenis === 'b2b';
            $targets = self::targets($q)->when(! $b2b, fn ($w) => $w->where('x.id', $input['id_peserta']))->get();
            if ($targets->isEmpty() || $targets->contains(fn ($x) => $x->tgl_isi !== null)) {
                throw ValidationException::withMessages(['konfirmasi' => 'Jawaban sudah diterima atau peserta tidak termasuk putaran ini. Perubahan melalui Sales.']);
            }
            $invoice = self::invoice($q, $b2b ? null : (int) $targets->first()->id_quotation_peserta);
            self::choice($input['keputusan'], $invoice, $q);
            $following = array_values(array_unique(array_map('intval', $input['peserta'] ?? [])));
            if ($input['keputusan'] === 'pindah_jadwal') {
                if (! $file) {
                    throw ValidationException::withMessages(['surat' => 'Surat konfirmasi baru wajib diunggah.']);
                }
                if ($b2b && (count($following) < 10 || array_diff($following, $targets->pluck('id_quotation_peserta')->all()))) {
                    throw ValidationException::withMessages(['peserta' => 'Pilih minimal 10 peserta terdaftar, tanpa nama baru.']);
                }
                if ($b2b) {
                    $company = DB::table('quotation_konfirmasi_company')->where('id_quotation', $q->id)->orderByDesc('revisi')->first();
                    if (! $company) {
                        throw ValidationException::withMessages(['konfirmasi' => 'Snapshot konfirmasi Company belum tersedia; hubungi Sales.']);
                    }
                    $data = array_intersect_key((array) $company, array_flip(['nama_perusahaan', 'alamat_perusahaan', 'pic_nama', 'pic_jabatan', 'pic_hp', 'pic_email']));
                    DB::table('quotation_konfirmasi_company')->insert($data + ['id_quotation' => $q->id, 'revisi' => $q->revisi, 'jumlah_peserta' => count($following), 'surat_konfirmasi' => $file['path'], 'surat_konfirmasi_nama' => $file['name'], 'tgl_kirim' => now(), 'created_by' => null, 'created_date' => now()]);
                }
            }
            $changes = [];
            foreach ($targets as $a) {
                $decision = $b2b && $input['keputusan'] === 'pindah_jadwal' && ! in_array($a->id_quotation_peserta, $following) ? 'tidak' : $input['keputusan'];
                if ($decision === 'pindah_jadwal' && ! $b2b) {
                    self::letter($a->id_quotation_peserta, $file);
                }
                DB::table('quotation_peserta_konfirmasi_ulang')->where('id', $a->id)->update(['keputusan' => $decision, 'catatan' => $input['catatan'] ?? null, 'surat_konfirmasi' => $file['path'] ?? null,
                    'surat_konfirmasi_nama' => $file['name'] ?? null, 'sumber' => $b2b ? 'company' : 'link', 'tgl_isi' => now(), 'diproses_date' => $decision === 'pindah_jadwal' ? now() : null]);
                $changes['Peserta #'.$a->id_quotation_peserta.' - Keputusan'] = ['old' => $a->keputusan, 'new' => $decision];
            }
            Audit::publicConfirmation('Konfirmasi Ulang', 'Jawaban revisi '.Sales::display($q), ['revisi' => $q->revisi, 'keputusan' => $input['keputusan'], 'jumlah_peserta' => $targets->count(), 'detail_changes' => $changes], $q->id, Sales::display($q));
        });
    }

    private static function letter(int $id, array $file): void
    {
        $old = DB::table('quotation_peserta')->where('id', $id)->first();
        if ($old->surat_konfirmasi) {
            DB::table('quotation_peserta_surat_riwayat')->insert(['id_quotation_peserta' => $id, 'surat_konfirmasi' => $old->surat_konfirmasi, 'surat_konfirmasi_nama' => $old->surat_konfirmasi_nama ?: 'Surat lama',
                'sumber' => 'reschedule', 'alasan' => 'Konfirmasi ulang jadwal baru', 'created_by' => null, 'created_date' => now()]);
        }
        DB::table('quotation_peserta')->where('id', $id)->update(['surat_konfirmasi' => $file['path'], 'surat_konfirmasi_nama' => $file['name']]);
    }

    public static function process(int $personId, array $input): void
    {
        Sales::transaction(function () use ($personId, $input) {
            $person = DB::table('quotation_peserta')->where('id', $personId)->first() ?? abort(404);
            $read = Quotation::row($person->id_quotation);
            $p = Sales::program($read->id_penjualan, true);
            Sales::open($p);
            $q = Quotation::row($read->id, true);
            if ((int) $input['revisi'] !== (int) $q->revisi || ! $q->revisi || $q->status === 'batal') {
                throw ValidationException::withMessages(['konfirmasi' => 'Revisi tidak berlaku; muat ulang.']);
            }
            $answer = DB::table('quotation_peserta_konfirmasi_ulang')->where('id_quotation_peserta', $personId)->where('revisi', $q->revisi)->first();
            if (! $answer) {
                throw ValidationException::withMessages(['konfirmasi' => 'Peserta tidak termasuk konfirmasi ulang revisi ini.']);
            }
            if ($answer->diproses_date && $answer->keputusan === $input['keputusan'] && (string) $answer->catatan === trim($input['alasan'] ?? '')) {
                return;
            }
            if ($person->status === 'keluar' || $person->status === 'batal') {
                if ($person->keluar_jenis === $input['keputusan'] && $person->keluar_alasan === ($input['alasan'] ?? '')) {
                    return;
                }
                throw ValidationException::withMessages(['konfirmasi' => 'Peserta sudah keluar; keputusan tidak dapat diubah lagi.']);
            }
            $b2b = $p->jenis === 'b2b';
            $invoice = self::invoice($q, $b2b ? null : $personId);
            $decision = $input['keputusan'];
            if ($decision !== 'tidak') {
                self::choice($decision, $invoice, $q);
            } elseif (! $b2b || $answer->keputusan !== 'tidak') {
                throw ValidationException::withMessages(['keputusan' => 'Opsi dicoret hanya dari jawaban PIC B2B.']);
            }
            $reason = trim($input['alasan'] ?? '');
            if ($decision !== 'pindah_jadwal' && $reason === '') {
                throw ValidationException::withMessages(['alasan' => 'Alasan tidak ikut wajib.']);
            }
            $company = $b2b && $decision !== 'tidak';
            $targets = self::targets($q)->when(! $company, fn ($w) => $w->where('x.id', $personId))->get();
            if ($company && $decision === 'pindah_jadwal' && $targets->count() < 10) {
                throw ValidationException::withMessages(['keputusan' => 'B2B Pindah jadwal minimal 10 peserta.']);
            }
            $so = DB::table('sales_order')->where('id_quotation', $q->id)->first();
            $refundId = null;
            if ($decision === 'refund') {
                if (! $so || ! $invoice?->tanggal_bayar || ! $targets->contains('status', 'deal')) {
                    throw ValidationException::withMessages(['keputusan' => 'Refund memerlukan peserta Deal dan Invoice lunas.']);
                }
                $refundId = Refund::create($q, $so, $invoice, $answer, $company ? null : $personId, $reason);
            }
            foreach ($targets as $a) {
                $old = DB::table('quotation_peserta')->where('id', $a->id_quotation_peserta)->first();
                $changes = ['updated_by' => Auth::id(), 'updated_date' => now()];
                if ($decision !== 'pindah_jadwal') {
                    $changes += ['status' => $old->status === 'deal' ? 'keluar' : 'batal', 'keluar_jenis' => $decision, 'keluar_alasan' => $reason, 'keluar_revisi' => $q->revisi, 'keluar_by' => Auth::id(), 'keluar_date' => now()];
                }
                DB::table('quotation_peserta')->where('id', $old->id)->update($changes);
                DB::table('quotation_peserta_konfirmasi_ulang')->where('id', $a->id)->update(['keputusan' => $decision, 'catatan' => $reason ?: null, 'sumber' => 'sales', 'tgl_isi' => now(), 'diproses_by' => Auth::id(), 'diproses_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()]);
                write_log('Quotation', $decision === 'pindah_jadwal' ? 'Konfirmasi Ulang' : 'Keluar', $old->nama.' - '.Sales::display($q), ['sub_module' => 'Data Peserta', 'old' => (array) $old, 'new' => array_merge((array) $old, $changes),
                    'detail_changes' => ['Keputusan' => ['old' => $a->keputusan, 'new' => $decision]], 'alasan' => $reason, 'refund_id' => $refundId], 'quotation_peserta', $old->id, Sales::display($q));
            }
            if ($decision === 'batal' && $invoice) {
                $old = Invoice::row($invoice->id, true);
                if ($old->tanggal_bayar !== null) {
                    throw ValidationException::withMessages(['invoice' => 'Invoice sudah dibayar; muat ulang untuk opsi Refund.']);
                }
                $changes = ['status' => 'batal', 'batal_by' => Auth::id(), 'batal_date' => now(), 'batal_alasan' => $reason, 'updated_by' => Auth::id(), 'updated_date' => now()];
                DB::table('invoice')->where('id', $old->id)->update($changes);
                write_log('Invoice', 'Batal', 'Batal '.$old->nomor.': '.$reason, ['old' => ['status' => $old->status], 'new' => ['status' => 'batal'], 'alasan' => $reason], 'invoice', $old->id, $old->nomor);
            }
        });
    }
}

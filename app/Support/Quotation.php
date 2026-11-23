<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class Quotation
{
    public static function participantDocuments($rows)
    {
        $history = DB::table('quotation_peserta_surat_riwayat')->whereIn('id_quotation_peserta', $rows->pluck('id'))->orderByDesc('id')->get()->groupBy('id_quotation_peserta');
        $companies = DB::table('quotation_konfirmasi_company as c')->join('quotation as q', function ($join) {
            $join->on('q.id', '=', 'c.id_quotation')->on('q.revisi', '=', 'c.revisi');
        })
            ->whereIn('q.id', $rows->pluck('id_quotation'))->pluck('c.id', 'c.id_quotation');

        return $rows->map(function ($p) use ($history, $companies) {
            $p->history = $history->get($p->id, collect());
            $p->company_confirmation_id = $companies->get($p->id_quotation);
            if (Schema::hasTable('quotation_peserta_konfirmasi_ulang')) {
                $q = self::row($p->id_quotation);
                $p->revisi = $q->revisi;
                $p->konfirmasi_ulang = DB::table('quotation_peserta_konfirmasi_ulang')->where('id_quotation_peserta', $p->id)->where('revisi', $q->revisi)->first(['keputusan', 'catatan', 'tgl_isi', 'diproses_date']);
                $p->konfirmasi_deadline = $q->revisi ? Reconfirmation::deadline(Sales::program($q->id_penjualan))->format('d-m-Y H:i').' WIB' : null;
                $p->konfirmasi_expired = $q->revisi && now('Asia/Jakarta')->gte(Reconfirmation::deadline(Sales::program($q->id_penjualan)));
                $p->b2b = Sales::program($q->id_penjualan)->jenis === 'b2b';
                $i = Reconfirmation::invoice($q);
                $p->sudah_bayar = $i?->tanggal_bayar !== null;
                $p->refund = DB::table('sales_order_refund')->where('id_sales_order', DB::table('sales_order')->where('id_quotation', $q->id)->value('id'))->where(fn ($w) => $w->where('id_quotation_peserta', $p->id)->orWhereNull('id_quotation_peserta'))->first(['id', 'nomor']);
            }

            return $p;
        });
    }

    public static function row(int $id, bool $lock = false): object
    {
        return DB::table('quotation')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first() ?? abort(404);
    }

    public static function calculate(object|array $quotation, int $count): array
    {
        $q = (array) $quotation;
        $money = fn ($v) => BigDecimal::of((string) ($v ?? 0));
        $b2b = ($q['skema_harga'] ?? 'peserta') === 'allin';
        $extraCount = $b2b ? max(0, $count - (int) ($q['paket_maks_peserta'] ?? 25)) : 0;
        $extra = $money($q['tambahan_per_peserta'] ?? 0)->multipliedBy($extraCount);
        $subtotal = $b2b ? $money($q['harga_allin'])->plus($extra) : $money($q['harga_peserta'])->multipliedBy($count);
        $discount = $money($q['diskon'] ?? 0)->multipliedBy($b2b ? 1 : $count);
        $dpp = $subtotal->minus($discount);
        $tax = ! empty($q['is_ppn']) ? $dpp->multipliedBy((string) ($q['ppn_persen'] ?? 11))->dividedBy(100, 2, RoundingMode::HalfUp) : $money(0);

        return ['n_peserta' => $count, 'n_tambahan' => $extraCount, ...array_map(fn ($n) => (string) $n->toScale(2, RoundingMode::HalfUp), [
            'subtotal' => $subtotal, 'tambahan' => $extra, 'diskon' => $discount, 'dpp' => $dpp, 'ppn' => $tax, 'biaya_lain' => $money($q['biaya_lain'] ?? 0), 'grand_total' => $dpp->plus($tax)->plus($q['biaya_lain'] ?? 0),
        ])];
    }

    /** Termin TOP quotation berurutan; kosong bila Full payment atau termin kurang dari dua. */
    public static function termin(object|array $quotation): array
    {
        $q = (array) $quotation;
        if (($q['cara_bayar'] ?? 'full') !== 'top' || empty($q['id']) || ! Schema::hasTable('quotation_termin')) {
            return [];
        }
        $rows = DB::table('quotation_termin')->where('id_quotation', $q['id'])->orderBy('urutan')->get(['urutan', 'persen', 'keterangan'])->all();

        return count($rows) > 1 ? $rows : [];
    }

    /**
     * Nilai termin ke-$ke dari nilai Deal (QTN-FR-44, SO-FR-24): persen x nilai, termin terakhir menampung selisih pembulatan.
     * ponytail: dasar persen x nilai Deal dan pembulatan di termin terakhir berstatus [USULAN] di spec; ubah di sini bila BA menetapkan lain.
     *
     * @param  array<int, object>  $termin
     * @param  array<int, string|int>  $sudah  nilai termin yang sudah ditagih (jumlah dipakai untuk termin terakhir)
     */
    public static function terminShare(array $termin, int $ke, string|int $nilai, string|int $sudah = 0): string
    {
        $last = $ke === count($termin);
        if ($last) {
            return (string) BigDecimal::of((string) $nilai)->minus((string) $sudah)->toScale(0, RoundingMode::HalfUp);
        }

        return (string) BigDecimal::of((string) $nilai)->multipliedBy((string) $termin[$ke - 1]->persen)->dividedBy(100, 0, RoundingMode::HalfUp);
    }

    /** Tulis ulang termin dari input form; hanya berlaku untuk cara_bayar top. */
    public static function saveTermin(int $id, string $cara, array $rows): void
    {
        DB::table('quotation_termin')->where('id_quotation', $id)->delete();
        if ($cara !== 'top') {
            return;
        }
        foreach (array_values($rows) as $i => $r) {
            DB::table('quotation_termin')->insert(['id_quotation' => $id, 'urutan' => $i + 1, 'persen' => $r['persen'], 'keterangan' => trim($r['keterangan']),
                'created_by' => Auth::id(), 'created_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()]);
        }
    }

    public static function count(object $q): int
    {
        return $q->status === 'deal' ? DB::table('quotation_peserta')->where('id_quotation', $q->id)->where('status', 'deal')->count() : (int) $q->kuota_peserta;
    }

    public static function matchParticipant(array $p): ?object
    {
        $matches = [];
        $nik = preg_replace('/\D/', '', (string) ($p['nik'] ?? ''));
        $email = mb_strtolower(trim((string) ($p['email'] ?? '')));
        $phone = preg_replace('/\D/', '', (string) (($p['no_wa'] ?? '') ?: ($p['no_hp'] ?? '')));
        // Periksa semua kunci agar NIK yang cocok tidak menutupi email/WA milik orang lain.
        foreach (['nik' => $nik, 'email' => $email, 'no_telp' => $phone] as $field => $value) {
            if ($value === '') {
                continue;
            }
            $query = DB::table('ms_peserta')->whereNull('deleted_date');
            if ($field === 'no_telp') {
                $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(no_telp, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') LIKE ?", ['%'.substr($value, -7)]);
                $rows = $query->lockForUpdate()->get()->filter(fn ($m) => preg_replace('/\D/', '', (string) $m->no_telp) === $value);
            } else {
                $rows = $query->whereRaw('LOWER(TRIM('.$field.')) = ?', [$value])->lockForUpdate()->get();
            }
            if ($rows->count() > 1) {
                throw ValidationException::withMessages(['peserta' => 'Identitas peserta ambigu di Master Peserta; periksa master sebelum Deal.']);
            }
            if ($rows->isNotEmpty()) {
                $matches[$rows->first()->id] = $rows->first();
            }
        }
        if (count($matches) > 1) {
            throw ValidationException::withMessages(['peserta' => 'NIK, email, atau WA menunjuk peserta yang berbeda; periksa master.']);
        }
        $match = $matches ? reset($matches) : null;
        if ($match) {
            foreach (['nik' => $nik, 'email' => $email, 'no_telp' => $phone] as $field => $value) {
                $stored = $field === 'email' ? mb_strtolower(trim((string) $match->$field)) : preg_replace('/\D/', '', (string) $match->$field);
                if ($value !== '' && $stored !== '' && $value !== $stored) {
                    throw ValidationException::withMessages(['peserta' => 'Identitas bertentangan dengan Master Peserta; data tidak digabung otomatis.']);
                }
            }
        }

        return $match;
    }

    public static function syncParticipant(array $p, object $q, object $program): bool
    {
        $match = self::matchParticipant($p);
        $company = $q->id_customer ? DB::table('master_customer')->where('id_customer', $q->id_customer)->first() : null;
        $fields = DB::table('master_program as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')->where('m.id', $program->id_master_program)->distinct()->pluck('p.id_jenis_bidang');
        $data = ['nama' => $p['nama'], 'nik' => $p['nik'] ?? null, 'email' => $p['email'] ?? null, 'no_telp' => ($p['no_wa'] ?? null) ?: ($p['no_hp'] ?? null),
            'instansi' => $company?->name_customer ?? ($p['instansi'] ?? null), 'id_customer' => $company?->id_customer, 'npwp_instansi' => $company?->npwp,
            'id_jenis_bidang' => $fields->count() === 1 ? $fields->first() : null, 'id_prov' => $company?->id_prov, 'provinsi' => $company?->provinsi, 'id_kab' => $company?->id_city, 'kabupaten' => $company?->kota];
        if ($match) {
            $update = array_filter($data, fn ($v, $k) => $v !== null && $v !== '' && ($match->$k === null || trim((string) $match->$k) === ''), ARRAY_FILTER_USE_BOTH);
            if ($update) {
                DB::table('ms_peserta')->where('id', $match->id)->update($update + ['updated_by' => Auth::id(), 'updated_date' => now()]);
                write_log('Master Peserta', 'Edit', 'Lengkapi '.$match->kode.' dari '.$q->nomor, ['old' => (array) $match, 'new' => array_merge((array) $match, $update)], 'ms_peserta', $match->id, $match->kode);
            }
            $id = $match->id;
        } else {
            $data += ['kode' => CodeGenerator::next('ms_peserta', config('labmania.code_prefix.peserta')), 'aktif' => 1, 'created_by' => Auth::id(), 'created_date' => now()];
            $id = DB::table('ms_peserta')->insertGetId($data);
            write_log('Master Peserta', 'Tambah', 'Tambah '.$data['kode'].' dari '.$q->nomor, $data, 'ms_peserta', $id, $data['kode']);
        }
        DB::table('quotation_peserta')->where('id', $p['id'])->update(['id_peserta' => $id]);

        return $match === null;
    }

    public static function deal(int $id, array $ids, array $files, ?string $note): array
    {
        return Sales::transaction(function () use ($id, $ids, $files, $note) {
            // Kunci program sebelum quotation: sama dengan kiriman link dan pembuatan quotation B2B.
            $read = self::row($id);
            $program = Sales::program($read->id_penjualan, true);
            Sales::open($program);
            $old = self::row($id, true);
            if ($old->status === 'deal') {
                return ['so' => DB::table('sales_order')->where('id_quotation', $id)->where('status', '!=', 'batal')->value('nomor'), 'baru' => 0, 'cocok' => 0, 'replay' => true];
            }
            // Deal ulang (QTN-FR-42): quotation Terkirim yang sebelumnya Deal lalu direschedule. Dokumen lama terbawa, unggahan baru opsional.
            $again = self::needsRedeal($old);
            if ($old->status !== 'terkirim' || (! $files && ! ($again && DB::table('quotation_dokumen')->where('id_quotation', $id)->exists()))) {
                throw ValidationException::withMessages(['quotation' => 'Deal memerlukan quotation Terkirim dan dokumen customer.']);
            }
            if ($again && today('Asia/Jakarta')->gte(Carbon::parse($program->tgl_mulai, 'Asia/Jakarta'))) {
                throw ValidationException::withMessages(['quotation' => 'Deal ulang paling lambat H-1; batalkan quotation ini bila tidak jadi.']);
            }
            $rows = DB::table('quotation_peserta')->where('id_quotation', $id)->lockForUpdate()->get();
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $selected = $again ? $rows->whereIn('id', $ids)->whereIn('status', ['menunggu', 'deal']) : $rows->whereIn('id', $ids)->where('status', 'menunggu');
            if ($again) {
                // Yang dapat dicentang hanya peserta yang menjawab Pindah jadwal pada revisi terakhir (QTN-FR-42 butir 1).
                $follow = Reconfirmation::targets($old)->where('a.keputusan', 'pindah_jadwal')->pluck('x.id')->map(fn ($v) => (int) $v)->all();
                if (array_diff($ids, $follow)) {
                    throw ValidationException::withMessages(['peserta' => 'Deal ulang hanya untuk peserta yang memilih Pindah jadwal.']);
                }
                // Boleh disimpan bila semua peserta sudah menjawab, atau mulai H-1 (yang belum menjawab dianggap batal).
                $h1 = Carbon::parse($program->tgl_mulai, 'Asia/Jakarta')->subDay()->startOfDay();
                if (Reconfirmation::targets($old)->whereNull('a.tgl_isi')->whereNull('a.diproses_date')->exists() && today('Asia/Jakarta')->lt($h1)) {
                    throw ValidationException::withMessages(['peserta' => 'Masih ada peserta yang belum menjawab konfirmasi ulang; simpan setelah semua menjawab atau mulai H-1.']);
                }
            } elseif (Reconfirmation::pending($old, $ids)) {
                throw ValidationException::withMessages(['peserta' => 'Peserta harus menyelesaikan konfirmasi ulang sebelum Deal.']);
            }
            if ($selected->count() !== count($ids) || count($ids) < ($program->jenis === 'b2b' ? 10 : 1)) {
                throw ValidationException::withMessages(['peserta' => 'Pilih peserta Menunggu dari quotation ini; B2B minimal 10.']);
            }
            if (! $program->id_trainer || ! $program->id_asisten || $program->status !== 'final') {
                throw ValidationException::withMessages(['program' => 'Program harus Final dengan trainer dan asisten sah.']);
            }
            // Serialisasi pencocokan lintas program, termasuk identitas yang belum memiliki baris master.
            DB::table('ms_menu')->where('kode', 'master_data.peserta')->lockForUpdate()->first();
            $newCount = 0;
            foreach ($selected as $p) {
                $newCount += self::syncParticipant((array) $p, $old, $program) ? 1 : 0;
            }
            $changes = [];
            $exits = [];
            foreach ($rows as $p) {
                if (in_array($p->id, $ids, true)) {
                    $status = 'deal';
                } elseif ($again && $p->status === 'deal') {
                    $exits[] = $p;
                    $status = 'keluar';
                } else {
                    $status = $p->status === 'keluar' ? 'keluar' : 'batal';
                }
                $changes['Peserta #'.$p->id.' '.$p->nama] = ['old' => $p->status, 'new' => $status];
                if ($status !== $p->status) {
                    DB::table('quotation_peserta')->where('id', $p->id)->update(['status' => $status]);
                }
            }
            $auto = $again ? self::autoExit($old, $exits) : [];
            foreach ($files as $file) {
                self::customerDocument($id, $file);
            }
            $update = ['status' => 'deal', 'deal_by' => Auth::id(), 'deal_date' => now(), 'dokumen_keterangan' => $note ?? $old->dokumen_keterangan, 'updated_by' => Auth::id(), 'updated_date' => now()];
            if ($again) {
                $update += ['deal_ulang_revisi' => null, 'deal_ulang_waktu' => null, 'deal_ulang_selesai_revisi' => $old->revisi, 'deal_ulang_selesai_date' => now()];
            }
            DB::table('quotation')->where('id', $id)->update($update);
            // Deal ulang: SO baru memakai nomor SO lama + -rev{n} (n = revisi quotation), nomor dasar sama (SO-FR-21).
            $previous = $again ? DB::table('sales_order')->where('id_quotation', $id)->where('status', 'batal')->orderByDesc('id')->first() : null;
            $so = $previous ? $previous->nomor : Sales::number('sales_order', 'LMI/SO/'.now()->format('m/y'));
            $soId = DB::table('sales_order')->insertGetId(['nomor' => $so, 'id_quotation' => $id, 'id_penjualan' => $program->id, 'revisi' => $old->revisi, 'tanggal' => today()->toDateString(), 'status' => 'open', 'created_by' => Auth::id(), 'created_date' => now()]);
            if ($previous) {
                DB::table('sales_order')->where('id', $previous->id)->update(['digantikan_oleh' => $soId]);
                // Invoice aktif tetap dipakai (tidak ditagih ulang): dipindah ke SO baru agar penagihan membaca SO yang berlaku.
                DB::table('invoice')->where('id_quotation', $id)->where('status', 'invoiced')->update(['id_sales_order' => $soId, 'updated_by' => Auth::id(), 'updated_date' => now()]);
            }
            $changes['Sales Order'] = ['old' => null, 'new' => $so];
            write_log('Quotation', 'Tambah', 'Tambah '.$so.' dari '.$old->nomor, ['sub_module' => 'Sales Order', 'nomor' => $so], 'sales_order', $soId, $so);
            if (! DB::table('surat_kerja_trainer')->where('id_penjualan', $program->id)->where('status', 'berlaku')->exists()) {
                $number = Sales::number('surat_kerja_trainer', 'SKT-'.now()->format('ym'));
                $sid = DB::table('surat_kerja_trainer')->insertGetId(['nomor' => $number, 'id_penjualan' => $program->id, 'id_trainer' => $program->id_trainer, 'id_asisten' => $program->id_asisten, 'tanggal' => today()->toDateString(), 'status' => 'berlaku', 'snapshot' => json_encode(SalesOrder::programSnapshot($program), JSON_THROW_ON_ERROR), 'created_by' => Auth::id(), 'created_date' => now()]);
                $changes['Surat Tugas'] = ['old' => null, 'new' => $number];
                write_log('Quotation', 'Tambah', 'Tambah '.$number, ['sub_module' => 'Surat Kerja Trainer', 'nomor' => $number], 'surat_kerja_trainer', $sid, $number);
            }
            write_log('Quotation', $again ? 'Deal Ulang' : 'Deal', ($again ? 'Deal ulang ' : 'Deal ').$old->nomor.'; Master Peserta: '.$newCount.' baru, '.(count($ids) - $newCount).' dicocokkan', ['old' => (array) $old, 'new' => array_merge((array) $old, $update), 'detail_changes' => $changes + ($auto ? ['Tidak ikut diproses' => ['old' => null, 'new' => $auto]] : [])], 'quotation', $id, $old->nomor);

            return ['so' => $so, 'baru' => $newCount, 'cocok' => count($ids) - $newCount, 'keluar' => count($exits), 'ulang' => $again];
        });
    }

    /**
     * Penanda reschedule per quotation (QTN-FR-43): kirim link baru, Konfirmasi ulang x/y, Siap Deal ulang, atau Deal ulang rev{n}.
     * Null bila quotation tidak sedang atau tidak pernah melewati Deal ulang.
     */
    public static function redealMarker(object $q): ?array
    {
        if (($q->deal_ulang_selesai_revisi ?? null) !== null && $q->status === 'deal') {
            return ['kode' => 'selesai', 'teks' => 'Deal ulang rev'.$q->deal_ulang_selesai_revisi, 'sub' => $q->deal_ulang_selesai_date ? substr((string) $q->deal_ulang_selesai_date, 0, 10) : null];
        }
        if (! self::needsRedeal($q)) {
            return null;
        }
        $targets = Reconfirmation::targets($q)->get();
        $answered = $targets->whereNotNull('tgl_isi')->count() + $targets->whereNull('tgl_isi')->whereNotNull('diproses_date')->count();
        $follow = $targets->where('keputusan', 'pindah_jadwal')->count();
        if ($targets->isNotEmpty() && $answered === $targets->count()) {
            return $follow > 0 ? ['kode' => 'siap', 'teks' => 'Siap Deal ulang', 'sub' => $follow.' peserta pindah jadwal']
                : ['kode' => 'semua_tidak', 'teks' => 'Semua peserta tidak ikut', 'sub' => 'batalkan quotation'];
        }

        return $answered === 0 ? ['kode' => 'kirim_link', 'teks' => 'Kirim link konfirmasi baru', 'sub' => 'belum ada jawaban']
            : ['kode' => 'konfirmasi', 'teks' => 'Konfirmasi ulang '.$answered.'/'.$targets->count(), 'sub' => 'lalu Deal ulang'];
    }

    /** Quotation Terkirim yang sebelumnya Deal lalu direschedule: menunggu Deal ulang (QTN-FR-42). */
    public static function needsRedeal(object $q): bool
    {
        return $q->status === 'terkirim' && ($q->deal_ulang_revisi ?? null) !== null;
    }

    /**
     * Peserta Deal lama yang tidak ikut jadwal baru diproses otomatis (QTN-FR-42 butir 3): invoice dibayar menjadi Refund; invoice terbit belum
     * dibayar dibatalkan; belum ditagih keluar tanpa tagihan. Dikembalikan ringkasan untuk audit.
     *
     * @param  array<int, object>  $people
     */
    private static function autoExit(object $q, array $people): array
    {
        $summary = [];
        $program = Sales::program($q->id_penjualan);
        $so = DB::table('sales_order')->where('id_quotation', $q->id)->orderByDesc('id')->first();
        foreach ($people as $person) {
            $answer = DB::table('quotation_peserta_konfirmasi_ulang')->where('id_quotation_peserta', $person->id)->where('revisi', $q->revisi)->first();
            $reason = $answer && $answer->keputusan && $answer->keputusan !== 'pindah_jadwal' ? ($answer->catatan ?: 'Tidak ikut jadwal baru') : 'Tidak menjawab konfirmasi ulang sampai H-1';
            $b2b = $program->jenis === 'b2b';
            $invoice = Reconfirmation::invoice($q, $b2b ? null : $person->id);
            $paid = $invoice?->tanggal_bayar !== null && $invoice !== null;
            $kind = $paid ? 'refund' : 'batal';
            $refund = null;
            if ($paid && $so) {
                $refund = Refund::create($q, $so, $invoice, $answer, $b2b ? null : (int) $person->id, 'Peserta tidak ikut jadwal baru '.Sales::display($program).': '.$reason);
            }
            DB::table('quotation_peserta')->where('id', $person->id)->update(['keluar_jenis' => $kind, 'keluar_alasan' => $reason, 'keluar_revisi' => $q->revisi, 'keluar_by' => Auth::id(), 'keluar_date' => now()]);
            if ($answer && ! $answer->diproses_date) {
                DB::table('quotation_peserta_konfirmasi_ulang')->where('id', $answer->id)->update(['diproses_date' => now(), 'diproses_by' => Auth::id(), 'updated_by' => Auth::id(), 'updated_date' => now()]);
            }
            $summary[] = $person->nama.' ('.($refund ? 'Refund' : 'Batal ikut').')';
        }
        if ($people) {
            // Invoice belum dibayar milik peserta yang keluar dibatalkan; TOP: semua termin belum dibayar.
            if (self::termin($q)) {
                Reconfirmation::cancelUnpaid($q, 'Peserta tidak ikut jadwal baru');
            } else {
                foreach (DB::table('invoice')->where('id_quotation', $q->id)->where('status', 'invoiced')->whereNull('tanggal_bayar')->lockForUpdate()->get() as $open) {
                    $remaining = DB::table('invoice_peserta as ip')->join('quotation_peserta as x', 'x.id', '=', 'ip.id_quotation_peserta')->where('ip.id_invoice', $open->id)->where('x.status', 'deal')->exists();
                    if (! $remaining) {
                        DB::table('invoice')->where('id', $open->id)->update(['status' => 'batal', 'batal_by' => Auth::id(), 'batal_date' => now(), 'batal_alasan' => 'Peserta tidak ikut jadwal baru', 'updated_by' => Auth::id(), 'updated_date' => now()]);
                        write_log('Invoice', 'Batal', 'Batal '.$open->nomor.': peserta tidak ikut jadwal baru', ['old' => ['status' => 'invoiced'], 'new' => ['status' => 'batal']], 'invoice', $open->id, $open->nomor);
                    }
                }
            }
        }

        return $summary;
    }

    public static function customerDocument(int $id, array $f): void
    {
        DB::table('quotation_dokumen')->insert(['id_quotation' => $id, 'nama_file' => $f['name'], 'path_file' => $f['path'], 'tipe_file' => $f['ext'], 'ukuran_byte' => $f['size'], 'created_by' => Auth::id(), 'created_date' => now()]);
    }
}

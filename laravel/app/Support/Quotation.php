<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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
        $fields = DB::table('master_program_produk as m')->join('paket_produk as p', 'p.id', '=', 'm.id_paket_produk')->where('m.id_master_program', $program->id_master_program)->distinct()->pluck('p.id_jenis_bidang');
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
                return ['so' => DB::table('sales_order')->where('id_quotation', $id)->value('nomor'), 'baru' => 0, 'cocok' => 0, 'replay' => true];
            }
            if ($old->status !== 'terkirim' || ! $files) {
                throw ValidationException::withMessages(['quotation' => 'Deal memerlukan quotation Terkirim dan dokumen customer.']);
            }
            $rows = DB::table('quotation_peserta')->where('id_quotation', $id)->lockForUpdate()->get();
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $selected = $rows->whereIn('id', $ids)->where('status', 'menunggu');
            if (Reconfirmation::pending($old, $ids)) {
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
            foreach ($rows as $p) {
                $status = in_array($p->id, $ids, true) ? 'deal' : 'batal';
                $changes['Peserta #'.$p->id.' '.$p->nama] = ['old' => $p->status, 'new' => $status];
                DB::table('quotation_peserta')->where('id', $p->id)->update(['status' => $status]);
            }
            foreach ($files as $file) {
                self::customerDocument($id, $file);
            }
            $update = ['status' => 'deal', 'deal_by' => Auth::id(), 'deal_date' => now(), 'dokumen_keterangan' => $note, 'updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('quotation')->where('id', $id)->update($update);
            $so = Sales::number('sales_order', 'LMI/SO/'.now()->format('m/y'));
            $soId = DB::table('sales_order')->insertGetId(['nomor' => $so, 'id_quotation' => $id, 'id_penjualan' => $program->id, 'revisi' => $old->revisi, 'tanggal' => today()->toDateString(), 'status' => 'open', 'created_by' => Auth::id(), 'created_date' => now()]);
            $changes['Sales Order'] = ['old' => null, 'new' => $so];
            write_log('Quotation', 'Tambah', 'Tambah '.$so.' dari '.$old->nomor, ['sub_module' => 'Sales Order', 'nomor' => $so], 'sales_order', $soId, $so);
            if (! DB::table('surat_kerja_trainer')->where('id_penjualan', $program->id)->where('status', 'berlaku')->exists()) {
                $number = Sales::number('surat_kerja_trainer', 'SKT-'.now()->format('ym'));
                $sid = DB::table('surat_kerja_trainer')->insertGetId(['nomor' => $number, 'id_penjualan' => $program->id, 'id_trainer' => $program->id_trainer, 'id_asisten' => $program->id_asisten, 'tanggal' => today()->toDateString(), 'status' => 'berlaku', 'snapshot' => json_encode(SalesOrder::programSnapshot($program), JSON_THROW_ON_ERROR), 'created_by' => Auth::id(), 'created_date' => now()]);
                $changes['Surat Tugas'] = ['old' => null, 'new' => $number];
                write_log('Quotation', 'Tambah', 'Tambah '.$number, ['sub_module' => 'Surat Kerja Trainer', 'nomor' => $number], 'surat_kerja_trainer', $sid, $number);
            }
            write_log('Quotation', 'Deal', 'Deal '.$old->nomor.'; Master Peserta: '.$newCount.' baru, '.(count($ids) - $newCount).' dicocokkan', ['old' => (array) $old, 'new' => array_merge((array) $old, $update), 'detail_changes' => $changes], 'quotation', $id, $old->nomor);

            return ['so' => $so, 'baru' => $newCount, 'cocok' => count($ids) - $newCount];
        });
    }

    public static function customerDocument(int $id, array $f): void
    {
        DB::table('quotation_dokumen')->insert(['id_quotation' => $id, 'nama_file' => $f['name'], 'path_file' => $f['path'], 'tipe_file' => $f['ext'], 'ukuran_byte' => $f['size'], 'created_by' => Auth::id(), 'created_date' => now()]);
    }
}

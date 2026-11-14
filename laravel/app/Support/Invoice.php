<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Invoice
{
    public static function rupiah(string|int $value): string
    {
        $rounded = (string) BigDecimal::of((string) $value)->toScale(0, RoundingMode::HalfUp);

        return 'Rp '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $rounded);
    }

    public static function terbilang(string|int $value): string
    {
        $n = (int) $value;
        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($n < 12) {
            return $words[$n];
        }
        if ($n < 20) {
            return $words[$n - 10].' belas';
        }
        if ($n < 100) {
            return trim($words[intdiv($n, 10)].' puluh '.self::terbilang($n % 10));
        }
        if ($n < 200) {
            return trim('seratus '.self::terbilang($n - 100));
        }
        if ($n < 1000) {
            return trim($words[intdiv($n, 100)].' ratus '.self::terbilang($n % 100));
        }
        if ($n < 2000) {
            return trim('seribu '.self::terbilang($n - 1000));
        }
        foreach ([1000000000000000 => 'kuadriliun', 1000000000000 => 'triliun', 1000000000 => 'miliar', 1000000 => 'juta', 1000 => 'ribu'] as $unit => $word) {
            if ($n >= $unit) {
                return trim(self::terbilang(intdiv($n, $unit)).' '.$word.' '.self::terbilang($n % $unit));
            }
        }

        return '';
    }

    public static function row(int $id, bool $lock = false): object
    {
        return DB::table('invoice')->where('id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first() ?? abort(404);
    }

    public static function account(string $key, ?int $selected = null): object
    {
        $config = config('labmania.invoice.coa.'.$key, []);
        $id = $selected ?: ($config['id'] ?? null);
        $number = $config['number'] ?? null;
        $query = DB::table('coa_master');
        $rows = $id ? $query->where('id', $id)->get() : ($number ? $query->where('no_perkiraan', $number)->get() : collect());
        if ($rows->count() !== 1 || trim((string) $rows->first()?->no_perkiraan) === '' || trim((string) $rows->first()?->nama) === '') {
            throw ValidationException::withMessages(['coa_penjualan' => 'Konfigurasi COA '.$key.' belum valid atau ambigu; pilih ID akun master yang sah.']);
        }

        return $rows->first();
    }

    public static function source(int $soId): array
    {
        $so = SalesOrder::row($soId);
        $detail = SalesOrder::detail($so);
        $q = $detail['quotation'];
        if ($so->status !== 'siap' || $q->status !== 'deal' || $detail['peserta']->isEmpty()) {
            throw ValidationException::withMessages(['invoice' => 'Invoice memerlukan SO Siap invoice dengan peserta Deal.']);
        }
        if ($detail['closed']) {
            throw ValidationException::withMessages(['invoice' => 'Program sudah Closed; perubahan ditolak.']);
        }
        if (Reconfirmation::pending($q)) {
            throw ValidationException::withMessages(['invoice' => 'Selesaikan seluruh konfirmasi ulang peserta Deal sebelum membuat Invoice baru.']);
        }
        if ($detail['program']['jenis'] === 'b2b' && ($q->skema_harga !== 'allin' || $q->tujuan !== 'company')) {
            throw ValidationException::withMessages(['invoice' => 'B2B wajib All-in 1 company; rekonsiliasi quotation diperlukan.']);
        }
        $treatment = $q->id_customer ? ($detail['company']?->perlakuan_ppn ?? null) : 'PKP';
        if (! in_array($treatment, ['PKP', 'WAPU', 'KB'], true)) {
            throw ValidationException::withMessages(['invoice' => 'Lengkapi Perlakuan PPN pada Master Company sebelum Invoice.']);
        }
        $detail['perlakuan_ppn'] = $treatment;
        $detail['amounts'] = self::calculate($q, $detail['peserta']->count(), $treatment);

        return $detail;
    }

    public static function journals(array $rows, array $accounts, string $number, string $quotation, string $date, string $treatment): array
    {
        self::balance($rows);

        return array_map(function ($r, $i) use ($accounts, $number, $quotation, $date, $treatment) {
            $a = $accounts[$r[0]];

            return ['urutan' => $i + 1, 'tanggal' => $date, 'company' => 'PT LabMania Indonesia Jaya', 'coa' => $a->id,
                'no_coa' => $a->no_perkiraan, 'nama_coa' => $a->nama, 'deskripsi' => $a->nama.' - '.$number.' - '.$quotation.($treatment === 'KB' ? ' - Kawasan Berikat' : ''), 'debit' => $r[1], 'credit' => $r[2]];
        }, $rows, array_keys($rows));
    }

    public static function accounts(array $rows, int $revenue): array
    {
        $accounts = ['penjualan' => self::account('penjualan', $revenue)];
        foreach ($rows as $r) {
            $accounts[$r[0]] = self::account($r[0], $r[0] === 'penjualan' ? $revenue : null);
        }

        return $accounts;
    }

    public static function save(int $soId, array $input): int
    {
        return Sales::transaction(function () use ($soId, $input) {
            $read = SalesOrder::row($soId);
            Sales::open(Sales::program($read->id_penjualan, true));
            $so = SalesOrder::row($soId, true);
            Quotation::row($so->id_quotation, true);
            $existing = DB::table('invoice')->where('id_sales_order', $soId)->where('status', 'invoiced')->first();
            if ($existing) {
                if ($existing->tanggal !== $input['tanggal'] || ($existing->no_faktur ?? '') !== ($input['no_faktur'] ?? '') || (int) $existing->coa_penjualan !== (int) $input['coa_penjualan']) {
                    throw ValidationException::withMessages(['invoice' => 'SO sudah memiliki Invoice aktif dengan data berbeda.']);
                }

                return $existing->id;
            }
            $source = self::source($soId);
            $q = $source['quotation'];
            if ($q->is_ppn && trim($input['no_faktur'] ?? '') === '') {
                throw ValidationException::withMessages(['no_faktur' => 'Nomor Faktur wajib untuk quotation dengan PPN.']);
            }
            $rows = self::journal($source['amounts'], $source['perlakuan_ppn']);
            $accounts = self::accounts($rows, $input['coa_penjualan']);
            $date = Carbon::parse($input['tanggal']);
            $number = Sales::number('invoice', 'LMI/INV/'.$date->format('m/y'));
            // Snapshot hanya fakta cetak; token, path berkas dan konfigurasi privat tidak disalin.
            $snapshot = array_intersect_key($source, array_flip(['so', 'program', 'judul', 'produk', 'sales', 'company', 'perlakuan_ppn']));
            $snapshot['quotation'] = array_intersect_key((array) $q, array_flip(['id', 'nomor', 'revisi', 'tujuan', 'kepada_nama', 'up_nama', 'alamat', 'skema_harga', 'harga_peserta', 'harga_allin', 'paket_maks_peserta', 'tambahan_per_peserta', 'diskon', 'is_ppn']));
            $snapshot['quotation']['nomor'] = Sales::display($q);
            $snapshot['peserta'] = $source['peserta']->map(fn ($p) => array_intersect_key((array) $p, array_flip(['id', 'nama', 'instansi', 'jabatan'])))->all();
            if ($q->tujuan === 'personal') {
                $snapshot['quotation']['kepada_nama'] = $source['peserta']->first()->nama;
                $snapshot['quotation']['up_nama'] = $source['peserta']->first()->nama;
            }
            $header = ['nomor' => $number, 'tanggal' => $input['tanggal'], 'id_sales_order' => $soId, 'id_quotation' => $q->id, 'id_penjualan' => $so->id_penjualan,
                'cara_tagih' => 'satu', 'perlakuan_ppn' => $source['perlakuan_ppn'], 'no_faktur' => $q->is_ppn ? ($input['no_faktur'] ?? null) : null,
                ...$source['amounts'], 'coa_penjualan' => $input['coa_penjualan'], 'status' => 'invoiced', 'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), ...self::audit()];
            $id = DB::table('invoice')->insertGetId($header);
            $count = count($snapshot['peserta']);
            $part = BigDecimal::of($header['penjualan'])->dividedBy($count, 0, RoundingMode::Down);
            foreach ($snapshot['peserta'] as $i => $p) {
                $nominal = $i === $count - 1 ? BigDecimal::of($header['penjualan'])->minus($part->multipliedBy($count - 1)) : $part;
                DB::table('invoice_peserta')->insert(['id_invoice' => $id, 'id_quotation_peserta' => $p['id'], 'nama' => $p['nama'], 'nominal' => (string) $nominal, ...self::audit()]);
            }
            $journals = self::journals($rows, $accounts, $number, $q->nomor, $input['tanggal'], $header['perlakuan_ppn']);
            foreach ($journals as $j) {
                DB::table('invoice_jurnal')->insert(['id_invoice' => $id, 'jenis' => 'invoice', ...$j, ...self::audit()]);
            }
            write_log('Invoice', 'Tambah', 'Tambah '.$number.' dari '.$so->nomor, ['new' => $header, 'detail_changes' => ['Jurnal' => ['old' => [], 'new' => $journals]]], 'invoice', $id, $number);

            return $id;
        });
    }

    public static function audit(): array
    {
        return ['created_by' => Auth::id(), 'created_date' => now()];
    }

    public static function record(int $id, array $input, bool $withholding = false): void
    {
        Sales::transaction(function () use ($id, $input, $withholding) {
            $read = self::row($id);
            Sales::open(Sales::program($read->id_penjualan, true));
            $old = self::row($id, true);
            if ($old->status !== 'invoiced') {
                throw ValidationException::withMessages(['invoice' => 'Invoice tidak aktif.']);
            }
            $changes = [];
            if ($withholding) {
                if ($old->perlakuan_ppn !== 'WAPU' || BigDecimal::of((string) $old->ppn)->isZero()) {
                    throw ValidationException::withMessages(['invoice' => 'Bukti setor hanya untuk Invoice WAPU dengan PPN.']);
                }
                $existing = DB::table('invoice_bukti_setor')->where('id_invoice', $id)->first();
                if ($existing) {
                    self::same($existing->tanggal === $input['tanggal'] && ($existing->nomor ?? '') === ($input['nomor'] ?? ''));

                    return;
                }
                // Gunakan COA jurnal Invoice historis, bukan pemetaan yang mungkin berubah.
                $original = DB::table('invoice_jurnal')->where('id_invoice', $id)->where('jenis', 'invoice')->get();
                $advance = $original->first(fn ($j) => (int) $j->coa !== (int) $old->coa_penjualan && BigDecimal::of((string) $j->debit)->isEqualTo((string) $old->ppn) && $j->urutan === 3);
                $liability = $original->firstWhere('urutan', 4);
                if (! $advance || ! $liability) {
                    throw ValidationException::withMessages(['invoice' => 'Jurnal WAPU historis memerlukan rekonsiliasi.']);
                }
                self::account('uang_muka_wapu', $advance->coa);
                self::account('hutang_wapu', $liability->coa);
                $snapshot = json_decode($old->snapshot, true, 512, JSON_THROW_ON_ERROR);
                $rows = [['hutang_wapu', (string) $old->ppn, '0'], ['uang_muka_wapu', '0', (string) $old->ppn]];
                $journals = self::journals($rows, ['hutang_wapu' => (object) ['id' => $liability->coa, 'no_perkiraan' => $liability->no_coa, 'nama' => $liability->nama_coa], 'uang_muka_wapu' => (object) ['id' => $advance->coa, 'no_perkiraan' => $advance->no_coa, 'nama' => $advance->nama_coa]], $old->nomor, $snapshot['quotation']['nomor'], $input['tanggal'], 'WAPU');
                foreach ($journals as $j) {
                    DB::table('invoice_jurnal')->insert(['id_invoice' => $id, 'jenis' => 'bukti_setor', ...$j, ...self::audit()]);
                }
                DB::table('invoice_bukti_setor')->insert(['id_invoice' => $id, 'tanggal' => $input['tanggal'], 'nomor' => $input['nomor'] ?? null, ...self::audit()]);
                $changes = ['Jurnal bukti setor' => ['old' => [], 'new' => $journals], 'Bukti setor' => ['old' => null, 'new' => $input]];
                $update = [];
            } else {
                if ($old->tanggal_bayar !== null) {
                    self::same($old->tanggal_bayar === $input['tanggal'] && ($old->keterangan_bayar ?? '') === ($input['keterangan'] ?? ''));

                    return;
                }
                $update = ['tanggal_bayar' => $input['tanggal'], 'keterangan_bayar' => $input['keterangan'] ?? null, 'bayar_by' => Auth::id(), 'bayar_date' => now()];
            }
            $update += ['updated_by' => Auth::id(), 'updated_date' => now()];
            DB::table('invoice')->where('id', $id)->update($update);
            write_log('Invoice', $withholding ? 'Bukti Setor WAPU' : 'Catat Lunas', ($withholding ? 'Bukti setor ' : 'Lunas ').$old->nomor,
                ['old' => (array) $old, 'new' => array_merge((array) $old, $update), 'detail_changes' => $changes], 'invoice', $id, $old->nomor);
        });
    }

    private static function same(bool $same): void
    {
        if (! $same) {
            throw ValidationException::withMessages(['invoice' => 'Pencatatan sudah tersimpan dengan data berbeda dan tidak dapat diubah.']);
        }
    }

    public static function calculate(object $quotation, int $count, string $treatment): array
    {
        $q = Quotation::calculate($quotation, $count);
        $sale = BigDecimal::of($q['dpp'])->toScale(0, RoundingMode::HalfUp);
        $other = BigDecimal::of($q['biaya_lain'])->toScale(0, RoundingMode::HalfUp);
        $dpp = $quotation->is_ppn ? $sale->multipliedBy(11)->dividedBy(12, 0, RoundingMode::HalfUp) : BigDecimal::zero();
        $tax = $dpp->multipliedBy(12)->dividedBy(100, 0, RoundingMode::HalfUp);
        $total = $sale->plus($tax)->plus($other);
        if ($sale->isNegative() || $other->isNegative() || $total->isGreaterThan('999999999999999999')) {
            throw ValidationException::withMessages(['invoice' => 'Nilai Invoice di luar batas yang didukung.']);
        }

        return array_map('strval', ['penjualan' => $sale, 'dpp' => $dpp, 'ppn' => $tax, 'biaya_lain' => $other,
            'total_tagihan' => $total, 'piutang' => $treatment === 'PKP' ? $total : $sale->plus($other)]);
    }

    public static function journal(array $h, string $treatment): array
    {
        $revenue = (string) BigDecimal::of($h['penjualan'])->plus($h['biaya_lain']);
        $rows = [['piutang', $h['piutang'], '0'], ['penjualan', '0', $revenue]];
        if ($treatment === 'PKP') {
            $rows[] = ['ppn_dn', '0', $h['ppn']];
        } elseif ($treatment === 'WAPU') {
            $rows[] = ['uang_muka_wapu', $h['ppn'], '0'];
            $rows[] = ['hutang_wapu', '0', $h['ppn']];
        }
        $rows = array_values(array_filter($rows, fn ($r) => $r[1] !== '0' || $r[2] !== '0'));
        self::balance($rows);

        return $rows;
    }

    public static function balance(array $rows): void
    {
        $balance = BigDecimal::zero();
        foreach ($rows as $r) {
            $balance = $balance->plus($r[1])->minus($r[2]);
        }
        if (! $balance->isZero()) {
            throw ValidationException::withMessages(['jurnal' => 'Jurnal Invoice tidak seimbang.']);
        }
    }
}

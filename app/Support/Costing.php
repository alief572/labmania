<?php

namespace App\Support;

use App\Http\Controllers\RateController;
use Illuminate\Support\Facades\DB;

/**
 * Rumus costing Master Program (MP-NFR-04/06). Port persis dari Master_program_model::calculate_costing (CI3);
 * tes membandingkan keduanya. Klien memakai resources/js/composables/costing.ts hanya untuk tampilan.
 */
class Costing
{
    /** @param  array<int, array{tipe:string, harga:mixed, nama_satuan?:string|null}>  $biayas */
    public static function calculate($durasi, $targetPeserta, $hargaJual, array $biayas): array
    {
        $durasi = (int) $durasi ?: 1;
        $target = (int) $targetPeserta ?: 0;
        $harga = (float) $hargaJual ?: 0;

        $totalFixed = 0;
        $varPerPeserta = 0;
        foreach ($biayas as $b) {
            $satuan = $b['nama_satuan'] ?? '';
            $factor = ($satuan === 'Day' || $satuan === 'Peserta/Day') ? $durasi : 1;
            $sub = (float) $b['harga'] * $factor;
            if ($b['tipe'] === 'fixed') {
                $totalFixed += $sub;
            } else {
                $varPerPeserta += $sub;
            }
        }

        $totalVar = $varPerPeserta * $target;
        $totalHpp = $totalFixed + $totalVar;
        $hppPerPax = $target > 0 ? ($totalHpp / $target) : 0;
        $pendapatan = $harga * $target;
        $profit = $pendapatan - $totalHpp;
        $margin = $pendapatan > 0 ? round(($profit / $pendapatan) * 100, 1) : 0;

        $kontribusi = $harga - $varPerPeserta;
        $bep = null;
        if ($kontribusi > 0) {
            $bep = max(1, (int) ceil(round($totalFixed / $kontribusi, 6)));
        }

        return [
            'total_fixed' => $totalFixed, 'var_per_pax' => $varPerPeserta, 'total_var' => $totalVar,
            'total_hpp' => $totalHpp, 'hpp_per_pax' => $hppPerPax, 'pendapatan' => $pendapatan,
            'profit' => $profit, 'margin' => $margin, 'bep_peserta' => $bep,
        ];
    }

    /** Peringatan Finalkan (MP-FR-12); tidak memblokir. */
    public static function warnings(array $calc, $target): array
    {
        $w = [];
        if ($calc['profit'] < 0) {
            $w[] = 'Profit minus.';
        }
        if ($calc['bep_peserta'] === null) {
            $w[] = 'BEP tidak tercapai: harga jual ≤ biaya variable / peserta.';
        } elseif ($calc['bep_peserta'] > (int) $target) {
            $w[] = 'BEP '.$calc['bep_peserta'].' peserta di atas target '.(int) $target.' peserta.';
        }

        return $w;
    }

    /** Baris biaya: satuan sesuai tipe (FR-25), rate tidak ganda dan aktif (kecuali sudah dipakai), nominal ≥ 0, nominal 0 dibuang (MP-FR-07). */
    public static function cleanRows(array $rows, array $oldRates): array
    {
        $satuan = ['fixed' => RateController::satuans('fixed')->pluck('id')->all(), 'variable' => RateController::satuans('variable')->pluck('id')->all()];
        $errors = [];
        $clean = [];
        $dropped = 0;
        $seen = [];
        foreach ($rows as $i => $b) {
            $key = "biaya.$i";
            if (! is_array($b)) {
                continue;
            }
            $tipe = $b['tipe'] ?? '';
            $item = trim((string) ($b['item'] ?? ''));
            $manual = ! empty($b['is_manual']);
            $idRate = ! empty($b['id_rate']) ? (int) $b['id_rate'] : null;
            $label = $item !== '' ? $item : '(biaya lain tanpa nama)';
            if (! in_array($tipe, ['fixed', 'variable'], true)) {
                $errors[$key] = 'Jenis biaya tidak valid.';

                continue;
            }
            if ($manual) {
                if ($item === '') {
                    $errors[$key] = 'Nama biaya lain wajib diisi.';

                    continue;
                }
                $idRate = null;
            } else {
                $rate = $idRate ? DB::table('ms_rate')->where('id', $idRate)->first() : null;
                if (! $rate || $rate->tipe !== $tipe) {
                    $errors[$key] = 'Item Master Rate '.$label.' tidak ditemukan.';

                    continue;
                }
                if (! $rate->aktif && ! in_array($idRate, $oldRates, true)) {
                    $errors[$key] = 'Item '.$label.' sudah nonaktif di Master Rate.';

                    continue;
                }
                if (isset($seen[$idRate])) {
                    $errors[$key] = 'Item '.$label.' sudah ada (tidak boleh dua kali).';

                    continue;
                }
                $seen[$idRate] = true;
                $item = $item !== '' ? $item : $rate->item;
            }
            if (mb_strlen($item) > 150) {
                $errors[$key] = 'Nama biaya maksimal 150 karakter.';

                continue;
            }
            $idSatuan = (int) ($b['id_satuan'] ?? 0);
            if (! in_array($idSatuan, $satuan[$tipe], true)) {
                $errors[$key] = 'Satuan '.$label.' tidak sesuai jenis biaya.';

                continue;
            }
            $nominal = str_replace('.', '', trim((string) ($b['harga'] ?? '')));
            if ($nominal === '' || ! ctype_digit($nominal) || strlen($nominal) > 13) {
                $errors[$key] = 'Nominal '.$label.' wajib diisi (angka rupiah ≥ 0).';

                continue;
            }
            if ((int) $nominal === 0) {
                $dropped++;

                continue;
            }
            $clean[] = ['id_rate' => $idRate, 'tipe' => $tipe, 'item' => $item, 'id_satuan' => $idSatuan, 'harga' => (float) $nominal, 'is_manual' => $manual ? 1 : 0];
        }

        return [$clean, $errors, $dropped];
    }

    /** ['Item [Nama] - Harga|Ditambahkan|Dihapus' => [old, new]] untuk Activity Log (detail_changes). */
    public static function changes(array $old, array $new): array
    {
        $key = fn ($r) => $r['tipe'].'|'.($r['id_rate'] ?: 'm:'.$r['item']).'|'.$r['id_satuan'];
        $o = [];
        foreach ($old as $r) {
            $o[$key($r)] = $r;
        }
        $n = [];
        foreach ($new as $r) {
            $n[$key($r)] = $r;
        }
        $changes = [];
        foreach ($n as $k => $r) {
            if (! isset($o[$k])) {
                $changes['Item ['.$r['item'].'] - Ditambahkan'] = ['old' => null, 'new' => (float) $r['harga']];
            } elseif ((float) $o[$k]['harga'] !== (float) $r['harga']) {
                $changes['Item ['.$r['item'].'] - Harga'] = ['old' => (float) $o[$k]['harga'], 'new' => (float) $r['harga']];
            }
        }
        foreach ($o as $k => $r) {
            if (! isset($n[$k])) {
                $changes['Item ['.$r['item'].'] - Dihapus'] = ['old' => (float) $r['harga'], 'new' => null];
            }
        }

        return $changes;
    }
}

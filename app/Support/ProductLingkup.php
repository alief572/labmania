<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductLingkup
{
    /** Pecah teks (satu item per baris) menjadi daftar item. */
    public static function fromText(?string $text): array
    {
        return preg_split('/\R/u', (string) $text) ?: [];
    }

    /**
     * Simpan Lingkup satu Product (FR-67): baris kosong diabaikan, item kembar (tanpa beda huruf) ditolak,
     * maksimal max_items x max_length. Panggil di dalam transaksi. Kembalian: true bila ada perubahan.
     * Perubahan tercatat sebagai Edit Master Produk, field lingkup (old -> new).
     */
    public static function save(object $paket, array $items, string $errorKey = 'lingkup'): bool
    {
        $max = (int) config('labmania.lingkup.max_items');
        $len = (int) config('labmania.lingkup.max_length');
        $clean = [];
        $seen = [];
        $errors = [];
        foreach (array_values($items) as $i => $value) {
            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }
            if (mb_strlen($text) > $len) {
                $errors[] = 'Baris '.($i + 1)." melebihi $len karakter.";

                continue;
            }
            $key = mb_strtolower($text);
            if (isset($seen[$key])) {
                $errors[] = 'Baris '.($i + 1).' sama dengan item lain.';

                continue;
            }
            $seen[$key] = true;
            $clean[] = $text;
        }
        if (count($clean) > $max) {
            $errors[] = "Maksimal $max item lingkup per Product.";
        }
        if ($errors) {
            throw ValidationException::withMessages([$errorKey => implode(' ', $errors)]);
        }

        $old = DB::table('paket_produk_lingkup')->where('id_paket_produk', $paket->id)->orderBy('urutan')->pluck('lingkup')->all();
        if ($old === $clean) {
            return false;
        }
        DB::table('paket_produk_lingkup')->where('id_paket_produk', $paket->id)->delete();
        $now = now();
        foreach ($clean as $n => $text) {
            DB::table('paket_produk_lingkup')->insert(['id_paket_produk' => $paket->id, 'urutan' => $n + 1, 'lingkup' => $text, 'created_by' => Auth::id(), 'created_date' => $now]);
        }
        $format = fn (array $list) => implode("\n", array_map(fn ($t, $i) => ($i + 1).'. '.$t, $list, array_keys($list)));
        $base = (array) $paket;
        write_log('Master Produk', 'Edit', 'Update Lingkup Product '.$paket->nomor, [
            'old' => array_merge($base, ['lingkup' => $format($old)]),
            'new' => array_merge($base, ['lingkup' => $format($clean)]),
        ], 'paket_produk', $paket->id, $paket->nomor);

        return true;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProductLingkup
{
    /** Pecah teks (satu item per baris) menjadi daftar item. */
    public static function fromText(?string $text): array
    {
        return preg_split('/\R/u', (string) $text) ?: [];
    }

    /** Harga start from: kosong = belum diisi (null); selain itu bilangan bulat rupiah >= 0 (FR-69). */
    public static function hargaMulai(mixed $value): ?string
    {
        $text = trim(str_replace('.', '', (string) $value));
        if ($text === '') {
            return null;
        }
        if (! preg_match('/^\d{1,13}$/', $text)) {
            throw ValidationException::withMessages(['harga_mulai' => 'Harga start from harus berupa angka rupiah tanpa desimal.']);
        }

        return ltrim($text, '0') === '' ? '0' : ltrim($text, '0');
    }

    /**
     * Simpan Harga start from satu Product (FR-69). Perkiraan promosi, bukan harga final: tidak dipakai di Master Program,
     * Penjualan Program, quotation, atau invoice. Perubahan dicatat sebagai Edit Master Produk, field harga_mulai (old -> new).
     * Kembalian: true bila berubah. Panggil di dalam transaksi.
     */
    public static function saveHargaMulai(object $paket, ?string $harga): bool
    {
        if (! Schema::hasColumn('paket_produk', 'harga_mulai')) {
            return false;
        }
        $old = DB::table('paket_produk')->where('id', $paket->id)->value('harga_mulai');
        $old = $old === null ? null : (string) (int) $old;
        if ($old === $harga) {
            return false;
        }
        DB::table('paket_produk')->where('id', $paket->id)->update(['harga_mulai' => $harga, 'updated_by' => Auth::id(), 'updated_date' => now()]);
        $base = (array) $paket;
        write_log('Master Produk', 'Edit', 'Update Harga start from Product '.$paket->nomor, [
            'old' => array_merge($base, ['harga_mulai' => $old]),
            'new' => array_merge($base, ['harga_mulai' => $harga]),
        ], 'paket_produk', $paket->id, $paket->nomor);

        return true;
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

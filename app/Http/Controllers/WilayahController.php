<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Provinsi dan Kota/Kabupaten dari wilayah.id (sumber yang sama dengan CI3), dicache 14 hari.
 * Hanya data wilayah publik yang dikirim; tidak ada data peserta/company. Gagal ambil = daftar kosong (provinsi: enam provinsi utama).
 */
class WilayahController extends Controller
{
    private const FALLBACK = [
        ['code' => '31', 'name' => 'DKI JAKARTA'], ['code' => '32', 'name' => 'JAWA BARAT'], ['code' => '36', 'name' => 'BANTEN'],
        ['code' => '33', 'name' => 'JAWA TENGAH'], ['code' => '34', 'name' => 'DI YOGYAKARTA'], ['code' => '35', 'name' => 'JAWA TIMUR'],
    ];

    public function provinces()
    {
        return response()->json(['status' => 1, 'data' => $this->fetch('wilayah.provinces', 'https://wilayah.id/api/provinces.json') ?? self::FALLBACK]);
    }

    public function regencies(string $code)
    {
        abort_unless(preg_match('/^\d{1,4}$/', $code), 404);

        return response()->json(['status' => 1, 'data' => $this->fetch('wilayah.regencies.'.$code, 'https://wilayah.id/api/regencies/'.$code.'.json') ?? []]);
    }

    private function fetch(string $key, string $url): ?array
    {
        return Cache::remember($key, now()->addDays(14), function () use ($url) {
            try {
                $data = Http::timeout(6)->acceptJson()->get($url)->json('data');
            } catch (\Throwable) {
                return null;
            }

            return is_array($data) && $data ? $data : null;
        });
    }
}

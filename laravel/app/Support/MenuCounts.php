<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Badge jumlah data di sidebar: kode menu => tabel ERD (mockup core.js countOf). Menu tanpa tabel sendiri tidak diberi badge. */
class MenuCounts
{
    private const TABLES = [
        'master_data.jenis_produk' => 'ms_jenis_produk', 'master_data.jenis_sertifikasi' => 'ms_jenis_sertifikasi',
        'master_data.jenis_bidang' => 'ms_jenis_bidang', 'master_data.varian_pelaksanaan' => 'ms_varian_pelaksanaan',
        'master_data.paket_produk' => 'paket_produk', 'master_data.katalog' => 'paket_produk',
        'master_data.peserta' => 'ms_peserta', 'master_data.company' => 'master_customer', 'master_trainer' => 'ms_trainer',
        'master_data.template_keterangan' => 'ms_template_keterangan', 'master_data.template_konfirmasi' => 'ms_template_konfirmasi',
        'master_data.rate' => 'ms_rate', 'master_data.users' => 'ms_user_karyawan',
        'master_data.role' => 'groups', 'permission_group' => 'groups',
        'master_program' => 'master_program', 'penjualan_program' => 'penjualan_program', 'quotation_program' => 'quotation',
        'sales_order' => 'sales_order', 'activity_log' => 'logs',
    ];

    private const SOFT_DELETE = ['ms_peserta', 'master_customer'];

    public static function for(?string $code): ?int
    {
        $table = self::TABLES[$code] ?? null;
        if ($table === null) {
            return null;
        }
        try {
            $query = DB::table($table);
            if (in_array($table, self::SOFT_DELETE, true)) {
                $query->whereNull('deleted_date');
            }

            return $query->count();
        } catch (\Throwable) {
            return null; // tabel belum dimigrasi: badge menu itu kosong, menu lain tetap tampil
        }
    }
}

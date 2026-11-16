<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $groups = [
                'm_master_data' => [
                    1 => ['master_data.jenis_produk' => 'Jenis Produk', 'master_data.jenis_sertifikasi' => 'Jenis Sertifikat', 'master_data.jenis_bidang' => 'Jenis Bidang', 'master_data.varian_pelaksanaan' => 'Varian Pelaksanaan', 'master_data.paket_produk' => 'Product', 'master_data.katalog' => 'Katalog Produk', 'master_data.rate' => 'Master Rate'],
                    2 => ['master_data.users' => 'Master User', 'master_data.role' => 'Master Role', 'permission_group' => 'Role & Permission'],
                    3 => ['master_data.peserta' => 'Master Peserta', 'master_data.company' => 'Master Company', 'master_trainer' => 'Master Trainer'],
                    4 => ['master_data.template_keterangan' => 'Template Keterangan Quotation', 'master_data.template_konfirmasi' => 'Template Formulir Konfirmasi'],
                ],
                'm_penjualan' => [1 => ['master_program' => 'Daftar Master Program'], 2 => ['penjualan_program' => 'Daftar Penjualan Program']],
                'm_quotation' => [1 => ['quotation_program' => 'Daftar Quotation'], 2 => ['sales_order' => 'Sales Order', 'sales_order_refund' => 'Refund']],
                'm_pengaturan' => [1 => ['menu_labmania' => 'Menu Sidebar']],
            ];
            $standalone = ['invoicing' => ['Invoicing', 50], 'manage_peserta' => ['Manage Peserta', 60], 'monitoring_peserta' => ['Monitoring Peserta', 61], 'pengajuan_biaya' => ['Pengajuan Biaya', 70], 'approval_pengajuan_biaya' => ['Approval Pengajuan', 71], 'activity_log' => ['Activity Log', 80]];
            $codes = array_merge(array_keys($groups), array_keys($standalone));
            foreach ($groups as $sections) {
                foreach ($sections as $members) {
                    $codes = array_merge($codes, array_keys($members));
                }
            }
            $rows = DB::table('ms_menu')->whereIn('kode', $codes)->lockForUpdate()->get()->groupBy('kode');
            foreach ($codes as $code) {
                if (($rows[$code] ?? collect())->count() !== 1) {
                    throw new RuntimeException("Sidebar: kode wajib hilang atau duplikat: $code. Lengkapi menu melalui administrasi sebelum migrasi.");
                }
            }
            foreach (array_keys($groups) as $code) {
                $parent = $rows[$code]->first();
                if ($parent->link !== '#' || (int) $parent->parent_id !== 0 || (int) $parent->aktif !== 1) {
                    throw new RuntimeException("Sidebar: induk $code harus aktif, tingkat atas, dan link #.");
                }
            }
            foreach ($groups as $parent => $sections) {
                foreach ($sections as $section => $members) {
                    $position = 0;
                    foreach ($members as $code => $title) {
                        DB::table('ms_menu')->where('id', $rows[$code]->first()->id)->update(['judul' => $title, 'parent_id' => $rows[$parent]->first()->id, 'kelompok' => $section, 'urutan' => ++$position * 10]);
                    }
                }
            }
            foreach ($standalone as $code => [$title, $position]) {
                DB::table('ms_menu')->where('id', $rows[$code]->first()->id)->update(['judul' => $title, 'parent_id' => 0, 'kelompok' => 1, 'urutan' => $position]);
            }
            DB::table('ms_menu')->where('id', $rows['m_pengaturan']->first()->id)->update(['urutan' => 90]);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Rollback sidebar memerlukan snapshot ms_menu sebelum migrasi; susunan sebelumnya berbeda tiap instalasi.');
    }
};

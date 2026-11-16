<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('lm_paket_derived')) {
    /**
     * Master Program dapat memuat lebih dari satu Product (master_program_produk, MP-FR-02 to be).
     * Fungsi ini mengembalikan tabel turunan berbentuk satu baris per Master Program, supaya query lama yang memakai
     * `pp.judul`, `pp.nomor`, `pp.id`, `pp.id_jenis_*` tetap jalan:
     *   - judul dan nomor = semua Product digabung " + " menurut urutan;
     *   - id dan atribut (id_jenis_produk, id_jenis_sertifikasi, id_jenis_bidang, id_varian_pelaksanaan, aktif) = Product utama (urutan 1).
     * Pemakaian: ->join(lm_paket_derived(), 'pp.id_master_program = mp.id', 'left', false)   (escape false: berisi subquery)
     * shortcut: GROUP_CONCAT dibatasi group_concat_max_len (default 1024 karakter); cukup untuk beberapa judul. Naikkan bila Product per program banyak.
     */
    function lm_paket_derived($alias = 'pp')
    {
        // Satu agregasi, satu kali membaca master_program_produk. Atribut Product utama diambil dengan MAX(IF(urutan = 1, ...)):
        // urutan unik per program (uk_mpp_program_urutan), jadi tepat satu baris yang cocok.
        $utama = function ($kolom) { return "MAX(IF(m.urutan = 1, p.$kolom, NULL)) AS $kolom"; };
        return "(SELECT m.id_master_program, "
            . $utama('id') . ", " . $utama('id_jenis_produk') . ", " . $utama('id_jenis_sertifikasi') . ", "
            . $utama('id_jenis_bidang') . ", " . $utama('id_varian_pelaksanaan') . ", " . $utama('aktif') . ", "
            . "GROUP_CONCAT(p.judul ORDER BY m.urutan SEPARATOR ' + ') AS judul, "
            . "GROUP_CONCAT(p.nomor ORDER BY m.urutan SEPARATOR ' + ') AS nomor "
            . "FROM master_program_produk m JOIN paket_produk p ON p.id = m.id_paket_produk "
            . "GROUP BY m.id_master_program) $alias";
    }
}

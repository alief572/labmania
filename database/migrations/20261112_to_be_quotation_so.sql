-- Migration: TO BE Quotation, Sales Order, Surat Kerja Trainer, Reschedule (Labmania)
-- Tanggal: 12-11-2026
-- Acuan: specs/docs-to-be/diagrams/erd.drawio (halaman ERD Quotation, ERD Penjualan Program, ERD Master Data).
-- JALANKAN SEKALI di DB uji dulu, lalu DB live. ALTER di bawah tidak idempotent (MySQL tanpa ADD COLUMN IF NOT EXISTS);
-- CREATE TABLE memakai IF NOT EXISTS. Backup DB sebelum menjalankan: quotation_peserta berubah bentuk.

-- 1. ms_peserta.nik (QTN-FR-33: kunci pencocokan peserta Deal)
-- Field wajib dilonggarkan ke NULL (ERD to be): peserta Deal dari link hanya punya Nama, sisanya dilengkapi admin
-- dan ditandai "Belum lengkap" (FR-15, QTN-FR-33). Jika kolom `nik` sudah ada di DB (dipakai Master_data_model.php),
-- hapus baris ADD COLUMN sebelum menjalankan.
ALTER TABLE `ms_peserta`
  MODIFY COLUMN `gender` ENUM('L','P') NULL,
  MODIFY COLUMN `instansi` VARCHAR(150) NULL,
  MODIFY COLUMN `id_jenis_bidang` INT NULL,
  MODIFY COLUMN `no_telp` VARCHAR(20) NULL,
  MODIFY COLUMN `email` VARCHAR(100) NULL,
  ADD COLUMN `nik` VARCHAR(20) NULL AFTER `email`;

-- 2. penjualan_program: Jam Pembelajaran (PJ-FR-29)
-- id_trainer wajib (PJ-FR-23) ditegakkan di aplikasi dulu; ALTER ... NOT NULL menyusul setelah data lama terisi trainer:
--   SELECT id, nomor FROM penjualan_program WHERE id_trainer IS NULL;
ALTER TABLE `penjualan_program`
  ADD COLUMN `jam_pembelajaran` INT NULL AFTER `durasi_hari`;

-- 3. quotation: kuota, link konfirmasi, B2B all-in 25 peserta + tambahan per peserta
ALTER TABLE `quotation`
  ADD COLUMN `kepada_nama` VARCHAR(100) NULL AFTER `up_nama`,
  ADD COLUMN `kuota_peserta` INT NOT NULL DEFAULT 1 AFTER `alamat`,
  ADD COLUMN `link_token` VARCHAR(40) NULL AFTER `konfirmasi_pernyataan`,
  ADD COLUMN `link_dibuat` DATETIME NULL AFTER `link_token`,
  ADD COLUMN `dokumen_keterangan` VARCHAR(200) NULL,
  ADD COLUMN `paket_maks_peserta` INT NULL,
  ADD COLUMN `tambahan_per_peserta` DECIMAL(15,2) NULL,
  ADD COLUMN `kolom_peserta_b2b` JSON NULL;

-- Data lama: kuota = jumlah peserta terdaftar (B2B minimal 10), token acak untuk baris lama
-- (baris baru: aplikasi memakai bin2hex(random_bytes(16))).
UPDATE `quotation` q
LEFT JOIN `penjualan_program` pj ON pj.`id` = q.`id_penjualan`
SET q.`kuota_peserta` = GREATEST(
      (SELECT COUNT(*) FROM `quotation_peserta` qp WHERE qp.`id_quotation` = q.`id`),
      IF(pj.`jenis` = 'b2b', 10, 1)),
    q.`link_token` = LEFT(SHA2(CONCAT(UUID(), RAND(), q.`id`), 256), 32),
    q.`link_dibuat` = COALESCE(q.`created_date`, NOW());

ALTER TABLE `quotation`
  MODIFY COLUMN `link_token` VARCHAR(40) NOT NULL,
  MODIFY COLUMN `link_dibuat` DATETIME NOT NULL,
  ADD UNIQUE KEY `uk_quotation_link_token` (`link_token`);

-- 4. quotation_peserta: dari tabel penghubung ke ms_peserta menjadi data isian link konfirmasi
ALTER TABLE `quotation_peserta`
  DROP INDEX `uk_quotation_peserta`,
  MODIFY COLUMN `id_peserta` INT NULL,
  ADD COLUMN `sumber` ENUM('link','manual','company') NOT NULL DEFAULT 'link' AFTER `id_quotation`,
  ADD COLUMN `tgl_isi` DATETIME NULL AFTER `sumber`,
  ADD COLUMN `input_by` INT NULL AFTER `tgl_isi`,
  ADD COLUMN `nama` VARCHAR(150) NULL AFTER `input_by`,
  ADD COLUMN `nik` VARCHAR(20) NULL AFTER `nama`,
  ADD COLUMN `jabatan` VARCHAR(100) NULL AFTER `nik`,
  ADD COLUMN `instansi` VARCHAR(150) NULL AFTER `jabatan`,
  ADD COLUMN `alamat_instansi` TEXT NULL AFTER `instansi`,
  ADD COLUMN `no_hp` VARCHAR(20) NULL AFTER `alamat_instansi`,
  ADD COLUMN `no_wa` VARCHAR(20) NULL AFTER `no_hp`,
  ADD COLUMN `email` VARCHAR(100) NULL AFTER `no_wa`,
  ADD COLUMN `pengalaman` VARCHAR(100) NULL AFTER `email`,
  ADD COLUMN `lsp` VARCHAR(100) NULL AFTER `pengalaman`,
  ADD COLUMN `tgl_habis_sertifikat` DATE NULL AFTER `lsp`,
  ADD COLUMN `isian_tambahan` TEXT NULL AFTER `tgl_habis_sertifikat`,
  ADD COLUMN `surat_konfirmasi` VARCHAR(255) NULL AFTER `status`,
  ADD COLUMN `surat_konfirmasi_nama` VARCHAR(150) NULL AFTER `surat_konfirmasi`,
  ADD KEY `idx_qp_quotation` (`id_quotation`),
  ADD KEY `idx_qp_peserta` (`id_peserta`);

-- Data lama: salin identitas dari ms_peserta supaya baris lama tetap terbaca.
UPDATE `quotation_peserta` qp
JOIN `ms_peserta` p ON p.`id` = qp.`id_peserta`
SET qp.`nama` = p.`nama`, qp.`instansi` = p.`instansi`, qp.`no_hp` = p.`no_telp`, qp.`email` = p.`email`, qp.`nik` = p.`nik`,
    qp.`tgl_isi` = NOW();

ALTER TABLE `quotation_peserta`
  MODIFY COLUMN `nama` VARCHAR(150) NOT NULL,
  MODIFY COLUMN `tgl_isi` DATETIME NOT NULL;

-- 5. quotation_dokumen: dokumen customer wajib saat Deal (PO, SPK, dll.)
CREATE TABLE IF NOT EXISTS `quotation_dokumen` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_quotation` INT NOT NULL,
  `nama_file` VARCHAR(255) NOT NULL,
  `path_file` VARCHAR(255) NOT NULL,
  `tipe_file` ENUM('pdf','jpg','png','doc','docx','xls','xlsx') NOT NULL,
  `ukuran_byte` INT NOT NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  KEY `idx_qd_quotation` (`id_quotation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. quotation_konfirmasi_company: konfirmasi B2B atas nama company, satu baris per quotation
CREATE TABLE IF NOT EXISTS `quotation_konfirmasi_company` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_quotation` INT NOT NULL,
  `nama_perusahaan` VARCHAR(150) NOT NULL,
  `alamat_perusahaan` TEXT NOT NULL,
  `pic_nama` VARCHAR(100) NOT NULL,
  `pic_jabatan` VARCHAR(100) NOT NULL,
  `pic_hp` VARCHAR(20) NOT NULL,
  `pic_email` VARCHAR(100) NOT NULL,
  `jumlah_peserta` INT NOT NULL,
  `surat_konfirmasi` VARCHAR(255) NOT NULL,
  `surat_konfirmasi_nama` VARCHAR(255) NOT NULL,
  `tgl_kirim` DATETIME NOT NULL,
  UNIQUE KEY `uk_qkc_quotation` (`id_quotation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. sales_order: dibentuk otomatis saat quotation Deal, 1 quotation = 1 SO
CREATE TABLE IF NOT EXISTS `sales_order` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor` VARCHAR(30) NOT NULL,
  `id_quotation` INT NOT NULL,
  `id_penjualan` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `status` ENUM('open','siap') NOT NULL DEFAULT 'open',
  `siap_by` INT NULL,
  `siap_date` DATETIME NULL,
  `revisi` INT NOT NULL DEFAULT 0,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  UNIQUE KEY `uk_so_nomor` (`nomor`),
  UNIQUE KEY `uk_so_quotation` (`id_quotation`),
  KEY `idx_so_penjualan` (`id_penjualan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. surat_kerja_trainer: satu per Penjualan Program, dibuat otomatis di SO pertama
CREATE TABLE IF NOT EXISTS `surat_kerja_trainer` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor` VARCHAR(30) NOT NULL,
  `id_penjualan` INT NOT NULL,
  `id_trainer` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  UNIQUE KEY `uk_skt_nomor` (`nomor`),
  UNIQUE KEY `uk_skt_penjualan` (`id_penjualan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Reschedule Penjualan Program (tanggal + trainer, alasan dan evidence wajib)
CREATE TABLE IF NOT EXISTS `penjualan_program_reschedule` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_penjualan` INT NOT NULL,
  `tgl_mulai_lama` DATE NOT NULL,
  `tgl_selesai_lama` DATE NOT NULL,
  `tgl_mulai_baru` DATE NOT NULL,
  `tgl_selesai_baru` DATE NOT NULL,
  `id_trainer_lama` INT NOT NULL,
  `id_trainer_baru` INT NOT NULL,
  `alasan` VARCHAR(500) NOT NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  KEY `idx_ppr_penjualan` (`id_penjualan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `penjualan_program_reschedule_evidence` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_reschedule` INT NOT NULL,
  `nama_file` VARCHAR(255) NOT NULL,
  `path_file` VARCHAR(255) NOT NULL,
  `tipe_file` ENUM('jpg','png','doc','docx','pdf') NOT NULL,
  `ukuran_byte` INT NOT NULL,
  KEY `idx_ppre_reschedule` (`id_reschedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

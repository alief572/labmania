-- Migration: Master Data, Master Program, Penjualan Program, Quotation & Activity Log
-- Tanggal: 31-10-2026
-- Acuan: specs/final/ERD Master - Quotation.drawio

-- 1. Atribut Produk
CREATE TABLE IF NOT EXISTS `ms_jenis_produk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ms_jenis_sertifikasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ms_jenis_bidang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Paket Produk (Product Combine)
CREATE TABLE IF NOT EXISTS `paket_produk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor` VARCHAR(20) NOT NULL UNIQUE,
  `judul` VARCHAR(150) NOT NULL,
  `id_jenis_produk` INT NOT NULL,
  `id_jenis_sertifikasi` INT NOT NULL,
  `id_jenis_bidang` INT NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL,
  UNIQUE KEY `uk_paket_kombinasi` (`id_jenis_produk`, `id_jenis_sertifikasi`, `id_jenis_bidang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Master Peserta
CREATE TABLE IF NOT EXISTS `ms_peserta` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `gender` ENUM('L','P') NOT NULL DEFAULT 'L',
  `instansi` VARCHAR(150) NOT NULL,
  `id_jenis_bidang` INT NOT NULL,
  `id_prov` VARCHAR(10) NULL,
  `provinsi` VARCHAR(100) NULL,
  `id_kab` VARCHAR(10) NULL,
  `kabupaten` VARCHAR(100) NULL,
  `no_telp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `npwp_instansi` VARCHAR(30) NULL,
  `id_customer` VARCHAR(20) NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL,
  `deleted_by` INT NULL,
  `deleted_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Kolom jenis_instansi di master_customer
ALTER TABLE `master_customer` 
  ADD COLUMN `jenis_instansi` VARCHAR(50) DEFAULT 'Swasta' AFTER `name_customer`;

-- 5. Master Rate (Biaya)
CREATE TABLE IF NOT EXISTS `ms_rate` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `tipe` ENUM('fixed','variable') NOT NULL,
  `item` VARCHAR(150) NOT NULL,
  `id_satuan` INT NOT NULL,
  `default_cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` VARCHAR(150) NULL,
  `coa` VARCHAR(20) NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Master Dokumen (Template Keterangan Quotation & Template Formulir Konfirmasi)
CREATE TABLE IF NOT EXISTS `ms_template_keterangan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `kategori` VARCHAR(20) NOT NULL,
  `teks` TEXT NOT NULL,
  `berlaku_untuk` VARCHAR(10) NOT NULL DEFAULT 'semua',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ms_template_konfirmasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(80) NOT NULL,
  `label_kegiatan` VARCHAR(80) NOT NULL,
  `id_jenis_produk` INT NULL,
  `isian` TEXT NULL,
  `pernyataan` TEXT NULL,
  `catatan` TEXT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Master Trainer
CREATE TABLE IF NOT EXISTS `ms_trainer` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(150) NOT NULL,
  `tempat_lahir` VARCHAR(100) NULL,
  `tgl_lahir` DATE NULL,
  `alamat` TEXT NULL,
  `no_telp` VARCHAR(50) NULL,
  `pend_smu` VARCHAR(255) NULL,
  `pend_diploma` VARCHAR(255) NULL,
  `pend_sarjana` VARCHAR(255) NULL,
  `pend_pascasarjana` VARCHAR(255) NULL,
  `bidang_keahlian` TEXT NULL,
  `pengalaman_lain` TEXT NULL,
  `cv_tempat` VARCHAR(100) NULL,
  `cv_tanggal` DATE NULL,
  `nama_ttd` VARCHAR(150) NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ms_trainer_pelatihan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_trainer` INT NOT NULL,
  `urutan` INT NOT NULL DEFAULT 1,
  `judul` VARCHAR(255) NOT NULL,
  `penyelenggara` VARCHAR(150) NULL,
  `waktu` VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ms_trainer_pengalaman_kerja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_trainer` INT NOT NULL,
  `urutan` INT NOT NULL DEFAULT 1,
  `jabatan` VARCHAR(150) NOT NULL,
  `institusi` VARCHAR(150) NULL,
  `tahun` VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ms_trainer_pengalaman_mengajar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_trainer` INT NOT NULL,
  `urutan` INT NOT NULL DEFAULT 1,
  `topik` VARCHAR(255) NOT NULL,
  `tempat` VARCHAR(150) NULL,
  `waktu` VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ms_trainer_lampiran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_trainer` INT NOT NULL,
  `dokumen` VARCHAR(100) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `created_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Master Program (MP) & Biaya
CREATE TABLE IF NOT EXISTS `master_program` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor` VARCHAR(20) NOT NULL UNIQUE,
  `id_paket_produk` INT NOT NULL,
  `durasi_hari` INT NOT NULL DEFAULT 1,
  `target_peserta` INT NOT NULL DEFAULT 10,
  `harga_jual_peserta` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `target_profit` DECIMAL(15,2) NULL DEFAULT 0.00,
  `status` ENUM('draft','final','batal') NOT NULL DEFAULT 'draft',
  `confirmed_by` INT NULL,
  `confirmed_date` DATETIME NULL,
  `closed_by` INT NULL,
  `closed_date` DATETIME NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `master_program_biaya` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_master_program` INT NOT NULL,
  `id_rate` INT NULL,
  `tipe` ENUM('fixed','variable') NOT NULL,
  `item` VARCHAR(150) NOT NULL,
  `id_satuan` INT NULL,
  `harga` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `is_manual` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Penjualan Program (PJ) & Biaya
CREATE TABLE IF NOT EXISTS `penjualan_program` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor` VARCHAR(20) NOT NULL UNIQUE,
  `id_master_program` INT NOT NULL,
  `jenis` ENUM('public','b2b') NOT NULL,
  `id_customer` VARCHAR(20) NULL,
  `id_trainer` INT NULL,
  `tgl_mulai` DATE NULL,
  `tgl_selesai` DATE NULL,
  `waktu` VARCHAR(50) NULL,
  `lokasi` VARCHAR(150) NULL,
  `durasi_hari` INT NOT NULL DEFAULT 1,
  `target_peserta` INT NOT NULL DEFAULT 10,
  `harga_jual_peserta` DECIMAL(15,2) NULL DEFAULT 0.00,
  `target_profit` DECIMAL(15,2) NULL DEFAULT 0.00,
  `status` ENUM('draft','final','batal') NOT NULL DEFAULT 'draft',
  `confirmed_by` INT NULL,
  `confirmed_date` DATETIME NULL,
  `closed_by` INT NULL,
  `closed_date` DATETIME NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `penjualan_program_biaya` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_penjualan` INT NOT NULL,
  `id_rate` INT NULL,
  `tipe` ENUM('fixed','variable') NOT NULL,
  `item` VARCHAR(150) NOT NULL,
  `id_satuan` INT NULL,
  `harga` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `is_manual` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Quotation & Detail
CREATE TABLE IF NOT EXISTS `quotation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor` VARCHAR(30) NOT NULL UNIQUE,
  `id_penjualan` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `berlaku_sampai` DATE NULL,
  `id_sales` INT NULL,
  `sumber_info` VARCHAR(30) NULL,
  `tujuan` ENUM('personal','company') NOT NULL DEFAULT 'personal',
  `id_customer` VARCHAR(20) NULL,
  `up_nama` VARCHAR(100) NULL,
  `alamat` TEXT NULL,
  `skema_harga` ENUM('peserta','allin') NOT NULL DEFAULT 'peserta',
  `harga_peserta` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `harga_allin` DECIMAL(15,2) NULL DEFAULT 0.00,
  `diskon` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `is_ppn` TINYINT(1) NOT NULL DEFAULT 1,
  `ppn_persen` DECIMAL(5,2) NOT NULL DEFAULT 11.00,
  `biaya_lain` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `id_template_konfirmasi` INT NULL,
  `konfirmasi_isian` TEXT NULL,
  `konfirmasi_pernyataan` TEXT NULL,
  `status` ENUM('draft','terkirim','deal','batal') NOT NULL DEFAULT 'draft',
  `sent_by` INT NULL,
  `sent_date` DATETIME NULL,
  `deal_by` INT NULL,
  `deal_date` DATETIME NULL,
  `closed_by` INT NULL,
  `closed_date` DATETIME NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quotation_peserta` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_quotation` INT NOT NULL,
  `id_peserta` INT NOT NULL,
  `status` ENUM('menunggu','deal','batal') NOT NULL DEFAULT 'menunggu',
  UNIQUE KEY `uk_quotation_peserta` (`id_quotation`, `id_peserta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quotation_keterangan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_quotation` INT NOT NULL,
  `id_template_keterangan` INT NULL,
  `kategori` VARCHAR(20) NOT NULL,
  `teks` TEXT NOT NULL,
  `urutan` INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Activity Log
CREATE TABLE IF NOT EXISTS `logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `module` VARCHAR(50) NOT NULL,
  `sub_module` VARCHAR(80) NULL,
  `action` VARCHAR(30) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `ref_table` VARCHAR(50) NULL,
  `ref_id` INT NULL,
  `ref_no` VARCHAR(50) NULL,
  `data` JSON NULL,
  `created_by` INT NOT NULL,
  `created_date` DATETIME NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  INDEX `idx_module_action` (`module`, `action`),
  INDEX `idx_ref` (`ref_table`, `ref_id`),
  INDEX `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

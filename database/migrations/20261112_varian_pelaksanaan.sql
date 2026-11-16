-- Varian Pelaksanaan kembali (TB-1, FR-01, FR-07, FR-22) + menu Product Combine -> Product.
-- Acuan: specs/docs-to-be/diagrams/erd.drawio (ms_varian_pelaksanaan, paket_produk.id_varian_pelaksanaan).
-- Jalankan SEKALI. ALTER dan UPDATE urutan menu tidak idempotent.

CREATE TABLE IF NOT EXISTS `ms_varian_pelaksanaan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL,
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data awal FR-26 (sheet Data Mentah Struktur Product).
INSERT IGNORE INTO `ms_varian_pelaksanaan` (`kode`, `nama`, `aktif`, `created_date`) VALUES
  ('VP-001', 'In House Training', 1, NOW()),
  ('VP-002', 'Workshop', 1, NOW()),
  ('VP-003', 'Workshop Berbasis Kompetensi', 1, NOW()),
  ('VP-004', 'Workshop Lisensi BSN', 1, NOW());

-- ERD: id_varian_pelaksanaan INT. Dibiarkan NULL untuk paket lama (PC-001..PC-005); admin mengisi lewat Edit.
-- Form Tambah dan Edit mewajibkan Varian (aplikasi). Unique MySQL mengizinkan banyak NULL, jadi paket lama tidak bentrok.
ALTER TABLE `paket_produk`
  ADD COLUMN `id_varian_pelaksanaan` INT NULL AFTER `id_jenis_bidang`,
  DROP INDEX `uk_paket_kombinasi`,
  ADD UNIQUE KEY `uk_paket_kombinasi` (`id_jenis_produk`, `id_jenis_sertifikasi`, `id_jenis_bidang`, `id_varian_pelaksanaan`);

-- Menu: Varian Pelaksanaan setelah Jenis Bidang; Product Combine menjadi Product.
UPDATE `ms_menu` SET `urutan` = `urutan` + 1 WHERE `parent_id` = 2 AND `urutan` >= 4;

INSERT IGNORE INTO `ms_menu` (`parent_id`, `kode`, `judul`, `link`, `ikon`, `kelompok`, `urutan`, `aktif`, `sistem`, `created_date`)
VALUES (2, 'master_data.varian_pelaksanaan', 'Varian Pelaksanaan', 'master_data/varian_pelaksanaan', NULL, 1, 4, 1, 0, NOW());

UPDATE `ms_menu` SET `judul` = 'Product' WHERE `kode` = 'master_data.paket_produk';

INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`)
SELECT m.`id`, a.`aksi` FROM `ms_menu` m
JOIN (SELECT 'lihat' AS aksi UNION ALL SELECT 'tambah' UNION ALL SELECT 'ubah' UNION ALL SELECT 'aktif') a
WHERE m.`kode` = 'master_data.varian_pelaksanaan';

-- Izin awal: salin dari Jenis Bidang supaya group yang sudah boleh mengelola atribut produk ikut boleh mengelola Varian.
INSERT IGNORE INTO `ms_group_akses` (`id_group`, `id_menu`, `aksi`)
SELECT ga.`id_group`, v.`id`, ga.`aksi`
FROM `ms_group_akses` ga
JOIN `ms_menu` b ON b.`id` = ga.`id_menu` AND b.`kode` = 'master_data.jenis_bidang'
JOIN `ms_menu` v ON v.`kode` = 'master_data.varian_pelaksanaan';

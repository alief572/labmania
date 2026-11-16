-- Master Program memilih satu atau lebih Product (MP-FR-02, to be 09-11-2026).
-- Acuan: specs/docs-to-be/diagrams/erd.drawio (master_program_produk menggantikan master_program.id_paket_produk).
-- Jalankan SEKALI, dan HANYA bersama kode aplikasi versi baru: kode lama membaca master_program.id_paket_produk.
-- Rollback: JSON backup master_program dari skrip jalan, atau ADD COLUMN id_paket_produk lalu isi dari urutan = 1.

CREATE TABLE IF NOT EXISTS `master_program_produk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_master_program` INT NOT NULL,
  `id_paket_produk` INT NOT NULL,
  `urutan` INT NOT NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  UNIQUE KEY `uk_mpp_program_paket` (`id_master_program`, `id_paket_produk`),
  UNIQUE KEY `uk_mpp_program_urutan` (`id_master_program`, `urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data lama: satu Product per program menjadi Product utama (urutan 1).
INSERT IGNORE INTO `master_program_produk` (`id_master_program`, `id_paket_produk`, `urutan`, `created_by`, `created_date`)
SELECT `id`, `id_paket_produk`, 1, `created_by`, COALESCE(`created_date`, NOW()) FROM `master_program`;

ALTER TABLE `master_program` DROP COLUMN `id_paket_produk`;

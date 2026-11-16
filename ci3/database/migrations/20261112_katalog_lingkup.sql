-- Lingkup per Paket Produk (FR-67) + aksi Katalog: ubah (isi Lingkup) dan cetak (PDF katalog, FR-62).
-- Acuan: specs/docs-to-be/diagrams/erd.drawio (paket_produk_lingkup).
-- Jalankan SEKALI.

CREATE TABLE IF NOT EXISTS `paket_produk_lingkup` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_paket_produk` INT NOT NULL,
  `urutan` INT NOT NULL,
  `lingkup` VARCHAR(200) NOT NULL,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  KEY `idx_ppl_paket` (`id_paket_produk`, `urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aksi baru di menu Katalog Produk.
INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`)
SELECT m.`id`, a.`aksi` FROM `ms_menu` m
JOIN (SELECT 'ubah' AS aksi UNION ALL SELECT 'cetak') a
WHERE m.`kode` = 'master_data.katalog';

-- Izin awal: group yang sudah boleh mengubah Product ikut boleh mengisi Lingkup dan mencetak Katalog (Q-06, Q-38d belum terjawab).
-- Group lain tetap hanya Lihat; atur lewat Master Role.
INSERT IGNORE INTO `ms_group_akses` (`id_group`, `id_menu`, `aksi`)
SELECT ga.`id_group`, k.`id`, a.`aksi`
FROM `ms_group_akses` ga
JOIN `ms_menu` p ON p.`id` = ga.`id_menu` AND p.`kode` = 'master_data.paket_produk' AND ga.`aksi` = 'ubah'
JOIN `ms_menu` k ON k.`kode` = 'master_data.katalog'
JOIN (SELECT 'ubah' AS aksi UNION ALL SELECT 'cetak') a;

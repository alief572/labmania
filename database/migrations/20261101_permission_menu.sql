-- Migration: Permission Group + Menu Sidebar (Labmania)
-- Tanggal: 01-11-2026
-- Tabel baru khusus Labmania; tabel ERP lama (permissions, menus, group_permissions, user_permissions) tidak dipakai.
-- `groups` + `user_groups` tetap dipakai sebagai group user. Group id 1 (`admin`) lolos semua (implisit, tanpa baris akses).

-- 1. groups: nama lebih panjang + kolom jejak
ALTER TABLE `groups`
  MODIFY COLUMN `nm_group` VARCHAR(50) NOT NULL,
  ADD COLUMN `created_by` INT NULL,
  ADD COLUMN `created_date` DATETIME NULL,
  ADD COLUMN `updated_by` INT NULL,
  ADD COLUMN `updated_date` DATETIME NULL;

-- 2. Menu sidebar (2 tingkat: modul -> menu). Modul = baris dengan parent_id 0 dan link '#'.
CREATE TABLE IF NOT EXISTS `ms_menu` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id` INT NOT NULL DEFAULT 0,
  `kode` VARCHAR(60) NOT NULL UNIQUE,
  `judul` VARCHAR(100) NOT NULL,
  `link` VARCHAR(150) NOT NULL DEFAULT '#',
  `ikon` VARCHAR(30) NULL,
  `kelompok` INT NOT NULL DEFAULT 1,
  `urutan` INT NOT NULL DEFAULT 0,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `sistem` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL,
  KEY `idx_menu_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Aksi yang berlaku per menu: lihat, tambah, ubah, aktif (ubah status), hapus, cetak
CREATE TABLE IF NOT EXISTS `ms_menu_aksi` (
  `id_menu` INT NOT NULL,
  `aksi` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id_menu`, `aksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Izin per group
CREATE TABLE IF NOT EXISTS `ms_group_akses` (
  `id_group` INT NOT NULL,
  `id_menu` INT NOT NULL,
  `aksi` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id_group`, `id_menu`, `aksi`),
  KEY `idx_akses_menu` (`id_menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Seed menu (isi sidebar.php sebelumnya)
INSERT IGNORE INTO `ms_menu` (`id`, `parent_id`, `kode`, `judul`, `link`, `ikon`, `kelompok`, `urutan`, `aktif`, `sistem`, `created_date`) VALUES
  (1, 0, 'dashboard',            'Dashboard',         'dashboard_labmania', 'home',     1, 1, 1, 1, NOW()),
  (2, 0, 'm_master_data',        'Master Data',       '#',                  'database', 1, 2, 1, 0, NOW()),
  (3, 0, 'm_penjualan',          'Penjualan Program', '#',                  'document', 1, 3, 1, 0, NOW()),
  (4, 0, 'm_quotation',          'Quotation',         '#',                  'chat',     1, 4, 1, 0, NOW()),
  (5, 0, 'm_activity_log',       'Activity Log',      '#',                  'clock',    1, 5, 1, 0, NOW()),
  (6, 0, 'm_pengaturan',         'Pengaturan',        '#',                  'settings', 1, 6, 1, 0, NOW()),
  (11, 2, 'master_data.jenis_produk',        'Jenis Produk',                  'master_data/jenis_produk',        NULL, 1, 1,  1, 0, NOW()),
  (12, 2, 'master_data.jenis_sertifikasi',   'Jenis Sertifikat',              'master_data/jenis_sertifikasi',   NULL, 1, 2,  1, 0, NOW()),
  (13, 2, 'master_data.jenis_bidang',        'Jenis Bidang',                  'master_data/jenis_bidang',        NULL, 1, 3,  1, 0, NOW()),
  (14, 2, 'master_data.paket_produk',        'Product Combine',               'master_data/paket_produk',        NULL, 2, 4,  1, 0, NOW()),
  (15, 2, 'master_data.katalog',             'Katalog Produk',                'master_data/katalog',             NULL, 2, 5,  1, 0, NOW()),
  (16, 2, 'master_data.peserta',             'Master Peserta',                'master_data/peserta',             NULL, 3, 6,  1, 0, NOW()),
  (17, 2, 'master_data.company',             'Master Company',                'master_data/company',             NULL, 3, 7,  1, 0, NOW()),
  (18, 2, 'master_trainer',                  'Master Trainer',                'master_trainer',                  NULL, 4, 8,  1, 0, NOW()),
  (19, 2, 'master_data.template_keterangan', 'Template Keterangan Quotation', 'master_data/template_keterangan', NULL, 5, 9,  1, 0, NOW()),
  (20, 2, 'master_data.template_konfirmasi', 'Template Formulir Konfirmasi',  'master_data/template_konfirmasi', NULL, 5, 10, 1, 0, NOW()),
  (21, 2, 'master_data.rate',                'Master Rate',                   'master_data/rate',                NULL, 6, 11, 1, 0, NOW()),
  (22, 2, 'master_data.users',               'Master User',                   'master_data/users',               NULL, 7, 12, 1, 0, NOW()),
  (31, 3, 'master_program',                  'Daftar Master Program',         'master_program',                  NULL, 1, 1,  1, 0, NOW()),
  (32, 3, 'penjualan_program',               'Daftar Penjualan Program',      'penjualan_program',               NULL, 2, 2,  1, 0, NOW()),
  (41, 4, 'quotation_program',               'Daftar Quotation',              'quotation_program',               NULL, 1, 1,  1, 0, NOW()),
  (51, 5, 'activity_log',                    'Activity Log',                  'activity_log_labmania',           NULL, 1, 1,  1, 0, NOW()),
  (61, 6, 'permission_group',                'Permission Group',              'permission_group',                NULL, 1, 1,  1, 1, NOW()),
  (62, 6, 'menu_labmania',                   'Menu Sidebar',                  'menu_labmania',                   NULL, 1, 2,  1, 1, NOW());

-- 6. Aksi yang berlaku per menu
INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`)
SELECT m.`id`, a.`aksi` FROM `ms_menu` m
JOIN (SELECT 'lihat' AS aksi) a
WHERE m.`parent_id` <> 0;

INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`)
SELECT m.`id`, a.`aksi` FROM `ms_menu` m
JOIN (SELECT 'tambah' AS aksi UNION ALL SELECT 'ubah' UNION ALL SELECT 'aktif') a
WHERE m.`kode` IN ('master_data.jenis_produk', 'master_data.jenis_sertifikasi', 'master_data.jenis_bidang', 'master_data.paket_produk',
                   'master_trainer', 'master_data.template_keterangan', 'master_data.template_konfirmasi', 'master_data.rate',
                   'master_data.users', 'master_program', 'penjualan_program', 'quotation_program', 'menu_labmania');

INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`)
SELECT m.`id`, a.`aksi` FROM `ms_menu` m
JOIN (SELECT 'tambah' AS aksi UNION ALL SELECT 'ubah' UNION ALL SELECT 'hapus') a
WHERE m.`kode` IN ('master_data.peserta', 'master_data.company', 'permission_group');

INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`) SELECT `id`, 'cetak' FROM `ms_menu` WHERE `kode` = 'quotation_program';

-- Dashboard hanya Lihat (sudah tercakup parent_id = 0? tidak: dashboard bukan anak), tambahkan manual
INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`) SELECT `id`, 'lihat' FROM `ms_menu` WHERE `kode` = 'dashboard';

-- 7. Izin awal (dapat diubah lewat Permission Group)
-- Admin Developer: semua aksi
INSERT IGNORE INTO `ms_group_akses` (`id_group`, `id_menu`, `aksi`)
SELECT g.`id_group`, a.`id_menu`, a.`aksi` FROM `groups` g JOIN `ms_menu_aksi` a WHERE g.`nm_group` = 'Admin Developer';

-- Manajemen: Lihat semua menu (termasuk Activity Log)
INSERT IGNORE INTO `ms_group_akses` (`id_group`, `id_menu`, `aksi`)
SELECT g.`id_group`, a.`id_menu`, 'lihat' FROM `groups` g JOIN `ms_menu_aksi` a ON a.`aksi` = 'lihat' WHERE g.`nm_group` = 'Manajemen';

-- users (group umum): Lihat, kecuali Activity Log dan menu Pengaturan
INSERT IGNORE INTO `ms_group_akses` (`id_group`, `id_menu`, `aksi`)
SELECT g.`id_group`, m.`id`, 'lihat' FROM `groups` g JOIN `ms_menu` m
WHERE g.`nm_group` = 'users' AND m.`parent_id` <> 0 OR (g.`nm_group` = 'users' AND m.`kode` = 'dashboard');
DELETE ga FROM `ms_group_akses` ga
JOIN `groups` g ON g.`id_group` = ga.`id_group` AND g.`nm_group` = 'users'
JOIN `ms_menu` m ON m.`id` = ga.`id_menu` AND m.`kode` IN ('activity_log', 'permission_group', 'menu_labmania');

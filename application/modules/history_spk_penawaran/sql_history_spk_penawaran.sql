-- Tabel History SPK Penawaran
-- Jalankan script ini di database untuk membuat tabel-tabel history

-- 1. Tabel Header History
CREATE TABLE IF NOT EXISTS `kons_tr_spk_penawaran_history` (
  `id_history` VARCHAR(50) NOT NULL,
  `id_spk_penawaran` VARCHAR(50) NOT NULL,
  `id_penawaran` VARCHAR(50) DEFAULT NULL,
  `id_customer` VARCHAR(50) DEFAULT NULL,
  `nm_customer` VARCHAR(255) DEFAULT NULL,
  `address` TEXT,
  `npwp_cust` VARCHAR(50) DEFAULT NULL,
  `nm_pic` VARCHAR(100) DEFAULT NULL,
  `tipe_informasi_awal` VARCHAR(50) DEFAULT NULL,
  `detail_informasi_awal` TEXT,
  `waktu_from` DATE DEFAULT NULL,
  `waktu_to` DATE DEFAULT NULL,
  `id_sales` VARCHAR(50) DEFAULT NULL,
  `nm_sales` VARCHAR(100) DEFAULT NULL,
  `upload_proposal` VARCHAR(255) DEFAULT NULL,
  `id_project` VARCHAR(50) DEFAULT NULL,
  `nm_project` VARCHAR(255) DEFAULT NULL,
  `id_divisi` VARCHAR(50) DEFAULT NULL,
  `nm_divisi` VARCHAR(100) DEFAULT NULL,
  `id_project_leader` VARCHAR(50) DEFAULT NULL,
  `nm_project_leader` VARCHAR(100) DEFAULT NULL,
  `id_konsultan_1` VARCHAR(50) DEFAULT NULL,
  `nm_konsultan_1` VARCHAR(100) DEFAULT NULL,
  `id_konsultan_2` VARCHAR(50) DEFAULT NULL,
  `nm_konsultan_2` VARCHAR(100) DEFAULT NULL,
  `nilai_kontrak` DECIMAL(20,2) DEFAULT 0,
  `biaya_subcont` DECIMAL(20,2) DEFAULT 0,
  `biaya_akomodasi` DECIMAL(20,2) DEFAULT 0,
  `biaya_others` DECIMAL(20,2) DEFAULT 0,
  `biaya_tandem` DECIMAL(20,2) DEFAULT 0,
  `biaya_lab` DECIMAL(20,2) DEFAULT 0,
  `biaya_subcont_tenaga_ahli` DECIMAL(20,2) DEFAULT 0,
  `biaya_subcont_perusahaan` DECIMAL(20,2) DEFAULT 0,
  `nilai_kontrak_bersih` DECIMAL(20,2) DEFAULT 0,
  `mandays_rate` DECIMAL(20,2) DEFAULT 0,
  `total_mandays` DECIMAL(20,2) DEFAULT 0,
  `mandays_subcont` DECIMAL(20,2) DEFAULT 0,
  `mandays_internal` DECIMAL(20,2) DEFAULT 0,
  `nm_pemberi_informasi_1_komisi` VARCHAR(100) DEFAULT NULL,
  `persen_pemberi_informasi_1_komisi` DECIMAL(5,2) DEFAULT 0,
  `nominal_pemberi_informasi_1_komisi` DECIMAL(20,2) DEFAULT 0,
  `nm_pemberi_informasi_2_komisi` VARCHAR(100) DEFAULT NULL,
  `persen_pemberi_informasi_2_komisi` DECIMAL(5,2) DEFAULT 0,
  `nominal_pemberi_informasi_2_komisi` DECIMAL(20,2) DEFAULT 0,
  `nm_sales_1_komisi` VARCHAR(100) DEFAULT NULL,
  `persen_sales_1_komisi` DECIMAL(5,2) DEFAULT 0,
  `nominal_sales_1_komisi` DECIMAL(20,2) DEFAULT 0,
  `nm_sales_2_komisi` VARCHAR(100) DEFAULT NULL,
  `persen_sales_2_komisi` DECIMAL(5,2) DEFAULT 0,
  `nominal_sales_2_komisi` DECIMAL(20,2) DEFAULT 0,
  `isu_khusus` TEXT,
  `tipe_info_awal_eks` VARCHAR(50) DEFAULT NULL,
  `detail_info_awal_eks` TEXT,
  `cp_info_awal_eks` VARCHAR(100) DEFAULT NULL,
  `sts_spk` VARCHAR(1) DEFAULT NULL,
  `approval_sales_sts` VARCHAR(50) DEFAULT NULL,
  `approval_sales_date` DATETIME DEFAULT NULL,
  `approval_konsultan_1_sts` VARCHAR(50) DEFAULT NULL,
  `approval_konsultan_1_date` DATETIME DEFAULT NULL,
  `approval_konsultan_2_sts` VARCHAR(50) DEFAULT NULL,
  `approval_konsultan_2_date` DATETIME DEFAULT NULL,
  `approval_level2_sts` VARCHAR(50) DEFAULT NULL,
  `approval_level2_date` DATETIME DEFAULT NULL,
  `approval_manager_sales` VARCHAR(50) DEFAULT NULL,
  `approval_manager_sales_date` DATETIME DEFAULT NULL,
  `approval_project_leader_sts` VARCHAR(50) DEFAULT NULL,
  `approval_project_leader_date` DATETIME DEFAULT NULL,
  `reject_sales_sts` VARCHAR(50) DEFAULT NULL,
  `reject_konsultan_1_sts` VARCHAR(50) DEFAULT NULL,
  `reject_konsultan_2_sts` VARCHAR(50) DEFAULT NULL,
  `reject_project_leader_sts` VARCHAR(50) DEFAULT NULL,
  `reject_manager_sales_sts` VARCHAR(50) DEFAULT NULL,
  `reject_level2_by` VARCHAR(50) DEFAULT NULL,
  `revisi` INT(11) DEFAULT 0,
  `input_by` VARCHAR(50) DEFAULT NULL,
  `input_date` DATETIME DEFAULT NULL,
  `deleted_by` VARCHAR(50) DEFAULT NULL,
  `deleted_date` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_history`),
  KEY `id_spk_penawaran` (`id_spk_penawaran`),
  KEY `id_penawaran` (`id_penawaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Aktivitas History
CREATE TABLE IF NOT EXISTS `kons_tr_spk_aktifitas_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_history` VARCHAR(50) NOT NULL,
  `id_penawaran` VARCHAR(50) DEFAULT NULL,
  `id_spk_penawaran` VARCHAR(50) DEFAULT NULL,
  `id_aktifitas` VARCHAR(50) DEFAULT NULL,
  `nm_aktifitas` VARCHAR(255) DEFAULT NULL,
  `bobot` DECIMAL(5,2) DEFAULT NULL,
  `mandays` DECIMAL(10,2) DEFAULT 0,
  `mandays_rate` DECIMAL(20,2) DEFAULT 0,
  `mandays_tandem` DECIMAL(10,2) DEFAULT 0,
  `mandays_rate_tandem` DECIMAL(20,2) DEFAULT 0,
  `harga_aktifitas` DECIMAL(20,2) DEFAULT 0,
  `total_aktifitas` DECIMAL(20,2) DEFAULT 0,
  `input_by` VARCHAR(50) DEFAULT NULL,
  `input_date` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_history` (`id_history`),
  KEY `id_spk_penawaran` (`id_spk_penawaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Subcont History
CREATE TABLE IF NOT EXISTS `kons_tr_spk_penawaran_subcont_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_history` VARCHAR(50) NOT NULL,
  `id_spk_penawaran` VARCHAR(50) DEFAULT NULL,
  `nm_aktifitas` VARCHAR(255) DEFAULT NULL,
  `mandays_subcont` DECIMAL(10,2) DEFAULT 0,
  `price_subcont` DECIMAL(20,2) DEFAULT 0,
  `total_subcont` DECIMAL(20,2) DEFAULT 0,
  `keterangan` TEXT,
  `dibuat_oleh` VARCHAR(50) DEFAULT NULL,
  `dibuat_tgl` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_history` (`id_history`),
  KEY `id_spk_penawaran` (`id_spk_penawaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Payment History
CREATE TABLE IF NOT EXISTS `kons_tr_spk_penawaran_payment_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_history` VARCHAR(50) NOT NULL,
  `id_spk_penawaran` VARCHAR(50) DEFAULT NULL,
  `term_payment` VARCHAR(100) DEFAULT NULL,
  `persen_payment` DECIMAL(5,2) DEFAULT 0,
  `nominal_payment` DECIMAL(20,2) DEFAULT 0,
  `desc_payment` TEXT,
  `dibuat_oleh` VARCHAR(50) DEFAULT NULL,
  `dibuat_tgl` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_history` (`id_history`),
  KEY `id_spk_penawaran` (`id_spk_penawaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Fix untuk tabel payment history jika sudah ada (ubah nama kolom)
-- ALTER TABLE `kons_tr_spk_penawaran_payment_history` CHANGE `nilai_payment` `nominal_payment` DECIMAL(20,2) DEFAULT 0;

-- 5. Insert Permissions untuk History SPK Penawaran
INSERT INTO `permissions` (`nm_permission`, `ket`, `created_on`, `created_by`)
SELECT 'History_SPK_Penawaran.View', 'View', NOW(), 'system' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE nm_permission = 'History_SPK_Penawaran.View');
INSERT INTO `permissions` (`nm_permission`, `ket`, `created_on`, `created_by`)
SELECT 'History_SPK_Penawaran.Add', 'Add', NOW(), 'system' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE nm_permission = 'History_SPK_Penawaran.Add');
INSERT INTO `permissions` (`nm_permission`, `ket`, `created_on`, `created_by`)
SELECT 'History_SPK_Penawaran.Manage', 'Manage', NOW(), 'system' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE nm_permission = 'History_SPK_Penawaran.Manage');
INSERT INTO `permissions` (`nm_permission`, `ket`, `created_on`, `created_by`)
SELECT 'History_SPK_Penawaran.Delete', 'Delete', NOW(), 'system' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE nm_permission = 'History_SPK_Penawaran.Delete');

-- 6. Insert Menu untuk History SPK Penawaran
INSERT INTO `menus` (`title`, `link`, `icon`, `target`, `group_menu`, `parent_id`, `permission_id`, `status`, `order`, `created_on`, `created_by`) 
SELECT 'History SPK Penawaran', 'history_spk_penawaran', 'fa fa-history', '0', '0', '0', COALESCE(MAX(id_permission), 0), '1', COALESCE((SELECT MAX(`order`) FROM menus), 0) + 1, NOW(), 'system'
FROM permissions WHERE nm_permission = 'History_SPK_Penawaran.View' AND NOT EXISTS (SELECT 1 FROM menus WHERE link = 'history_spk_penawaran');

-- Update permission_id di menus
UPDATE menus m 
SET m.permission_id = (SELECT id_permission FROM permissions WHERE nm_permission = 'History_SPK_Penawaran.View' LIMIT 1)
WHERE m.link = 'history_spk_penawaran';
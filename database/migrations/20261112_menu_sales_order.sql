-- Menu Sales Order (SO-FR-02, SO-FR-11): sub menu di modul Quotation, tab Sales Order dan Surat Kerja Trainer.
-- Kode menu = nama modul HMVC `sales_order`. Aksi: lihat, ubah (Siap dibuatkan invoice), cetak.
INSERT IGNORE INTO `ms_menu` (`parent_id`, `kode`, `judul`, `link`, `ikon`, `kelompok`, `urutan`, `aktif`, `sistem`, `created_date`)
SELECT p.`id`, 'sales_order', 'Sales Order', 'sales_order', NULL, 1, 2, 1, 0, NOW()
FROM `ms_menu` p WHERE p.`kode` = 'm_quotation';

INSERT IGNORE INTO `ms_menu_aksi` (`id_menu`, `aksi`)
SELECT m.`id`, a.`aksi` FROM `ms_menu` m
JOIN (SELECT 'lihat' AS aksi UNION ALL SELECT 'ubah' UNION ALL SELECT 'cetak') a
WHERE m.`kode` = 'sales_order';

-- Izin awal (dapat diubah lewat Master Role): Admin Developer semua aksi, Manajemen dan users Lihat.
INSERT IGNORE INTO `ms_group_akses` (`id_group`, `id_menu`, `aksi`)
SELECT g.`id_group`, a.`id_menu`, a.`aksi` FROM `groups` g JOIN `ms_menu_aksi` a
JOIN `ms_menu` m ON m.`id` = a.`id_menu`
WHERE g.`nm_group` = 'Admin Developer' AND m.`kode` = 'sales_order';

INSERT IGNORE INTO `ms_group_akses` (`id_group`, `id_menu`, `aksi`)
SELECT g.`id_group`, m.`id`, 'lihat' FROM `groups` g JOIN `ms_menu` m
WHERE g.`nm_group` IN ('Manajemen', 'users') AND m.`kode` = 'sales_order';

-- Migration: Master User field Role + Override Permission (FR-37, FR-38, FR-65, draf 11w [MRO])
-- Tanggal: 07-11-2026
-- Role = record `groups` (dikelola di Permission Group / Master Role). Kolom department/division/jabatan tetap ada (Q-37), tidak dipakai lagi oleh form.
-- Override Permission: hak menu tambahan per user, bentuk sama dengan ms_group_akses.

ALTER TABLE `ms_user_karyawan` ADD COLUMN `id_group` INT NULL AFTER `jabatan`;

-- Role karyawan yang sudah punya akun login diambil dari user_groups
UPDATE `ms_user_karyawan` k
JOIN (SELECT id_user, MIN(id_group) AS id_group FROM `user_groups` GROUP BY id_user) ug ON ug.id_user = k.id_user
SET k.`id_group` = ug.`id_group`
WHERE k.`id_group` IS NULL;

CREATE TABLE IF NOT EXISTS `ms_user_akses` (
  `id_user` INT NOT NULL,
  `id_menu` INT NOT NULL,
  `aksi` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id_user`, `id_menu`, `aksi`),
  KEY `idx_user_akses_menu` (`id_menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

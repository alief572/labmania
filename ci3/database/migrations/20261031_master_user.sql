-- Migration: Master User (FR-36 – FR-39)
-- Tanggal: 31-10-2026
-- Acuan: specs/final/Mock Up Master - Quotation (Master User), specs/final/FR & NFR Master - Quotation.md bagian 4.10
-- `employees` di DB ini adalah VIEW di atas `users`, jadi data karyawan disimpan di tabel baru `ms_user_karyawan`.
-- Relasi ke akun login: ms_user_karyawan.id_user -> users.id_user (users.employee_id tidak dipakai, agar modul lama tidak terganggu).

CREATE TABLE IF NOT EXISTS `ms_user_karyawan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `nik` VARCHAR(16) NULL,
  `tmp_lahir` VARCHAR(50) NULL,
  `tgl_lahir` DATE NULL,
  `department` VARCHAR(50) NULL,
  `division` VARCHAR(50) NULL,
  `jabatan` VARCHAR(30) NULL,
  `gender` VARCHAR(10) NULL,
  `agama` VARCHAR(20) NULL,
  `telp` VARCHAR(20) NULL,
  `email` VARCHAR(100) NULL,
  `alamat` VARCHAR(255) NULL,
  `npwp` VARCHAR(20) NULL,
  `tgl_masuk` DATE NULL,
  `tipe` VARCHAR(20) NULL,
  `jobdesc` VARCHAR(500) NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `id_user` INT NULL UNIQUE,
  `created_by` INT NULL,
  `created_date` DATETIME NULL,
  `updated_by` INT NULL,
  `updated_date` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Akun login yang sudah ada dicatat sebagai karyawan agar tetap tampil di Master User
-- (Department/Division/Jabatan dst. dilengkapi lewat Edit).
INSERT INTO `ms_user_karyawan` (`kode`, `nama`, `email`, `telp`, `alamat`, `aktif`, `id_user`, `created_date`)
SELECT CONCAT('USR-', LPAD(u.`id_user`, 3, '0')), COALESCE(NULLIF(u.`nm_lengkap`, ''), u.`username`), u.`email`, u.`hp`, u.`alamat`,
       IFNULL(u.`st_aktif`, 1), u.`id_user`, NOW()
FROM `users` u
WHERE IFNULL(u.`deleted`, 0) = 0
  AND NOT EXISTS (SELECT 1 FROM `ms_user_karyawan` k WHERE k.`id_user` = u.`id_user`);

-- Group akses (FR-36): Admin Developer wajib ada; Manajemen untuk akses Activity Log (LOG-FR-09)
INSERT INTO `groups` (`nm_group`, `ket`)
SELECT 'Manajemen', 'Manajemen' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `nm_group` = 'Manajemen');

INSERT INTO `groups` (`nm_group`, `ket`)
SELECT 'Admin Developer', 'Admin sistem & developer' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `nm_group` = 'Admin Developer');

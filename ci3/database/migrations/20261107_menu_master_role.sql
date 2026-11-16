-- Master Role di sidebar (FR-64): halaman Permission Group (CRUD group + Atur Izin) dipindah ke Master Data > setelah Master User.
-- Kode menu `permission_group` tetap, jadi hak akses yang sudah diberikan tidak berubah.
UPDATE `ms_menu` SET `parent_id` = 2, `judul` = 'Master Role', `urutan` = 13, `kelompok` = 7 WHERE `kode` = 'permission_group';

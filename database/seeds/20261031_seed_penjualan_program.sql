-- Seed Data Master Trainer, Customer, Peserta & Penjualan Program Baseline
-- Acuan: specs/final/Mock Up Master - Quotation/app.js (db.trainer, db.company, db.peserta, db.quot)

-- 1. Master Trainer
INSERT IGNORE INTO `ms_trainer` (`id`, `kode`, `nama`, `tempat_lahir`, `tgl_lahir`, `alamat`, `no_telp`, `pend_sarjana`, `pend_pascasarjana`, `bidang_keahlian`, `pengalaman_lain`, `cv_tempat`, `cv_tanggal`, `nama_ttd`, `aktif`, `created_date`) VALUES
(1, 'TR-001', 'Dr. Hendra Gunawan, M.Si', 'Bandung', '1980-05-12', 'Jl. Kemang Raya No. 12, Bekasi', '081234567890', 'S1 Kimia UNPAD (2002)', 'S2 & S3 Kimia ITB (2009)', 'ISO 17025, Analisis Kimia, Kalibrasi', 'Konsultan mutu laboratorium 15 tahun', 'Bekasi', '2026-10-01', 'Dr. Hendra Gunawan, M.Si', 1, NOW()),
(2, 'TR-002', 'Siti Rahmawati, S.Si', 'Semarang', '1985-08-25', 'Jl. Anggrek No. 45, Bekasi', '081298765432', 'S1 Biologi UNDIP (2007)', 'S2 Mikrobiologi IPB (2012)', 'Mikrobiologi, Pangan, ISO 22000', 'Auditor sistem pangan & lab mikrobiologi', 'Bekasi', '2026-10-01', 'Siti Rahmawati, S.Si', 1, NOW()),
(3, 'TR-003', 'Budi Santoso, ST', 'Jakarta', '1978-03-15', 'Jl. Tebet Barat No. 8, Jakarta Selatan', '081311223344', 'S1 Teknik Lingkungan Trisakti (2001)', '', 'K3 Laboratorium, Pengelolaan Limbah B3', 'Instruktur K3 bersertifikat BNSP', 'Jakarta', '2026-10-01', 'Budi Santoso, ST', 1, NOW());

-- 2. Master Customer (Company)
INSERT IGNORE INTO `master_customer` (`id_customer`, `name_customer`, `jenis_instansi`, `address_office`, `telephone`, `email`, `created_on`) VALUES
('CUST-001', 'PT Kimia Farma Tbk', 'BUMN', 'Jl. Veteran No. 9, Jakarta Pusat', '021-3847755', 'info@kimiafarma.co.id', NOW()),
('CUST-002', 'PT Indo Acidatama Tbk', 'Swasta', 'Jl. Raya Kebakkramat Km 10, Karanganyar', '0271-825555', 'corporate@acidatama.co.id', NOW()),
('CUST-003', 'Balai Pengujian Standar Instrumen LH', 'Pemerintah', 'Jl. Ciheuleut No. 5, Bogor', '0251-8312345', 'bpsi.lh@menlhk.go.id', NOW());

-- 3. Master Peserta
INSERT IGNORE INTO `ms_peserta` (`id`, `kode`, `nama`, `gender`, `instansi`, `id_jenis_bidang`, `id_prov`, `provinsi`, `id_kab`, `kabupaten`, `no_telp`, `email`, `id_customer`, `aktif`, `created_date`) VALUES
(1, 'PS-001', 'Rina Kusuma, S.Si', 'P', 'PT Kimia Farma Tbk', 1, '32', 'JAWA BARAT', '3275', 'KOTA BEKASI', '081288990011', 'rina.kusuma@kimiafarma.co.id', 'CUST-001', 1, NOW()),
(2, 'PS-002', 'Ahmad Dahlan, ST', 'L', 'PT Indo Acidatama Tbk', 14, '33', 'JAWA TENGAH', '3372', 'KOTA SURAKARTA', '081377889922', 'ahmad.dahlan@acidatama.co.id', 'CUST-002', 1, NOW());

-- 4. Penjualan Program Baseline
-- Hapus jika sudah ada untuk re-run yang bersih
DELETE FROM `penjualan_program_biaya` WHERE `id_penjualan` IN (1, 2, 3, 4);
DELETE FROM `penjualan_program` WHERE `id` IN (1, 2, 3, 4);

-- QT1: Public Draft (MP-001)
INSERT INTO `penjualan_program` (`id`, `nomor`, `id_master_program`, `jenis`, `id_customer`, `id_trainer`, `tgl_mulai`, `tgl_selesai`, `waktu`, `lokasi`, `durasi_hari`, `target_peserta`, `harga_jual_peserta`, `target_profit`, `status`, `created_date`) VALUES
(1, 'PJP-2611-001', 1, 'public', NULL, 1, '2026-11-10', '2026-11-11', '09.00-15.15 WIB', 'Offline Bekasi', 2, 10, 650000.00, 2000000.00, 'draft', NOW());

-- QT2: B2B Final (MP-002) - In house Kimia Farma
INSERT INTO `penjualan_program` (`id`, `nomor`, `id_master_program`, `jenis`, `id_customer`, `id_trainer`, `tgl_mulai`, `tgl_selesai`, `waktu`, `lokasi`, `durasi_hari`, `target_peserta`, `harga_jual_peserta`, `target_profit`, `status`, `confirmed_date`, `created_date`) VALUES
(2, 'PJB-2611-001', 2, 'b2b', 'CUST-001', 2, '2026-11-17', '2026-11-19', '09.00-15.15 WIB', 'In house - Bekasi', 3, 20, 2250000.00, 5000000.00, 'final', NOW(), NOW());

-- QT3: Public Final (MP-003) - Online Zoom
INSERT INTO `penjualan_program` (`id`, `nomor`, `id_master_program`, `jenis`, `id_customer`, `id_trainer`, `tgl_mulai`, `tgl_selesai`, `waktu`, `lokasi`, `durasi_hari`, `target_peserta`, `harga_jual_peserta`, `target_profit`, `status`, `confirmed_date`, `created_date`) VALUES
(3, 'PJP-2611-002', 3, 'public', NULL, 1, '2026-11-24', '2026-11-25', '09.00-15.15 WIB', 'Online Zoom', 2, 15, 900000.00, 3000000.00, 'final', NOW(), NOW());

-- QT4: B2B Batal (MP-001) - PT Indo Acidatama
INSERT INTO `penjualan_program` (`id`, `nomor`, `id_master_program`, `jenis`, `id_customer`, `id_trainer`, `tgl_mulai`, `tgl_selesai`, `waktu`, `lokasi`, `durasi_hari`, `target_peserta`, `harga_jual_peserta`, `target_profit`, `status`, `closed_date`, `created_date`) VALUES
(4, 'PJB-2611-002', 1, 'b2b', 'CUST-002', 1, '2026-12-01', '2026-12-02', '09.00-15.15 WIB', 'In house - Solo', 2, 12, 850000.00, 1500000.00, 'batal', NOW(), NOW());

-- 5. Rincian Biaya Penjualan Program (Salin dari Master Program Biaya)
-- QT1 (id=1): Salin dari MP-1
INSERT INTO `penjualan_program_biaya` (`id_penjualan`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`)
SELECT 1, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual` FROM `master_program_biaya` WHERE `id_master_program` = 1;

-- QT2 (id=2): Salin dari MP-2 + Biaya Manual
INSERT INTO `penjualan_program_biaya` (`id_penjualan`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`)
SELECT 2, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual` FROM `master_program_biaya` WHERE `id_master_program` = 2;
INSERT INTO `penjualan_program_biaya` (`id_penjualan`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`) VALUES
(2, NULL, 'fixed', 'Sewa alat peraga', 65, 1500000.00, 1);

-- QT3 (id=3): Salin dari MP-3
INSERT INTO `penjualan_program_biaya` (`id_penjualan`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`)
SELECT 3, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual` FROM `master_program_biaya` WHERE `id_master_program` = 3;

-- QT4 (id=4): Salin dari MP-1
INSERT INTO `penjualan_program_biaya` (`id_penjualan`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`)
SELECT 4, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual` FROM `master_program_biaya` WHERE `id_master_program` = 1;

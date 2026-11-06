-- Seed Data Baseline Quotation Labmania
-- Acuan: specs/final/Mock Up Master - Quotation/app.js (db.qtn)

DELETE FROM `quotation_keterangan` WHERE `id_quotation` IN (1, 2, 3);
DELETE FROM `quotation_peserta` WHERE `id_quotation` IN (1, 2, 3);
DELETE FROM `quotation` WHERE `id` IN (1, 2, 3);

-- Quotation 1: Terikat ke PJB-2611-001 (id_penjualan=2) - B2B Deal PT Kimia Farma
INSERT INTO `quotation` (`id`, `nomor`, `id_penjualan`, `tanggal`, `berlaku_sampai`, `id_sales`, `sumber_info`, `tujuan`, `id_customer`, `up_nama`, `alamat`, `skema_harga`, `harga_peserta`, `harga_allin`, `diskon`, `is_ppn`, `ppn_persen`, `biaya_lain`, `id_template_konfirmasi`, `status`, `deal_date`, `created_date`) VALUES
(1, 'QT-2611-001', 2, '2026-11-01', '2026-11-30', 1, 'In-house Request', 'company', 'CUST-001', 'Ibu Nurul / Training Head', 'Jl. Veteran No. 9, Jakarta Pusat', 'allin', 2250000.00, 45000000.00, 2000000.00, 1, 11.00, 0.00, NULL, 'deal', NOW(), NOW());

-- Quotation 2: Terikat ke PJP-2611-002 (id_penjualan=3) - Public Terkirim Rina Kusuma
INSERT INTO `quotation` (`id`, `nomor`, `id_penjualan`, `tanggal`, `berlaku_sampai`, `id_sales`, `sumber_info`, `tujuan`, `id_customer`, `up_nama`, `alamat`, `skema_harga`, `harga_peserta`, `harga_allin`, `diskon`, `is_ppn`, `ppn_persen`, `biaya_lain`, `id_template_konfirmasi`, `status`, `sent_date`, `created_date`) VALUES
(2, 'QT-2611-002', 3, '2026-11-05', '2026-12-05', 1, 'Website', 'personal', NULL, '', 'Bekasi, Jawa Barat', 'peserta', 900000.00, 0.00, 0.00, 1, 11.00, 0.00, 1, 'terkirim', NOW(), NOW());

-- Quotation 3: Terikat ke PJP-2611-002 (id_penjualan=3) - Public Draft Ahmad Dahlan
INSERT INTO `quotation` (`id`, `nomor`, `id_penjualan`, `tanggal`, `berlaku_sampai`, `id_sales`, `sumber_info`, `tujuan`, `id_customer`, `up_nama`, `alamat`, `skema_harga`, `harga_peserta`, `harga_allin`, `diskon`, `is_ppn`, `ppn_persen`, `biaya_lain`, `id_template_konfirmasi`, `status`, `created_date`) VALUES
(3, 'QT-2611-003', 3, '2026-11-08', '2026-12-08', 1, 'Instagram', 'personal', NULL, '', 'Solo, Jawa Tengah', 'peserta', 900000.00, 0.00, 50000.00, 1, 11.00, 0.00, 1, 'draft', NOW());

-- Peserta Quotation
-- Q1: Rina Kusuma
INSERT INTO `quotation_peserta` (`id_quotation`, `id_peserta`, `status`) VALUES
(1, 1, 'deal');

-- Q2: Rina Kusuma
INSERT INTO `quotation_peserta` (`id_quotation`, `id_peserta`, `status`) VALUES
(2, 1, 'menunggu');

-- Q3: Ahmad Dahlan
INSERT INTO `quotation_peserta` (`id_quotation`, `id_peserta`, `status`) VALUES
(3, 2, 'menunggu');

-- Keterangan S&K Default untuk Q1, Q2, Q3
INSERT INTO `quotation_keterangan` (`id_quotation`, `id_template_keterangan`, `kategori`, `teks`, `urutan`)
SELECT 1, `id`, `kategori`, `teks`, 1 FROM `ms_template_keterangan` WHERE `berlaku_untuk` IN ('semua', 'b2b');

INSERT INTO `quotation_keterangan` (`id_quotation`, `id_template_keterangan`, `kategori`, `teks`, `urutan`)
SELECT 2, `id`, `kategori`, `teks`, 1 FROM `ms_template_keterangan` WHERE `berlaku_untuk` IN ('semua', 'public');

INSERT INTO `quotation_keterangan` (`id_quotation`, `id_template_keterangan`, `kategori`, `teks`, `urutan`)
SELECT 3, `id`, `kategori`, `teks`, 1 FROM `ms_template_keterangan` WHERE `berlaku_untuk` IN ('semua', 'public');

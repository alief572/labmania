-- Seed Data Master Labmania
-- Acuan: specs/final/Mock Up Master - Quotation/app.js & FR & NFR Master - Quotation.md

-- 1. Jenis Produk
INSERT IGNORE INTO `ms_jenis_produk` (`id`, `kode`, `nama`, `aktif`, `created_date`) VALUES
(1, 'JP-001', 'Pelatihan', 1, NOW()),
(2, 'JP-002', 'Sertifikasi', 1, NOW());

-- 2. Jenis Sertifikat
INSERT IGNORE INTO `ms_jenis_sertifikasi` (`id`, `kode`, `nama`, `aktif`, `created_date`) VALUES
(1, 'JS-001', 'Sertifikat BNSP', 1, NOW()),
(2, 'JS-002', 'Sertifikat KAN ISO 29993', 1, NOW()),
(3, 'JS-003', 'Sertifikat Labmania', 1, NOW()),
(4, 'JS-004', 'Sertifikat Labmania KAN ISO 29993', 1, NOW()),
(5, 'JS-005', 'Sertifikat Labmania Lisensi BSN', 1, NOW());

-- 3. Jenis Bidang
INSERT IGNORE INTO `ms_jenis_bidang` (`id`, `kode`, `nama`, `aktif`, `created_date`) VALUES
(1, 'JB-001', 'Analisis Kimia', 1, NOW()),
(2, 'JB-002', 'Analisis Mikrobiologi', 1, NOW()),
(3, 'JB-003', 'Energi', 1, NOW()),
(4, 'JB-004', 'ISO 17025', 1, NOW()),
(5, 'JB-005', 'ISO 17025 Manajemen', 1, NOW()),
(6, 'JB-006', 'ISO 22000 Manajemen', 1, NOW()),
(7, 'JB-007', 'ISO 9001 Manajemen', 1, NOW()),
(8, 'JB-008', 'K3', 1, NOW()),
(9, 'JB-009', 'Kalibrasi', 1, NOW()),
(10, 'JB-010', 'Kimia', 1, NOW()),
(11, 'JB-011', 'Mikrobiologi', 1, NOW()),
(12, 'JB-012', 'Okupasi', 1, NOW()),
(13, 'JB-013', 'Pangan', 1, NOW()),
(14, 'JB-014', 'Pengelolaan Limbah', 1, NOW()),
(15, 'JB-015', 'Penyelia', 1, NOW()),
(16, 'JB-016', 'Petugas Sampling', 1, NOW()),
(17, 'JB-017', 'Trainer', 1, NOW());

-- 4. Paket Produk (Product Combine)
INSERT IGNORE INTO `paket_produk` (`id`, `nomor`, `judul`, `id_jenis_produk`, `id_jenis_sertifikasi`, `id_jenis_bidang`, `aktif`, `created_date`) VALUES
(1, 'PC-001', 'Pelatihan ISO/IEC 17025:2017', 1, 3, 4, 1, NOW()),
(2, 'PC-002', 'Pelatihan ISO/IEC 17025:2017', 1, 4, 4, 1, NOW()),
(3, 'PC-003', 'Sertifikasi Petugas Sampling', 2, 1, 16, 1, NOW());

-- 5. Master Rate
-- Ambil id satuan: Event (id 65), Day (id 66), Peserta/Event (id 67), Peserta/Day (id 68)
INSERT IGNORE INTO `ms_rate` (`id`, `kode`, `tipe`, `item`, `id_satuan`, `default_cost`, `keterangan`, `coa`, `aktif`, `created_date`) VALUES
(1, 'FC-001', 'fixed', 'Transport Trainer Luar Kota PP', 65, 250000.00, 'Transport / Hari', '6102', 1, NOW()),
(2, 'FC-002', 'fixed', 'Marketing/Ads', 65, 0.00, 'Iklan', '6105', 1, NOW()),
(3, 'FC-003', 'fixed', 'Dokumentasi', 65, 0.00, 'Dokumentasi', '6106', 1, NOW()),
(4, 'FC-004', 'fixed', 'Banner/Promosi', 65, 100000.00, 'Promosi', '6105', 1, NOW()),
(5, 'FC-005', 'fixed', 'Administrasi', 65, 0.00, 'Administrasi', '6106', 1, NOW()),
(6, 'FC-006', 'fixed', 'Mandays Rate Charge Labmania', 65, 1600000.00, 'Sumber: per Jam -> Event', '6101', 1, NOW()),
(7, 'FC-007', 'fixed', 'Honor Trainer', 65, 2125000.00, 'Sumber: per Jam -> Event', '6101', 1, NOW()),
(8, 'FC-008', 'fixed', 'Hotel Trainer **', 66, 375000.00, 'Hotel bintang 2', '6102', 1, NOW()),
(9, 'FC-009', 'fixed', 'Hotel Trainer ***', 66, 0.00, 'Hotel bintang 3', '6102', 1, NOW()),
(10, 'FC-010', 'fixed', 'Konsumsi Trainer, di luar konsumsi selama training', 66, 140000.00, 'Konsumsi trainer / Hari', '6103', 1, NOW()),
(11, 'VC-001', 'variable', 'Modul', 67, 50000.00, 'Modul', '6104', 1, NOW()),
(12, 'VC-002', 'variable', 'Sertifikat', 67, 5000.00, 'Sertifikat', '6104', 1, NOW()),
(13, 'VC-003', 'variable', 'Seminar Kit', 67, 35000.00, 'Kit', '6104', 1, NOW()),
(14, 'VC-004', 'variable', 'Goodybag', 67, 5000.00, 'Goodybag', '6104', 1, NOW()),
(15, 'VC-005', 'variable', 'Merchandise', 67, 0.00, 'Merchandise', '6104', 1, NOW()),
(16, 'VC-006', 'variable', 'Sertifikasi LSP', 67, 0.00, 'Sertifikasi LSP', '6101', 1, NOW()),
(17, 'VC-007', 'variable', 'Ongkir Dokumen', 67, 15000.00, 'Ongkir Dokumen', '6106', 1, NOW()),
(18, 'VC-008', 'variable', 'Makan Siang Training', 68, 50000.00, 'Untuk peserta + trainer + asisten', '6103', 1, NOW()),
(19, 'VC-009', 'variable', 'Coffee Break', 68, 40000.00, 'Untuk peserta + trainer + asisten', '6103', 1, NOW());

-- 6. Template Keterangan
INSERT IGNORE INTO `ms_template_keterangan` (`id`, `kode`, `kategori`, `teks`, `berlaku_untuk`, `is_default`, `aktif`, `created_date`) VALUES
(1, 'TK-001', 'syarat', 'Peserta bekerja atau akan bekerja di bidang laboratorium', 'semua', 1, 1, NOW()),
(2, 'TK-002', 'termasuk', 'Materi', 'semua', 1, 1, NOW()),
(3, 'TK-003', 'termasuk', 'Sertifikat pelatihan', 'semua', 1, 1, NOW()),
(4, 'TK-004', 'termasuk', 'Pajak', 'semua', 1, 1, NOW()),
(5, 'TK-005', 'termasuk', 'Makan siang dan coffee break', 'semua', 0, 1, NOW()),
(6, 'TK-006', 'tidak', 'Transportasi dan akomodasi peserta', 'semua', 1, 1, NOW()),
(7, 'TK-007', 'tidak', 'Sewa ruang pelatihan di lokasi pelanggan (in-house)', 'b2b', 1, 1, NOW()),
(8, 'TK-008', 'dokumen', 'Formulir konfirmasi keikutsertaan yang telah diisi', 'public', 1, 1, NOW()),
(9, 'TK-009', 'dokumen', 'Fotokopi ijazah terakhir', 'semua', 0, 1, NOW()),
(10, 'TK-010', 'klausul', 'Apabila pelanggan menyetujui penawaran kami mohon untuk menandatangani surat penawaran ini, dan dikirimkan kembali ke LabMania.', 'semua', 1, 1, NOW()),
(11, 'TK-011', 'klausul', 'Dengan menandatangani surat ini, pelanggan telah membaca dan menyetujui syarat dan ketentuan dari LabMania.', 'semua', 1, 1, NOW());

-- 7. Template Konfirmasi
INSERT IGNORE INTO `ms_template_konfirmasi` (`id`, `kode`, `nama`, `label_kegiatan`, `id_jenis_produk`, `isian`, `pernyataan`, `catatan`, `aktif`, `created_date`) VALUES
(1, 'KF-001', 'Workshop', 'Workshop', 1, '["nama","nik","jabatan","instansi","alamat","wa","email","pengalaman"]', 'Saya **akan hadir** pada kegiatan "**{judul}**" yang akan diselenggarakan pada tanggal **{tanggal}** pukul {waktu} ({durasi} hari training, {lokasi})', 'Catatan:\n1. Pembayaran paling lambat H-3 sebelum kegiatan dilaksanakan.', 1, NOW()),
(2, 'KF-002', 'Training & Sertifikasi', 'Training dan Sertifikasi', 2, '["nama","nik","jabatan","instansi","alamat","wa","email","pengalaman"]', 'Saya **akan hadir** pada kegiatan "**Training dan Sertifikasi {judul}**" yang akan diselenggarakan pada tanggal **{tanggal}** pukul {waktu} ({durasi} hari training dan sertifikasi, {lokasi})', 'Catatan:\n1. Pembayaran paling lambat H-3 sebelum kegiatan dilaksanakan.', 1, NOW());

-- 8. Identitas Perusahaan
UPDATE `identitas` SET `nm_perusahaan` = 'Labmania' WHERE `ididentitas` = 1;

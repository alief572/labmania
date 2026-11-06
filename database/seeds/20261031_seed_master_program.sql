-- Seed Master Program dari Mockup BA
-- Acuan: specs/final/Mock Up Master - Quotation/app.js (lines 208-220)

-- 1. MP-2610-001 (Final)
INSERT IGNORE INTO `master_program` (`id`, `nomor`, `id_paket_produk`, `durasi_hari`, `target_peserta`, `harga_jual_peserta`, `target_profit`, `status`, `confirmed_by`, `confirmed_date`, `created_date`) VALUES
(1, 'MP-2610-001', 1, 2, 25, 600000.00, 3000000.00, 'final', 1, NOW(), NOW());

INSERT IGNORE INTO `master_program_biaya` (`id_master_program`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`) VALUES
(1, 1, 'fixed', 'Transport Trainer Luar Kota PP', 65, 250000.00, 0),
(1, 4, 'fixed', 'Banner/Promosi', 65, 100000.00, 0),
(1, 6, 'fixed', 'Mandays Rate Charge Labmania', 65, 1600000.00, 0),
(1, 7, 'fixed', 'Honor Trainer', 65, 2125000.00, 0),
(1, 8, 'fixed', 'Hotel Trainer **', 66, 375000.00, 0),
(1, 10, 'fixed', 'Konsumsi Trainer, di luar konsumsi selama training', 66, 140000.00, 0),
(1, 11, 'variable', 'Modul', 67, 50000.00, 0),
(1, 12, 'variable', 'Sertifikat', 67, 5000.00, 0),
(1, 13, 'variable', 'Seminar Kit', 67, 35000.00, 0),
(1, 18, 'variable', 'Makan Siang Training', 68, 50000.00, 0),
(1, 19, 'variable', 'Coffee Break', 68, 40000.00, 0);

-- 2. MP-2610-002 (Final)
INSERT IGNORE INTO `master_program` (`id`, `nomor`, `id_paket_produk`, `durasi_hari`, `target_peserta`, `harga_jual_peserta`, `target_profit`, `status`, `confirmed_by`, `confirmed_date`, `created_date`) VALUES
(2, 'MP-2610-002', 2, 3, 20, 900000.00, 0.00, 'final', 1, NOW(), NOW());

INSERT IGNORE INTO `master_program_biaya` (`id_master_program`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`) VALUES
(2, 1, 'fixed', 'Transport Trainer Luar Kota PP', 65, 250000.00, 0),
(2, 4, 'fixed', 'Banner/Promosi', 65, 100000.00, 0),
(2, 6, 'fixed', 'Mandays Rate Charge Labmania', 65, 1600000.00, 0),
(2, 7, 'fixed', 'Honor Trainer', 65, 2125000.00, 0),
(2, 8, 'fixed', 'Hotel Trainer **', 66, 375000.00, 0),
(2, 10, 'fixed', 'Konsumsi Trainer, di luar konsumsi selama training', 66, 140000.00, 0),
(2, 11, 'variable', 'Modul', 67, 50000.00, 0),
(2, 12, 'variable', 'Sertifikat', 67, 5000.00, 0),
(2, 18, 'variable', 'Makan Siang Training', 68, 50000.00, 0),
(2, 19, 'variable', 'Coffee Break', 68, 40000.00, 0);

-- 3. MP-2610-003 (Draft)
INSERT IGNORE INTO `master_program` (`id`, `nomor`, `id_paket_produk`, `durasi_hari`, `target_peserta`, `harga_jual_peserta`, `target_profit`, `status`, `created_date`) VALUES
(3, 'MP-2610-003', 3, 1, 20, 750000.00, 0.00, 'draft', NOW());

INSERT IGNORE INTO `master_program_biaya` (`id_master_program`, `id_rate`, `tipe`, `item`, `id_satuan`, `harga`, `is_manual`) VALUES
(3, 1, 'fixed', 'Transport Trainer Luar Kota PP', 65, 250000.00, 0),
(3, 4, 'fixed', 'Banner/Promosi', 65, 100000.00, 0),
(3, 6, 'fixed', 'Mandays Rate Charge Labmania', 65, 1600000.00, 0),
(3, 7, 'fixed', 'Honor Trainer', 65, 2125000.00, 0),
(3, 12, 'variable', 'Sertifikat', 67, 5000.00, 0),
(3, 18, 'variable', 'Makan Siang Training', 68, 50000.00, 0);

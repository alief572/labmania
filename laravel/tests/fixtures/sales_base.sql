-- Fixture sintetis schema-only dari salinan sebelum fase 3.
CREATE TABLE `penjualan_program` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
`nomor` TEXT NOT NULL,
`id_master_program` INTEGER NOT NULL,
`jenis` TEXT NOT NULL,
`id_customer` TEXT DEFAULT NULL,
`id_trainer` INTEGER DEFAULT NULL,
`tgl_mulai` TEXT DEFAULT NULL,
`tgl_selesai` TEXT DEFAULT NULL,
`waktu` TEXT DEFAULT NULL,
`lokasi` TEXT DEFAULT NULL,
`durasi_hari` INTEGER NOT NULL DEFAULT '1',
`target_peserta` INTEGER NOT NULL DEFAULT '10',
`harga_jual_peserta` NUMERIC DEFAULT '0.00',
`target_profit` NUMERIC DEFAULT '0.00',
`status` TEXT NOT NULL DEFAULT 'draft',
`confirmed_by` INTEGER DEFAULT NULL,
`confirmed_date` TEXT DEFAULT NULL,
`closed_by` INTEGER DEFAULT NULL,
`closed_date` TEXT DEFAULT NULL,
`created_by` INTEGER DEFAULT NULL,
`created_date` TEXT DEFAULT NULL,
`updated_by` INTEGER DEFAULT NULL,
`updated_date` TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX `penjualan_program_nomor` ON `penjualan_program` (`nomor`);
CREATE TABLE `penjualan_program_biaya` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_penjualan` INTEGER NOT NULL,
`id_rate` INTEGER DEFAULT NULL,
`tipe` TEXT NOT NULL,
`item` TEXT NOT NULL,
`id_satuan` INTEGER DEFAULT NULL,
`harga` NUMERIC NOT NULL DEFAULT '0.00',
`is_manual` INTEGER NOT NULL DEFAULT '0'
);

CREATE TABLE `quotation` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
`nomor` TEXT NOT NULL,
`id_penjualan` INTEGER NOT NULL,
`tanggal` TEXT NOT NULL,
`berlaku_sampai` TEXT DEFAULT NULL,
`id_sales` INTEGER DEFAULT NULL,
`sumber_info` TEXT DEFAULT NULL,
`tujuan` TEXT NOT NULL DEFAULT 'personal',
`id_customer` TEXT DEFAULT NULL,
`up_nama` TEXT DEFAULT NULL,
`alamat` TEXT,
`skema_harga` TEXT NOT NULL DEFAULT 'peserta',
`harga_peserta` NUMERIC NOT NULL DEFAULT '0.00',
`harga_allin` NUMERIC DEFAULT '0.00',
`diskon` NUMERIC NOT NULL DEFAULT '0.00',
`is_ppn` INTEGER NOT NULL DEFAULT '1',
`ppn_persen` NUMERIC NOT NULL DEFAULT '11.00',
`biaya_lain` NUMERIC NOT NULL DEFAULT '0.00',
`id_template_konfirmasi` INTEGER DEFAULT NULL,
`konfirmasi_isian` TEXT,
`konfirmasi_pernyataan` TEXT,
`status` TEXT NOT NULL DEFAULT 'draft',
`sent_by` INTEGER DEFAULT NULL,
`sent_date` TEXT DEFAULT NULL,
`deal_by` INTEGER DEFAULT NULL,
`deal_date` TEXT DEFAULT NULL,
`closed_by` INTEGER DEFAULT NULL,
`closed_date` TEXT DEFAULT NULL,
`created_by` INTEGER DEFAULT NULL,
`created_date` TEXT DEFAULT NULL,
`updated_by` INTEGER DEFAULT NULL,
`updated_date` TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX `quotation_nomor` ON `quotation` (`nomor`);
CREATE TABLE `quotation_peserta` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_quotation` INTEGER NOT NULL,
`id_peserta` INTEGER NOT NULL,
`status` TEXT NOT NULL DEFAULT 'menunggu'
);
CREATE UNIQUE INDEX `uk_quotation_peserta` ON `quotation_peserta` (`id_quotation`,`id_peserta`);
CREATE TABLE `quotation_keterangan` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_quotation` INTEGER NOT NULL,
`id_template_keterangan` INTEGER DEFAULT NULL,
`kategori` TEXT NOT NULL,
`teks` TEXT NOT NULL,
`urutan` INTEGER NOT NULL DEFAULT '1'
);



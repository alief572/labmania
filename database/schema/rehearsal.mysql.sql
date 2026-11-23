-- Schema-only Laravel fase 1-6, 14-11-2026. Hanya target rehearsal baru.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE `ci_sessions` (
  `id` varchar(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `timestamp` int unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `ci_sessions_timestamp` (`timestamp`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `coa_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_perkiraan` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `nama` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `kdcab` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
  `saldoawal` double NOT NULL DEFAULT '0',
  `bln` smallint NOT NULL DEFAULT '0',
  `thn` int NOT NULL DEFAULT '0',
  `debet` double DEFAULT '0',
  `kredit` double DEFAULT '0',
  `tmp` varchar(7) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `tipe` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '0',
  `level` char(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `grup` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `faktor` smallint DEFAULT NULL,
  `cf_grup` char(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `cf_urut` smallint DEFAULT NULL,
  `cf_child` varchar(4) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `prev_month` double DEFAULT NULL,
  `kode_bank` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `nomor` (`no_perkiraan`,`kdcab`,`bln`,`thn`,`level`) USING BTREE,
  UNIQUE KEY `NO` (`kdcab`,`no_perkiraan`,`bln`,`thn`,`level`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `groups` (
  `id_group` int NOT NULL AUTO_INCREMENT,
  `nm_group` varchar(50) NOT NULL,
  `ket` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `st_default` tinyint(1) DEFAULT NULL COMMENT 'default group for new user',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id_group`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `identitas` (
  `ididentitas` int NOT NULL AUTO_INCREMENT,
  `nm_perusahaan` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `kota` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `no_telp` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `gambar` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`ididentitas`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `invoice` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nomor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `id_sales_order` int unsigned NOT NULL,
  `id_quotation` int NOT NULL,
  `id_penjualan` int NOT NULL,
  `cara_tagih` enum('satu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `perlakuan_ppn` enum('PKP','WAPU','KB') COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_faktur` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penjualan` decimal(18,0) NOT NULL,
  `dpp` decimal(18,0) NOT NULL,
  `ppn` decimal(18,0) NOT NULL,
  `biaya_lain` decimal(18,0) NOT NULL,
  `total_tagihan` decimal(18,0) NOT NULL,
  `piutang` decimal(18,0) NOT NULL,
  `coa_penjualan` int NOT NULL,
  `status` enum('invoiced','batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invoiced',
  `so_aktif` int unsigned GENERATED ALWAYS AS ((case when (`status` = _utf8mb4'invoiced') then `id_sales_order` else NULL end)) STORED,
  `snapshot` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_bayar` date DEFAULT NULL,
  `keterangan_bayar` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bayar_by` int DEFAULT NULL,
  `bayar_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `batal_by` int DEFAULT NULL,
  `batal_date` datetime DEFAULT NULL,
  `batal_alasan` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_nomor_unique` (`nomor`),
  UNIQUE KEY `uk_invoice_so_aktif` (`so_aktif`),
  KEY `invoice_id_sales_order_index` (`id_sales_order`),
  KEY `invoice_id_quotation_index` (`id_quotation`),
  KEY `invoice_id_penjualan_index` (`id_penjualan`),
  KEY `invoice_coa_penjualan_index` (`coa_penjualan`),
  CONSTRAINT `invoice_id_sales_order_foreign` FOREIGN KEY (`id_sales_order`) REFERENCES `sales_order` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoice_bukti_setor` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_invoice` int unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `nomor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_bukti_setor_id_invoice_unique` (`id_invoice`),
  CONSTRAINT `invoice_bukti_setor_id_invoice_foreign` FOREIGN KEY (`id_invoice`) REFERENCES `invoice` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoice_jurnal` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_invoice` int unsigned NOT NULL,
  `jenis` enum('invoice','bukti_setor') COLLATE utf8mb4_unicode_ci NOT NULL,
  `urutan` int NOT NULL,
  `tanggal` date NOT NULL,
  `company` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `coa` int NOT NULL,
  `no_coa` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_coa` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debit` decimal(18,0) NOT NULL,
  `credit` decimal(18,0) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_jurnal` (`id_invoice`,`jenis`,`urutan`),
  KEY `invoice_jurnal_coa_index` (`coa`),
  CONSTRAINT `invoice_jurnal_id_invoice_foreign` FOREIGN KEY (`id_invoice`) REFERENCES `invoice` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoice_peserta` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_invoice` int unsigned NOT NULL,
  `id_quotation_peserta` int NOT NULL,
  `nominal` decimal(18,0) NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_peserta` (`id_invoice`,`id_quotation_peserta`),
  KEY `invoice_peserta_id_quotation_peserta_index` (`id_quotation_peserta`),
  CONSTRAINT `invoice_peserta_id_invoice_foreign` FOREIGN KEY (`id_invoice`) REFERENCES `invoice` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `sub_module` varchar(80) DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `ref_table` varchar(50) DEFAULT NULL,
  `ref_id` int DEFAULT NULL,
  `ref_no` varchar(50) DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_module_action` (`module`,`action`),
  KEY `idx_ref` (`ref_table`,`ref_id`),
  KEY `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `master_customer` (
  `id_customer` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `id_category_customer` varchar(29) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `name_customer` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `jenis_instansi` varchar(50) DEFAULT 'Swasta',
  `telephone` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `telephone_2` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `fax` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `id_country` int DEFAULT NULL,
  `id_prov` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `id_city` varchar(10) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `address_office` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `zip_code` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `id_karyawan` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `id_reference` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `npwp` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `vat_name` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `npwp_address` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `start_date` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `receipt_time_1` varchar(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `receipt_time_2` varchar(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `reference` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `credit_limit` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `longitude` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `latitude` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `payment_req` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `pic_finance` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `day_invoice_receive` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `invoice_address` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `va_number` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `payment_category` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `payment_day` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `remarks` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `activation` varchar(254) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  `pic_name` varchar(100) DEFAULT NULL,
  `pic_jabatan` varchar(100) DEFAULT NULL,
  `pic_hp` varchar(20) DEFAULT NULL,
  `pic_email` varchar(100) DEFAULT NULL,
  `perlakuan_ppn` enum('PKP','WAPU','KB') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `master_program` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor` varchar(20) NOT NULL,
  `id_paket_produk` int NOT NULL,
  `durasi_hari` int NOT NULL DEFAULT '1',
  `jam_pembelajaran` int unsigned DEFAULT NULL,
  `target_peserta` int NOT NULL DEFAULT '10',
  `harga_jual_peserta` decimal(15,2) NOT NULL DEFAULT '0.00',
  `target_profit` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','final','batal') NOT NULL DEFAULT 'draft',
  `confirmed_by` int DEFAULT NULL,
  `confirmed_date` datetime DEFAULT NULL,
  `closed_by` int DEFAULT NULL,
  `closed_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor` (`nomor`),
  KEY `idx_mp_paket` (`id_paket_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `master_program_biaya` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_master_program` int NOT NULL,
  `id_rate` int DEFAULT NULL,
  `tipe` enum('fixed','variable') NOT NULL,
  `item` varchar(150) NOT NULL,
  `id_satuan` int DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_manual` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ms_group_akses` (
  `id_group` int NOT NULL,
  `id_menu` int NOT NULL,
  `aksi` varchar(10) NOT NULL,
  PRIMARY KEY (`id_group`,`id_menu`,`aksi`),
  KEY `idx_akses_menu` (`id_menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_jenis_bidang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  UNIQUE KEY `uk_ms_jenis_bidang_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_jenis_produk` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  UNIQUE KEY `uk_ms_jenis_produk_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_jenis_sertifikasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  UNIQUE KEY `uk_ms_jenis_sertifikasi_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL DEFAULT '0',
  `kode` varchar(60) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `link` varchar(150) NOT NULL DEFAULT '#',
  `ikon` varchar(30) DEFAULT NULL,
  `kelompok` int NOT NULL DEFAULT '1',
  `urutan` int NOT NULL DEFAULT '0',
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `sistem` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  KEY `idx_menu_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_menu_aksi` (
  `id_menu` int NOT NULL,
  `aksi` varchar(10) NOT NULL,
  PRIMARY KEY (`id_menu`,`aksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_peserta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `gender` enum('L','P') DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `id_jenis_bidang` int DEFAULT NULL,
  `id_prov` varchar(10) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `id_kab` varchar(10) DEFAULT NULL,
  `kabupaten` varchar(100) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `npwp_instansi` varchar(30) DEFAULT NULL,
  `id_customer` varchar(20) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_rate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `tipe` enum('fixed','variable') NOT NULL,
  `item` varchar(150) NOT NULL,
  `id_satuan` int NOT NULL,
  `default_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(150) DEFAULT NULL,
  `coa` varchar(20) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_satuan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` enum('unit','packing','rate_fixed','rate_variable') DEFAULT 'unit',
  `code` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `nama` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `deleted` enum('N','Y') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'N',
  `deleted_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `ms_template_keterangan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `kategori` varchar(20) NOT NULL,
  `teks` text NOT NULL,
  `berlaku_untuk` varchar(10) NOT NULL DEFAULT 'semua',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_template_konfirmasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(80) NOT NULL,
  `label_kegiatan` varchar(80) NOT NULL,
  `id_jenis_produk` int DEFAULT NULL,
  `isian` text,
  `pernyataan` text,
  `catatan` text,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_trainer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `alamat` text,
  `no_telp` varchar(50) DEFAULT NULL,
  `pend_smu` varchar(255) DEFAULT NULL,
  `pend_diploma` varchar(255) DEFAULT NULL,
  `pend_sarjana` varchar(255) DEFAULT NULL,
  `pend_pascasarjana` varchar(255) DEFAULT NULL,
  `bidang_keahlian` text,
  `pengalaman_lain` text,
  `cv_tempat` varchar(100) DEFAULT NULL,
  `cv_tanggal` date DEFAULT NULL,
  `nama_ttd` varchar(150) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `id_user` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  UNIQUE KEY `uk_ms_trainer_id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_trainer_lampiran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_trainer` int NOT NULL,
  `urutan` int NOT NULL DEFAULT '1',
  `dokumen` varchar(255) NOT NULL,
  `tahun` varchar(20) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_trainer_pelatihan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_trainer` int NOT NULL,
  `urutan` int NOT NULL DEFAULT '1',
  `judul` varchar(255) NOT NULL,
  `penyelenggara` varchar(150) DEFAULT NULL,
  `waktu` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_trainer_pengalaman_kerja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_trainer` int NOT NULL,
  `urutan` int NOT NULL DEFAULT '1',
  `jabatan` varchar(150) NOT NULL,
  `institusi` varchar(150) DEFAULT NULL,
  `tahun` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_trainer_pengalaman_mengajar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_trainer` int NOT NULL,
  `urutan` int NOT NULL DEFAULT '1',
  `topik` varchar(255) NOT NULL,
  `tempat` varchar(150) DEFAULT NULL,
  `tahun` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_user_akses` (
  `id_user` int NOT NULL,
  `id_menu` int NOT NULL,
  `aksi` varchar(10) NOT NULL,
  PRIMARY KEY (`id_user`,`id_menu`,`aksi`),
  KEY `idx_user_akses_menu` (`id_menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_user_karyawan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `tmp_lahir` varchar(50) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `division` varchar(50) DEFAULT NULL,
  `jabatan` varchar(30) DEFAULT NULL,
  `id_group` int DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `telp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `npwp` varchar(20) DEFAULT NULL,
  `tgl_masuk` date DEFAULT NULL,
  `tipe` varchar(20) DEFAULT NULL,
  `jobdesc` varchar(500) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `id_user` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  UNIQUE KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `ms_varian_pelaksanaan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktif` tinyint NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ms_varian_pelaksanaan_kode_unique` (`kode`),
  UNIQUE KEY `uk_ms_varian_pelaksanaan_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `paket_produk` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor` varchar(20) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `id_jenis_produk` int NOT NULL,
  `id_jenis_sertifikasi` int NOT NULL,
  `id_jenis_bidang` int NOT NULL,
  `id_varian_pelaksanaan` int DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `kombinasi_aktif` varchar(60) GENERATED ALWAYS AS ((case when (`aktif` = 1) then concat(`id_jenis_produk`,_utf8mb4'-',`id_jenis_sertifikasi`,_utf8mb4'-',`id_jenis_bidang`,_utf8mb4'-',ifnull(`id_varian_pelaksanaan`,0)) end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor` (`nomor`),
  UNIQUE KEY `uk_paket_kombinasi_aktif` (`kombinasi_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `paket_produk_lingkup` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_paket_produk` int NOT NULL,
  `urutan` int NOT NULL,
  `lingkup` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppl_paket` (`id_paket_produk`,`urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengajuan_biaya` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_penjualan` int NOT NULL,
  `tipe_biaya` enum('fixed','variable') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('kasbon','direct_payment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_rekening` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_rekening` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_by` int DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `paid_keterangan` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal` date NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `status` enum('menunggu','disetujui','paid','ditolak','batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `request_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `closed_by` int DEFAULT NULL,
  `closed_date` datetime DEFAULT NULL,
  `alasan_tolak` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengajuan_biaya_nomor_unique` (`nomor`),
  KEY `pengajuan_biaya_id_penjualan_index` (`id_penjualan`),
  KEY `pengajuan_biaya_request_by_index` (`request_by`),
  CONSTRAINT `pengajuan_biaya_id_penjualan_foreign` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan_program` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengajuan_biaya_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pengajuan` int NOT NULL,
  `id_overbudget_detail` int DEFAULT NULL,
  `id_penjualan_biaya` int DEFAULT NULL,
  `item` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pengajuan_biaya_detail_id_pengajuan_foreign` (`id_pengajuan`),
  KEY `pengajuan_biaya_detail_id_overbudget_detail_foreign` (`id_overbudget_detail`),
  KEY `pengajuan_biaya_detail_id_penjualan_biaya_foreign` (`id_penjualan_biaya`),
  CONSTRAINT `pengajuan_biaya_detail_id_overbudget_detail_foreign` FOREIGN KEY (`id_overbudget_detail`) REFERENCES `pengajuan_overbudget_detail` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pengajuan_biaya_detail_id_pengajuan_foreign` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan_biaya` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pengajuan_biaya_detail_id_penjualan_biaya_foreign` FOREIGN KEY (`id_penjualan_biaya`) REFERENCES `penjualan_program_biaya` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_pengajuan_sumber` CHECK (((`id_penjualan_biaya` is null) <> (`id_overbudget_detail` is null)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengajuan_biaya_lampiran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pengajuan` int NOT NULL,
  `nama_file` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pengajuan_biaya_lampiran_id_pengajuan_foreign` (`id_pengajuan`),
  CONSTRAINT `pengajuan_biaya_lampiran_id_pengajuan_foreign` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan_biaya` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengajuan_overbudget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_penjualan` int NOT NULL,
  `tipe_biaya` enum('fixed','variable') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `status` enum('menunggu','disetujui','ditolak','batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `request_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `closed_by` int DEFAULT NULL,
  `closed_date` datetime DEFAULT NULL,
  `alasan_tolak` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengajuan_overbudget_nomor_unique` (`nomor`),
  KEY `pengajuan_overbudget_id_penjualan_index` (`id_penjualan`),
  KEY `pengajuan_overbudget_request_by_index` (`request_by`),
  CONSTRAINT `pengajuan_overbudget_id_penjualan_foreign` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan_program` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengajuan_overbudget_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_overbudget` int NOT NULL,
  `id_penjualan_biaya` int DEFAULT NULL,
  `item` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pengajuan_overbudget_detail_id_overbudget_foreign` (`id_overbudget`),
  KEY `pengajuan_overbudget_detail_id_penjualan_biaya_foreign` (`id_penjualan_biaya`),
  CONSTRAINT `pengajuan_overbudget_detail_id_overbudget_foreign` FOREIGN KEY (`id_overbudget`) REFERENCES `pengajuan_overbudget` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `pengajuan_overbudget_detail_id_penjualan_biaya_foreign` FOREIGN KEY (`id_penjualan_biaya`) REFERENCES `penjualan_program_biaya` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `penjualan_program` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor` varchar(20) NOT NULL,
  `id_master_program` int NOT NULL,
  `jenis` enum('public','b2b') NOT NULL,
  `id_customer` varchar(20) DEFAULT NULL,
  `id_trainer` int DEFAULT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `waktu` varchar(50) DEFAULT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `durasi_hari` int NOT NULL DEFAULT '1',
  `target_peserta` int NOT NULL DEFAULT '10',
  `harga_jual_peserta` decimal(15,2) DEFAULT '0.00',
  `target_profit` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','final','batal') NOT NULL DEFAULT 'draft',
  `confirmed_by` int DEFAULT NULL,
  `confirmed_date` datetime DEFAULT NULL,
  `closed_by` int DEFAULT NULL,
  `closed_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `jam_pembelajaran` int DEFAULT NULL,
  `id_asisten` int DEFAULT NULL,
  `revisi` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor` (`nomor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `penjualan_program_biaya` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_penjualan` int NOT NULL,
  `id_rate` int DEFAULT NULL,
  `tipe` enum('fixed','variable') NOT NULL,
  `item` varchar(150) NOT NULL,
  `id_satuan` int DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_manual` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `penjualan_program_reschedule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_penjualan` int NOT NULL,
  `tgl_mulai_lama` date NOT NULL,
  `tgl_selesai_lama` date NOT NULL,
  `tgl_mulai_baru` date NOT NULL,
  `tgl_selesai_baru` date NOT NULL,
  `id_trainer_lama` int NOT NULL,
  `id_trainer_baru` int NOT NULL,
  `id_asisten_lama` int NOT NULL,
  `id_asisten_baru` int NOT NULL,
  `alasan` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `snapshot` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `penjualan_program_reschedule_request_id_unique` (`request_id`),
  KEY `penjualan_program_reschedule_id_penjualan_index` (`id_penjualan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `penjualan_program_reschedule_evidence` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_reschedule` int unsigned NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe_file` enum('jpg','png','doc','docx','pdf') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ukuran_byte` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `penjualan_program_reschedule_evidence_id_reschedule_foreign` (`id_reschedule`),
  CONSTRAINT `penjualan_program_reschedule_evidence_id_reschedule_foreign` FOREIGN KEY (`id_reschedule`) REFERENCES `penjualan_program_reschedule` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `program_survey` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_penjualan` int NOT NULL,
  `token` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('belum','buka','tutup') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `dibuka_by` int DEFAULT NULL,
  `dibuka_date` datetime DEFAULT NULL,
  `ditutup_by` int DEFAULT NULL,
  `ditutup_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_survey_id_penjualan_unique` (`id_penjualan`),
  UNIQUE KEY `program_survey_token_unique` (`token`),
  CONSTRAINT `program_survey_id_penjualan_foreign` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan_program` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quotation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor` varchar(30) NOT NULL,
  `id_penjualan` int NOT NULL,
  `tanggal` date NOT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `id_sales` int DEFAULT NULL,
  `sumber_info` varchar(30) DEFAULT NULL,
  `tujuan` enum('personal','company') NOT NULL DEFAULT 'personal',
  `id_customer` varchar(20) DEFAULT NULL,
  `up_nama` varchar(100) DEFAULT NULL,
  `alamat` text,
  `skema_harga` enum('peserta','allin') NOT NULL DEFAULT 'peserta',
  `harga_peserta` decimal(15,2) NOT NULL DEFAULT '0.00',
  `harga_allin` decimal(15,2) DEFAULT '0.00',
  `diskon` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_ppn` tinyint(1) NOT NULL DEFAULT '1',
  `ppn_persen` decimal(5,2) NOT NULL DEFAULT '11.00',
  `biaya_lain` decimal(15,2) NOT NULL DEFAULT '0.00',
  `id_template_konfirmasi` int DEFAULT NULL,
  `konfirmasi_isian` text,
  `konfirmasi_pernyataan` text,
  `status` enum('draft','terkirim','deal','batal') NOT NULL DEFAULT 'draft',
  `sent_by` int DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL,
  `deal_by` int DEFAULT NULL,
  `deal_date` datetime DEFAULT NULL,
  `closed_by` int DEFAULT NULL,
  `closed_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `kepada_nama` varchar(100) DEFAULT NULL,
  `dokumen_keterangan` varchar(200) DEFAULT NULL,
  `kuota_peserta` int NOT NULL DEFAULT '1',
  `link_token` varchar(40) DEFAULT NULL,
  `link_dibuat` datetime DEFAULT NULL,
  `paket_maks_peserta` int DEFAULT NULL,
  `tambahan_per_peserta` decimal(15,2) DEFAULT NULL,
  `kolom_peserta_b2b` json DEFAULT NULL,
  `revisi` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor` (`nomor`),
  UNIQUE KEY `uk_quotation_link_token` (`link_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `quotation_dokumen` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_quotation` int NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe_file` enum('pdf','jpg','png','doc','docx','xls','xlsx') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ukuran_byte` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_dokumen_id_quotation_index` (`id_quotation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quotation_keterangan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_quotation` int NOT NULL,
  `id_template_keterangan` int DEFAULT NULL,
  `kategori` varchar(20) NOT NULL,
  `teks` text NOT NULL,
  `urutan` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `quotation_konfirmasi_company` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_quotation` int NOT NULL,
  `revisi` int NOT NULL DEFAULT '0',
  `nama_perusahaan` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_perusahaan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_jabatan` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_hp` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah_peserta` int NOT NULL,
  `surat_konfirmasi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surat_konfirmasi_nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tgl_kirim` datetime NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_qkc_quotation_revisi` (`id_quotation`,`revisi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quotation_peserta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_quotation` int NOT NULL,
  `id_peserta` int DEFAULT NULL,
  `status` enum('menunggu','deal','batal','keluar') NOT NULL DEFAULT 'menunggu',
  `sumber` enum('link','manual','company') NOT NULL DEFAULT 'link',
  `tgl_isi` datetime DEFAULT NULL,
  `input_by` int DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `no_wa` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pengalaman` varchar(100) DEFAULT NULL,
  `lsp` varchar(100) DEFAULT NULL,
  `surat_konfirmasi` varchar(255) DEFAULT NULL,
  `surat_konfirmasi_nama` varchar(150) DEFAULT NULL,
  `alamat_instansi` text,
  `isian_tambahan` text,
  `tgl_habis_sertifikat` date DEFAULT NULL,
  `keluar_jenis` varchar(20) DEFAULT NULL,
  `keluar_alasan` varchar(500) DEFAULT NULL,
  `keluar_revisi` int DEFAULT NULL,
  `keluar_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `keluar_date` datetime DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qp_quotation` (`id_quotation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `quotation_peserta_konfirmasi_ulang` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_quotation_peserta` int NOT NULL,
  `id_reschedule` int unsigned NOT NULL,
  `revisi` int NOT NULL,
  `keputusan` enum('pindah_jadwal','batal','refund','tidak') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surat_konfirmasi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surat_konfirmasi_nama` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sumber` enum('link','company','sales') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_isi` datetime DEFAULT NULL,
  `diproses_by` int DEFAULT NULL,
  `diproses_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_qku_peserta_revisi` (`id_quotation_peserta`,`revisi`),
  KEY `quotation_peserta_konfirmasi_ulang_id_reschedule_foreign` (`id_reschedule`),
  KEY `quotation_peserta_konfirmasi_ulang_id_quotation_peserta_index` (`id_quotation_peserta`),
  CONSTRAINT `quotation_peserta_konfirmasi_ulang_id_reschedule_foreign` FOREIGN KEY (`id_reschedule`) REFERENCES `penjualan_program_reschedule` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quotation_peserta_sertifikat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_quotation_peserta` int NOT NULL,
  `no_sertifikat` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_terbit` date DEFAULT NULL,
  `file_sertifikat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_kirim` enum('belum','soft','hard') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `tgl_kirim` date DEFAULT NULL,
  `kirim_via` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lsp` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skema` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metode` enum('ukom','porto') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hasil` enum('belum','kompeten','belum_kompeten') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `no_sertifikat_bnsp` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_terima_bnsp` date DEFAULT NULL,
  `tgl_kirim_bnsp` date DEFAULT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `draft_kirim_date` date DEFAULT NULL,
  `id_card` enum('belum','sudah') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `file_sertifikat_bnsp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan_monitoring` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_sertifikat` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_konfirmasi_by` int DEFAULT NULL,
  `nama_konfirmasi_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_peserta_sertifikat_id_quotation_peserta_unique` (`id_quotation_peserta`),
  CONSTRAINT `quotation_peserta_sertifikat_id_quotation_peserta_foreign` FOREIGN KEY (`id_quotation_peserta`) REFERENCES `quotation_peserta` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quotation_peserta_surat_riwayat` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_quotation_peserta` int NOT NULL,
  `surat_konfirmasi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surat_konfirmasi_nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sumber` enum('reschedule','ganti_sales') COLLATE utf8mb4_unicode_ci NOT NULL,
  `alasan` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_peserta_surat_riwayat_id_quotation_peserta_index` (`id_quotation_peserta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quotation_revisi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_quotation` int NOT NULL,
  `id_reschedule` int unsigned NOT NULL,
  `revisi` int NOT NULL,
  `link_token_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_qrev_quotation_revisi` (`id_quotation`,`revisi`),
  UNIQUE KEY `quotation_revisi_link_token_hash_unique` (`link_token_hash`),
  KEY `quotation_revisi_id_reschedule_foreign` (`id_reschedule`),
  KEY `quotation_revisi_id_quotation_index` (`id_quotation`),
  CONSTRAINT `quotation_revisi_id_reschedule_foreign` FOREIGN KEY (`id_reschedule`) REFERENCES `penjualan_program_reschedule` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nomor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_quotation` int NOT NULL,
  `id_penjualan` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('open','siap') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `siap_by` int DEFAULT NULL,
  `siap_date` datetime DEFAULT NULL,
  `revisi` int NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_so_nomor` (`nomor`),
  UNIQUE KEY `uk_so_quotation` (`id_quotation`),
  KEY `sales_order_id_penjualan_index` (`id_penjualan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_order_refund` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nomor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `id_sales_order` int unsigned NOT NULL,
  `id_quotation_peserta` int DEFAULT NULL,
  `revisi` int NOT NULL,
  `id_invoice` int unsigned NOT NULL,
  `id_konfirmasi_ulang` int unsigned NOT NULL,
  `kunci_penerima` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal_tagihan` decimal(18,0) NOT NULL,
  `status` enum('menunggu','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `nominal_refund` decimal(18,0) DEFAULT NULL,
  `bank` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_rekening` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atas_nama` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_transfer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_transfer_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alasan` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `selesai_by` int DEFAULT NULL,
  `selesai_date` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refund_invoice_penerima` (`id_invoice`,`kunci_penerima`),
  UNIQUE KEY `sales_order_refund_nomor_unique` (`nomor`),
  KEY `sales_order_refund_id_sales_order_foreign` (`id_sales_order`),
  KEY `sales_order_refund_id_konfirmasi_ulang_foreign` (`id_konfirmasi_ulang`),
  KEY `sales_order_refund_id_quotation_peserta_index` (`id_quotation_peserta`),
  CONSTRAINT `sales_order_refund_id_invoice_foreign` FOREIGN KEY (`id_invoice`) REFERENCES `invoice` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_order_refund_id_konfirmasi_ulang_foreign` FOREIGN KEY (`id_konfirmasi_ulang`) REFERENCES `quotation_peserta_konfirmasi_ulang` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `sales_order_refund_id_sales_order_foreign` FOREIGN KEY (`id_sales_order`) REFERENCES `sales_order` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `surat_kerja_trainer` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nomor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_penjualan` int NOT NULL,
  `id_trainer` int NOT NULL,
  `id_asisten` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('berlaku','diganti') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'berlaku',
  `id_reschedule` int DEFAULT NULL,
  `program_berlaku` int GENERATED ALWAYS AS ((case when (`status` = _utf8mb4'berlaku') then `id_penjualan` else NULL end)) STORED,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `snapshot` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `surat_kerja_trainer_nomor_unique` (`nomor`),
  UNIQUE KEY `uk_skt_program_berlaku` (`program_berlaku`),
  KEY `surat_kerja_trainer_id_penjualan_index` (`id_penjualan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `survey_jawaban` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_program_survey` int NOT NULL,
  `id_quotation_peserta` int NOT NULL,
  `tgl_isi` datetime NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_survey_peserta` (`id_program_survey`,`id_quotation_peserta`),
  KEY `survey_jawaban_id_quotation_peserta_foreign` (`id_quotation_peserta`),
  CONSTRAINT `survey_jawaban_id_program_survey_foreign` FOREIGN KEY (`id_program_survey`) REFERENCES `program_survey` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `survey_jawaban_id_quotation_peserta_foreign` FOREIGN KEY (`id_quotation_peserta`) REFERENCES `quotation_peserta` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `survey_jawaban_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_survey_jawaban` int NOT NULL,
  `id_survey_pertanyaan` int NOT NULL,
  `pertanyaan_teks` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` int DEFAULT NULL,
  `jawaban` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_survey_jawaban_detail` (`id_survey_jawaban`,`id_survey_pertanyaan`),
  KEY `survey_jawaban_detail_id_survey_pertanyaan_foreign` (`id_survey_pertanyaan`),
  CONSTRAINT `survey_jawaban_detail_id_survey_jawaban_foreign` FOREIGN KEY (`id_survey_jawaban`) REFERENCES `survey_jawaban` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `survey_jawaban_detail_id_survey_pertanyaan_foreign` FOREIGN KEY (`id_survey_pertanyaan`) REFERENCES `survey_pertanyaan` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `survey_pertanyaan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `urutan` int NOT NULL,
  `bagian` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teks` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` enum('skala','pilihan','teks') COLLATE utf8mb4_unicode_ci NOT NULL,
  `opsi` json DEFAULT NULL,
  `wajib` tinyint(1) NOT NULL DEFAULT '1',
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_groups` (
  `id_user` int NOT NULL,
  `id_group` int NOT NULL,
  PRIMARY KEY (`id_user`,`id_group`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `password` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `nm_lengkap` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `alamat` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `kota` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `hp` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `kdcab` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '100',
  `work_location` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `group_id` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `login_terakhir` datetime DEFAULT NULL,
  `st_aktif` tinyint(1) DEFAULT '1' COMMENT '0 = Tidak Aktif\n1 = Aktif',
  `photo` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `department_id` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `employee_id` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `ga_secret` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  PRIMARY KEY (`id_user`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS=1;
INSERT INTO migrations (migration,batch) VALUES ('2026_11_13_100000_master_data_program_to_be',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_11_13_110000_ms_peserta_to_be',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_11_13_120000_ms_trainer_id_user',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_11_13_130000_penjualan_quotation_to_be',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_11_14_090000_sales_order_invoice',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_11_14_100000_reschedule_refund',1);
INSERT INTO migrations (migration,batch) VALUES ('2026_11_14_110000_operasional_biaya',1);

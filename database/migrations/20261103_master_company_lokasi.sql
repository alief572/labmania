-- Migration: nama Provinsi & Kota pada Master Company (FR-31, FR-32 kolom Lokasi)
-- Tanggal: 03-11-2026
-- Acuan: specs/final/ERD Master - Quotation.drawio (customer: provinsi, kota); pola sama dengan ms_peserta (id_prov + provinsi, id_kab + kabupaten).
-- `id_prov` / `id_city` tetap menyimpan kode wilayah (dipakai cascading dropdown); kolom baru menyimpan namanya untuk daftar.
-- Data lama: kolom kosong sampai company di-edit dan disimpan ulang.
-- `id_city` semula CHAR(4); kode kabupaten/kota API wilayah berbentuk "33.01" (5 karakter) dan terpotong, jadi dilebarkan.

ALTER TABLE `master_customer`
  MODIFY COLUMN `id_city` VARCHAR(10) NULL,
  ADD COLUMN `provinsi` VARCHAR(100) NULL AFTER `id_city`,
  ADD COLUMN `kota` VARCHAR(100) NULL AFTER `provinsi`;

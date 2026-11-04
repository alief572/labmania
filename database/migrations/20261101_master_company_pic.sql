-- Migration: kolom PIC pada Master Company (FR-31, UC-11)
-- Tanggal: 01-11-2026
-- Acuan: specs/final/ERD Master - Quotation.drawio (customer: pic_name, pic_jabatan, pic_hp, pic_email)
-- Tabel `master_customer` bawaan aplikasi belum punya kolom ini; tanpa kolom ini INSERT/UPDATE company gagal.

ALTER TABLE `master_customer`
  ADD COLUMN `pic_name` VARCHAR(100) NULL,
  ADD COLUMN `pic_jabatan` VARCHAR(100) NULL,
  ADD COLUMN `pic_hp` VARCHAR(20) NULL,
  ADD COLUMN `pic_email` VARCHAR(100) NULL;

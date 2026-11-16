-- Migration: skema Master Trainer disamakan dengan ERD (FR-45, ms_trainer_pengalaman_mengajar, ms_trainer_lampiran)
-- Tanggal: 03-11-2026
-- Acuan: specs/final/ERD Master - Quotation.drawio
--   ms_trainer_pengalaman_mengajar: kolom `waktu` -> `tahun`
--   ms_trainer_lampiran: id, id_trainer, urutan, dokumen VARCHAR(255), tahun VARCHAR(20) NULL, file_path VARCHAR(255) NULL
-- Kolom `created_date` pada ms_trainer_lampiran tidak ada di ERD; dibiarkan (tidak dipakai lagi oleh kode).
-- Saat migrasi dibuat, kedua tabel berisi 0 baris.

ALTER TABLE `ms_trainer_pengalaman_mengajar`
  CHANGE COLUMN `waktu` `tahun` VARCHAR(50) NULL;

ALTER TABLE `ms_trainer_lampiran`
  MODIFY COLUMN `dokumen` VARCHAR(255) NOT NULL,
  MODIFY COLUMN `file_path` VARCHAR(255) NULL,
  ADD COLUMN `urutan` INT NOT NULL DEFAULT 1 AFTER `id_trainer`,
  ADD COLUMN `tahun` VARCHAR(20) NULL AFTER `dokumen`;

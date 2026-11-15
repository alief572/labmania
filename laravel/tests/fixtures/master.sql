-- Fixture sintetis SQLite dari metadata salinan labmania_laravel (13-11-2026). Bukan migrasi produksi.
-- Tipe disesuaikan ke SQLite; tidak membuktikan semantik MySQL (kolasi, FK, lock).

CREATE TABLE "ms_jenis_produk" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_jenis_produk_kode" ON "ms_jenis_produk" ("kode");
CREATE UNIQUE INDEX "ms_jenis_produk_uk_ms_jenis_produk_nama" ON "ms_jenis_produk" ("nama");

CREATE TABLE "ms_jenis_sertifikasi" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_jenis_sertifikasi_kode" ON "ms_jenis_sertifikasi" ("kode");
CREATE UNIQUE INDEX "ms_jenis_sertifikasi_uk_ms_jenis_sertifikasi_nama" ON "ms_jenis_sertifikasi" ("nama");

CREATE TABLE "ms_jenis_bidang" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_jenis_bidang_kode" ON "ms_jenis_bidang" ("kode");
CREATE UNIQUE INDEX "ms_jenis_bidang_uk_ms_jenis_bidang_nama" ON "ms_jenis_bidang" ("nama");

CREATE TABLE "ms_varian_pelaksanaan" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_varian_pelaksanaan_ms_varian_pelaksanaan_kode_unique" ON "ms_varian_pelaksanaan" ("kode");
CREATE UNIQUE INDEX "ms_varian_pelaksanaan_uk_ms_varian_pelaksanaan_nama" ON "ms_varian_pelaksanaan" ("nama");

CREATE TABLE "paket_produk" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nomor" TEXT NOT NULL,
  "judul" TEXT NOT NULL,
  "id_jenis_produk" INTEGER NOT NULL,
  "id_jenis_sertifikasi" INTEGER NOT NULL,
  "id_jenis_bidang" INTEGER NOT NULL,
  "id_varian_pelaksanaan" INTEGER,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT,
  "kombinasi_aktif" TEXT GENERATED ALWAYS AS ((case when (aktif = 1) then (id_jenis_produk || '-' || id_jenis_sertifikasi || '-' || id_jenis_bidang || '-' || ifnull(id_varian_pelaksanaan,0)) end)) STORED
);
CREATE UNIQUE INDEX "paket_produk_nomor" ON "paket_produk" ("nomor");
CREATE UNIQUE INDEX "paket_produk_uk_paket_kombinasi_aktif" ON "paket_produk" ("kombinasi_aktif");

CREATE TABLE "paket_produk_lingkup" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_paket_produk" INTEGER NOT NULL,
  "urutan" INTEGER NOT NULL,
  "lingkup" TEXT NOT NULL,
  "created_by" INTEGER,
  "created_date" TEXT
);
CREATE INDEX "paket_produk_lingkup_idx_ppl_paket" ON "paket_produk_lingkup" ("id_paket_produk","urutan");

CREATE TABLE "ms_peserta" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "gender" TEXT,
  "instansi" TEXT,
  "id_jenis_bidang" INTEGER,
  "id_prov" TEXT,
  "provinsi" TEXT,
  "id_kab" TEXT,
  "kabupaten" TEXT,
  "no_telp" TEXT,
  "email" TEXT,
  "nik" TEXT,
  "npwp_instansi" TEXT,
  "id_customer" TEXT,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT,
  "deleted_by" INTEGER,
  "deleted_date" TEXT
);
CREATE UNIQUE INDEX "ms_peserta_kode" ON "ms_peserta" ("kode");

CREATE TABLE "master_customer" (
  "perlakuan_ppn" TEXT DEFAULT NULL CHECK ("perlakuan_ppn" IN ('PKP', 'WAPU', 'KB')),
  "id_customer" TEXT,
  "id_category_customer" TEXT,
  "name_customer" TEXT,
  "jenis_instansi" TEXT DEFAULT 'Swasta',
  "telephone" TEXT,
  "telephone_2" TEXT,
  "fax" TEXT,
  "email" TEXT,
  "id_country" INTEGER,
  "id_prov" TEXT,
  "id_city" TEXT,
  "provinsi" TEXT,
  "kota" TEXT,
  "address_office" TEXT,
  "zip_code" TEXT,
  "id_karyawan" TEXT,
  "id_reference" TEXT,
  "npwp" TEXT,
  "vat_name" TEXT,
  "npwp_address" TEXT,
  "start_date" TEXT,
  "receipt_time_1" TEXT,
  "receipt_time_2" TEXT,
  "reference" TEXT,
  "credit_limit" TEXT,
  "longitude" TEXT,
  "latitude" TEXT,
  "payment_req" TEXT,
  "pic_finance" TEXT,
  "day_invoice_receive" TEXT,
  "invoice_address" TEXT,
  "va_number" TEXT,
  "payment_category" TEXT,
  "payment_day" TEXT,
  "remarks" TEXT,
  "activation" TEXT,
  "created_by" INTEGER,
  "created_on" TEXT,
  "modified_by" INTEGER,
  "modified_on" TEXT,
  "deleted_by" INTEGER,
  "deleted_date" TEXT,
  "pic_name" TEXT,
  "pic_jabatan" TEXT,
  "pic_hp" TEXT,
  "pic_email" TEXT
);

CREATE TABLE "ms_rate" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "tipe" TEXT NOT NULL,
  "item" TEXT NOT NULL,
  "id_satuan" INTEGER NOT NULL,
  "default_cost" NUMERIC NOT NULL DEFAULT 0.00,
  "keterangan" TEXT,
  "coa" TEXT,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_rate_kode" ON "ms_rate" ("kode");

CREATE TABLE "ms_satuan" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "category" TEXT DEFAULT 'unit',
  "code" TEXT,
  "nama" TEXT,
  "created_by" TEXT,
  "created_date" TEXT,
  "updated_by" TEXT,
  "updated_date" TEXT,
  "deleted" TEXT DEFAULT 'N',
  "deleted_by" TEXT,
  "deleted_date" TEXT
);

CREATE TABLE "coa_master" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "no_perkiraan" TEXT NOT NULL DEFAULT '',
  "nama" TEXT NOT NULL DEFAULT '',
  "kdcab" TEXT NOT NULL DEFAULT '',
  "saldoawal" NUMERIC NOT NULL DEFAULT 0,
  "bln" INTEGER NOT NULL DEFAULT 0,
  "thn" INTEGER NOT NULL DEFAULT 0,
  "debet" NUMERIC DEFAULT 0,
  "kredit" NUMERIC DEFAULT 0,
  "tmp" TEXT,
  "tipe" TEXT DEFAULT 0,
  "level" TEXT,
  "grup" TEXT,
  "faktor" INTEGER,
  "cf_grup" TEXT,
  "cf_urut" INTEGER,
  "cf_child" TEXT,
  "prev_month" NUMERIC,
  "kode_bank" TEXT
);
CREATE UNIQUE INDEX "coa_master_NO" ON "coa_master" ("kdcab","no_perkiraan","bln","thn","level");
CREATE UNIQUE INDEX "coa_master_nomor" ON "coa_master" ("no_perkiraan","kdcab","bln","thn","level");

CREATE TABLE "master_program" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nomor" TEXT NOT NULL,
  "durasi_hari" INTEGER NOT NULL DEFAULT 1,
  "jam_pembelajaran" INTEGER,
  "target_peserta" INTEGER NOT NULL DEFAULT 10,
  "harga_jual_peserta" NUMERIC NOT NULL DEFAULT 0.00,
  "target_profit" NUMERIC DEFAULT 0.00,
  "status" TEXT NOT NULL DEFAULT 'draft',
  "confirmed_by" INTEGER,
  "confirmed_date" TEXT,
  "closed_by" INTEGER,
  "closed_date" TEXT,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "master_program_nomor" ON "master_program" ("nomor");

CREATE TABLE "master_program_produk" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_master_program" INTEGER NOT NULL,
  "id_paket_produk" INTEGER NOT NULL,
  "urutan" INTEGER NOT NULL,
  "created_by" INTEGER,
  "created_date" TEXT
);
CREATE UNIQUE INDEX "master_program_produk_uk_mpp_program_paket" ON "master_program_produk" ("id_master_program","id_paket_produk");
CREATE UNIQUE INDEX "master_program_produk_uk_mpp_program_urutan" ON "master_program_produk" ("id_master_program","urutan");

CREATE TABLE "master_program_biaya" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_master_program" INTEGER NOT NULL,
  "id_rate" INTEGER,
  "tipe" TEXT NOT NULL,
  "item" TEXT NOT NULL,
  "id_satuan" INTEGER,
  "harga" NUMERIC NOT NULL DEFAULT 0.00,
  "is_manual" INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE "ms_trainer" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "tempat_lahir" TEXT,
  "tgl_lahir" TEXT,
  "alamat" TEXT,
  "no_telp" TEXT,
  "pend_smu" TEXT,
  "pend_diploma" TEXT,
  "pend_sarjana" TEXT,
  "pend_pascasarjana" TEXT,
  "bidang_keahlian" TEXT,
  "pengalaman_lain" TEXT,
  "cv_tempat" TEXT,
  "cv_tanggal" TEXT,
  "nama_ttd" TEXT,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "id_user" INTEGER,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_trainer_kode" ON "ms_trainer" ("kode");
CREATE UNIQUE INDEX "ms_trainer_uk_ms_trainer_id_user" ON "ms_trainer" ("id_user");

CREATE TABLE "ms_trainer_pelatihan" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_trainer" INTEGER NOT NULL,
  "urutan" INTEGER NOT NULL DEFAULT 1,
  "judul" TEXT NOT NULL,
  "penyelenggara" TEXT,
  "waktu" TEXT
);

CREATE TABLE "ms_trainer_pengalaman_kerja" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_trainer" INTEGER NOT NULL,
  "urutan" INTEGER NOT NULL DEFAULT 1,
  "jabatan" TEXT NOT NULL,
  "institusi" TEXT,
  "tahun" TEXT
);

CREATE TABLE "ms_trainer_pengalaman_mengajar" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_trainer" INTEGER NOT NULL,
  "urutan" INTEGER NOT NULL DEFAULT 1,
  "topik" TEXT NOT NULL,
  "tempat" TEXT,
  "tahun" TEXT
);

CREATE TABLE "ms_trainer_lampiran" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_trainer" INTEGER NOT NULL,
  "urutan" INTEGER NOT NULL DEFAULT 1,
  "dokumen" TEXT NOT NULL,
  "tahun" TEXT,
  "file_path" TEXT,
  "created_date" TEXT
);

CREATE TABLE "ms_template_keterangan" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "kategori" TEXT NOT NULL,
  "teks" TEXT NOT NULL,
  "berlaku_untuk" TEXT NOT NULL DEFAULT 'semua',
  "is_default" INTEGER NOT NULL DEFAULT 0,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_template_keterangan_kode" ON "ms_template_keterangan" ("kode");

CREATE TABLE "ms_template_konfirmasi" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "label_kegiatan" TEXT NOT NULL,
  "id_jenis_produk" INTEGER,
  "isian" TEXT,
  "pernyataan" TEXT,
  "catatan" TEXT,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_template_konfirmasi_kode" ON "ms_template_konfirmasi" ("kode");

CREATE TABLE "ms_user_karyawan" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "kode" TEXT NOT NULL,
  "nama" TEXT NOT NULL,
  "nik" TEXT,
  "tmp_lahir" TEXT,
  "tgl_lahir" TEXT,
  "department" TEXT,
  "division" TEXT,
  "jabatan" TEXT,
  "id_group" INTEGER,
  "gender" TEXT,
  "agama" TEXT,
  "telp" TEXT,
  "email" TEXT,
  "alamat" TEXT,
  "npwp" TEXT,
  "tgl_masuk" TEXT,
  "tipe" TEXT,
  "jobdesc" TEXT,
  "aktif" INTEGER NOT NULL DEFAULT 1,
  "id_user" INTEGER,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "ms_user_karyawan_id_user" ON "ms_user_karyawan" ("id_user");
CREATE UNIQUE INDEX "ms_user_karyawan_kode" ON "ms_user_karyawan" ("kode");

CREATE TABLE "penjualan_program" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nomor" TEXT NOT NULL,
  "id_master_program" INTEGER NOT NULL,
  "jenis" TEXT NOT NULL,
  "id_customer" TEXT,
  "id_trainer" INTEGER,
  "tgl_mulai" TEXT,
  "tgl_selesai" TEXT,
  "waktu" TEXT,
  "lokasi" TEXT,
  "durasi_hari" INTEGER NOT NULL DEFAULT 1,
  "target_peserta" INTEGER NOT NULL DEFAULT 10,
  "harga_jual_peserta" NUMERIC DEFAULT 0.00,
  "target_profit" NUMERIC DEFAULT 0.00,
  "status" TEXT NOT NULL DEFAULT 'draft',
  "confirmed_by" INTEGER,
  "confirmed_date" TEXT,
  "closed_by" INTEGER,
  "closed_date" TEXT,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "penjualan_program_nomor" ON "penjualan_program" ("nomor");

CREATE TABLE "quotation" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nomor" TEXT NOT NULL,
  "id_penjualan" INTEGER NOT NULL,
  "tanggal" TEXT NOT NULL,
  "berlaku_sampai" TEXT,
  "id_sales" INTEGER,
  "sumber_info" TEXT,
  "tujuan" TEXT NOT NULL DEFAULT 'personal',
  "id_customer" TEXT,
  "up_nama" TEXT,
  "alamat" TEXT,
  "skema_harga" TEXT NOT NULL DEFAULT 'peserta',
  "harga_peserta" NUMERIC NOT NULL DEFAULT 0.00,
  "harga_allin" NUMERIC DEFAULT 0.00,
  "diskon" NUMERIC NOT NULL DEFAULT 0.00,
  "is_ppn" INTEGER NOT NULL DEFAULT 1,
  "ppn_persen" NUMERIC NOT NULL DEFAULT 11.00,
  "biaya_lain" NUMERIC NOT NULL DEFAULT 0.00,
  "id_template_konfirmasi" INTEGER,
  "konfirmasi_isian" TEXT,
  "konfirmasi_pernyataan" TEXT,
  "status" TEXT NOT NULL DEFAULT 'draft',
  "sent_by" INTEGER,
  "sent_date" TEXT,
  "deal_by" INTEGER,
  "deal_date" TEXT,
  "closed_by" INTEGER,
  "closed_date" TEXT,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);
CREATE UNIQUE INDEX "quotation_nomor" ON "quotation" ("nomor");

CREATE TABLE "quotation_peserta" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "id_quotation" INTEGER NOT NULL,
  "id_peserta" INTEGER NOT NULL,
  "status" TEXT NOT NULL DEFAULT 'menunggu'
);
CREATE UNIQUE INDEX "quotation_peserta_uk_quotation_peserta" ON "quotation_peserta" ("id_quotation","id_peserta");


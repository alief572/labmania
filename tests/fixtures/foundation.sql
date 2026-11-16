-- Fixture SQLite dari metadata schema-only 13-11-2026; bukan migrasi produksi.

CREATE TABLE "users" (
  "id_user" INTEGER PRIMARY KEY AUTOINCREMENT,
  "username" TEXT,
  "password" TEXT,
  "email" TEXT,
  "nm_lengkap" TEXT,
  "alamat" TEXT,
  "kota" TEXT,
  "hp" TEXT,
  "kdcab" TEXT,
  "work_location" TEXT,
  "group_id" TEXT,
  "ip" TEXT,
  "login_terakhir" TEXT,
  "st_aktif" INTEGER,
  "photo" TEXT,
  "created_on" TEXT,
  "deleted" INTEGER,
  "department_id" TEXT,
  "employee_id" TEXT,
  "ga_secret" TEXT
);

CREATE TABLE "user_groups" (
  "id_user" INTEGER NOT NULL,
  "id_group" INTEGER NOT NULL,
  PRIMARY KEY ("id_user", "id_group")
);

CREATE TABLE "groups" (
  "id_group" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nm_group" TEXT NOT NULL,
  "ket" TEXT NOT NULL,
  "st_default" INTEGER,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);

CREATE TABLE "ms_menu" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "parent_id" INTEGER NOT NULL,
  "kode" TEXT NOT NULL,
  "judul" TEXT NOT NULL,
  "link" TEXT NOT NULL,
  "ikon" TEXT,
  "kelompok" INTEGER NOT NULL,
  "urutan" INTEGER NOT NULL,
  "aktif" INTEGER NOT NULL,
  "sistem" INTEGER NOT NULL,
  "created_by" INTEGER,
  "created_date" TEXT,
  "updated_by" INTEGER,
  "updated_date" TEXT
);

CREATE TABLE "ms_menu_aksi" (
  "id_menu" INTEGER NOT NULL,
  "aksi" TEXT NOT NULL,
  PRIMARY KEY ("id_menu", "aksi")
);

CREATE TABLE "ms_group_akses" (
  "id_group" INTEGER NOT NULL,
  "id_menu" INTEGER NOT NULL,
  "aksi" TEXT NOT NULL,
  PRIMARY KEY ("id_group", "id_menu", "aksi")
);

CREATE TABLE "ms_user_akses" (
  "id_user" INTEGER NOT NULL,
  "id_menu" INTEGER NOT NULL,
  "aksi" TEXT NOT NULL,
  PRIMARY KEY ("id_user", "id_menu", "aksi")
);

CREATE TABLE "logs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "module" TEXT NOT NULL,
  "sub_module" TEXT,
  "action" TEXT NOT NULL,
  "description" TEXT NOT NULL,
  "ref_table" TEXT,
  "ref_id" INTEGER,
  "ref_no" TEXT,
  "data" TEXT,
  "created_by" INTEGER NOT NULL,
  "created_date" TEXT NOT NULL,
  "ip_address" TEXT
);

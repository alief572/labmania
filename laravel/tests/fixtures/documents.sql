-- Fixture SQLite, nama/kolom sesuai ERD Quotation dan 20261112_to_be_quotation_so.sql.
CREATE TABLE quotation_dokumen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_quotation INTEGER NOT NULL,
    nama_file TEXT NOT NULL,
    path_file TEXT NOT NULL,
    tipe_file TEXT NOT NULL,
    ukuran_byte INTEGER NOT NULL,
    created_by INTEGER,
    created_date TEXT
);

CREATE TABLE penjualan_program_reschedule_evidence (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_reschedule INTEGER NOT NULL,
    nama_file TEXT NOT NULL,
    path_file TEXT NOT NULL,
    tipe_file TEXT NOT NULL,
    ukuran_byte INTEGER NOT NULL
);

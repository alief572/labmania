# Master Trainer (format CV trainer), dipilih di Penjualan Program

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat + file di shared input
- **Contents:**
  - pesan verbatim
  - `input/Master Trainer Ivan Sarifudin.xlsx` — contoh data trainer, 5 sheet:
    - *Identitas Trainer* — ID Trainer (kosong), Nama lengkap, Tempat & tanggal lahir, Alamat rumah, No. telepon rumah/HP, Pendidikan formal (SMU/sederajat, Diploma, Sarjana, Pascasarjana), Bidang keahlian (dipisah `;`), Pengalaman lainnya, Tanggal & tempat pengesahan CV, Nama pada tanda tangan
    - *Pelatihan dan Seminar* — No., Judul Pelatihan/Seminar/Lainnya, Penyelenggara/Tempat, Waktu (tahun) — 57 baris
    - *Pengalaman Kerja* — No., Jabatan, Institusi/Perusahaan, Tahun (rentang, mis. "2018–sekarang") — 9 baris
    - *Pengalaman Mengajar* — No., Topik Pembelajaran, Tempat, Tahun — 61 baris
    - *Lampiran Dokumen* — No., Dokumen / Sertifikat Lampiran, Tahun — 10 baris
  - Excel berisi data pribadi trainer (alamat, telepon). Mockup memakai nama dan riwayatnya, tetapi alamat/telepon/tanggal lahir diganti contoh.

## Verbatim

> aku ada input excel dengan nama file Master Trainer Ivan Sarifudin. tolong buatkan master trainer dengan ketentuan seperti yang ada di excel tersebut, master trainer ini akan ada di saat penjualan program. jadi select trainer gitu

## Catatan penafsiran

- Master Trainer = data trainer dengan isi seperti Excel: satu bagian identitas + empat daftar riwayat (pelatihan & seminar, pengalaman kerja, pengalaman mengajar, lampiran dokumen).
- "ID Trainer" kosong di Excel → dibuat otomatis seperti kode master lain (format Q-01) `[USULAN]`.
- "Tempat, tanggal lahir" dipecah menjadi Tempat lahir dan Tanggal lahir; Bidang keahlian menjadi daftar (satu per baris) `[USULAN]`.
- Lampiran Dokumen: tiap baris dapat diberi file unggahan (Excel hanya berisi nama & tahun) `[USULAN]`.
- Penjualan Program memilih **satu** Trainer dari Master Trainer aktif — 1 event = 1 trainer (`input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` butir 1). Opsional atau wajib belum dinyatakan → opsional `[USULAN]`.
- Seperti master lain: tanpa hapus, hanya Aktifkan/Nonaktifkan; trainer nonaktif tidak muncul di pilihan Penjualan Program `[USULAN]`.
- Belum dijelaskan: siapa pengguna yang mengelola, field wajib selain Nama, dan apakah Honor Trainer (Master Rate) berbeda per trainer.

## Extracted

- FR-44 – FR-47, UC-38, bagian 4.12 -> docs/fr-nfr.md (draf 11l)
- PJ-FR-23 -> docs/fr-nfr-penjualan-program.md
- ERD halaman "ERD Master Trainer" + `penjualan_program.id_trainer` -> docs/diagrams/erd.drawio (draf 10)
- mockup -> ui-mockup/produk/app.js (draf 11q)

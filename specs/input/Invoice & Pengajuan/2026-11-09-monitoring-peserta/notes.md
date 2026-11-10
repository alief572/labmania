# Monitoring Peserta di modul Manage Peserta (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim; acuan isi file `input/Data Peserta In House Training, Public Training, Training dan Sertifikasi 2026.xlsx` (sudah ada di `input/`, tidak disalin)
- **Supersedes:** tidak ada; menambah sub menu di `docs/fr-nfr-manage-peserta.md` → `docs-to-be/fr-nfr-manage-peserta.md`.

## Verbatim

> nah sekarang kamu liat di input ada data excel dengan format file "Data Peserta In House Training, Public Training, Training dan Sertifikasi 2026" nah dari labmania mau ada monitoring peserta, kurang lebih isinya seperti itu. coba buatkan ya. lokasinya nanti di menu "Manage Peserta" buatkan ada sub menu "Monitoring Peserta"

## Isi file acuan (ringkas)

| Sheet | Kolom utama |
|---|---|
| RECAP DATA SERTIFIKASI 2026 | Tanggal Sertifikasi, Nama, Instansi, Email, No HP, Skema, LSP, Hasil |
| RECAP IHT 2026 | Tanggal Pelatihan, PIC, Sales, Judul Pelatihan, Nama Instansi, Jumlah Peserta, status pembayaran, Sertifikat pelatihan, Noted |
| RECAP Pendampingan 2026 | Tanggal, PIC, Sales, Judul, Nama Instansi, Jumlah Peserta, status pembayaran |
| RECAP DATA RESERTIFIKASI 2026 | Nama, Instansi, Jabatan, NIK KTP, Email, WA, Judul, Tanggal Pelaksanaan, Pembayaran ke LSP, Pembayaran, Note, PIC, EXPIRED Sertifikat, Sertifikat Tiba, Sertifikat Dikirim, Id card |
| Data Peserta TS Februari | Nama, Instansi, Jabatan, NIK KTP, Email, WA, Judul, Tanggal, Tipe (TS/T/S), Ukom/Porto, Pembayaran, Online/Offline, PIC, Nama/Kontak PIC Customer, Tanggal Bayar, No Invoice, sertifikat pelatihan, Penerimaan BNSP, Pengiriman BNSP |

## Catatan penafsiran

1. Monitoring = **satu baris per peserta Deal**, menggabungkan data yang sudah ada di sistem (peserta dari link, program, PIC/Sales, nomor invoice) dengan **status tindak lanjut** yang diperbarui staf: pembayaran, sertifikat pelatihan, sertifikasi BNSP (metode, LSP, skema, hasil, penerimaan, pengiriman), ID card, masa berlaku, catatan.
2. Tipe (Pelatihan / Sertifikasi / Training & Sertifikasi) diambil dari Jenis Produk Product di Master Program, menggantikan kolom "Tipe (TS/T/S)".
3. Rekap per program (sheet IHT, Pendampingan) dan pemisahan per sheet belum dibuat; ditanyakan (Q-MON-01, Q-MON-03). Status pembayaran sementara manual (Q-MON-02).

## Extracted

- MON-FR-01 – MON-FR-06, UC-66, UC-67, Q-MON-01 – Q-MON-05 -> `docs-to-be/fr-nfr-manage-peserta.md` (dokumen to be baru)
- ERD `quotation_peserta_monitoring` -> `docs-to-be/diagrams/erd.drawio` halaman *ERD Quotation*
- Mockup -> `ui-mockup/produk-to-be/mod-manage-peserta.js`, `core.js` (menu), `main.js` (aksi), `style.css`

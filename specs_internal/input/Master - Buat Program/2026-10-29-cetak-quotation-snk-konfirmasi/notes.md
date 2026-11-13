# Cetak Quotation = PDF Penawaran + Syarat & Ketentuan + Formulir Konfirmasi

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat + file di shared input
- **Contents:**
  - pesan verbatim
  - `input/Template Penawaran Labmania.pdf` — template surat penawaran, 1 halaman, kode `FR/LMI-TR-01/01/Ed.Rev-1.1`, berlaku 01/01/2025:
    - kop PT LabMania Indonesia Jaya (alamat Ruby Commercial TC 25 Summarecon Bekasi)
    - Kepada: nama instansi/perusahaan, alamat
    - blok "Penawaran": Nomor `LM-I/AM/PW/MM/YY-XXX`, Tanggal, Pembayaran `[Cash In Advance/Termin/Tempo]`
    - tabel: No Catalog `[KODE]`, Item `[Produk]`, Deskripsi `[Durasi] [Tanggal] [Waktu] [Tempat]`, Kts. `[QTY]`, Harga `[SATUAN]`, Diskon `[PERSENTASE]`, Total Harga
    - Keterangan: "Up. Ibu/Bapak …"; Catatan 1–3; "Biaya sudah termasuk mendapatkan: Materi, Sertifikat pelatihan, Pajak"; dua kalimat persetujuan (tanda tangan & S&K)
    - ringkasan: Sub Total, Diskon, PPN (11%), Biaya Lain-lain, Total
    - tanda tangan: tanggal + Divisi Pembuat Penawaran + nama (contoh: Ismaya Krisdawati); "Disetujui oleh Pelanggan" + Nama
  - `input/SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN.pdf` — hasil pindai, 2 halaman, kode `DP/LMI-TR-01/01/Ed.Rev-1.1`, berlaku 21/10/2024, disahkan Operational Director (Ivan Sarifudin), Bekasi 21 Oktober 2024. Tujuh bagian: (1) Pembatalan Kepesertaan, Penarikan dan Pengembalian Uang, (2) Pembayaran, (3) Penggantian Barang Rusak/Hilang, (4) Kerahasiaan, (5) Sertifikat Pelatihan, (6) Lisensi, (7) Umpan Balik, Saran dan Keluhan.
  - `input/Tamplate Formulir Konfirmasi Workshop.doc` — formulir 1 halaman, kode `FR/LMI-TR-01/02/Ed.Rev-1.0`, berlaku 01/08/2023, judul "Konfirmasi Keikutsertaan Peserta":
    - isian: Nama Lengkap Peserta* (acuan sertifikat), NIK, Jabatan, Nama Instansi, Alamat Instansi, Nomor WhatsApp, Alamat Email Aktif, Masa Pengalaman Bekerja Sesuai Skema
    - pernyataan: (1) akan hadir pada kegiatan "<judul>" tanggal <tanggal> pukul <jam> WIB (<n> hari training offline); (2) telah membaca & menyetujui S&K LabMania
    - tempat/tanggal, tanda tangan, Jabatan
    - catatan: kirim kembali ke email/nomor marketing (contoh ismaya@labmaniaindonesia.id, 0812-8148-1385) dengan subject "Konfirmasi Peserta kegiatan Workshop dengan judul <judul>"; dihitung dari pengiriman tercepat; panitia berhak menolak bila kuota penuh; peserta setuju tidak membatalkan; maksimal diserahkan H-14; kontak diskusi.

## Verbatim

> saat cetak quotation akan ada pdf quotation beserta S&K dan konfirmasi.
>
> untuk S&K bisa lihat di file "SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN"
> untuk konfirmasi bisa lihat di file "Tamplate Formulir Konfirmasi Workshop"
> untuk quotationnya bisa lihat di file "Template Penawaran Labmania"

## Catatan penafsiran

- Aksi **Kirim dan Cetak Quotation** menghasilkan **satu PDF** berisi tiga bagian berurutan: (1) Penawaran, (2) Syarat & Ketentuan, (3) Formulir Konfirmasi Keikutsertaan.
- **Nomor quotation** mengikuti template: `LM-I/AM/PW/MM/YY-XXX` (menggantikan usulan `QT-YYMM-NNN`, Q-QTN-01). Arti segmen `AM` belum dijelaskan; mockup memakainya tetap.
- Field baru di quotation yang dituntut template: **Pembayaran** (Cash In Advance / Termin / Tempo), **Biaya Lain-lain** (Rp), **Catatan** (maks. 3 butir) `[USULAN]` (isian; template hanya menunjukkan tempatnya).
- **Waktu** acara (jam, mis. "09.00–15.15 WIB") dibutuhkan Penawaran dan Formulir Konfirmasi, tetapi belum ada di data → diusulkan sebagai field Penjualan Program `[USULAN]`.
- Kolom **Diskon** di template berupa persentase, sedangkan keputusan 29-10-2026 (`2026-10-29-quotation-diskon-aksi/` butir 3) diskon **Rupiah saja** → kolom diisi Rupiah per peserta `[USULAN]`.
- Penanda tangan penawaran = **Sales / marketing** quotation (Master User), "Divisi Pembuat Penawaran" = Department/Divisi user tersebut; kontak di Formulir Konfirmasi = email & telepon sales tersebut `[USULAN]`.
- Formulir Konfirmasi: **satu halaman per peserta** quotation, terisi dari Master Peserta (Nama, Instansi, Alamat Instansi, WhatsApp, Email); NIK, Jabatan, Masa Pengalaman dikosongkan untuk diisi peserta `[USULAN]`.
- S&K poin 1 menyatakan in-house training tidak memerlukan formulir konfirmasi; apakah penjualan B2B selalu in-house belum jelas (pertanyaan).
- **Confirmation Letter** (aksi setelah Deal, format menyusul — Q-QTN-05) dibaca memakai format Formulir Konfirmasi ini, untuk peserta Deal `[USULAN]`.
- S&K dicetak sebagai teks tetap sesuai dokumen (versi `Ed.Rev-1.1`) `[USULAN]`.

## Extracted

- QTN-FR-04, QTN-FR-08, QTN-FR-10, QTN-FR-13, QTN-FR-14 – QTN-FR-16 (baru), bagian 7 -> docs/fr-nfr-quotation.md (draf 3)
- PJ-FR-24 (Waktu acara) -> docs/fr-nfr-penjualan-program.md; ERD `penjualan_program.waktu`
- mockup -> ui-mockup/produk/app.js (draf 11r)

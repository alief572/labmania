# Tiga template Formulir Konfirmasi — dibuat seperti Keterangan (master + default + dapat diubah + preview)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat + file di shared input
- **Contents:**
  - pesan verbatim (dua pesan)
  - `input/Tamplate Formulir Konfirmasi Workshop.doc` (sudah ada sejak `2026-10-29-cetak-quotation-snk-konfirmasi/`)
  - `input/Template Formulir Konfirmasi Training Sertifikasi .doc` (baru)
  - `input/Template Formulir Konfirmasi Resertifikasi.doc` (baru)
  - Ketiganya dibaca lewat konversi Microsoft Word read-only ke scratchpad; file asli tidak diubah.

## Perbandingan

| | Workshop | Training Sertifikasi | Resertifikasi |
|---|---|---|---|
| Isian | Nama Lengkap Peserta*, NIK, Jabatan, Nama Instansi, Alamat Instansi, Nomor WhatsApp, Alamat Email Aktif, Masa Pengalaman Bekerja Sesuai Skema | sama dengan Workshop | Nama Lengkap Peserta*, NIK, Jabatan, Nama Instansi, Alamat Instansi, **Nomor Hp**, Nomor WhatsApp, Alamat Email Aktif, **LSP**, **Tanggal Habis Sertifikat** |
| Pernyataan 1 | Saya **akan hadir** pada kegiatan "Workshop Validasi dan Verifikasi Metode Pengujian Kimia" yang akan diselenggarakan pada tanggal 28-29 April 2025 pukul 09.00-15.15 WIB (2 hari training offline) | Saya **akan hadir** pada kegiatan "Training dan Sertifikasi Laboran Kimia" … tanggal 3-6 Maret 2025 pukul 09.00-15.15 WIB (3 hari training online dan 1 hari sertifikasi online) | Saya **akan mengikuti** pada kegiatan **Resertifikasi K3 Laboratorium** dan menyiapkan dokumen yang dibutuhkan dalam rentang waktu resertifikasi **(H-3 Bulan)** — tanpa tanggal/waktu |
| Subject email (catatan 1) | Konfirmasi Peserta kegiatan **Workshop** dengan judul … | … kegiatan **Training dan Sertifikasi** dengan judul … | … kegiatan **Reertifikasi** [sic] dengan judul … |
| Pernyataan 2, catatan lain, kop, kode `FR/LMI-TR-01/02/Ed.Rev-1.0` | sama | sama | sama |

## Verbatim

> dan ternyata ada template yang berbeda juga terkait formulir konfirmasi, baiknya gimana ya? kamu liat ya file yang judulnya "Template Formulir konfirmasi" ada 3

> oke bagus dan sama halnya dengan yang formulir konfirmasi deh, udah aku masukin ke input tuh contoh templatenya

## Catatan penafsiran

- "Sama halnya" = pola Keterangan quotation (`2026-10-29-keterangan-quotation-default/`, `2026-10-29-keterangan-ux-preview/`): master berisi template, nilai default otomatis masuk quotation, dapat disesuaikan per quotation, terlihat di Preview.
- Master **Template Formulir Konfirmasi**: tiga template awal (Workshop, Training & Sertifikasi, Resertifikasi), masing-masing berisi label kegiatan untuk subject email, daftar isian, kalimat pernyataan dengan penanda isian otomatis ({judul}, {tanggal}, {waktu}, {durasi}, {lokasi}), dan catatan `[USULAN]` (bentuk penyimpanan).
- Pemilihan template default per quotation: dari **Jenis Produk** paket (Pelatihan → Workshop, Sertifikasi → Training & Sertifikasi); Resertifikasi dipilih manual — belum ada atribut produk untuk resertifikasi `[USULAN]` (pertanyaan).
- Per quotation (langkah baru "Formulir Konfirmasi"): ganti template, centang isian, ubah kalimat pernyataan; catatan tidak tampil di form (dari master, seperti Klausul tanda tangan) `[USULAN]`.
- Isian yang ada di Master Peserta terisi otomatis (nama, instansi, alamat, WhatsApp, email); lainnya dikosongkan untuk diisi peserta. Nomor Hp dikosongkan karena Master Peserta hanya punya satu nomor (Telp/WhatsApp) `[USULAN]`.
- Contoh "3 hari training online dan 1 hari sertifikasi online" tidak dapat diturunkan dari data (Penjualan Program hanya punya durasi total) → teks pernyataan dapat diubah per quotation.

## Extracted

- FR-51 – FR-53, UC-40, bagian 4.14, Q-31, Q-32 -> docs/fr-nfr.md (draf 11n)
- QTN-FR-13, QTN-FR-16, QTN-FR-18, QTN-FR-19 (baru) -> docs/fr-nfr-quotation.md (draf 6)
- ERD `ms_template_konfirmasi` -> docs/diagrams/erd.drawio
- mockup -> ui-mockup/produk/app.js (draf 11u)

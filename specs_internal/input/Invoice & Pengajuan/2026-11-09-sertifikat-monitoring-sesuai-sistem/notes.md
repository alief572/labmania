# Monitoring Peserta disesuaikan dengan data sistem; tab Sertifikat di Manage Peserta (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** sebagian `input/Invoice & Pengajuan/2026-11-09-monitoring-peserta/` (isian status pembayaran dan sertifikat di Monitoring Peserta).

## Verbatim

> terkait monitoring peserta, boleh disesuaikan dengan yang udah ada ga? misal.. tab pelatihan gt kenapa koson? lalu kan di sistem ini belum ada tuh menu buat sertifikat gitu, tambahin menu atau tab di manage peserta, lalu isian update di monitoring, ada sertif LSP dsbnya padahal saat ini ya belum ada

## Catatan penafsiran

1. **Tab Pelatihan kosong** karena data contoh: semua program Final memakai Master Program yang berisi Product Pelatihan + Sertifikasi. Data contoh mockup dibenahi (Master Program B2B kembali satu Product Pelatihan; contoh multi Product dipindah ke Master Program baru untuk program Public). Bukan perubahan aturan.
2. **Tempat sertifikat**: tab baru **Sertifikat** di halaman peserta program (Manage Peserta › Lihat Peserta), untuk input sertifikat pelatihan dan sertifikasi LSP/BNSP per peserta, termasuk unggah file.
3. **Monitoring disesuaikan dengan yang sudah ada**: Monitoring hanya membaca data sistem (peserta, program, PIC, invoice, sertifikat dari tab Sertifikat). Isian "Update Monitoring" (status bayar, LSP, dsb.) dihapus; yang tersisa hanya **catatan**. Status bayar diganti nomor invoice / Uninvoiced karena pelunasan belum ada di modul Invoice (Q-MON-02).

## Extracted

- SRT-FR-01, SRT-FR-02, MON-FR-02 – MON-FR-05, UC-67, Q-MON-02, Q-MON-04, Q-MON-06, Q-MON-07 -> `docs-to-be/fr-nfr-manage-peserta.md`
- ERD `quotation_peserta_sertifikat` (menggantikan `quotation_peserta_monitoring`) -> `docs-to-be/diagrams/erd.drawio` halaman *ERD Quotation*
- Mockup -> `ui-mockup/produk-to-be/mod-manage-peserta.js`, `main.js`, `core.js` (data contoh Master Program)

# Tanggal print out dokumen = hari dicetak; Confirmation Letter = tanggal peserta mengisi link (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tanggal cetak di `docs-to-be/fr-nfr-quotation.md` QTN-FR-14 (Tanggal quotation) dan `docs-to-be/fr-nfr-sales-order.md` SO-FR-06, SO-FR-14 (tanggal SO / surat).

## Verbatim

> untuk tanggal yang ada setiap prin out dokumen (quotation, surat kerja, SO) itu dibuat ke per hari ini. untuk konfirmasi letter tanggal sesuai dengan user mengisi link generate nya ya

## Catatan penafsiran

1. "Per hari ini" = **tanggal dokumen dicetak / di-print out**. Berlaku untuk tanggal yang tercetak di PDF **Quotation** (Penawaran: baris Tanggal dan tanggal di atas tanda tangan), **Sales Order** (Tanggal dan tanda tangan), dan **Surat Kerja Trainer** (Tanggal dan "Bekasi, ...").
2. Tanggal yang tersimpan di data (Tanggal quotation, tanggal SO, tanggal surat) **tidak berubah**: tetap tampil di daftar dan detail, dan tetap dipakai untuk nomor dokumen dan Berlaku sampai. Hanya tanggal yang tercetak yang mengikuti hari cetak.
3. **Confirmation Letter**: tanggal = waktu calon peserta **mengisi link Konfirmasi Kehadiran**. Peserta yang ditambah manual oleh Sales memakai waktu input (tidak ada isian link): `[USULAN]`.

## Extracted

- QTN-FR-30 -> `docs-to-be/fr-nfr-quotation.md`
- SO-FR-15 -> `docs-to-be/fr-nfr-sales-order.md`
- Mockup -> `ui-mockup/produk-to-be/mod-quotation.js`, `mod-sales-order.js`

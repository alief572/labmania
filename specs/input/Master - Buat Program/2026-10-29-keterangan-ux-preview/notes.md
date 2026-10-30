# Keterangan quotation dibuat lebih rapi + preview sebelum cetak

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat (keluhan + permintaan, usulan analis, persetujuan)
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tampilan blok Keterangan di form quotation `docs/fr-nfr-quotation.md` draf 4 QTN-FR-17 (lima kotak berisi textarea, dropdown "dari master", tombol "Tulis" — `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`).

## Verbatim

> balik ke yang keterangan dan catatan sebelumnya. aku sudah liat tapi menurut aku ga rapih. menurut kamu gimana supaya user experiencenya bisa lebih simple dan rapih ya? lalu mau ada previewnya sebelum benar-benar di cetak

Analis mengusulkan: (1) form quotation 3 langkah — Data Quotation → Keterangan → Preview; (2) Keterangan berupa daftar centang per kategori yang dapat dilipat (butir Default tercentang, "+ Tambah teks lain", ✎ untuk ubah teks, ringkasan "x dari y dipilih"); Klausul tanda tangan tidak tampil di form, diatur dari master; (3) preview PDF di langkah 3 sebelum disimpan, dan label "Preview — belum dikirim" di aksi Kirim dan Cetak. Ditanyakan: Klausul disembunyikan dari form atau tetap dapat diubah per quotation.

> boleh setuju, boleh dibuatin dulu mock up nya

## Catatan penafsiran

- Usulan disetujui seluruhnya. Pertanyaan Klausul tidak dijawab secara khusus → ikut usulan: **Klausul tidak tampil di form**, dicetak dari master `[USULAN]`.

## Extracted

- QTN-FR-10, QTN-FR-17, QTN-FR-18 (baru) -> docs/fr-nfr-quotation.md (draf 5)
- mockup -> ui-mockup/produk/app.js (draf 11t)

# Link Konfirmasi Kehadiran: template surat konfirmasi untuk dicetak dan ditandatangani basah, unggahan wajib (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-10
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (khusus to be):** tanda tangan Confirmation Letter diganti keterangan "Dikonfirmasi oleh peserta lewat link" (`docs-to-be/fr-nfr-quotation.md` QTN-FR-16); Confirmation Letter yang dilihat Sales = dokumen yang dibentuk sistem dari isian (QTN-FR-13, QTN-FR-28, QTN-FR-29); tanggal Confirmation Letter = waktu peserta mengisi link (QTN-FR-30, dari `input/Master - Buat Program/2026-11-09-tanggal-print-out/`). Menjawab Q-TB-05 (tanda tangan basah).

## Verbatim

> sama ini di link konfirmasi dan pengisian, adain template konfirmasi untuk calon peserta print dan ttd, lalu ada field untuk peserta upload si surat hard copy nya. tujuannya karena dibutuhkan ttd basah. saat ini kan tanpa ttd nya.
>
> yang bisa di view oleh sales/marketing adalah uploadan surat konfirmasi calon peserta. lalu disaat calon peserta isi link konfirmasi, upload dokumen konfirmasi letter itu dibuat wajib ya

## Catatan penafsiran

1. Di halaman link Konfirmasi Kehadiran ada **template surat konfirmasi** (format Formulir Konfirmasi Keikutsertaan `FR/LMI-TR-01/02`, template formulir quotation) yang dicetak calon peserta. Surat **sudah terisi data diri yang diketik di link**, ruang tanda tangan dibiarkan kosong untuk **tanda tangan basah** `[USULAN]` (terisi, bukan kosong).
2. Calon peserta menandatangani surat cetak itu, memotret/memindai, lalu **mengunggahnya di link**. **Unggahan wajib**: konfirmasi tidak dapat dikirim tanpa berkas.
3. **Sales/marketing melihat surat yang diunggah** (bukan dokumen yang dibentuk sistem): Lihat dan Unduh per peserta di tab Data Peserta quotation, detail batch Data Peserta, dan View Sales Order. Export PDF batch memuat surat unggahan tiap peserta `[USULAN]`.
4. Tanggal di atas tanda tangan pada template = **tanggal surat dicetak** (sejalan dengan aturan tanggal = tanggal print, `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`); tempat diisi tangan `[USULAN]`.
5. Berkas: satu berkas PDF, JPG, atau PNG, maksimal 5 MB `[USULAN]` (Q-TB-09). Dari ponsel, peserta dapat langsung memotret.

## Extracted

- UC-56, UC-37, UC-57, QTN-FR-13, QTN-FR-16, QTN-FR-25, QTN-FR-26, QTN-FR-28, QTN-FR-29, QTN-FR-30, QTN-NFR-01, QTN-NFR-05, QTN-NFR-08, QTN-NFR-09, Q-TB-05, Q-TB-09 -> `docs-to-be/fr-nfr-quotation.md`
- SO-FR-05 -> `docs-to-be/fr-nfr-sales-order.md`
- ERD to be `quotation_peserta.surat_konfirmasi`, `surat_konfirmasi_nama` -> `docs-to-be/diagrams/erd.drawio`
- `docs-to-be/README.md`
- Mockup -> `ui-mockup/produk-to-be/konfirmasi.html`, `mod-quotation.js`, `mod-sales-order.js`, `style.css`

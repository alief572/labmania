# Unggah dokumen dari customer saat Deal, tampil di Sales Order (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim (dua pesan berurutan), tanpa lampiran
- **Supersedes:** tidak ada; menambah isian di popup Deal (`docs-to-be/fr-nfr-quotation.md` QTN-FR-11) dan bagian View Sales Order (`docs-to-be/fr-nfr-sales-order.md` SO-FR-05).

## Verbatim

> lalu pihak labmania mau ada tempat menampung dokumen yang diberikan peserta gitu, kayak PO, itu letaknya di modul apa ya?

(Analis mengusulkan: dokumen customer seperti PO di Sales Order; dokumen pribadi peserta per peserta di Data Peserta.)

> nah boleh tuh, saat mau deal ada upload dokumen dulu.. ga hanya PO tapi dokumen papaun ituuu. nanti bisa dilihat di SO karena quotation bisa menjadi SO apabila sudah deal kan

## Catatan penafsiran

1. Saat menandai **Deal**, Sales **wajib mengunggah dokumen dari customer** dulu. Jenisnya bebas: PO, SPK, surat penunjukan, bukti bayar, atau dokumen lain ("dokumen apapun"). Minimal satu file, boleh lebih dari satu.
2. Dokumen disimpan pada quotation dan **tampil di View Sales Order** (SO terbentuk otomatis saat Deal), juga di detail quotation (bagian Dokumen Deal).
3. Tipe file PDF, JPG, PNG, DOC/DOCX, XLS/XLSX; maks. 5 MB per file; Keterangan dokumen opsional (mis. nomor PO): `[USULAN]` (Q-QTN-20). Belum diputuskan: boleh menambah dokumen setelah Deal.
4. Dokumen pribadi per peserta (KTP, ijazah) **tidak** termasuk request ini.

## Extracted

- UC-35, QTN-FR-11, Q-QTN-20 -> `docs-to-be/fr-nfr-quotation.md`
- SO-FR-05 -> `docs-to-be/fr-nfr-sales-order.md`
- ERD `quotation_dokumen`, `quotation.dokumen_keterangan` -> `docs-to-be/diagrams/erd.drawio` halaman *ERD Quotation*
- Mockup -> `ui-mockup/produk-to-be/mod-quotation.js`, `mod-sales-order.js`, `style.css`

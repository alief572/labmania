# Aksi quotation tanpa "Lihat Sales Order" dan "Confirmation Letter" (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** sebagian `docs-to-be/fr-nfr-quotation.md` QTN-FR-09 (aksi Lihat Sales Order dan Confirmation Letter di menu quotation).

## Verbatim

> nah sama di quotation itu gausah ada action "Lihat Sales Order" kan bisa lihat langsung ke menu Sales Order. lalu action confirmation letter juga gausah ada, soalnya kan udah ada tab "Data Peserta"

## Catatan penafsiran

1. Aksi **Lihat Sales Order** dan **Confirmation Letter** dihapus dari menu gear quotation dan dari tombol panel Detail Quotation. Tombol "Lihat Sales Order" di bagian Invoice detail quotation ikut dihapus karena alasannya sama.
2. Confirmation Letter tetap ada sebagai dokumen; dibuka dari tab **Data Peserta** (tombol Confirmation Letter (semua), Lihat/PDF per peserta). Sales Order dibuka dari menu **Sales Order**.
3. Setelah Deal, tombol utama panel detail menjadi **Cetak Quotation** (satu-satunya aksi yang tersisa).

## Extracted

- QTN-FR-09, QTN-FR-26, UC-37, bagian 6 -> `docs-to-be/fr-nfr-quotation.md`
- Mockup -> `ui-mockup/produk-to-be/mod-quotation.js`

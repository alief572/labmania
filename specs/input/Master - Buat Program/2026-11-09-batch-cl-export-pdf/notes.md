# Detail batch Data Peserta: Confirmation Letter per peserta, Export PDF menggantikan Export Excel (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** `input/Master - Buat Program/2026-11-09-tab-data-peserta-batch-detail/` butir penafsiran 3 (Export Excel di detail batch).

## Verbatim

> tiap peserta bisa view confirmation letter nya ya, terus export to excel ganti ke export to pdf yang dimana bisa narik si confirmation letter juga ya

## Catatan penafsiran

1. Halaman detail batch (tab Data Peserta di Daftar Quotation): tiap baris peserta punya aksi **Confirmation Letter** yang membuka pratinjau Confirmation Letter peserta itu. Peserta quotation B2B (tanpa Confirmation Letter, Q-TB-03) membuka halaman **Data Peserta** sebagai gantinya.
2. **Export Excel diganti Export PDF**. Isi PDF: halaman 1 **Daftar Peserta** batch (No, Nama Lengkap, Instansi, Kontak, Quotation, Status), lalu **Confirmation Letter tiap peserta** (B2B: halaman Data Peserta), urut sesuai daftar.
3. Peserta Batal tidak ikut Export PDF: `[USULAN]` (Q-QTN-19).

## Extracted

- QTN-FR-29, Q-QTN-19 -> `docs-to-be/fr-nfr-quotation.md`
- Mockup -> `ui-mockup/produk-to-be/mod-quotation.js`, `main.js`, `style.css`

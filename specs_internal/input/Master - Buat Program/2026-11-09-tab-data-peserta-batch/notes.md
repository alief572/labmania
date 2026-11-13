# Tab Data Peserta per batch Penjualan Program di Daftar Quotation, dengan Export Excel (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tidak ada; menambah tab di `docs-to-be/fr-nfr-quotation.md` QTN-FR-01.

## Verbatim

> nah terus yang data peserta per quotation better dibuat ke tab aja, kan di quotation ada tab "On Going" lalu ada tab "Selesai" nah tambah tab "Data Peserta" dia perbacth si penjualan program gitu ya. lalu ada export to excel juga

## Catatan penafsiran

1. Daftar Quotation mendapat tab ketiga **Data Peserta** di samping On going dan Selesai.
2. Isi tab dikelompokkan **per batch Penjualan Program**: satu kelompok per Penjualan Program Final, berisi peserta dari semua quotation-nya (isian link dan tambah manual), dengan nomor quotation tiap peserta.
3. **Export Excel** per batch: semua peserta batch itu dalam satu file.
4. Tab mencakup batch On going dan Selesai; peserta quotation Batal ikut tampil dengan status Batal dan dapat disaring: `[USULAN]` (Q-QTN-19).
5. Tab Data Peserta di detail quotation **tetap ada** (tempat tambah manual, Preview, Confirmation Letter): `[USULAN]` (Q-QTN-19). Pesan tidak menyebut tab itu dihapus.

## Extracted

- QTN-FR-29, Q-QTN-19 -> `docs-to-be/fr-nfr-quotation.md`
- Mockup -> `ui-mockup/produk-to-be/mod-quotation.js`, `main.js`, `style.css`

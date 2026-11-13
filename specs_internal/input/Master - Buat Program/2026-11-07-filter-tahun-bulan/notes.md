# Filter tahun dan bulan di Penjualan Program, Quotation, dan Sales Order

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-07
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tidak ada (tambahan)

## Verbatim

> nah tambahan ya, di menu penjualan program, quotation, dan SO ada filter tahun dan bulan, agar sales & marketing atau user yang menggunakan bisa mencari program lebih mudah karena bisa sangat banyak nantinya

## Catatan penafsiran

1. Filter **Tahun** dan **Bulan** ditambahkan di daftar **Penjualan Program**, **Quotation** (daftar per program), dan **Sales Order** (tab Sales Order dan tab Surat Kerja Trainer). Tujuannya: memudahkan mencari **program** saat datanya sudah banyak.
2. Acuan tanggal = **tanggal mulai acara** Penjualan Program `[USULAN]` (bukan tanggal quotation/SO), karena yang dicari adalah program.
3. Pilihan tahun = tahun yang ada di data; bulan = Januari – Desember; keduanya punya "Semua" dan dapat dipakai bersama filter lain dan pencarian `[USULAN]`.
4. Program tanpa tanggal (masih Draft) hanya tampil bila tahun dan bulan = Semua `[USULAN]`.
5. Filter kembali ke "Semua" saat pindah menu `[USULAN]`.
6. Dibuat di **to be** (mockup `ui-mockup/produk-to-be/`, `docs-to-be/`), mengikuti percakapan.

## Extracted

- `docs-to-be/fr-nfr-penjualan-program.md` PJ-FR-01; `docs-to-be/fr-nfr-quotation.md` QTN-FR-01; `docs-to-be/fr-nfr-sales-order.md` SO-FR-02, SO-FR-11
- mockup `core.js` (ymFilter), `main.js`, `mod-program.js`, `mod-quotation.js`, `mod-sales-order.js`

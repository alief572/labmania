# Detail batch Quotation tanpa kartu, ada tombol Create Quotation

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tampilan detail di `docs/fr-nfr-quotation.md` draf 2 QTN-FR-02 (kartu ringkasan program dengan bar progres, kartu alur Draft › Terkirim › Deal | Batal) — usulan analis mengikuti `input/labmania.html`.

## Verbatim

> di modul quotation, saat klik batchnya maka tampilannya gausah ada card-card lagi, tapi ada tombol create quotation terhadap batch itu

## Catatan penafsiran

- "Batch" = satu Penjualan Program di index Quotation (tombol Lihat detail).
- Di detail batch: kartu alur status dan kartu ringkasan program (bar progres) dihapus. Info program tetap tampil sebagai teks di bawah judul halaman `[USULAN]`.
- Tombol **+ Create Quotation** di detail batch membuka form quotation dengan Penjualan Program batch itu terisi. Untuk B2B yang sudah punya quotation aktif, tombol tampil tetapi nonaktif (Q-QTN-08) `[USULAN]`.
- Filter status yang dulu lewat kartu pindah ke dropdown status `[USULAN]`.
- Index (daftar batch) tidak diubah — kartu alur di index tetap.

## Extracted

- QTN-FR-02 -> docs/fr-nfr-quotation.md (draf 2a)
- mockup -> ui-mockup/produk/app.js (draf 11p)

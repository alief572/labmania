# Katalog Produk (to be): input Lingkup per produk, banyak item, tampil di PDF

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-07
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tidak ada. Menambah `input/Master - Buat Program/2026-11-05-katalog-produk/` (Q-36: isi tambahan per paket di katalog) untuk **to be**.

## Verbatim

> lalu di katalog produk itu sudah benar tapi ada proses didalamnya dimana nanti bisa input lingkup dari di product nya dan di print outnya juga bisa lihat isian lingkupnya, buat pas proses isi lingkupnya itu bisa lebih dari 1 item

Konteks sesi: pesan ini menyusul pembahasan dokumen to be (`input/Master - Buat Program/2026-11-07-dokumen-to-be-terpisah/`).

## Catatan penafsiran

1. Tampilan Katalog Produk yang ada sudah benar; yang ditambah adalah proses **isi Lingkup** per Paket Produk.
2. Lingkup diisi **dari halaman Katalog Produk** (tombol di kartu tiap paket) `[USULAN]`. Belum ada isian Lingkup di form Product Combine.
3. Satu paket dapat punya **lebih dari satu item** Lingkup (daftar teks, satu baris per item, urutan sesuai input). Batas 20 item dan 200 karakter per item `[USULAN]`. Item kembar dalam satu paket ditolak `[USULAN]`.
4. PDF katalog menampilkan Lingkup di kartu paket. Paket tanpa Lingkup tetap tampil, tanpa blok Lingkup `[USULAN]`.
5. Dibuat di **to be** (mockup `ui-mockup/produk-to-be/`, `docs-to-be/`), mengikuti percakapan yang sedang membahas to be. As is tidak diubah `[USULAN]`.
6. Belum jelas: Lingkup juga perlu tampil di Penawaran/Quotation atau Master Program? Siapa yang boleh mengisi (Q-06)?

## Extracted

- FR-67, FR-68, FR-61/FR-62 (diperbarui), Q-38 -> `docs-to-be/fr-nfr.md`
- ERD `paket_produk_lingkup` -> `docs-to-be/diagrams/erd.drawio`
- mockup -> `ui-mockup/produk-to-be/mod-master-data.js`, `main.js`, `style.css`

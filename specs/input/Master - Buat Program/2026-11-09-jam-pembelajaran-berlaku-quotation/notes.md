# Jam Pembelajaran (JP) di Penjualan Program, default Berlaku sampai quotation 31 Desember (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** sebagian `docs-to-be/fr-nfr-quotation.md` QTN-FR-04 — default "Berlaku sampai" +30 hari diganti 31 Desember.

## Verbatim

> aku ada request lagi nih:
> 1. saat di penjualan program ada select "Jam Pembelajaran" dengan isian input manual dalam format angka, lalu sebelah kanannya ada satuannya yaitu "JP" ini akan ada di print out quotation juga ya
> 2. pada quotation kan ada tuh select tanggal quotation dan tanggal berlaku sampai kapan, nah berlaku sampai kapan itu dibuat default aja ke 31 desember 2026, apabila ganti tahun maka otomatis ganti tahun 2027 tetapi tanggal dan bulan tetap 31 desember

## Catatan penafsiran

1. "select" dibaca sebagai **isian** (bukan dropdown) karena nilainya diketik manual berupa angka; satuan "JP" tampil tetap di sebelah kanan isian. Bilangan bulat ≥ 1, wajib, terkunci setelah Final, tidak diubah oleh Reschedule: `[USULAN]` (Q-PJ-25). Dicetak di kolom Deskripsi halaman Penawaran quotation, mis. "16 JP".
2. "Ganti tahun" dibaca sebagai **tahun Tanggal quotation**: default Berlaku sampai = 31 Desember tahun Tanggal quotation (quotation 2026 → 31-12-2026; 2027 → 31-12-2027). Bila Tanggal quotation diganti ke tahun lain dan Berlaku sampai belum diubah manual, ikut pindah ke 31 Desember tahun itu. Berlaku sampai tetap dapat diubah dan tidak boleh sebelum Tanggal quotation.

## Extracted

- PJ-FR-29, PJ-NFR-06, Q-PJ-25 -> `docs-to-be/fr-nfr-penjualan-program.md`
- QTN-FR-04, QTN-FR-14 -> `docs-to-be/fr-nfr-quotation.md`
- `penjualan_program.jam_pembelajaran` -> `docs-to-be/diagrams/erd.drawio` halaman *ERD Penjualan Program*
- Mockup -> `ui-mockup/produk-to-be/mod-program.js`, `mod-quotation.js`

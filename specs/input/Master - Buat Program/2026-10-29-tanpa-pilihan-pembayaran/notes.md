# Quotation tanpa pilihan cara pembayaran

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** field **Pembayaran** (Cash In Advance / Termin / Tempo) di form quotation dan baris "Pembayaran" di halaman Penawaran — `docs/fr-nfr-quotation.md` draf 3–6 QTN-FR-04, QTN-FR-14, diturunkan dari `input/Template Penawaran Labmania.pdf` (`input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/`).

## Verbatim

> di quotation tidak usah ada pilih cara pembayaran

## Catatan penafsiran

- Field Pembayaran dihapus dari form quotation.
- Karena tidak ada lagi nilai yang dipilih, baris "Pembayaran" di blok Penawaran PDF juga tidak dicetak `[USULAN]` — template asli memuat baris ini; bila baris tetap perlu ada dengan teks tetap, perlu konfirmasi.
- Pesan sebelumnya di sesi yang sama menanyakan masa berlaku quotation ("di quotation sekarang ada masa berlaku ya>"); analis menjawab dan menawarkan pilihan — belum ada keputusan, field Berlaku sampai tidak diubah.

## Extracted

- QTN-FR-04, QTN-FR-14, Q-QTN-14 -> docs/fr-nfr-quotation.md (draf 7)
- mockup -> ui-mockup/produk/app.js (draf 11v)

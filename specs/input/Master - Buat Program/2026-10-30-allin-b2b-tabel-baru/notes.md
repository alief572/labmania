# All-in hanya B2B; tabel lama tr_penawaran tidak dipakai

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat — jawaban atas sisa Q-QTN-15 dan Q-QTN-04 (`docs/fr-nfr-quotation.md` draf 9)
- **Contents:** pesan verbatim
- **Supersedes:** — (mengonfirmasi usulan analis)

## Pertanyaan analis (konteks)

1. Q-QTN-15 (sisa): all-in hanya untuk B2B, atau Public ke Company juga boleh?
2. Q-QTN-04: ERD memakai tabel baru, bukan `tr_penawaran` — tabel lama memang tidak dipakai?

## Verbatim

> 1. all in hanya untuk b2b ya
> 2. iya tidak dipakai, update aja

## Catatan penafsiran

- 1: skema all-in **hanya B2B**; Public selalu per peserta. Cetak satu baris paket (bagian lain Q-QTN-15) tidak dijawab — tetap usulan.
- 2: tabel lama `tr_penawaran` **tidak dipakai**; Quotation memakai tabel baru `quotation`, `quotation_peserta`, `quotation_keterangan` dan kolom baru `ms_peserta.id_customer` (ERD Quotation draf 1).

## Extracted

- QTN-FR-20, QTN-NFR-05, bagian 7 (Q-QTN-04, Q-QTN-15) -> docs/fr-nfr-quotation.md (draf 9a)
- catatan ERD Quotation -> docs/diagrams/erd.drawio
- tooltip Q-QTN-15 -> ui-mockup/produk/app.js

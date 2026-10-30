# B2B: all-in boleh diskon, invoice dipecah = all-in ÷ peserta, tanpa Confirmation Letter, minimal 10 saat Deal

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat — jawaban atas Q-QTN-15 – Q-QTN-18 (`docs/fr-nfr-quotation.md` draf 8)
- **Contents:** pesan verbatim
- **Supersedes:** usulan analis draf 8 "all-in tanpa diskon" (QTN-FR-20) — all-in **boleh diberi diskon**.

## Pertanyaan analis (konteks)

1. Q-QTN-15: all-in hanya B2B, boleh diskon (usulan: tidak), dicetak satu baris paket?
2. Q-QTN-16: invoice B2B dipecah per peserta — nilai per peserta = all-in ÷ jumlah peserta, dipilih saat membuat invoice?
3. Q-QTN-17: B2B tanpa Confirmation Letter?
4. Q-QTN-18: minimal 10 peserta B2B juga berlaku saat Deal?

## Verbatim

> 1. bisa dibeli diskon juga kalo all in
> 2. iya coba begitu dulu
> 3. iya tidak usah ada konfirmasi letter
> 4. betul
>
> langsung update ke ERD, Use Case, Mock Up, dan FR & NFR ya

## Catatan penafsiran

- 1: "dibeli" dibaca "diberi". Skema all-in **boleh diskon**. Diskon tetap Rupiah saja `[QDA-3]`; karena all-in satu harga, diskon all-in = Rupiah **total** quotation, 0 ≤ diskon ≤ harga all-in `[USULAN]`. Bagian lain Q-QTN-15 (all-in hanya B2B, cetak satu baris) tidak dijawab — tetap usulan.
- 2: usulan Q-QTN-16 disetujui **sementara** ("coba begitu dulu"): pecah invoice per peserta = all-in (setelah diskon) ÷ jumlah peserta, dipilih saat membuat invoice. Untuk modul Invoice.
- 3: B2B **tanpa Confirmation Letter**.
- 4: minimal 10 peserta B2B berlaku juga saat **Deal** (peserta Deal ≥ 10).
- Permintaan: perbarui ERD (halaman Quotation baru), use case (workbook), mockup, FR & NFR.

## Extracted

- QTN-FR-08, QTN-FR-13, QTN-FR-20, QTN-FR-21, bagian 7 -> docs/fr-nfr-quotation.md (draf 9)
- ERD halaman *ERD Quotation* (draf 1) + `ms_peserta.id_customer` di *ERD Master Data* (draf 13) -> docs/diagrams/erd.drawio
- UC-33 – UC-37, modul Quotation, entity Quotation -> docs/workbook.xlsx
- mockup -> ui-mockup/produk/app.js (draf 11x)

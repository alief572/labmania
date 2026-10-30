# B2B di Quotation: harga per peserta / all-in, minimal 10 peserta, tanpa Sales Order, tanpa Formulir Konfirmasi, satu quotation

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat — jawaban atas lima pertanyaan analis tentang beda B2B vs Public di Quotation
- **Contents:** pesan verbatim
- **Supersedes:**
  - `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` dan `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/` bagian **Sales Order** — Sales Order **tidak ada lagi**; B2B hanya lewat Quotation (Q-QTN-03).
  - Usulan analis `docs/fr-nfr-quotation.md` draf 7: B2B mencetak Formulir Konfirmasi (Q-QTN-13), B2B satu quotation aktif (Q-QTN-08, kini dikonfirmasi).

## Pertanyaan analis (konteks)

1. Apa bedanya B2B dan Public di mata klien setelah Final?
2. Harga paket dan minimal 10 peserta untuk B2B masih berlaku? Kalau iya, di Quotation atau Sales Order?
3. Sales Order masih dipakai atau sudah digantikan Quotation?
4. In-house (B2B) tetap mencetak Formulir Konfirmasi? (S&K: in-house tidak perlu)
5. B2B boleh lebih dari satu quotation?

## Verbatim

> 1. iyaaa company, tapi di quotation tetap ada harga perpeserta gt dan ada harga all in 1 company
> 2. berlaku, dari penjualan program juga harus minimal 10 sampai ke quotation
> 3. Sales order sudah tidak ada, quotation saja
> 4. iya tidka perlu
> 5. kalau memang ke company memang 1 quotation tapi invoice bisa dipecah ke personel kalau memang client b2b maunya invoice perorangan

## Catatan penafsiran

- 1: B2B = penjualan ke **satu company**. Di Quotation B2B ada dua **skema harga**: **per peserta** (harga Penjualan Program × peserta) atau **all-in** satu nilai untuk company. Skema all-in hanya untuk B2B `[USULAN]`; nilai all-in diisi manual, default harga / peserta × jumlah peserta `[USULAN]`; diskon per peserta tidak dipakai pada all-in `[USULAN]`.
- 2: **Minimal 10 peserta** untuk B2B berlaku sejak **Penjualan Program** (target peserta ≥ 10) **sampai Quotation** (peserta quotation ≥ 10).
- 3: **Sales Order dihapus**. Satu-satunya dokumen setelah Penjualan Program Final adalah Quotation, untuk Public maupun B2B.
- 4: Quotation B2B (in-house) **tidak mencetak Formulir Konfirmasi** — langkah Formulir Konfirmasi tidak ada dan PDF hanya Penawaran + S&K. Confirmation Letter (= formulir konfirmasi peserta Deal, QTN-FR-13) ikut tidak tersedia untuk B2B `[USULAN]`, dikonfirmasi lewat Q-QTN-17.
- 5: B2B **satu quotation** per Penjualan Program (Q-QTN-08 terjawab). **Invoice** B2B dapat **dipecah per peserta** bila client minta invoice perorangan — dicatat untuk modul Invoice (belum dikerjakan). Cara memecah nilai all-in per peserta belum dijelaskan (Q-QTN-16).

## Extracted

- QTN-FR-03, -05, -08, -10, -13, -16, -18, -19, bagian 1, 6, 7 -> docs/fr-nfr-quotation.md (draf 8)
- Sales Order dihapus, B2B minimal 10 peserta -> docs/fr-nfr-penjualan-program.md (draf 12)
- mockup -> ui-mockup/produk/app.js (draf 11w)

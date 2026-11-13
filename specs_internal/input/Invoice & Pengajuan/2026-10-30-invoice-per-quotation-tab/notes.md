# Invoice per quotation Deal, tab Public / B2B, kolom nomor program

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat (lanjutan `input/Invoice & Pengajuan/2026-10-30-invoice-consulting/`)
- **Contents:** pernyataan verbatim, tanpa file lampiran

## Verbatim

> nah aku mau kalo di 1 program terdiri dari banyak quotation yang deal, maka yang deal itu per itemnya akan menjadi invoice per item jg, ga digabung.
>
> kalo emang 1 program 1 quotation deal, maka jadi 1 invoice, tapi kalo 1 program ada 10 quotation deal maka akan menjadi 10 invoice.
>
> pisah aja di tab invoice, mana yang program public dan mana yang program b2b. lalu pada seiap item invoice juga harus ada kolom yang menjadi identifikasi nomor programnya, jadi kalo ditelusur mudah

## Catatan penafsiran

1. **Satu quotation Deal = satu invoice.** Beberapa quotation Deal dalam satu Penjualan Program tidak digabung menjadi satu invoice.
2. **Daftar Invoicing dipisah dalam tab Public dan B2B**, menurut jenis Penjualan Program.
3. **Setiap item invoice punya kolom Nomor Program** (nomor Penjualan Program, `PJP-`/`PJB-`) agar mudah ditelusuri.
4. Belum dijelaskan: apakah butir 1 juga menghapus pecah invoice B2B per peserta (`[B2B-5]`, `[BAD-2]`). B2B hanya punya satu quotation, sehingga menurut aturan ini B2B jadi satu invoice. Pecah per peserta tetap dipertahankan sebagai opsi bila client minta, dan ditanyakan sebagai Q-IVC-15.

## Extracted

- Bagian 1, IVC-FR-02, IVC-FR-06, IVC-FR-10, IVC-FR-13, IVC-FR-14, Q-IVC-03, Q-IVC-15 -> docs/fr-nfr-invoice.md (draf 2)

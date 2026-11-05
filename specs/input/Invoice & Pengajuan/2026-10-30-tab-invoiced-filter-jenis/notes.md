# Status hanya Uninvoiced / Invoiced; tab Invoiced; Public / B2B lewat filter

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat. User bertanya arti status "Sebagian" di mockup draf 13b, lalu memutuskan.
- **Contents:** pernyataan verbatim, tanpa file lampiran
- **Supersedes:**
  - `[IPQ-2]` di `input/Invoice & Pengajuan/2026-10-30-invoice-per-quotation-tab/`: tab Program Public / Program B2B diganti filter.
  - Usulan analis: status "Sebagian" untuk B2B per peserta.

## Verbatim

> iya maunya uninvoiced dan invoiced. nah kalo sekarang kan kegabung jadi satu, kalo bisa tambah tab lagi khusus yang sudah terinvoice, nanti filter aja mana yang b2b mana yang public.

## Catatan penafsiran

1. **Status hanya dua: Uninvoiced dan Invoiced.** Status "Sebagian" dihapus. Quotation B2B per peserta tetap **Uninvoiced** selama masih ada peserta Deal yang belum ditagih (keterangan "n dari m peserta belum ditagih").
2. **Tab dipisah menurut status:** tab **Uninvoiced** (quotation Deal yang masih harus ditagih) dan tab **Invoiced** (khusus yang sudah terinvoice). Di tab Invoiced, satu baris per invoice (penafsiran analis), karena satu quotation B2B per peserta bisa punya banyak invoice.
3. **Public / B2B jadi filter jenis program**, bukan tab.

## Extracted

- Bagian 1, IVC-FR-02, IVC-FR-03, bagian 6 -> docs/fr-nfr-invoice.md (draf 2b)

# Invoice tanpa Nomor PO; Nomor Faktur per invoice; COA Penjualan tetap

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-03
- **Channel:** chat (jawaban atas Q-IVC-16 di spec Invoice draf 2h, lanjutan `input/Invoice & Pengajuan/2026-11-03-b2b-per-peserta-pecah-otomatis/`)
- **Contents:** pernyataan verbatim, tanpa file lampiran
- **Supersedes:** field **Nomor PO** di form Add Invoicing (wajib menurut acuan `Invoice Consulting.xlsx` sheet Konsep / `Invoice Consulting.html`), usulan "Nomor PO opsional untuk Personal" (Q-IVC-06), dan usulan "Nomor PO diisi sekali untuk semua invoice pecahan" (Q-IVC-16).

## Verbatim

Pertanyaan yang dijawab (Q-IVC-16): Nomor PO diisi sekali untuk semua invoice pecahan, Nomor Faktur per invoice, Tanggal dan COA Penjualan sama?

> nomor PO itu di invoice gausah ada, COA penjualan sementara gitu dulu, iya faktur per invoice,

## Catatan penafsiran

1. **Nomor PO dihapus dari invoice** untuk semua jenis (Public, B2B all-in, B2B per peserta): tidak ada di form, daftar, Lihat Invoice, cetakan, maupun tabel. Q-IVC-06 tidak relevan lagi.
2. **Nomor Faktur per invoice**: dikonfirmasi. B2B per peserta mengisi Nomor Faktur untuk setiap invoice pecahan.
3. **COA Penjualan** tetap seperti rancangan sekarang (dipilih di jurnal, default per jenis penjualan, sama untuk semua invoice pecahan) — sementara, dapat berubah.
4. Tanggal Invoice yang sama untuk semua invoice pecahan tidak dibantah; tetap usulan.

## Extracted

- IVC-FR-02, IVC-FR-05, IVC-FR-06, IVC-FR-11, IVC-FR-13, IVC-FR-15, UC-40, bagian 7 (`no_po` dihapus), Q-IVC-06, Q-IVC-16 -> docs/fr-nfr-invoice.md (draf 2i)
- Mockup modul Invoice -> ui-mockup/produk/app.js (draf 13h)

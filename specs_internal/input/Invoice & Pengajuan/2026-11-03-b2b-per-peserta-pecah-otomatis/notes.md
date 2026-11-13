# Invoice B2B per peserta dipecah otomatis, nomor invoice berbeda per peserta

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-03
- **Channel:** chat (lanjutan `input/Invoice & Pengajuan/2026-10-30-invoice-b2b-ikut-skema/`)
- **Contents:** pernyataan verbatim, tanpa file lampiran
- **Supersedes:** usulan analis di spec Invoice draf 2a – 2g (IVC-FR-06): form Add Invoicing B2B per peserta memakai dropdown **"Peserta yang ditagih"**, sehingga satu kali Save membuat invoice untuk satu peserta, lalu diulang sampai semua peserta selesai.

## Verbatim

Konteks pertanyaan: di form Add Invoicing B2B skema per peserta ada pilihan peserta lagi (satu invoice per peserta, dipilih satu per satu).

> kalo gitu, saat di quotation b2b dan quotation ke setiap peserta maka di invoicingnya kepecah aja. jangan 1 nomor invoice yang sama

## Catatan penafsiran

1. Quotation B2B dengan skema harga **Per peserta** dipecah **otomatis** di invoicing. Satu kali Add Invoicing membuat **satu invoice untuk setiap peserta Deal**. User tidak memilih peserta satu per satu.
2. Setiap invoice punya **nomor invoice sendiri** (`LMI/INV/MM/yy-zzz` berurutan). Tidak boleh satu nomor invoice dipakai bersama oleh beberapa peserta.
3. Aturan `[BSK]` tetap berlaku: B2B All-in ke company = 1 invoice; Public = 1 invoice per quotation.
4. Belum dijelaskan (usulan analis, Q-IVC-16): **Nomor PO** diisi satu kali dan dipakai semua invoice (PO dari company yang sama), sedangkan **Nomor Faktur** diisi per invoice/peserta karena faktur pajak unik per invoice. Tanggal Invoice dan COA Penjualan sama untuk semua invoice hasil pecahan.

## Extracted

- Bagian 1, UC-40, IVC-FR-02, IVC-FR-06, IVC-FR-07, IVC-FR-11, IVC-NFR-01, IVC-NFR-03, bagian 6, Q-IVC-16 -> docs/fr-nfr-invoice.md (draf 2h)
- QTN-FR-11 -> docs/fr-nfr-quotation.md (draf 10a, rujukan)
- Mockup modul Invoice, form Add Invoicing -> ui-mockup/produk/app.js

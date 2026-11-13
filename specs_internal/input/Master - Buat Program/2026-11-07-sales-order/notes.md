# Sales Order (to be): 1 quotation Deal = 1 SO, daftar per program, View, Print, Siap dibuatkan invoice

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-07
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (khusus to be):**
  - `[B2B-3]` di `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/` ("Sales Order tidak ada"): Sales Order kembali ada di to be.
  - Invoice dibuat dari quotation Deal (`docs/fr-nfr-invoice.md` IVC-FR-02, `[IPQ-1]`): di to be invoice dibuat dari Sales Order yang sudah ditandai siap.

## Verbatim

> nah siapin 1 menu Sales Order juga di Quotation, apabila quotation sudah deal maka setiap 1 quotation akan membantuk 1 sales order. disini listing si SO berdasarkan programnya. jadi 1 program klik action lihat SO maka muncul halaman baru isinya list Sales order, akan ada action view dan print out juga, apabila view maka akan ada detail si quotation beserta surat konfrimasi yang diisi melalui link di quotation. kalau print out SO maka akan ada hasil PDF SO dari quotation tersebut. dari SO tersebut lah akan menjadi invoice, kalau sebelumnya invoice berdasarkan quotation. nah disini SO akan bisa dibuat invoice apabila ada action "siap dibuatkan invoice" gitu juga. tolong deh ya buatin dulu

## Catatan penafsiran

1. Menu baru **Sales Order** di modul Quotation (to be).
2. Quotation **Deal** → otomatis terbentuk **satu Sales Order** (1 : 1). Tanggal SO = tanggal Deal `[USULAN]`.
3. Index Sales Order dikelompokkan **per Penjualan Program**; aksi **Lihat SO** membuka halaman baru berisi daftar SO program itu.
4. Aksi per SO: **View** (detail quotation + surat konfirmasi kehadiran yang diisi lewat link quotation), **Print Out SO** (PDF SO dari quotation itu), **Siap dibuatkan invoice**.
5. Invoice dibuat **dari Sales Order**, hanya bila SO sudah ditandai "siap dibuatkan invoice". Sebelumnya invoice langsung dari quotation Deal.
6. Usulan analis (belum dinyatakan klien):
   - Nomor SO `LMI/SO/MM/yy-zzz`, mengikuti Penawaran `LMI/PW` dan Invoice `LMI/INV` (Q-SO-01).
   - Status SO: Belum siap invoice → Siap invoice → Invoiced (Invoiced diturunkan dari invoice).
   - Siap dibuatkan invoice: belum ada aksi batal; aktor Sales/Manajemen (Q-SO-03).
   - Format PDF SO mengikuti Penawaran + daftar peserta Deal; kode dokumen menyusul (Q-SO-04).
   - B2B: View menampilkan data peserta tanpa surat konfirmasi (sejalan Q-TB-03).

## Extracted

- `docs-to-be/fr-nfr-sales-order.md` (baru), `docs-to-be/fr-nfr-quotation.md` (QTN-FR-09, QTN-FR-11, bagian 1, 6), `docs-to-be/README.md`
- ERD `sales_order` -> `docs-to-be/diagrams/erd.drawio`; BPMN -> `docs-to-be/diagrams/bpmn-to-be.drawio`
- mockup -> `ui-mockup/produk-to-be/mod-sales-order.js` (baru), `mod-quotation.js`, `mod-invoice.js`, `core.js`, `main.js`, `mod-activity-log.js`, `mod-role-permission.js`, `Labmania.html`

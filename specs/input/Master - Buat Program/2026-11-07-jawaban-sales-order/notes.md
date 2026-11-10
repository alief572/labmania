# Jawaban Sales Order: nomor SO sendiri (format menyusul), permission menyusul, SO tidak bisa batal

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-07
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** usulan di `input/Master - Buat Program/2026-11-07-sales-order/` catatan penafsiran 6 (nomor `LMI/SO/MM/yy-zzz`; aktor Sales/Manajemen).

## Verbatim

> jangan no quotation seperti saat ini aja, no SO baru buat lagi, akan ada formatnya, menyusul. lalu untuk permisiion nya menyusul juga, kalau sudah SO maka tidak bisa batal ya. nah aku mau nanya juga, biasanya sales order ini tuh untuk apa ya? kayak surat kerja kah?

## Catatan penafsiran

1. Nomor SO **tidak** memakai pola nomor quotation (usulan `LMI/SO/MM/yy-zzz` ditolak). SO punya **penomoran sendiri**; formatnya **menyusul** dari Labmania. Sampai ada, mockup memakai nomor sementara `SO-nnn` yang ditandai "format menyusul". Menjawab Q-SO-01 sebagian.
2. **Permission** Sales Order (siapa yang boleh melihat, mencetak, dan menandai siap dibuatkan invoice) **menyusul**. Menjawab Q-SO-03 sebagian.
3. **Sales Order tidak dapat dibatalkan** setelah terbentuk. Menjawab Q-SO-05: quotation Deal juga tidak dapat dibatalkan, karena sudah punya SO.
4. Pertanyaan "SO untuk apa, seperti surat kerja?" dijawab di chat, tidak mengubah requirement.

## Extracted

- `docs-to-be/fr-nfr-sales-order.md` (SO-FR-01, SO-FR-07, SO-FR-10 baru, aktor, UC-60 – UC-63, Q-SO-01, Q-SO-03, Q-SO-05), BPMN catatan Q-SO-03
- mockup `ui-mockup/produk-to-be/mod-sales-order.js` (nomor sementara, catatan permission, keterangan tidak bisa batal)

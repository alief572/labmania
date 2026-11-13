# Opsi konfirmasi ulang menurut status bayar; invoice belum dibayar dibatalkan (to be)

- **Source:** BA proyek, lewat sesi Claude Code
- **Date:** 2026-11-12
- **Channel:** chat
- **Contents:** pesan verbatim, menjawab tiga pertanyaan analis dari jawaban sebelumnya (Q-QTN-24 a, b, e)
- **Supersedes (khusus to be, hari yang sama):** opsi "Ikut jadwal baru / Batal ikut / Refund bila sudah dibayar" (`input/Master - Buat Program/2026-11-12-opsi-konfirmasi-ulang-rev-program/` catatan penafsiran 3)

## Verbatim

Pertanyaan analis: (1) peserta yang sudah bayar tetapi memilih Batal ikut, dananya hangus? (2) invoice sudah terbit tetapi belum dibayar lalu peserta batal ikut, tagihan disesuaikan bagaimana? (3) pembayaran cukup dicatat lunas penuh dengan tanggal bayar?

> 1. bukan batal ikut pas udah bayar. jadi kalo udah bayar opsinya "Refund" dan "Pindah Jadwal" kalo belum bayar "batal ikut" dan "pindah jadwal"
> 2. batal invoice aja
> 3. begitu dulu aja
>
> tab refund ada di menu apa?

## Catatan penafsiran

1. Opsi konfirmasi ulang: **sudah bayar** = **Refund** atau **Pindah jadwal**; **belum bayar** (termasuk belum ada invoice) = **Batal ikut** atau **Pindah jadwal**. "Pindah jadwal" = ikut jadwal baru program yang sama (hasil reschedule), sesuai `input/Master - Buat Program/2026-11-12-opsi-konfirmasi-ulang-rev-program/` butir 3.
2. Batal ikut dengan invoice yang sudah terbit tetapi belum dibayar: **invoice dibatalkan** (status Batal, tidak dihapus). Peserta lain di invoice yang sama kembali belum ditagih, sehingga Sales Order-nya muncul lagi di tab Uninvoiced untuk ditagih ulang `[USULAN]`.
3. Pencatatan pembayaran cukup **tanggal bayar, dianggap lunas penuh** (dikonfirmasi "begitu dulu aja").
4. Tab Refund ada di menu **Quotation › Sales Order**, tab ketiga (setelah Sales Order dan Surat Kerja Trainer). Dijawab di chat, tidak mengubah dokumen.

## Extracted

- `docs-to-be/fr-nfr-quotation.md` QTN-FR-35 – QTN-FR-37, UC-71, UC-72, bagian 6, Q-QTN-24 (a, b, e terjawab)
- `docs-to-be/README.md` (dampak Invoice: status Batal)
- ERD to be: `quotation_peserta_konfirmasi_ulang.keputusan`
- Mockup: `mod-quotation.js`, `mod-invoice.js`, `mod-sales-order.js` (rsDemo), `konfirmasi.html`

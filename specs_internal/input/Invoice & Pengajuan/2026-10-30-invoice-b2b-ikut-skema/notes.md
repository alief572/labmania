# Invoice B2B mengikuti skema quotation (all-in ke company / per peserta)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code (chat dan jawaban pilihan)
- **Date:** 2026-10-30
- **Channel:** chat (lanjutan `input/Invoice & Pengajuan/2026-10-30-invoice-per-quotation-tab/`)
- **Contents:** pernyataan verbatim, tanpa file lampiran
- **Supersedes:** `[BAD-2]` di `input/Master - Buat Program/2026-10-30-b2b-allin-diskon-konfirmasi/`, yaitu "pecah invoice = (all-in − diskon) ÷ peserta" (sementara). Juga usulan analis di spec Invoice draf 1–2: pilihan manual "Satu invoice / Per peserta" di form invoice B2B.

## Verbatim

> untuk b2b bisa pecah memang, jadi invoice b2b mengikuti bagaimana quotationnya. yang pasti 1 quotation 1 invoice.

Jawaban atas pertanyaan "pemecahannya dilakukan di mana":

> kan di b2b ini bisa quotationnya terhadap 1 company tersebut all in, tapi bisa juga jadi perpeserta yang dimana quotationnya pecah, otomatis pada saat invoice mengikuti apakah quotationnya terhadap company atau terhadap peserta.

## Catatan penafsiran

1. Cara tagih B2B **tidak dipilih di form invoice**. Cara tagih otomatis mengikuti **skema harga quotation** (QTN-FR-20).
   - **All-in 1 company:** quotation ditujukan ke company, sehingga dibuat **satu invoice** untuk seluruh peserta Deal.
   - **Per peserta:** quotation dianggap "pecah" per peserta, sehingga dibuat **satu invoice per peserta Deal**. Nilainya = harga − diskon per peserta.
2. "1 quotation 1 invoice" tetap berlaku untuk all-in dan untuk Public. Pada skema per peserta, satu baris peserta Deal di quotation = satu invoice.
3. Karena all-in tidak lagi dipecah, rumus `[BAD-2]` "(all-in − diskon) ÷ peserta" tidak dipakai lagi untuk invoice.
4. Belum dijelaskan: apakah Public yang ditujukan ke Company dengan skema per peserta juga dipecah. Public tidak punya pilihan skema dan sudah diputuskan satu invoice per quotation `[IPQ-1]`, jadi Public tetap satu invoice.

## Extracted

- Bagian 1, UC-40, IVC-FR-06, IVC-FR-09, Q-IVC-15 -> docs/fr-nfr-invoice.md (draf 2a)

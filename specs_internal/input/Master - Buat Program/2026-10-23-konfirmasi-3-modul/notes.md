# Konfirmasi 6 pertanyaan setelah requirements 3 modul

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** chat (jawaban atas 6 butir "Perlu konfirmasi" setelah `input/Master - Buat Program/2026-10-23-requirements-3-modul/`)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:** `input/Master - Buat Program/2026-10-23-requirements-3-modul/` — *Master Data* No 8 & *Master Program* No 13 (satuan Hours per jam), *Field Quotation* (B2B jumlah peserta "informasi"; diskon B2B "belum ditentukan" tetap)

## Butir yang ditanyakan (ringkas, oleh analis)

1. Satuan Hours: Event, Day, atau per jam? (Q-22 / Q-MP-03)
2. Item bermode Fix boleh diedit di quotation? (Q-PJ-05)
3. Variable Cost B2B × jumlah peserta atau dilebur ke harga all-in? (Q-PJ-04)
4. Setelah ada peserta Deal, harga & costing quotation Public dikunci? (Q-PJ-07)
5. Diskon nominal Rp atau persen? (Q-PJ-06)
6. Hotel Trainer \*\* dan \*\*\* keduanya terisi: peringatan atau ditolak? (Q-23 / Q-MP-05)

## Verbatim

1. hours itu hanya perhitungan ke trainer, tp jatohnya per event jg. 1 event pasti 1 trainer
2. iyaa yang fix juga
3. tetep ke per peserta ya, all in nanti langsung akumulasi dari per peserta. oh iya tambahan target peserta dibuat defaul 10 tapi bisa edit ya jadi minimal ya 10 orang untuk B2B
4. setuju
5. diskon keduanya
6. biarin aja

## Catatan penafsiran

- Butir 1: biaya trainer yang di sumber "per jam" (Mandays Rate Charge Labmania, Honor Trainer) dihitung **sekali per event** (satuan Event); satu event selalu satu trainer. Tidak ada pengali hari maupun jam. Mengonfirmasi `input/Master - Buat Program/2026-10-23-klarifikasi-program/` butir 1.
- Butir 2: di Quotation, item bermode **Fix** juga dapat diedit. Mode Fix tetap mengunci di Master Program.
- Butir 3: Variable Cost B2B dihitung **per peserta × jumlah peserta**; total biaya all-in = akumulasi Fixed + Variable per peserta. Harga paket all-in tetap diinput. Jumlah peserta B2B **default 10, dapat diedit, minimal 10**, dan dipakai dalam perhitungan (bukan lagi sekadar informasi).
- Butir 5: diskon per peserta dapat berupa **nominal Rp atau persen** — dipilih per peserta.
- Butir 6: tidak ada aturan khusus; perilaku mockup (peringatan saja, tidak menolak) dibiarkan.

## Extracted

- Q-22, Q-23 -> docs/fr-nfr.md (draf 6)
- Q-MP-03, Q-MP-05 -> docs/fr-nfr-master-program.md (draf 2)
- Q-PJ-04 (sebagian), Q-PJ-05, Q-PJ-06, Q-PJ-07; PJ-FR-04, PJ-FR-05, PJ-FR-11, PJ-FR-13, PJ-FR-14, PJ-FR-17 -> docs/fr-nfr-penjualan-program.md (draf 2)
- `quotation_program_peserta.diskon_tipe` -> docs/diagrams/erd.drawio

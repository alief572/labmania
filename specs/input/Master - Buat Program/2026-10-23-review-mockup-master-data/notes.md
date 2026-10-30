# Review mockup Modul Produk → Master Data

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** chat (review atas `ui-mockup/produk/` draf 2)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:** sebagian `input/Master - Buat Program/2026-10-22-klarifikasi-produk/` — jawaban Q-08 ("event, day, hours itu satuannya"): Hours tidak dipakai

## Verbatim

1. di mock up itu kan struktur master ya, nah dijadiin 1 modul aja namanya "Master Data" lalu sub modulnya baru deh yang sekarang itu
2. lalu di product combine, indexnya agak berantakan, boleh tolong dirapihin?
3. kalo untuk master rate:
   1. satuan di fixed cost hanya ada /event dan /day
   2. satuan di variavle cost hanya ada /peserta/day dan /peserta/event
4. untuk setiap kolom action, itu pakai icon gear baru deh muncul opsinya
5. sama tolong di sidebar bagian atas dibawah logo labmania, ada fitur search menu yaa
6. di freeze ya, jadi nanti kalo udah banyak menunya walaupun di scroll tetep bisa search

## Catatan penafsiran

- Butir 2: "index" = halaman daftar Product Combine. Dirapikan dengan memecah kolom *Detail Product* menjadi kolom Produk, Bidang, Varian, Sertifikasi (urutan sama dengan form). Penataan ini usulan analis yang sudah diterapkan di mockup.
- Butir 1: nama sub modul *Atribut Produk*, *Produk*, *Peserta & Rate* adalah usulan analis (grup lama "Master Data" diganti agar tidak sama dengan nama modul induk) — belum dikonfirmasi.

## Extracted

- FR-08, FR-10, FR-15, FR-19, FR-20, FR-25, FR-27 – FR-29, NFR-11, Q-15 -> docs/fr-nfr.md (draf 3)
- UC-01 – UC-10 (Modul/Sub Modul, UC-08/UC-09 satuan) -> docs/workbook.xlsx (UC Non-BPMN, Modules, Entities)
- ERD -> docs/diagrams/erd.drawio
- BPMN to-be (diturunkan dari FR draf 3, bukan dari intake ini saja) -> docs/diagrams/bpmn-to-be.drawio

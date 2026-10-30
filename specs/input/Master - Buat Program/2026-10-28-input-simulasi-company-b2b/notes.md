# Input simulasi tampil ganda (bagian 1 & 4); pilih Company di Penjualan Program B2B

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-28
- **Channel:** chat (masukan atas mockup `ui-mockup/produk/` draf 11a)
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** sebagian `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` — Company B2B tidak lagi hanya di Sales Order; menjawab Q-PJ-18 (`docs/fr-nfr-penjualan-program.md` draf 8b)

## Verbatim

> di menu master program pada poin 1 kan ada input simulasi agar pada poin 4 yang hasil simulasi bisa keliatan, gimana kalo input simulasi itu pindah ke simulasi aja langsung? soalnya agak jauh untuk lihatnya, atau dibuat double aja tapi inputan ngunci jadi 1, ubah dipoin 1 maka di poin 4 keubah otomatis dan sebaliknya.
>
> lalu saat penjualan program untuk b2b ada select company ya. tarik dari master data

## Catatan penafsiran

- Butir 1: dua opsi diberikan (pindah ke bagian 4, atau tampil ganda dan tersinkron). Analis memilih **tampil ganda**: bagian 1 tetap memuat input simulasi (durasi dan target peserta juga dipakai Qty di bagian 2–3), bagian 4 memuat input yang sama di atas hasil simulasi. Satu nilai, dua tempat: ubah di salah satu, yang lain ikut. Pilihan ini usulan analis — bila klien ingin input hanya di bagian 4, cukup hapus dari bagian 1.
- Butir 1 disebut untuk Master Program. Karena form dan simulasi Penjualan Program sama dengan Master Program `[JLR-1]`, perilaku yang sama diterapkan juga di Penjualan Program (usulan analis).
- Butir 2: Penjualan Program **B2B** memilih **Company** dari Master Company (tabel `customer`). Public tidak berubah (tidak terikat company). Wajib diisi untuk B2B (usulan analis — dicek saat simpan). Company dipakai di surat penawaran B2B dan diteruskan ke Sales Order.

## Extracted

- PJ-FR-01, PJ-FR-09, PJ-FR-10, PJ-FR-20, PJ-FR-22 (baru), bagian 1, 4.4, Q-PJ-18 terjawab -> docs/fr-nfr-penjualan-program.md (draf 8c)
- MP-FR-10 -> docs/fr-nfr-master-program.md (draf 9b)
- `penjualan_program.id_customer` -> docs/diagrams/erd.drawio halaman *ERD Penjualan Program*
- input simulasi ganda, select Company B2B -> ui-mockup/produk/app.js

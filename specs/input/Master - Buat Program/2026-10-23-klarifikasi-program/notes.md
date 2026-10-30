# Klarifikasi konflik Product rev (1) & Program

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** chat (jawaban atas 7 butir konflik yang diajukan analis setelah membaca `input/Master - Buat Program/2026-10-23-requirements-program/`)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:**
  - `input/Master - Buat Program/2026-10-23-requirements-program/` — Program butir 19 dan 27 (Hours, Mandays sebagai satuan), Wishlist No 1 (rumus profit), REQ-7 (field wajib Peserta)
  - `input/Master - Buat Program/2026-10-23-review-mockup-master-data/` butir 3 — "Fix Cost / Day & Hours" tidak lagi seluruhnya bersatuan Day

## Butir yang ditanyakan (ringkas, oleh analis)

1. Hours dan Mandays sebagai satuan, padahal 23-10 Fixed Cost hanya Event/Day.
2. Field wajib Master Peserta: ikut REQ-7 (4 field wajib)?
3. Master Rate perlu Default Cost, status Aktif, dan mode harga (Default / Fix / Free text) — sekaligus jawaban nominal rate dan nonaktif-bukan-hapus.
4. Rate B2B vs Public: item terpisah atau satu item dengan dua harga default?
5. Rumus profit Wishlist `Pendapatan − (Fixed − Variable)` vs simulasi `Pendapatan − (Fixed + Variable)`.
6. Temuan di Excel Cost Master (D39 dobel hitung, Transport ganda, satuan Jam).
7. Program / Product Costing sebagai dokumen spec terpisah atau digabung.

## Verbatim

1. hours itu jatohnya sama kayak/event ya. nah mandays ini tuh jadi output dari costing btb
2. buat wajib semua aja
3. setuju
4. 2 harga dari mana? kan master cost hanya 1 inputan harga aja
5. oh iya fixed + variable ya --> ini hanya untuk public, kalo b2b aku masih menunggu konfirmasi
6. benerin aja
7. pisah ya, jadi ada 2 (master data (sama aja kayak product dan program)

lalu: "buat FR dan NFR dari 2 data reuquirements untuk 2 modul utama yang master data dan modul program penjualan / buatin use case tabel untuk master data dan program penjualan / buatin erd diagram untuk master data dan program penjualan / buatin mockup nya ya, update dari yang sudah ada"

## Catatan penafsiran

- Butir 1: item bersatuan Jam/Hours dicatat dengan satuan **Event** (Mandays Rate Charge Labmania, Honor Trainer). Mandays bukan satuan item biaya, tetapi **hasil** (output) costing B2B; rumusnya belum ada.
- Butir 2: berlaku untuk Master Peserta — semua field form wajib (FR-16 tetap). Master Company tidak ikut ditanyakan; field wajibnya mengikuti REQ-8 dan dicatat sebagai pertanyaan.
- Butir 3: disetujui tiga hal sekaligus — Default Cost di Master Rate, status Aktif (item rate dinonaktifkan, tidak dihapus), dan mode harga per item.
- Butir 4: satu item, satu Default Cost; dipakai Public maupun B2B. Catatan E31 di Program butir 29 gugur.
- Butir 5: rumus profit `Pendapatan − (Fixed + Variable)` hanya untuk Public. Rumus B2B menunggu konfirmasi.
- Butir 6: file di `input/` tidak boleh diubah (append-only), jadi koreksi diterapkan di data awal spec (`docs/fr-nfr.md` lampiran): Transport dimuat satu kali, item Jam → Event, total tidak dipakai.
- Butir 7: dua modul utama — **Master Data** (semua master, termasuk Peserta, Company, Rate dari kedua file) dan **Program Penjualan**. Dokumen FR/NFR dipisah per modul.

## Extracted

- Q-08b, Q-11b, Q-15b, Q-16 (sebagian) terjawab; FR-16, FR-19, FR-20, FR-25, lampiran -> docs/fr-nfr.md (draf 4)
- Rumus Public, B2B ditunda, Mandays -> docs/fr-nfr-program-penjualan.md (draf 1)

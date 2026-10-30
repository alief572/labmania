# Arah modul Penjualan Program: Final → Quotation (Public) / Sales Order (B2B)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** chat (jawaban atas pertanyaan terbuka setelah `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:**
  - `input/Master - Buat Program/2026-10-23-requirements-3-modul/` *Penjualan Program - Labmania.xlsx* — status Draft → Deal / Batal (`[QT-10]` – `[QT-12]`), peserta & Deal per peserta (`[QT-3]`, `[QT-11]`), surat quotation (`[QT-13]`), realisasi "cost tetap" (`[QT-14]`): dipindah ke modul Quotation yang **ditunda**
  - `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` butir 4 dan 5 (kunci costing setelah peserta Deal; diskon per peserta) — ikut ditunda ke modul Quotation

## Butir yang ditanyakan (ringkas, oleh analis)

1. Rumus Mandays (Q-PJ-04). 2. Diskon B2B (Q-PJ-06). 3. HPP aktual = As Is atau ikut perubahan (Q-PJ-09). 4. Langkah sesudah Deal (Q-PJ-10). 5. Format surat quotation (Q-PJ-11). 6. Master Rate Delete vs nonaktif (Q-24).

## Verbatim

1. rumus mandays belakangan ya
2. B2B juga ada diskon
3. HPP di master program berarti yang as is, saat penjualan program baru mengikuti perubahan
4. untuk penjualan program apabila public maka akan ada action final dan masuk ke quotation, nanti dulu aja tp. kalau b2b setelah penjualan program melakukan action final maka akan masuk ke sales order, nanti dulu ana tp. saat ini fokus ke 3 dulu, master data, master program, dan penjualan program
5. yang berkaitan dengan quotation nanti dulu
6. iya non aktif dulu aja

## Catatan penafsiran

- Butir 4 & 5: dokumen di modul Penjualan Program **bukan** quotation. Namanya **Penjualan Program**, dengan status **Draft → Final**, atau Batal. Aksi **Final**: Public → masuk ke modul **Quotation**, B2B → masuk ke modul **Sales Order**. Kedua modul itu di luar cakupan saat ini. Semua yang di Excel *Penjualan Program* bersifat quotation dipindah ke modul Quotation (ditunda): pilih peserta, diskon per peserta, Deal/Batal per peserta, surat quotation, realisasi peserta fix.
- Company (B2B) tetap dipilih di Penjualan Program, karena B2B langsung ke Sales Order tanpa quotation.
- Butir 2: diskon B2B berada di Penjualan Program, atas harga paket. Bentuknya mengikuti diskon Public di konfirmasi sebelumnya (nominal Rp atau persen) — usulan analis.
- Butir 3: **As Is = angka Master Program** (baseline). Penjualan Program mengikuti perubahan yang dibuat di sana (durasi, jumlah peserta, harga, item biaya). Laporan *As Is vs Aktual* menjadi perbandingan Master Program vs Penjualan Program. Realisasi berdasarkan peserta fix menunggu modul Quotation.
- Butir 1: Mandays ditunda — tidak ditampilkan sebagai angka.
- Butir 6: Master Rate tanpa hapus, hanya nonaktif (Q-24 terjawab).

## Extracted

- Q-24 -> docs/fr-nfr.md (draf 7); FR-30 (Company dipilih di Penjualan Program B2B)
- MP-FR-17 (As Is) -> docs/fr-nfr-master-program.md (draf 3)
- docs/fr-nfr-penjualan-program.md (draf 3, ditulis ulang): status Draft → Final / Batal, diskon B2B, As Is vs Penjualan; UC-24, UC-25, UC-27 dihapus; UC-29 baru
- ERD: `quotation_program*` → `penjualan_program*`, tabel peserta dihapus -> docs/diagrams/erd.drawio

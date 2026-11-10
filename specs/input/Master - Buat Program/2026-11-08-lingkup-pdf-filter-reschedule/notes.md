# Lingkup di PDF katalog, acuan filter Tahun/Bulan, pertanyaan reschedule Penjualan Program (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-08
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tidak ada. Mengonfirmasi `input/Master - Buat Program/2026-11-07-filter-tahun-bulan/` (acuan tanggal) dan menindaklanjuti `input/Master - Buat Program/2026-11-07-katalog-lingkup/`.

## Verbatim

> 1. saat print to pdf di katalog, lingkupnya juga ada ya.. tolong dibuatin
> 2. filter Tahun/Bulan mengikuti tanggal acara ya
> 3. mau nanya, kan kemarin ada request terkait penjualan program, bagaimana kalo mau reschedule? karena banyak case terjadi seperti itu, menurut kamu gimana? soalnya kan saat ini tidak ada revisi atau approval terkait penjualan program

## Catatan penafsiran

1. Lingkup di PDF katalog sudah diatur FR-68 dan sudah ada di mockup. Lingkup tidak terlihat karena paket contoh belum punya isian Lingkup. Mockup to be kini memuat Lingkup contoh di PC-001 s.d. PC-004; PC-005 sengaja kosong (paket tanpa Lingkup tetap tampil tanpa blok itu).
2. Acuan filter Tahun/Bulan = **tanggal (mulai) acara** program: dikonfirmasi, tidak lagi `[USULAN]`.
3. Reschedule: masih pertanyaan, belum aturan. Status Penjualan Program saat ini Draft → Final (terkunci) atau Batal dari Draft, tanpa approval (PJ-FR-14, PJ-FR-17). Usulan dibahas di chat; menunggu keputusan Labmania sebelum masuk FR/BPMN.

## Extracted

- PJ-FR-01 -> `docs-to-be/fr-nfr-penjualan-program.md`; QTN-FR-01 -> `docs-to-be/fr-nfr-quotation.md` (acuan tanggal dikonfirmasi)
- Lingkup contoh -> `ui-mockup/produk-to-be/core.js`

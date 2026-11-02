# Format penomoran Penawaran (Quotation) dan Invoice

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-02
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** nomor quotation `LM-I/AM/PW/MM/YY-XXX` (`input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/`, Q-QTN-01) dan usulan nomor invoice `NNN/LMI/YYYY` (Q-IVC-04)

## Verbatim

> aku kasih format penomoran ya, untuk penawaran dulu "XXX/YY/MM/yy-zzz", detailnya:
> 1. XXX = kode PT Labmania yaitu "LMI"
> 2. YY = Kode Pneawaran "PW"
> 3. MM = yaitu bulan "01" / "10" / lainnya mengikuti bulan itu
> 4. yy = Tahun, misalnya "26" 2 digit terakhir tahun, kalo 2026 berarti "26"
> 5. zzz = nomor urut penawarannya, misal "001"
>
> kalo untuk invoice juga sama bedanya di "YY" kalo penawaran kan "PW" kalo invoice "INV". tolong buatin ya, jangan lupa masukin ke FR & NFR juga

## Catatan penafsiran

1. **Penawaran (Quotation):** `LMI/PW/MM/yy-zzz`, contoh `LMI/PW/11/26-001`.
2. **Invoice:** `LMI/INV/MM/yy-zzz`, contoh `LMI/INV/11/26-001`.
3. Segmen tetap: `LMI` (kode PT LabMania Indonesia Jaya) dan kode dokumen (`PW` / `INV`). Segmen `AM` di template lama tidak dipakai lagi.
4. `zzz` minimal 3 digit, diisi nol di depan (`001`); bila lewat 999 tetap bertambah (`1000`) `[USULAN]`.
5. Belum disebut klien, diisi usulan analis:
   - `MM/yy` diambil dari **tanggal dokumen** (Tanggal quotation / Tanggal Invoice), bukan tanggal input `[USULAN]`.
   - Nomor urut **di-reset tiap bulan** (urutan per kombinasi MM/yy), karena bulan & tahun sudah ada di nomor; sama dengan pola `PJP-YYMM-NNN` (urut ulang per bulan) `[USULAN]`.
   - Penawaran dan Invoice punya urutan masing-masing `[USULAN]`.

## Extracted

- QTN-FR-04, Q-QTN-01 -> docs/fr-nfr-quotation.md (draf 10)
- IVC-FR-05, IVC-NFR-03, Q-IVC-04 -> docs/fr-nfr-invoice.md (draf 2f)
- mockup -> ui-mockup/produk/app.js (fungsi nomor quotation & invoice)

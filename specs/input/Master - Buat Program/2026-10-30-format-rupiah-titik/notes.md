# Semua isian harga/biaya memakai pemisah ribuan titik

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat
- **Contents:** pesan verbatim
- **Supersedes:** — (memperluas NFR-12 `docs/fr-nfr.md`, sebelumnya hanya Default Cost dan masih usulan)

## Verbatim

> semua field yang isi harga/biaya buat ada tanda titik, misal 600.000 jangan cuman 600000, membingungkan

## Catatan penafsiran

- Berlaku untuk **semua isian** (input) uang di seluruh modul: Default Cost Master Rate, Harga jual / peserta, Target profit, nominal item costing (Master Program & Penjualan Program), Harga all-in, Diskon, Biaya lain-lain (Quotation).
- Titik muncul **saat mengetik** (format otomatis), hanya angka yang diterima; nilai tersimpan tetap angka tanpa titik `[USULAN]`.
- Tampilan (non-isian) sudah memakai format `Rp 600.000` — tidak berubah.

## Extracted

- NFR-12 diperluas jadi semua isian uang -> docs/fr-nfr.md
- rujukan di MP-NFR-04, PJ-NFR-05, QTN-NFR-07 -> fr-nfr-master-program.md, fr-nfr-penjualan-program.md, fr-nfr-quotation.md
- mockup -> ui-mockup/produk/app.js, style.css (draf 11z)

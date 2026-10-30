# NPWP Instansi di Master Peserta & Master Company; master Varian dihapus

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:**
  - `[REQ-5]` dan FR-07 `docs/fr-nfr.md` (Master Varian Pelaksanaan) — master dihapus; Paket Produk tidak lagi punya atribut Varian.
  - `input/Master - Buat Program/2026-10-22-klarifikasi-produk/` sejauh menyangkut empat atribut Product Combine — kini tiga atribut.

## Verbatim

> ada revisi dan tambahan lagi:
> 1. Tambah field NPWP instansi di master peserta dan master company
> 2. Master Data > Jenis varian tidak usah ada lagi, maka di product combined juga tidak ada

## Catatan penafsiran

- 1: Master Company sudah punya field NPWP (opsional, FR-31); labelnya menjadi **NPWP Instansi**, tetap opsional. Master Peserta mendapat field baru **NPWP Instansi**, opsional `[USULAN]`; bila peserta terdaftar di Company, NPWP Instansi diisi dari company dan terkunci, seperti Instansi `[USULAN]`. Validasi 15/16 digit mengikuti NFR-06.
- 2: menu Master Data › Varian Pelaksanaan hilang; Product Combine = Produk + Bidang + Sertifikat. Keunikan kombinasi kini atas tiga atribut. Di tempat lain yang selama ini menampilkan varian paket (daftar Master Program, pilihan paket, surat penawaran/quotation/confirmation letter), varian tidak ditampilkan lagi.
- Akibat ERD: tabel `ms_varian_pelaksanaan` dan kolom `paket_produk.id_varian_pelaksanaan` dihapus; UNIQUE paket menjadi tiga kolom; kolom baru `ms_peserta.npwp_instansi`. Company memakai kolom existing `customer.npwp`.

## Extracted

- FR-01, FR-02, FR-07, FR-08, FR-09, FR-10, FR-12, FR-16, FR-22, FR-26, FR-31, FR-43, UC-04, UC-05, UC-07, UC-11, NFR-06, NFR-10, lampiran 7 -> docs/fr-nfr.md (draf 11k)
- ERD draf 9 -> docs/diagrams/erd.drawio; catatan Product Combine -> docs/diagrams/bpmn-to-be.drawio
- mockup -> ui-mockup/produk/app.js (draf 11o)

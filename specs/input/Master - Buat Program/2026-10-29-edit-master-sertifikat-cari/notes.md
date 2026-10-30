# Edit di semua master, "Jenis Sertifikat", dropdown dapat dicari di Product Combine

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran. Kalimat "sekarang hanya ada Hapus" sesuai dengan `input/labmania.html` (master Jenis Produk, Jenis Sertifikasi, Bidang, Varian, Produk hanya bertombol Hapus).
- **Supersedes:**
  - Q-14 `docs/fr-nfr.md` (Paket Produk hanya aktif/nonaktif, tidak dapat diubah) — kini Paket Produk (Produk) **dapat diedit**.
  - Istilah master "Jenis Sertifikasi" (`input/Master - Buat Program/2026-10-22-klarifikasi-produk/`, FR-05) — kini **"Jenis Sertifikat"**.

## Verbatim

> ada tambahan lagi dan revisi dikit ya:
> 1. Tambah action  Edit di semua master: Jenis Produk, Jenis Sertifikasi, Bidang, Varian, Produk. Sekarang hanya ada Hapus.
> 2. Ganti istilah "Jenis Sertifikasi" menjadi "Jenis Sertifikat". ini pada master
> 3. Tambah kolom cari di dropdown pilihan pada product combined, terutama Bidang di form Tambah Produk karena listnya panjang.

## Catatan penafsiran

- 1: master atribut (Jenis Produk, Jenis Sertifikat, Bidang, Varian) sudah punya Edit di spec (FR-08) dan mockup kita, dengan Aktifkan/Nonaktifkan sebagai pengganti hapus. Yang baru: **Produk** (Paket Produk / Product Combine) mendapat **Edit**. Yang dapat diubah: Judul dan keempat atribut, tetap dengan cek keunikan kombinasi `[USULAN]`.
- 2: label tampilan master dan kolom atribut menjadi "Jenis Sertifikat" / "Sertifikat". Nilai Jenis Produk "Sertifikasi" (Pelatihan / Sertifikasi) **tidak** berubah, karena itu bukan nama master. Nama tabel ERD `ms_jenis_sertifikasi` belum diubah (label UI boleh berbeda; diputuskan saat ERD diperbarui).
- 3: dropdown di form Product Combine (Produk, Bidang, Varian, Sertifikat) diberi kolom pencarian. Sudah dituntut NFR-10 tetapi belum ada di mockup.

## Extracted

- FR-05, FR-08, FR-10, FR-12, FR-43 (baru), UC-02, UC-10, NFR-10, Q-14 -> docs/fr-nfr.md (draf 11j)
- mockup -> ui-mockup/produk/app.js (draf 11n)

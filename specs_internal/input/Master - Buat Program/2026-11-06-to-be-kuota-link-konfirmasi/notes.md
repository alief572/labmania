# To be: Varian kembali, kuota peserta, link Konfirmasi Kehadiran, Data Peserta quotation

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-06
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (hanya untuk mockup to be, belum ke FR):**
  - `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/` butir 2 (Varian dihapus): Varian kembali ada.
  - QTN-FR-05 s.d. QTN-FR-07 (peserta dipilih dari Master Peserta / peserta baru di form): diganti kuota peserta + link.
  - QTN-FR-13, QTN-FR-16, QTN-FR-19 (Formulir Konfirmasi dicetak per peserta, Confirmation Letter): formulir diisi lewat link.

## Verbatim

> 1. sebelumnya pernaha da jenis varian, sekarang dibuat jadi ada lagi
> 2. Quotation: ganti select peserta menjadi input kuota peserta.
> 3. Peserta tidak diambil otomatis dari Master Peserta. Data peserta baru ada setelah Konfirmasi Kehadiran diisi.
> 4. Link pengisian data peserta digenerate otomatis saat Quotation dibuat, dan hanya berlaku untuk Quotation itu. Tiap calon peserta mengisi data dirinya sendiri.
> 5. Hasil Konfirmasi Kehadiran masuk ke Data Peserta di Quotation, lengkap dengan preview dan tombol download untuk Sales/Marketing. ada modul/tab yang menampung
> 6. Di Data Peserta quotation, Sales/Marketing tetap bisa menambah peserta secara manual.
>
> konfirmasi letter ini tuh yang menjadi dokumen output si quotation itu, coba dulu deh benerin

Permintaan sebelumnya di sesi yang sama: mockup to be dibuat **terpisah** dari mockup as is.

## Catatan penafsiran (dipakai di mockup to be, perlu konfirmasi)

- 1: master **Varian Pelaksanaan** kembali seperti sebelum 29-10-2026 (isi: In House Training, Workshop,
  Workshop Berbasis Kompetensi, Workshop Lisensi BSN); Product Combine kembali empat atribut
  (Produk, Bidang, Varian, Sertifikat); keunikan kombinasi atas empat atribut.
- 2: form quotation langkah 1 tidak lagi memilih peserta; diganti **Kuota peserta** (angka). Harga dihitung
  dari kuota sampai Deal; setelah Deal dari peserta Deal. B2B tetap minimal 10 `[USULAN]`.
  Quotation **Personal** tidak lagi punya "peserta pertama" untuk Kepada, jadi diberi isian **Kepada** `[USULAN]`.
- 3: peserta quotation **tidak** disimpan ke Master Peserta; datanya milik quotation itu `[USULAN]`
  (perlu dikonfirmasi apakah tetap disalin ke Master Peserta setelah Deal).
- 4: link dibuat saat quotation disimpan (Draft), satu link per quotation. Link menerima isian selama
  quotation Draft/Terkirim dan kuota belum penuh; ditutup saat Deal/Batal `[USULAN]`.
  Isian formulir mengikuti langkah Formulir Konfirmasi (template, isian, pernyataan).
- 5: detail quotation punya tab **Data Peserta**: daftar isian, Preview per peserta, Download per peserta
  dan Download semua.
- 6: tombol **+ Tambah peserta manual** di tab Data Peserta.
- "Confirmation Letter = dokumen output quotation": Confirmation Letter kini dibentuk dari isian
  Konfirmasi Kehadiran (satu halaman per peserta), bukan formulir kosong yang dicetak bersama quotation.
  PDF quotation menjadi Penawaran + Syarat & Ketentuan saja `[USULAN]`.

## Extracted

- mockup -> `ui-mockup/produk-to-be/` (mockup as is `ui-mockup/produk/` tidak diubah)
- FR/NFR, ERD, workbook: **belum** diperbarui (menunggu konfirmasi to be)

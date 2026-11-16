# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Penawaran (Quotation)
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Proses pembuatan penawaran (Quotation) kepada klien sebelumnya dilakukan secara manual, rentan kesalahan manusia (salah hitung subtotal), dan sulit dilacak riwayat perubahannya (revisi). 
- **Objective:** Membuat modul terpusat untuk *create, read, update, delete* (CRUD) penawaran yang sudah terintegrasi dengan persetujuan internal dan *auto-generate* PDF untuk klien.
- **Target Audience:** Tim Sales/Marketing yang menyusun penawaran dan Direktur/Manajemen yang memberikan *approval*.

---

## 3. Goals & Success Metrics
- **Business Goals:** Mempercepat SLA (Service Level Agreement) pembuatan penawaran dari berhari-hari menjadi hitungan menit, dan meminimalisir kesalahan perhitungan nilai kontrak (Margin of Error mendekati 0%).
- **Success Metrics (KPI):**
  - Pembuatan penawaran dan *approval* selesai dalam waktu < 1 jam.
  - Jumlah penawaran yang di-*generate* menjadi PDF meningkat 20% dalam 1 bulan pertama rilis.
- **Non-Goals:** Modul ini tidak mengurusi tagihan (Invoicing) – hanya sebatas penawaran komersial sebelum disetujui klien.

---

## 4. User Journey & Flow
- **User Persona:** Budi (Sales Manager) – Butuh mengirim penawaran cepat ke Klien A.
- **User Journey Map:**
  1. Budi masuk ke Modul Penawaran dan menekan tombol *New Quotation*.
  2. Budi memilih Customer dan mengisikan rincian biaya (Mandays, Akomodasi, dsb).
  3. Budi menekan *Save*, status menjadi Draft.
  4. Budi memajukan dokumen untuk *Request Approval*.
  5. Manajemen meng-*approve*.
  6. Budi men-*download* PDF dan mengirimkan via email ke klien.
  7. Jika klien negosiasi, Budi menekan *Revisi* (membuat versi/history baru).

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Create Penawaran | Form untuk menginput master data penawaran (Customer, Komponen Harga, Diskon, PPN). | P0 (Wajib) |
| FR-02 | Revisi (Versioning) | Fitur revisi yang menggandakan data sebelumnya dengan mengubah penomoran seri revisi, tanpa menghapus data asli. | P0 (Wajib) |
| FR-03 | Approval Workflow | Status berjenjang (Draft -> Waiting Approval -> Approved / Rejected) | P0 (Wajib) |
| FR-04 | Deal & Lose Flag | Kemampuan menandai apakah penawaran yang *approved* akhirnya "Deal" atau "Lose" oleh klien. | P1 (Penting) |
| FR-05 | Export PDF | Mencetak penawaran menjadi dokumen berformat PDF yang elegan dengan logo perusahaan. | P0 (Wajib) |

---

## 6. Non-Functional Requirements
- **Performance:** Waktu untuk *generate* PDF maksimal 5 detik.
- **Security:** Hanya pembuat (Sales) dan Admin yang bisa melihat harga *bottom line* / margin.
- **Scalability & Platform:** Diakses via Web Browser Desktop (dioptimalkan untuk resolusi layar lebar / Laptop).

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** 
  - Gunakan DataTables untuk daftar penawaran.
  - Tampilkan *Badge* warna untuk status (Kuning=Draft, Biru=Waiting, Hijau=Approved, Merah=Rejected).

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Data Master Customer dan Master Biaya (Akomodasi, Lab, dll) sudah diisi sebelumnya di modul Master.
- **Constraints:** Format penomoran dokumen sudah mutlak dan mengikuti standar ISO/SOP perusahaan.

---

## 9. Future Iterations / Phase 2
- Notifikasi email otomatis kepada Direktur saat ada penawaran baru yang butuh *approval*.
- Integrasi *Digital Signature* (Tanda tangan digital) pada PDF penawaran.

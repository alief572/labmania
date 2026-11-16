# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Subcont Perusahaan
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Untuk proyek besar, perusahaan perlu melakukan sub-kontrak ke perusahaan lain (B2B Vendor). Profil perusahaan vendor tersebut tidak bisa disamakan dengan *Master Customer* karena posisinya adalah rekanan pengeluaran (Biaya), bukan pemasukan.
- **Objective:** Membuat modul direktori profil perusahaan Vendor (Sub-Contractor).
- **Target Audience:** Tim Operasional dan Procurement.

---

## 3. Goals & Success Metrics
- **Business Goals:** Memusatkan data legalitas dan rekening Vendor / Mitra Perusahaan agar *transfer* uang (Kasbon/Realisasi) dari *Finance* tidak pernah salah sasaran.
- **Success Metrics (KPI):** Mempercepat waktu pencarian data rekening dan validasi NPWP vendor saat *Finance* melakukan pembayaran B2B.

---

## 4. User Journey & Flow
- Admin Procurement menambah Vendor (PT. Rekanan Sukses) -> Mengisi alamat, NPWP, nama PIC, Bank, dan Rekening. -> Vendor ini kelak akan dipilih sebagai Subcont di tahap *Project Budgeting*.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Profil Vendor (B2B) | Form untuk menyimpan nama PT, NPWP, Alamat Perusahaan. | P0 (Wajib) |
| FR-02 | Data PIC Vendor | Kontak person perwakilan dari Vendor (Nama, Jabatan, HP). | P0 (Wajib) |
| FR-03 | Rekening Pembayaran | Informasi Bank dan No Rekening Perusahaan Vendor (Bukan pribadi). | P0 (Wajib) |

---

## 6. Non-Functional Requirements
- **Performance:** Tabel dapat difilter atau diurutkan berdasarkan Vendor yang masih aktif.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Memiliki struktur form hampir mirip *Master Customer*, namun dengan orientasi data yang lebih simpel (tanpa opsi Multi-Address Invoicing).

---

## 8. Constraints & Assumptions
- **Assumptions:** Data subcont adalah entitas yang mutlak dan hanya dikelola oleh satu pintu (Procurement/Finance).

---

## 9. Future Iterations / Phase 2
- *Vendor Whitelisting/Blacklisting*: Status untuk menandai apakah vendor tersebut direkomendasikan untuk proyek mendatang atau masuk daftar *blacklist* akibat pekerjaan buruk di proyek sebelumnya.

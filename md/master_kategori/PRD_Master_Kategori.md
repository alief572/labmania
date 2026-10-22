# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Kategori (Kategori Konsultasi)
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Perusahaan konsultan memiliki banyak produk dan layanan. Diperlukan modul untuk mengkategorikan produk-produk tersebut (Contoh: "Kategori Sertifikasi ISO", "Kategori Pelatihan", dsb) untuk keperluan pelaporan dan penyusunan SPK.
- **Objective:** Membuat master data Kategori Utama untuk jenis layanan jasa konsultasi.
- **Target Audience:** Admin Master Data / Tim Produk.

---

## 3. Goals & Success Metrics
- **Business Goals:** Mengelompokkan jenis *revenue* perusahaan berdasarkan kategori layanan.
- **Success Metrics (KPI):** Mempermudah *filter* dan pencarian di *Dashboard Sales/Revenue*.

---

## 4. User Journey & Flow
- Admin membuka Master Kategori -> Menambah nama Kategori -> Menggunakan kategori ini saat menyusun *Master Konsultasi*.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | CRUD Master Kategori | Form teks untuk mendaftarkan nama kategori. | P0 (Wajib) |
| FR-02 | Toggle Status Aktif | Fitur untuk meng- *enable* atau mendisable kategori jika layanan sudah tidak dijual. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Scalability:** Berbasis Web Desktop.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Sederhana, form satu halaman / *Modal Box*.

---

## 8. Constraints & Assumptions
- **Assumptions:** Penamaan kategori bersifat final dan dikelola oleh admin tingkat tinggi (Head of Product).

---

## 9. Future Iterations / Phase 2
- *Mapping* kategori layanan secara otomatis ke sistem Akuntansi (Chart of Accounts) untuk pemisahan pajak.

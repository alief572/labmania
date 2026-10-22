# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Konsultasi (Paket Layanan)
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Perusahaan memiliki bundel "Paket" layanan konsultasi (Misal: "Paket Pendampingan ISO 9001 Lengkap"). Diperlukan master paket (*Header* dan *Detail*) untuk mempercepat pembuatan Penawaran (Quotation).
- **Objective:** Membuat modul yang merakit (merelasikan) Master Kategori dan Master Aktifitas menjadi satu bundel Master Konsultasi.
- **Target Audience:** Tim Produk / Management.

---

## 3. Goals & Success Metrics
- **Business Goals:** Tim Sales hanya perlu memilih "1 Paket Layanan" saat membuat penawaran, dan sistem akan otomatis memunculkan seluruh rincian aktifitas, sehingga menghemat waktu pengetikan ulang.
- **Success Metrics (KPI):** Peningkatan kecepatan pembuatan Draf Quotation hingga 50%.

---

## 4. User Journey & Flow
- Admin membuka Master Konsultasi -> Klik Add -> Mengisi Nama Paket Konsultasi (Header) -> Menambah baris (*Add Row*) untuk setiap Aktifitas yang termasuk dalam paket (Detail) -> Save.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Header-Detail Form | Form input yang memiliki *Header* (Nama Layanan) dan *Body/Detail* (List Aktifitas / Mandays). | P0 (Wajib) |
| FR-02 | Dynamic Detail Rows | Javascript (jQuery) untuk menambah / menghapus baris komponen layanan sebelum di- *submit*. | P0 (Wajib) |

---

## 6. Non-Functional Requirements
- **Security:** Validasi backend menggunakan *Transaction* ganda (karena menyimpan ke tabel *Header* dan tabel *Detail* sekaligus).

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Memiliki kemiripan visual dengan form faktur (*Invoice-like format*). Ada bagian atas untuk informasi utama, dan grid/tabel di bawahnya untuk rincian.

---

## 8. Constraints & Assumptions
- **Assumptions:** Data Master Aktifitas sudah *ready*.

---

## 9. Future Iterations / Phase 2
- Fitur duplikasi paket ( *Clone Package* ) agar Admin lebih cepat membuat variasi paket baru (misal: "ISO 9001 Basic" vs "ISO 9001 Premium").

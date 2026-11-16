# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Aktifitas
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Setiap proyek konsultasi terdiri dari tahapan-tahapan yang diukur menggunakan *Mandays* (Hari Kerja Konsultan). Jenis tahapan ini perlu diklasifikasikan (misalnya: "Audit Tahap 1", "Pelatihan Awareness", "Meeting").
- **Objective:** Membuat bank data referensi Master Aktifitas untuk dipakai di rincian Penawaran dan Laporan Kunjungan.
- **Target Audience:** Admin Konsultan / Operasional.

---

## 3. Goals & Success Metrics
- **Business Goals:** Standarisasi nomenklatur / penamaan kegiatan operasional agar tidak ada ambiguitas saat pelaporan ke klien.
- **Success Metrics (KPI):** 100% konsultan menggunakan *dropdown* aktifitas yang baku saat membuat *Laporan Kunjungan* (tidak mengetik bebas).

---

## 4. User Journey & Flow
- Admin membuat Master Aktifitas -> Sales memilih Aktifitas saat membuat Quotation -> Konsultan mengeksekusi dan melaporkan Aktifitas tersebut di Modul Kunjungan.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | CRUD Master Aktifitas | Pendaftaran nama *Activity* dan *Bobot/Indikator* (jika ada). | P0 (Wajib) |

---

## 6. Non-Functional Requirements
- **Performance:** Form *lightweight* (respon < 1 detik).

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Desain standar menggunakan tabel Master.

---

## 8. Constraints & Assumptions
- **Assumptions:** Nama aktifitas bisa ditambahkan sewaktu-waktu (fleksibel) jika ada layanan jasa baru.

---

## 9. Future Iterations / Phase 2
- *Activity Costing*: Memasang nilai harga *default* per hari kerja (Mandays Rate) langsung dari tabel ini.

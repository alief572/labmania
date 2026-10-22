# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Biaya
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Komponen biaya di luar gaji (misal: "Tiket Pesawat", "Hotel", "Sewa Mobil", "Uang Makan") harus dibakukan agar mudah dilacak dan diakumulasi.
- **Objective:** Membuat referensi baku untuk entitas biaya Akomodasi dan Beban Lain-lain (Others).
- **Target Audience:** Admin Finance.

---

## 3. Goals & Success Metrics
- **Business Goals:** Membakukan jenis klasifikasi akun (seperti Chart of Account mini untuk keperluan operasional proyek).
- **Success Metrics (KPI):** 100% Modul Project Budgeting menginduk pada klasifikasi ini untuk pencatatan *Akomodasi* dan *Others*.

---

## 4. User Journey & Flow
- Admin Finance login -> Buka menu Master Biaya -> Menambahkan "Uang Saku Harian" -> Menyimpan. (Setelah itu, opsi ini akan muncul saat konsultan mengambil Kasbon Akomodasi).

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | CRUD Master Biaya | Pembuatan nama biaya operasional / akomodasi. | P0 (Wajib) |
| FR-02 | Kategori Biaya | Flag pembeda apakah ini biaya "Akomodasi" atau "Others". | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Ringan, hanya berisi beberapa puluh baris data (statik).

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** List Tabel sederhana.

---

## 8. Constraints & Assumptions
- **Assumptions:** Data master ini jarang berubah (*Low Frequency Update*).

---

## 9. Future Iterations / Phase 2
- *Mapping* ke *Chart of Account* (COA) Sistem Akuntansi Utama perusahaan (misal: Sistem Jurnal/SAP).

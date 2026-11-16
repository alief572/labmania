# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Tenaga Ahli (Subcont)
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Seringkali proyek membutuhkan pihak ketiga secara perseorangan (Freelance Expert, Auditor Eksternal, dll). Data mereka perlu di-*manage* di luar modul *Employee* karena mereka tidak digaji bulanan, melainkan dibayar per proyek (honorarium).
- **Objective:** Mengelola pangkalan data Tenaga Ahli Eksternal (Perorangan).
- **Target Audience:** Tim Operasional dan HR.

---

## 3. Goals & Success Metrics
- **Business Goals:** Membangun kolam talenta (*Talent Pool*) tenaga ahli yang mudah di-*assign* ke proyek secara instan beserta tarif harian (*Mandays Rate*) mereka.
- **Success Metrics (KPI):** 100% tenaga ahli subcont memiliki data NPWP dan Rekening valid sebelum diikutkan ke *Project Budgeting*.

---

## 4. User Journey & Flow
- Admin memasukkan profil Bapak Budi (Pakar K3) -> Menuliskan rate mandays Rp 1.500.000/hari -> Menyimpan. Saat PM membuat *Budgeting* dan *Kasbon Subcont Tenaga Ahli*, nama Budi akan muncul di *dropdown*.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Profil Tenaga Ahli | Form input data diri (KTP, Nama, NPWP). | P0 (Wajib) |
| FR-02 | Rate Mandays Standard | Form *default rate* harian sebagai patokan nilai honorarium (*fee*). | P0 (Wajib) |
| FR-03 | Bank Account Mapping | Menyimpan informasi Bank & Nomor Rekening Tenaga Ahli. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Security:** Karena menyimpan data NPWP dan Rekening pribadi, form harus diamankan agar tidak sembarang admin bisa mengedit.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Memiliki struktur form mirip *Master Customer*, dipecah menjadi *Data Personal* dan *Data Keuangan/Bank*.

---

## 8. Constraints & Assumptions
- **Assumptions:** Rate tenaga ahli bisa bervariasi per proyek, sehingga rate di master ini hanya berfungsi sebagai *baseline/default*.

---

## 9. Future Iterations / Phase 2
- Modul *Rating & Review* tenaga ahli setelah proyek selesai (sebagai bahan evaluasi pemanggilan kembali di masa depan).

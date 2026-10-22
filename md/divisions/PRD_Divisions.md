# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Divisions
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Untuk membentuk struktur hierarki perusahaan yang dinamis, sistem memerlukan *dictionary* / daftar master divisi yang baku.
- **Objective:** Membuat antarmuka CRUD (Create, Read, Update, Delete) sederhana untuk memanajemen data divisi dalam entitas *Company* terkait.
- **Target Audience:** Admin HR / Superadmin.

---

## 3. Goals & Success Metrics
- **Business Goals:** Mencegah terjadinya *typo* (salah ketik) saat proses *onboarding* karyawan baru.
- **Success Metrics (KPI):** 100% divisi terdaftar dan menjadi parameter wajib (*dropdown*) di form pendaftaran karyawan.

---

## 4. User Journey & Flow
- **User Persona:** Admin Sistem.
- **User Journey Map:** Admin masuk ke Master Divisions -> Menekan tombol *Add* -> Memasukkan Nama Divisi dan mengasosiasikannya dengan Perusahaan (*Company*) -> Menekan *Save*.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | CRUD Division | Fasilitas tambah, edit, hapus, dan lihat data Divisi. | P0 (Wajib) |
| FR-02 | Company Mapping | *Dropdown* yang mengikat suatu Divisi ke *Company* tertentu (Untuk mendukung Multi-Company). | P0 (Wajib) |

---

## 6. Non-Functional Requirements
- **Performance:** Memuat tabel kurang dari 1 detik (Datatables).
- **Security:** Proteksi hapus data (*Restrict Delete*) jika ID Divisi tersebut sudah terpakai di tabel *Employee*.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Cukup gunakan form *Modal/Popup* (*SweetAlert* / Bootstrap Modal) agar tidak perlu memuat ulang halaman utama.

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Struktur organisasi bersifat konvensional (Perusahaan -> Divisi).

---

## 9. Future Iterations / Phase 2
- *Org-Chart Generator* (Pohon Struktur Organisasi) divisualisasikan otomatis berdasarkan relasi tabel ini.

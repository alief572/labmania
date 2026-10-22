# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Employee
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Data karyawan perusahaan selama ini belum tersentralisasi dengan baik, membuat modul absensi, *payroll*, dan penugasan proyek sulit melakukan *mapping* data secara riil.
- **Objective:** Membuat modul *Master Employee* sebagai pusat data HRIS mini yang mencatat informasi dasar, gaji, keluarga, hingga riwayat pendidikan.
- **Target Audience:** Tim HR (Human Resource) dan Manajemen.

---

## 3. Goals & Success Metrics
- **Business Goals:** Menciptakan *Single Source of Truth* untuk data seluruh karyawan yang terhubung langsung ke struktur penugasan proyek (SPK).
- **Success Metrics (KPI):**
  - 100% karyawan *onboarded* ke dalam sistem dengan data NIK, Divisi, dan Gaji yang terenkripsi.
- **Non-Goals:** Bukan untuk modul pencatatan KPI harian atau *Performance Review* tahunan.

---

## 4. User Journey & Flow
- **User Persona:** Budi (HR Admin).
- **User Journey Map:**
  1. Budi masuk ke halaman Master Employee dan menekan tombol *Add*.
  2. Budi mengisi data pribadi (Nama, Agama, dll), Gaji, Divisi, dan Jabatan.
  3. Budi menambahkan data keluarga (*Family*) dan Pendidikan (*Education*) melalui form *dynamic append*.
  4. Budi menyimpan data. Sistem men- *generate* NIK otomatis.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | CRUD Employee | Form input terpusat untuk profil, NIK Auto-Generate, dan penempatan Divisi. | P0 (Wajib) |
| FR-02 | Field Encryption | Fitur enkripsi pada data sensitif seperti `salary`, `jabatan`, dan `pulsa` menggunakan fungsi `Enkripsi()`. | P0 (Wajib) |
| FR-03 | Dynamic Dependent Dropdown | Dropdown yang bergantung antara Company -> Division -> Department -> Title. | P1 (Penting) |
| FR-04 | Sub-Form Keluarga & Pendidikan | Form multi-baris untuk memasukkan riwayat pendidikan dan tanggungan keluarga. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Form dengan multi-dropdown harus memuat dalam waktu < 1 detik.
- **Security:** Hak akses sangat dibatasi (RBAC). Hanya HR yang memiliki `managePermission` yang bisa melihat / meng-edit kolom gaji.
- **Scalability & Platform:** Web Browser Desktop.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Tampilan menggunakan *tab menu* atau *accordion* untuk memisahkan Data Pribadi, Keluarga, dan Pendidikan agar *scroll* tidak terlalu panjang.

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Data Master Divisi, Departemen, Posisi, dan *Title* sudah diinput sebelumnya.
- **Constraints:** Format NIK dibuat otomatis berdasarkan parameter Tahun dan Bulan (contoh: 2607XXX).

---

## 9. Future Iterations / Phase 2
- Integrasi ke mesin *fingerprint* / biometrik secara *real-time*.
- *Self-service portal* agar karyawan bisa melakukan *update* data keluarga mereka sendiri.

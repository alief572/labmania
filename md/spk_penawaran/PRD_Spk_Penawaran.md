# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul SPK Penawaran
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Setelah penawaran disetujui (Deal) oleh klien, tim operasional tidak memiliki *dashboard* atau instruksi resmi untuk mulai bekerja. SPK (Surat Perintah Kerja) manual seringkali terlambat turun.
- **Objective:** Membuat modul peralihan di mana "Penawaran Deal" diterjemahkan secara administratif menjadi "Surat Perintah Kerja" internal dengan *approval* multi-level.
- **Target Audience:** Project Manager (PM), Admin Operasional, dan Direksi (untuk *approval*).

---

## 3. Goals & Success Metrics
- **Business Goals:** Menghubungkan secara instan proses komersial (Sales) dengan proses operasional (Konsultan), mencegah *delay* pekerjaan akibat dokumen yang mandek.
- **Success Metrics (KPI):**
  - SLA pembuatan SPK kurang dari 1 hari kerja setelah Quotation ditandai *Deal*.
  - 100% konsultan mendapatkan notifikasi/keterlihatan alokasi tugas dari SPK.
- **Non-Goals:** Tidak mencakup proses *budgeting* operasional (hal ini dipisah ke Modul *Project Budgeting*).

---

## 4. User Journey & Flow
- **User Persona:** Sarah (Admin Operasional).
- **User Journey Map:**
  1. Sarah membuka Modul SPK Penawaran dan melihat ada 1 Penawaran berstatus "Deal" namun belum dibuatkan SPK.
  2. Sarah mengklik *Create SPK*.
  3. Sistem menarik otomatis data Penawaran. Sarah melengkapi target tanggal selesai (`waktu_to`) dan menyusun instruksi khusus.
  4. Sarah menyimpan (Draft).
  5. Sarah mengajukan *Request Approval*.
  6. Manajer (Approval Level 1) & Direktur (Approval Level 2) menyetujui.
  7. SPK aktif dan siap di- *budgeting*.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | List Pending SPK | Menampilkan daftar Penawaran berstatus `Deal` yang belum di- *convert* menjadi SPK. | P0 (Wajib) |
| FR-02 | Auto-Populate Form | Menarik otomatis data nama klien, total nilai, dan lingkup kerja dari Penawaran. | P0 (Wajib) |
| FR-03 | Dual-Level Approval | Proses *approval* berjenjang (Level 1: Manager, Level 2: Direktur). | P0 (Wajib) |
| FR-04 | Print SPK | Modul cetak dokumen SPK internal berbasis HTML/PDF. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Tarikan data dari Penawaran harus dilakukan secara efisien via JOIN untuk menghindari loading lama.
- **Security:** Hanya *user* dengan hak akses *Manager* atau *Direktur* yang bisa menekan tombol Approve.
- **Scalability & Platform:** Berbasis Web Desktop.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** 
  - Gunakan *tab navigation* jika formulir SPK terlalu panjang, pisahkan antara Data Penawaran (Read-only) dan Input SPK.

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Data Quotation yang masuk ke SPK dipastikan final dan harganya tidak akan berubah lagi.
- **Constraints:** SPK tidak bisa diubah jika *Approval Level 2* sudah terjadi. Segala perubahan (Adendum) akan ditangani di sistem eksternal atau versi berikutnya.

---

## 9. Future Iterations / Phase 2
- Integrasi penjadwalan *Gantt Chart* otomatis berdasarkan `waktu_to` dari SPK.
- Notifikasi WhatsApp/Email kepada konsultan terkait saat SPK di- *publish*.

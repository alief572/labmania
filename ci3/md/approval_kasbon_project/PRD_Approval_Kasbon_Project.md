# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Approval Kasbon Project
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 10 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Setelah tim operasional mengajukan dana melalui modul Kasbon Project, sistem memerlukan mekanisme persetujuan (otorisasi) terpusat untuk memvalidasi kelayakan anggaran. Selain itu, diperlukan jembatan otomatis untuk meneruskan perintah bayar ke departemen Keuangan (Finance), khususnya untuk metode bayar B2B (Direct Payment).
- **Objective:** Membuat antarmuka otorisasi kasbon yang transparan, merinci seluruh anggaran vs pengajuan, serta menyediakan integrasi antar-database untuk mencetak tagihan di sistem Finance.
- **Target Audience:** Manajer / Direktur (Sebagai Approver), dan Tim Operasional (Viewer).

---

## 3. Goals & Success Metrics
- **Business Goals:** Memangkas birokrasi *approval* berbasis kertas. Menghubungkan secara instan sistem *Project Operations* dengan sistem *Accounting & Finance* (*sendigs_finance*).
- **Success Metrics (KPI):**
  - Mengurangi waktu proses pencairan dana operasional dari berhari-hari menjadi hitungan jam.
  - Memastikan *Direct Payment* tercatat otomatis di sistem Keuangan tanpa perlu *data entry* ganda.
- **Non-Goals:** Modul ini BUKAN aplikasi pencairan bank langsung (*payment gateway/API banking*), melainkan sebatas otorisasi birokrasi.

---

## 4. User Journey & Flow
- **User Persona:** Bapak Budi (Manajer/Approver).
- **User Journey Map:**
  1. Budi login dan masuk ke "Approval Kasbon Project".
  2. Sistem menampilkan daftar kasbon yang berstatus "Waiting Approval".
  3. Budi membuka salah satu dokumen, melihat rincian sisa limit dan besaran yang diminta.
  4. Jika Budi menolak (Reject), Budi harus mengetikkan *Reject Reason*, lalu status dikembalikan ke pemohon.
  5. Jika Budi menyetujui (Approve), sistem secara *background* melempar data pembayaran (termasuk rekening bank) ke *database finance* (*Cross-DB*). 

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | List Pending Approval | Tabel *dashboard* berisi semua pengajuan (`sts` kosong/nol) yang terhindar dari *Soft Delete*. | P0 (Wajib) |
| FR-02 | Detailed Review Panel | Halaman rincian yang me-*load* seluruh komponen: Akomodasi, Lab, Subcont, beserta riwayat OVB (Overbudget) di dalamnya. | P0 (Wajib) |
| FR-03 | Reject Form with Notes | Tombol penolakan yang wajib melampirkan alasan penolakan, untuk direkam di *History*. | P0 (Wajib) |
| FR-04 | Auto Finance Bridging | Kemampuan mengeksekusi integrasi *cross-database* ke tabel `tr_direct_payment` (DB *sendigs_finance*) jika metode pembayarannya *Direct Payment*. | P0 (Wajib) |
| FR-05 | Auto Request Payment | Membuat antrean `request_payment` internal agar data siap diklaim pada tahap selanjutnya. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Form *Review* memanggil data dari puluhan *views* dan *tables* berbeda, *response time* harus optimal di bawah 2 detik.
- **Reliability:** Fungsi *Approve* harus 100% *Atomic*. Jika insert ke DB Finance gagal, maka status *Approval* di sistem operasi tidak boleh berubah menjadi 'Approved'.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Tampilan *Review Panel* harus menampilkan komparasi visual (Anggaran vs Pengajuan), gunakan *badge* warna untuk membedakan metode bayar (Direct Payment, Kasbon, PO).

---

## 8. Constraints & Assumptions
- **Assumptions:** Server *Database Finance* selalu *online* dan *credential*-nya (*username/password*) sama dengan server aplikasi saat ini.

---

## 9. Future Iterations / Phase 2
- Notifikasi *Push/Email* ke *Approver* saat ada dokumen baru masuk.

# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Kasbon Project
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026 (Updated: 10 Juli 2026)

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Permintaan dana operasional (Kasbon) oleh konsultan sering melebihi batas anggaran awal tanpa validasi yang ketat. Di samping itu, metode pengajuan dana tidak selalu berupa kasbon tunai, melainkan bisa berupa pembayaran langsung ke vendor (Direct Payment) atau sistem Purchase Order (PO).
- **Objective:** Mengunci pengajuan kasbon agar selalu <= Total Plafon Budget. Apabila pengeluaran melebihi plafon, harus melalui prosedur *Overbudget* (OVB). Selain itu, mengakomodasi pemilihan metode pembayaran.
- **Target Audience:** Konsultan Lapangan (Pemohon) dan Tim Operasional.

---

## 3. Goals & Success Metrics
- **Business Goals:** Menghilangkan kebocoran anggaran operasional tak terkontrol dan memperjelas skema pencairan dana (Tunai vs B2B Transfer).
- **Success Metrics (KPI):**
  - 100% *Rejection* otomatis oleh sistem jika form kasbon melebihi Sisa Limit Plafon.
  - Mempercepat validasi tipe pembayaran (Kasbon/DP/PO) di sisi Finance.
- **Non-Goals:** Tidak mencakup otorisasi / persetujuan akhir dan transfer dana (hal ini ditangani oleh Modul *Approval Kasbon Project*).

---

## 4. User Journey & Flow
- **User Persona:** Dina (Konsultan Lapangan).
- **User Journey Map:**
  1. Dina masuk ke menu Kasbon Project dan melihat sisa budget proyeknya.
  2. Dina mengajukan form pengeluaran, dan memilih **Metode Pembayaran** (misal: "Direct Payment" untuk bayar sewa mobil).
  3. Sistem memvalidasi apakah uang yang diajukan melebihi sisa plafon. Jika melampaui, tombol pengajuan akan berubah menjadi *Request OVB*.
  4. Jika dana cukup, draf akan diteruskan ke modul Approval.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Smart Baseline Tracker | Backend secara terus-menerus mengecek (Total Budget + OVB) - Total Kasbon, termasuk elemen baru seperti `subcont_perusahaan`. | P0 (Wajib) |
| FR-02 | Metode Pembayaran | Opsi tipe pengajuan dana: Kasbon (Tunai), Direct Payment, atau PO (Purchase Order). | P0 (Wajib) |
| FR-03 | Dynamic UI Button | Menyembunyikan tombol pengajuan reguler dan memunculkan "Request OVB" jika sisa limit sudah minus/habis. | P0 (Wajib) |
| FR-04 | Kategori Biaya Lengkap | Mengelompokkan input menjadi Subcont, Akomodasi, Others, Lab, Tenaga Ahli, hingga Subcont Perusahaan. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Kueri agregasi UNION ALL sangat besar (memasukkan 14-16 kueri penggabungan) sehingga harus dilayani via AJAX Server-side.
- **Security:** Validasi mutlak pada `get_data_spk()` agar data di sisi antarmuka tidak bisa dimanipulasi dengan *Inspect Element*.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** 
  - Sediakan Dropdown mencolok untuk *Metode Pembayaran*.
  - Indikator Progress Bar Sisa Plafon harus ada di layar.

---

## 8. Constraints & Assumptions
- **Assumptions:** Budget di Modul Project Budgeting bersifat mutlak sebagai batas penahan (Plafon).

---

## 9. Future Iterations / Phase 2
- *Machine Learning* untuk mendeteksi anomali (misal: Jika pengajuan hotel di luar kota terlalu mahal dibandingkan rata-rata *history* perusahaan).

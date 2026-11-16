# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Project Budgeting
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Meski SPK sudah terbit, tim internal perlu memiliki "Pagar Anggaran" (*Financial Baseline*) yang ketat sebelum turun ke lapangan, guna mencegah kebocoran *cashflow* dan hilangnya profit.
- **Objective:** Membuat modul yang menerjemahkan nilai anggaran "Estimasi" di SPK menjadi anggaran "Final / Plafon Aktual" yang mengikat per divisi atau per *item expense*.
- **Target Audience:** Project Leader (PL), Admin Finance, dan Manajemen/Approval.

---

## 3. Goals & Success Metrics
- **Business Goals:** Mengamankan profitabilitas proyek dengan mengunci plafon maksimal untuk setiap elemen biaya, sehingga *gross profit margin* tetap terjaga.
- **Success Metrics (KPI):**
  - 100% Proyek yang berjalan memiliki *budgeting* yang telah disetujui.
  - Waktu telaah selisih Estimasi vs Final oleh direksi berkurang dari berjam-jam menjadi < 5 menit per proyek.
- **Non-Goals:** Modul ini BUKAN untuk mencatat nota bon (hal itu ada di *Expense Report*), melainkan sebatas rancangan/alokasi budget.

---

## 4. User Journey & Flow
- **User Persona:** Andi (Project Leader).
- **User Journey Map:**
  1. Andi melihat ada 1 SPK baru *Approved* yang belum memiliki *budgeting*.
  2. Andi mengklik *Create Budget*.
  3. Andi membandingkan *Budget Estimasi* (kiri) dengan *Input Budget Final* (kanan) untuk Subcont, Akomodasi, dsb.
  4. Andi melihat *Summary Gross Profit* secara *real-time* di bagian bawah halaman.
  5. Andi men- *submit* Draf untuk *Approval*.
  6. Jika dirasa kurang *margin*-nya, Direktur menolak dan Andi harus melakukan Revisi (Edit). Jika disetujui, Plafon dikunci.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Filter SPK Berjenjang | Menarik daftar SPK yang telah *Approved* namun belum memiliki budget. | P0 (Wajib) |
| FR-02 | Komparasi Biaya (Estimasi vs Final) | Form dua pilar yang menampilkan Harga Estimasi (SPK) vs Harga Final (Alokasi aktual). | P0 (Wajib) |
| FR-03 | Auto-Calculate Summary | Menghitung rasio/total selisih biaya dan *Gross Margin* secara *real-time* menggunakan JS. | P1 (Penting) |
| FR-04 | Hapus & Tulis Ulang (Wipe & Replace) | Logika *database batch* yang menghapus data lama dan merekam data final baru pada saat *user* melakukan edit (Draft). | P0 (Wajib) |
| FR-05 | History Approval | Perekaman jejak persetujuan / penolakan draf budget oleh Manajemen. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Kueri *database* harus dibungkus dengan *Transaction* (`db->trans_begin()`) agar *insert batch* pada 7 tabel anak terhindar dari *corrupt data*.
- **Security:** Plafon biaya final tidak dapat diedit setelah status mencapai *Approved* (`sts = 1`).
- **Scalability & Platform:** Web Based Desktop (Kebutuhan visual layar lebar).

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** 
  - Bagian form disarankan menggunakan tabel atau kolom komparasi (Kiri: Estimasi, Kanan: Aktual).
  - Ringkasan di *footer* harus terlihat tebal/berwarna kontras jika nilai profit terlalu rendah.

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Data SPK yang ditarik sudah memiliki nilai estimasi yang lengkap.
- **Constraints:** Modul sangat bergantung pada konsistensi struktur tabel turunan penawaran (`akomodasi`, `lab`, `subcont`, dll).

---

## 9. Future Iterations / Phase 2
- Sistem alert merah (*Warning*) apabila Project Leader merencanakan biaya `Final` yang lebih besar daripada `Estimasi`.
- Dashboard analitik untuk melihat rata-rata profitabilitas per proyek per bulan.

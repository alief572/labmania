# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Laporan Kunjungan (Visit Report)
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Konsultan kerap bekerja di luar kantor / di lokasi klien tanpa sistem *tracking* harian yang transparan. Klien juga sering meminta bukti fisik "Mandays" (Hari Kerja) yang dihabiskan.
- **Objective:** Membuat modul Laporan Kunjungan yang mewajibkan perekaman *Time In & Time Out* berbasis server, pencatatan aktivitas, tindak lanjut (*Action Plan*), dan perbaikan berkelanjutan (*Improvements*).
- **Target Audience:** Konsultan Lapangan (End-User), Admin Operasional (Monitor), Klien (Penerima Laporan).

---

## 3. Goals & Success Metrics
- **Business Goals:** Meningkatkan keabsahan tagihan penawaran (buktinya adalah Laporan *Mandays* PDF) sehingga *invoice* Klien bisa cepat cair tanpa disputasi absensi.
- **Success Metrics (KPI):**
  - 100% Kunjungan *(Visit)* memiliki koordinasi waktu yang tercatat sah dan memiliki lampiran PDF Action Plan.
  - Pengurangan keluhan klien mengenai "konsultan tidak bekerja maksimal" turun signifikan.
- **Non-Goals:** Bukan alat pengganti sistem Payroll / Absensi utama perusahaan. HANYA melacak *Mandays Project*.

---

## 4. User Journey & Flow
- **User Persona:** Budi (Senior Consultant).
- **User Journey Map:**
  1. Budi tiba di pabrik Klien A. Budi *login* di HP, pilih SPK, lalu tekan tombol **Start Time**. (Waktu server mencatat jam 08:00 WIB).
  2. Budi melakukan pendampingan SOP sepanjang hari.
  3. Budi menekan tombol Edit (Draf), memasukkan *Activities*, dan mengetik 2 poin *Action Plan* lengkap dengan PIC klien dan *Due Date*.
  4. Jam 17:00, Budi menekan **Finish Time**.
  5. Budi menekan **Finalize**. Dokumen terkunci (Tidak bisa di-edit).
  6. Budi menekan logo PDF, lalu PDF otomatis ter- *download* dan ia serahkan ke Klien via Email.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | One-Way Time Puncher | Tombol AJAX *Start* & *Finish* yang hanya bisa ditekan sekali, mengambil waktu murni dari *Backend Server*. | P0 (Wajib) |
| FR-02 | SPK Restrictiveness | Konsultan hanya bisa melihat & mengakses *List SPK* di mana ia terdaftar sebagai Konsultan 1 atau Konsultan 2. | P0 (Wajib) |
| FR-03 | Dynamic Form Rows | Sistem *Add/Remove Row* di UI (memanfaatkan JS) untuk mengisi Array *Activities, Action Plans,* dan *Improvements*. | P0 (Wajib) |
| FR-04 | Historical Retrieval | Saat membuat laporan *Visit* kedua, form *Action Plan* dari *Visit* pertama otomatis muncul untuk ditinjau progresnya. | P1 (Penting) |
| FR-05 | Laporan PDF Cetak | Ekstraksi ke format PDF siap-cetak, lengkap dengan kop surat dan kolom Tanda Tangan. | P0 (Wajib) |

---

## 6. Non-Functional Requirements
- **Performance:** Form pengisian JSON dinamis harus ringan karena akan sering dibuka via jaringan 4G/HP.
- **Security:** Modul memiliki skema Anti-Hijack: User tidak bisa mengedit ID laporan (via URL URL Injection) jika *User_ID* miliknya berbeda dengan *Consultant_ID* pembuat laporan.
- **Scalability & Platform:** UI Harus *Mobile-First* atau sangat Responsif (*Single Page Application Feel*), mengingat Konsultan mengetik dari pabrik/lokasi klien.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** 
  - Tombol *Start Time* buat sangat mencolok (Warna Hijau Besar), Tombol *Finish Time* (Warna Merah).
  - Tampilkan *progress tracker* "Kuota Mandays = 10, Terpakai = 2" agar Konsultan tahu limit kunjungannya.

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Konsultan memiliki kuota internet yang cukup saat menekan tombol *Start*. Jika *offline*, waktu tidak akan tercatat.
- **Constraints:** Modul sangat *strict* mengunci *Form Edit* menjadi *Read Only* bila statusnya sudah diubah dari 'Draft' menjadi 'Final'.

---

## 9. Future Iterations / Phase 2
- *Geo-Tagging* / Absensi GPS *Location* di tombol *Start Time* agar validasi kehadiran secara fisik terjamin.
- E-Sign (Tanda Tangan Elektronik) Klien langsung di atas layar perangkat Konsultan, sehingga meniadakan proses cetak kertas.

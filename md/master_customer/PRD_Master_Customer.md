# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Customer
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Data klien (Customer) sering kali terpecah, tidak konsisten (duplikasi), dan alamat tagihan (Invoice) sering keliru akibat pencatatan yang kurang terpusat.
- **Objective:** Menciptakan wadah Master Data / *Single Source of Truth* (SSOT) yang *flat* (tanpa hierarki rumit) namun kaya akan detail, memfasilitasi pendataan Legalitas, PIC, dan Multi-Alamat.
- **Target Audience:** Tim Sales (Inputer Lead/Prospek), Tim Finance (Pengguna Alamat Invoice), Admin Data.

---

## 3. Goals & Success Metrics
- **Business Goals:** Menyediakan basis data klien yang solid, bersih, dan menekan *bounce rate* surat tagihan akibat alamat *invoice* atau *email PIC* yang tidak *valid*.
- **Success Metrics (KPI):**
  - 100% data pelanggan baru memiliki NPWP dan nomor telepon PIC tervalidasi di database.
  - Nol kasus duplikasi data (*Case Insensitive Prevention*).
- **Non-Goals:** Modul ini tidak menghandle *Parent/Child Company* (Hirarki Korporasi) — setiap anak cabang PT dicatat sebagai *Customer Entity* yang independen.

---

## 4. User Journey & Flow
- **User Persona:** Andi (Sales Executive).
- **User Journey Map:**
  1. Andi memprospek "PT Maju Jaya". Ia masuk ke modul Master Customer dan klik *Add*.
  2. Andi menginput nama perusahaan. Sistem otomatis memastikan tidak ada nama serupa (Duplikat).
  3. Andi memasukkan data PIC (Bapak Rio).
  4. Andi memasukkan 2 alamat tagihan (Alamat Pabrik dan Alamat Kantor Pusat).
  5. Andi menekan *Save*. Sistem secara otomatis memecah datanya ke dalam 5 tabel berbeda di *database* dengan 1 ID Customer.
  6. Data siap digunakan oleh modul Penawaran dan Modul SPK.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Smart ID Generator | *Auto-Generate ID* berdasarkan Bulan & Tahun berjalan dengan kombinasi *Sequence* (C100-YYMMXXX). | P0 (Wajib) |
| FR-02 | Anti-Duplicate Guard | Menolak form *Save* (Status 3) jika Nama Klien terdeteksi sudah ada (mengabaikan *Caps Lock*). Melakukan pelacakan HP & Email pada PIC. | P0 (Wajib) |
| FR-03 | Dynamic Invoice Address | Memungkinkan pengguna menambah/mengurangi banyak alamat tagihan secara *Batch* UI (Tekan `+ Add Address`). | P0 (Wajib) |
| FR-04 | Dependant Dropdown | *Dropdown* Provinsi - Kota yang bergantung satu sama lain (Pilih Jabar -> Otomatis daftar kotanya memunculkan Bandung, Bogor). | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Eksekusi penyimpanan (Save) ke 5 tabel terpisah wajib terjadi dalam 1 detik.
- **Security:** Eksekusi wajib menggunakan blok `db->trans_start()` (SQL Transaction) agar jika terjadi error, *Partial Data* (data rusak) akan dibatalkan otomatis (*Rollback*).
- **Scalability & Platform:** Diutamakan untuk *Data Entry* di platform Desktop Web Browser.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** 
  - Gunakan form model Vertikal Panjang (*Single Scroll*) yang dibagi menjadi blok (Kategori: Profil Usaha, Legalitas Pajak, PIC, Invoice, Referensi).
  - Tampilkan modal peringatan *Error* yang ramah jika terdeteksi duplikasi.

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Input nomor telepon bebas (pengguna bebas menginput karakter `-`). Backend yang akan menghilangkannya (*Regex/String Replace*).
- **Constraints:** Modul ini akan dikunci (*Manage Permission*) agar tidak sembarangan di-*Delete* jika nama Klien tersebut sudah dipakai dalam SPK yang sedang berjalan (Mencegah *Orphaned Record*).

---

## 9. Future Iterations / Phase 2
- Verifikasi profil langsung dengan API Ditjen Pajak untuk memvalidasi NPWP secara otomatis.
- *Tagging Customer Loyalty* otomatis (VIP, Bronze, Silver) berdasarkan frekuensi pengambilan SPK/Proyek.

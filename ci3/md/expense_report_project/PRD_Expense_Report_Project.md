# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Expense Report Project
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Konsultan menerima kasbon namun sering terlambat atau lupa memberikan laporan pertanggungjawaban fisik (nota/struk). Hal ini membuat gantung neraca keuangan perusahaan.
- **Objective:** Membuat modul realisasi (*Expense Report*) yang mewajibkan konsultan merincikan pengeluaran aktual dan mengunggah bukti, lalu menghitung otomatis apakah terjadi *Refund* (uang sisa dikembalikan) atau *Reimburse* (uang kurang diganti perusahaan).
- **Target Audience:** Konsultan Lapangan (Pelapor) dan Admin Finance (Checker/Eksekutor transfer).

---

## 3. Goals & Success Metrics
- **Business Goals:** Menutup siklus (*loop*) perputaran kas operasional agar pembukuan menjadi rapi dan transparan. Menghindari "kasus uang hilang di jalan".
- **Success Metrics (KPI):**
  - 95% kasbon yang dicairkan memiliki laporan pengeluaran (Expense Report) maksimal 3 hari setelah proyek selesai.
  - Kesalahan penulisan data rekening turun menjadi 0% (karena ditarik otomatis dari *Database* HR).
- **Non-Goals:** Bukan untuk meminta uang jalan awal (Itu fungsi Modul Kasbon).

---

## 4. User Journey & Flow
- **User Persona:** Dina (Konsultan Lapangan) & Rini (Finance).
- **User Journey Map:**
  1. Dina pulang dari lokasi klien, login, dan melihat daftar "Kasbon Siap Dilaporkan".
  2. Dina menekan *Create Expense Report*.
  3. Dina melihat bahwa ia dulu meminjam Rp 2.000.000.
  4. Dina menginput biaya Aktual Rp 2.100.000 dan mengunggah foto nota, serta memilih rekening BCA-nya dari *dropdown*.
  5. Sistem menampilkan status **Reimbursement** (Dina kurang bayar Rp 100.000).
  6. Rini (Finance) melihat laporan, melakukan *Approval*, dan mentransfer Rp 100.000 ke Dina. Tiket tertutup.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | Strict Kasbon Linking | Hanya memunculkan tiket Kasbon yang statusnya `sts = 1` (sudah cair) dan belum berstatus `Closed` / dilaporkan. | P0 (Wajib) |
| FR-02 | Dual Column Input | Merender nilai *Kasbon Awal* (Statik/Read-only) di sebelah kiri, dan Form Input *Pengeluaran Aktual* di sebelah kanan. | P0 (Wajib) |
| FR-03 | Auto Calc (Refund / Reimburse) | Logika algoritma pengurang (Kasbon - Aktual). Menghasilkan Flag (Lebih/Kurang) secara *Real-Time* di UI footer. | P0 (Wajib) |
| FR-04 | File Upload | Attachment file/gambar (JPG, PNG, PDF) per tiket untuk bukti potong (Nota). | P1 (Penting) |
| FR-05 | Cross-Database Rekening | Mengintegrasikan *dropdown* Rekening Bank (`ms_bank`) dari database lain (`sendigs_finance` & `gl_sendigs`). | P0 (Wajib) |

---

## 6. Non-Functional Requirements
- **Performance:** Proses Upload File difilter (Limit Max 2MB per file) agar tidak membebani kapasitas server.
- **Security:** *Database Transaction* ketat, pelaporan *expense* yang berstatus *Pending Approval* akan di- *lock* agar tidak bisa dilaporkan *double* (Double Claim Protection).
- **Scalability & Platform:** Responsive Web Design, dioptimalkan agar konsultan bisa foto struk dari Browser *Smartphone*.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** 
  - Gunakan elemen warna tegas untuk selisih (Warna Hijau jika Uang Lebih / Karyawan *Refund*, Warna Merah jika Uang Kurang / Perusahaan *Reimburse*).

---

## 8. Constraints & Assumptions (Kendala & Asumsi)
- **Assumptions:** Data rekening bank karyawan (Database `sendigs`) sudah *up-to-date*.
- **Constraints:** Ketergantungan *ping* ke server database ke-2 dan ke-3 (`accounting` dan `sendigs`). Jika server tersebut *down*, *dropdown* bank tidak akan muncul.

---

## 9. Future Iterations / Phase 2
- Integrasi OCR (*Optical Character Recognition*) untuk memindai nota fisik menjadi angka input otomatis.
- *Push notification* (WhatsApp/Email) penagihan ke Karyawan yang belum membuat Laporan Expense dalam 14 hari kerja.

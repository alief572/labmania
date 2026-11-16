# Product Requirements Document (PRD)

## 1. Metadata (Informasi Dasar)
- **Nama Produk/Fitur:** Modul Master Lab
- **Status:** Approved
- **Author:** Alief Daffa Naufal
- **Tim Terkait:** UI/UX Designer, Tech Lead, Backend, QA
- **Tanggal:** 9 Juli 2026

---

## 2. Executive Summary (Latar Belakang)
- **Background:** Seringkali dalam sebuah proyek (terutama Konsultasi Lingkungan/K3), perusahaan perlu menggunakan jasa uji Laboratorium Eksternal. Biaya uji ini perlu distandarisasi agar tidak terjadi fluktuasi saat Sales menawarkan harga ke klien.
- **Objective:** Membuat katalog Master Lab yang berisi rincian item pengujian beserta standar harganya.
- **Target Audience:** Admin Master Data, Tim Operasional & Sales (sebagai pengguna referensi).

---

## 3. Goals & Success Metrics
- **Business Goals:** Memusatkan (*centralize*) data biaya pengujian Lab agar penawaran harga menjadi konsisten dan margin profit perusahaan selalu terjaga.
- **Success Metrics (KPI):** 0% salah harga saat Sales memasukkan komponen biaya Lab pada form Quotation.

---

## 4. User Journey & Flow
- Admin membuat Item Lab Baru (Contoh: "Uji Emisi Udara") -> Memasukkan standar harga -> Menyimpan data.
- Data ini kelak akan diakses via *dropdown* di Modul Penawaran dan Modul Kasbon Project.

---

## 5. Functional Requirements (Fitur Utama)
| ID | Fitur/Komponen | Deskripsi | Prioritas |
|---|---|---|---|
| FR-01 | CRUD Master Lab | Pengisian *Item Uji* (Parameter Lingkungan/K3) dan *Harga Standar*. | P0 (Wajib) |
| FR-02 | Toggle Aktif/Nonaktif | Menonaktifkan parameter uji apabila Laboratorium rekanan sudah tidak menyediakan layanan tersebut. | P1 (Penting) |

---

## 6. Non-Functional Requirements
- **Performance:** Menampilkan > 1.000 parameter pengujian tanpa *lag* menggunakan Server-side DataTables.

---

## 7. UX/UI Design & Wireframes
- **Link Figma/Adobe XD:** `[Masukkan Link Figma Di Sini]`
- **Design Notes:** Tampilan Tabel Master standar. Gunakan format mata uang (Rupiah) yang rapi (Contoh: Rp 1.500.000) pada kolom harga.

---

## 8. Constraints & Assumptions
- **Assumptions:** Perubahan harga dari lab eksternal bisa terjadi kapan saja, maka harga *default* ini sifatnya bisa di-*override* (ditimpa) pada saat pengerjaan Modul Penawaran.

---

## 9. Future Iterations / Phase 2
- Integrasi ke API Laboratorium Eksternal rekanan untuk *update* harga secara otomatis (jika rekanan memiliki API).

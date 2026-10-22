# System Design Document: Modul Master Divisions

## 1. Context & Goals
**Background Singkat:** 
Struktur organisasi perusahaan bisa berubah-ubah seiring waktu (pembentukan divisi baru). Sistem membutuhkan tabel entitas kamus/referensi (Dictionary Entity) agar nama Divisi bisa dipanggil secara otomatis di fitur lain, seperti profil karyawan.

**Out of Scope:** 
Sistem ini belum mengakomodasi penggambaran bagan/hierarki organisasi secara visual (*Org-Chart Graph*).

---

## 2. Proposed Architecture
**Architecture Diagram:**
```mermaid
graph LR
    A[Modul Master Divisions] -->|CRUD Controller| B[(Tabel hr_sentral.divisions)]
    B -->|Relasi (Foreign Key)| C[(Tabel hr_sentral.employees)]
```

**Component Breakdown:**
- **Divisions Controller:** Pengontrol lurus (*Straightforward MVC*) untuk List, Add, Edit, dan Delete.

---

## 3. Data Model & Storage
**Schema Database (ERD Singkat):**
- **`hr_sentral.divisions`**: PK `id`, `company_id` (Relasi Perusahaan), `name` (Nama divisi).

**Caching Strategy:**
- Tidak ada kuki/cache. Eksekusi `SELECT *` dibatasi fitur Server-Side Pagination Datatables.

---

## 4. Interface Definitions (API Contract)
*(Hanya memproses form submit standar CI3)*

- **Endpoint:** `POST /divisions/add`
- **Request Payload:** Form *multipart/form-data* (Nama Divisi & ID Company).
- **Response Payload:** `status: 1, pesan: 'Add Divisions Success'`.

---

## 5. Non-Functional Requirements & Trade-offs
**Scalability & Performance:**
- Tingkat skalabilitas amat ringan. Query ke tabel Master bersifat *Lightweight*.

**Security:**
- Mengimplementasikan konsep perlindungan panti (*Orphan Protection*). Fungsi `delete()` tidak menghapus baris secara murni (Hard Delete), melainkan *Soft Delete* dengan melabeli bendera waktu di kolom `deleted_at`, mencegah putusnya relasi data dari pegawai yang menempati divisi lama.

**Trade-offs:**
- Memakai MVC statis tanpa AJAX pada proses *List* agar load data di awal sangat cepat, kendati ini adalah *trade-off* gaya pemrograman lawas di era modern *Single Page Applications*.

---

## 6. Infrastructure & Deployment Impact
**Infrastructure Changes:**
- -

**Migration Plan:**
- Sinkronisasi DDL Database `hr_sentral`. Tidak ada *downtime* migrasi.

# Kebutuhan Fungsional & Non-Fungsional — Jejak Data & Modul Activity Log Labmania

- **Versi:** draf 1 — 30-10-2026
- **Sumber:** `input/Master - Buat Program/2026-10-30-histori-activity-log/`; standar `write_log()` di `AGENTS.md` `[STD]`
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** berlaku lintas modul — Master Data (`docs/fr-nfr.md`), Master Program (`docs/fr-nfr-master-program.md`), Penjualan Program (`docs/fr-nfr-penjualan-program.md`), Quotation (`docs/fr-nfr-quotation.md`)
- **Mockup:** `ui-mockup/produk/index.html` → kolom "Dibuat / Diubah" di setiap daftar, menu gear "Lihat Riwayat", modul **Activity Log** (draf 12)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Activity Log*
- **Flow:** `docs/diagrams/bpmn-to-be.drawio` halaman *Level 1 — Activity Log*

Penomoran memakai awalan **LOG-**.

| Penanda | Arti |
|---|---|
| `[HAL-1]` | `input/Master - Buat Program/2026-10-30-histori-activity-log/` — jejak "created by, created date & hours" di semua modul; satu modul khusus untuk melihat pergerakan di sistem: menambah, mengedit, menghapus, lengkap |
| `[STD]` | standar proyek di `AGENTS.md` (`write_log()`, Before/After, `detail_changes`) |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

Setiap data di **Master Data, Master Program, Penjualan Program, dan Quotation** menyimpan **siapa yang membuat dan
kapan (tanggal & jam)** `[HAL-1]`, serta siapa dan kapan terakhir mengubah `[USULAN]`. Semua pergerakan data di sistem
— **tambah, edit, hapus**, dan aksi lain (aktif/nonaktif, finalkan, batalkan, kirim & cetak, deal, cetak dokumen) — tercatat
dan dapat dilihat di **satu modul khusus: Activity Log** `[HAL-1]`, lengkap dengan field yang berubah sebelum → sesudah `[STD]`.

```
Aksi pengguna di modul mana pun ──► data tersimpan + kolom jejak (created_* / updated_* / deleted_*)
                                   └► write_log() ──► tabel logs ──► menu Activity Log (semua) / "Lihat Riwayat" (satu data)
```

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Semua pengguna yang login | Aksinya tercatat otomatis (tidak ada langkah tambahan) | `[HAL-1]` |
| Manajemen | Melihat Activity Log dan riwayat data | `[USULAN]` (Q-LOG-04) |

---

## 3. Tabel Use Case

| Kode | Use case | Aktor | Prasyarat | Alur singkat | Hasil |
|---|---|---|---|---|---|
| UC-38 | Melihat Activity Log | Manajemen | Login; punya hak akses Activity Log | Buka menu Activity Log, saring (modul, aksi, pengguna, rentang tanggal) atau cari, buka **Detail** satu catatan | Daftar pergerakan data; detail field sebelum → sesudah |
| UC-39 | Melihat riwayat satu data | Manajemen | Data ada di daftar mana pun | Pilih menu gear **Lihat Riwayat** pada baris data | Activity Log tersaring ke data tersebut (dibuat, diubah, status, dihapus) |

---

## 4. Kebutuhan Fungsional (FR)

| ID | Kebutuhan | Sumber |
|---|---|---|
| LOG-FR-01 | **Kolom jejak** pada semua tabel data milik Master Data, Master Program, Penjualan Program, dan Quotation (header maupun detail): **`created_by`** (pengguna pembuat), **`created_date`** (tanggal & jam dibuat), **`updated_by`**, **`updated_date`** (pengguna & tanggal-jam perubahan terakhir); tabel yang datanya dapat dihapus memakai **hapus lunak** `deleted_by`, `deleted_date`. Terisi otomatis oleh sistem dari pengguna login dan jam server; tidak dapat diisi/diubah pengguna. | `[HAL-1]` + `[USULAN]` (updated_*, deleted_*, hapus lunak) |
| LOG-FR-02 | Setiap **daftar** di keempat modul menampilkan kolom **Dibuat / Diubah**: "Dibuat **nama** DD-MM-YYYY HH:mm" dan, bila pernah diubah, "Diubah **nama** DD-MM-YYYY HH:mm", sebelum kolom Action. | `[HAL-1]` + `[USULAN]` (letak kolom) |
| LOG-FR-03 | Modul **Activity Log** (menu sendiri, satu modul) menampilkan **semua pergerakan data** di sistem, terbaru di atas: No, **Waktu** (DD-MM-YYYY HH:mm), **Pengguna**, **Modul** & sub modul, **Aksi**, **Data** (kode/nomor), **Perubahan** (ringkas, field sebelum → sesudah), Action **Detail**. | `[HAL-1]` + `[USULAN]` (kolom) |
| LOG-FR-04 | Aksi yang tercatat: **Tambah, Edit, Hapus** `[HAL-1]`; **Aktifkan / Nonaktifkan**, **Finalkan**, **Batalkan**, **Kirim & Cetak**, **Deal**, **Cetak** (Surat Penawaran, cetak ulang Quotation, Confirmation Letter), termasuk peserta baru yang ditambahkan dari form Quotation `[USULAN]`. Melihat/membuka data tidak dicatat `[USULAN]`. | `[HAL-1]` + `[USULAN]` |
| LOG-FR-05 | **Saring & cari**: modul, aksi, pengguna, rentang tanggal (dari – s.d.), dan pencarian teks (data, keterangan, pengguna); paginasi. | `[USULAN]` |
| LOG-FR-06 | **Detail** satu catatan (popup): waktu, pengguna, modul › sub modul, aksi, keterangan, dan tabel **Field — Sebelum (merah) — Sesudah (hijau)**; perubahan baris detail (item biaya, peserta, Keterangan) ditampilkan per baris (`detail_changes`). Tambah menampilkan isi data baru; Hapus menampilkan isi data terakhir. | `[STD]` + `[USULAN]` |
| LOG-FR-07 | **Lihat Riwayat** di menu gear setiap baris data membuka Activity Log yang tersaring ke data itu, dengan tombol "Tampilkan semua". | `[USULAN]` |
| LOG-FR-08 | Catatan Activity Log **tidak dapat diubah atau dihapus** dari aplikasi. Data yang dihapus tetap terlacak (hapus lunak + catatan Hapus). | `[USULAN]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| LOG-NFR-01 | Audit | Pencatatan memakai `write_log()` sesuai standar `AGENTS.md`: Edit menyimpan snapshot `old` (diambil **sebelum** update) dan `new`; header–detail menyertakan `detail_changes`; perbedaan format angka (0 vs 0.00) dan NULL ↔ 0 pada kolom nominal tidak dianggap perubahan; nama modul tampil otomatis di filter (`SELECT DISTINCT module`). | `[STD]` |
| LOG-NFR-02 | Integritas data | Simpan data, kolom jejak, dan `write_log()` dalam **satu transaksi**: bila log gagal, simpan dibatalkan. Kolom jejak diisi di server (bukan dari isian klien). | `[USULAN]` |
| LOG-NFR-03 | Keamanan | Tabel `logs` hanya-tambah (tanpa endpoint ubah/hapus). Menu Activity Log mengikuti permission aplikasi. | `[USULAN]` (Q-LOG-04) |
| LOG-NFR-04 | Kinerja | Daftar Activity Log: paginasi & penyaringan di server, indeks pada (`created_date`), (`module`, `action`), (`ref_table`, `ref_id`), (`created_by`). | `[USULAN]` |
| LOG-NFR-05 | Format | Tanggal & jam tampil **DD-MM-YYYY HH:mm** (24 jam, waktu server WIB); disimpan DATETIME. | `[STD]` (format tanggal) + `[USULAN]` |

---

## 6. Data

| Tabel | Isi | Sumber |
|---|---|---|
| `logs` | `id`, `module`, `sub_module`, `action`, `description`, `ref_table`, `ref_id`, `ref_no` (kode/nomor data), `data` (JSON: `old`, `new`, `detail_changes`), `created_by` → `users.id_user`, `created_date`, `ip_address` | `[STD]` + `[USULAN]` (kolom) |
| Semua tabel modul | Kolom jejak `created_by`, `created_date`, `updated_by`, `updated_date` (+ `deleted_by`, `deleted_date` bila dapat dihapus) → `users.id_user` | LOG-FR-01 |

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-LOG-01 | Helper `write_log()` dan tabel log dari standar `AGENTS.md` sudah ada di aplikasi Labmania, atau dibuat baru? Bila sudah ada, nama kolom tabel `logs` disesuaikan dengan yang ada (ERD diperbarui). | LOG-NFR-01, ERD |
| Q-LOG-02 | **Login / logout** dan gagal login ikut dicatat di Activity Log? (usulan: belum, hanya pergerakan data) | LOG-FR-04 |
| Q-LOG-03 | Berapa lama log disimpan (retensi), dan perlu ekspor (Excel/PDF)? (usulan: disimpan permanen, tanpa ekspor) | LOG-NFR-04 |
| Q-LOG-04 | Siapa yang boleh membuka Activity Log — semua pengguna, atau Manajemen saja? (usulan: Manajemen) | LOG-NFR-03, UC-38 |
| Q-LOG-05 | Kolom "Dibuat / Diubah" di daftar cukup, atau juga perlu tampil di form/halaman detail? (usulan: di daftar + Lihat Riwayat) | LOG-FR-02 |

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 30-10-2026 | Penyusunan awal dari `input/Master - Buat Program/2026-10-30-histori-activity-log/`: kolom jejak di semua modul (LOG-FR-01, -02), modul Activity Log (LOG-FR-03 – LOG-FR-08), NFR, tabel `logs`, UC-38, UC-39; mockup draf 12. |

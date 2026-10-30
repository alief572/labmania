# Format form Master User (tangkapan layar "Add New Employee")

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-28
- **Channel:** file gambar di `input/` + chat
- **Contents:** `input/Master User.png` (tangkapan layar form *Add New Employee* dari aplikasi yang sudah ada). File gambar tetap di lokasi aslinya; folder ini hanya mencatat intake-nya.
- **Supersedes:** —

## Verbatim

> aku ada masukin di input foto dengan nama file "Master User" nah tolong di menu master data tambahin sub menu baru yaitu master user, dengan format sepertu file foto tersebut. kalo ada yang kurang ditambahin ya.

## Isi gambar (dibaca analis)

Judul modal **Add New Employee**, dua kolom, tombol **Save** dan **Close**. Tanda `*` = wajib.

| Kolom kiri | Kolom kanan |
|---|---|
| Employee ID * (teks) | Phone Number * (placeholder `+62...`) |
| Employee Name * (teks) | Email (placeholder `email@domain.com`) |
| Personal ID Number (placeholder `NIK`) | Address (teks panjang) |
| Birth Place (teks) | NPWP (placeholder `NPWP Number`) |
| Birth Date (tanggal dd/mm/yyyy) | Join Date (tanggal dd/mm/yyyy) |
| Department * (pilihan) | Employee Type * (pilihan) |
| Division * (pilihan; catatan "Pilih Department terlebih dahulu.") | Job Desc. (teks panjang) |
| Jabatan * (pilihan) | Status * (radio Active / Non Active, default Active) |
| Gender * (pilihan) | |
| Religion (pilihan) | |

## Catatan penafsiran

- Menu baru: Master Data → sub modul **Pengguna** → **Master User**. Nama sub modul usulan analis.
- Form mengikuti gambar (label, urutan, wajib/tidak). Division tersaring menurut Department.
- "Kalo ada yang kurang ditambahin": gambar hanya berisi data karyawan, belum ada akun login. Analis menambah bagian **Akun Login** (bisa login ya/tidak, Username, Password + konfirmasi, Group Akses), halaman daftar, filter, Aktifkan/Nonaktifkan (tanpa hapus), dan Reset Password — semuanya usulan analis.
- Aplikasi sudah punya modul `employees` (data karyawan) dan `users` (login, group via `user_groups`/`groups`). Master User diusulkan memakai tabel tersebut, bukan tabel baru.
- Isi pilihan Department, Division, Jabatan, Employee Type, Religion, dan Group Akses tidak terlihat di gambar; mockup memakai contoh (Q-25).

## Extracted

- FR-36 – FR-39, UC-32, Q-25 -> docs/fr-nfr.md (draf 11)
- menu & halaman Master User -> ui-mockup/produk/app.js (draf 11d)

# Role & Permission — role dari Division + Jabatan; Department dihapus dari Master User

- **Source:** user / BA proyek Labmania, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-04
- **Channel:** chat + video acuan `input/Invoice & Pengajuan/Role & Permission.mp4` (31 detik, layar aplikasi lain "ERP Platform" — `127.0.0.1:8000/system/roles`)
- **Contents:** pernyataan verbatim; dua frame video (`frame-daftar-role.png`, `frame-edit-role.png`)
- **Supersedes:** pilihan **Group Akses** di form Master User (FR-38); field **Department** di Master User (FR-37, FR-39); nama menu **Matrix Akses Menu** dengan tab per group (FR-54, FR-55).

## Verbatim

> kamu liat file video dengan nama file "Role & Permission" nah setiap sudah buat akun user, kan akan ada terbentuk sebuah role, nah di modul "Role & Permission" ini role tersebut baru deh bisa di setting aksesnya, bisa akses ke menu apa aja, lalu di menu itu bisa ngapain aja.
>
> yang saat ini ada di master user select role malah salah. soalnya role itu terbentuk dari divisi dan positions. btw yang department di master user gausah ada ya.

## Isi video (diamati dari frame)

1. Halaman **Roles & Permissions (RBAC)**: kartu ringkasan (Total Registered Roles, Distributed Users, System Permission Modules); pencarian nama role; tabel Role Name, Registered Users, Total Access Rights ("n Permissions Granted"), Permission Scope (preview chip), Action (edit, hapus).
2. Role **Super Admin** — "Full Access", "All Permissions Active", tidak bisa dihapus.
3. Modal **Edit Role & Permissions**: Role Name; **Access Permissions Matrix** "x of y selected", tombol **Select All Modules**, **Clear All**, cari modul/permission; per baris modul/menu: centang semua, chip aksi (View, Create, Edit, Delete …), **Select All / Deselect All** per modul, **Scope** x/y; footer "Total: n permissions selected", Cancel, Update Role.
4. Video memperlihatkan tombol "Add New Role" (role diketik manual) — **tidak dipakai** di Labmania: role terbentuk otomatis dari akun user (verbatim di atas).

## Catatan penafsiran

1. **Role = Division + Jabatan** user yang punya akun login, nama tampil "Division – Jabatan". Dibentuk otomatis saat akun user disimpan; tidak ada pilihan role di form Master User.
2. **Department dihapus** dari Master User (form, kolom daftar, filter). Division menjadi daftar tunggal (tidak lagi tersaring Department); filter daftar User memakai Division.
3. Menu **Matrix Akses Menu** diganti nama **Role & Permission**; tampilan mengikuti video (daftar role + modal matrix). Aksi tetap View / Add / Manage / Delete (FR-55).
4. Superadmin (developer) dan Admin (user Labmania) — Q-34 — tetap; kini dikenali dari role-nya. Mockup: Superadmin = role **IT – Developer**, Admin = role **Administrasi – Admin** (usulan, perlu konfirmasi).
5. Manajemen (Q-PB-14: ditentukan dari posisi) — sementara jabatan **Direktur**, daftar posisi masih menyusul.

## Extracted

- `docs/fr-nfr.md` draf 11t (FR-36 – FR-39, FR-54 – FR-58, UC-32, UC-49, NFR-13, Q-25, Q-35)
- `docs/fr-nfr-pengajuan-biaya.md`, `docs/fr-nfr-activity-log.md` (rujukan Group Akses → Role)
- ERD halaman *ERD Matrix Akses* (catatan Role)
- mockup `ui-mockup/produk/` draf 14c

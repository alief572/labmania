# Master User: field Role (dari Master Role) + Override Permission

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-05
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** role otomatis "Division – Jabatan" (`input/Invoice & Pengajuan/2026-11-04-role-permission/`, `docs/fr-nfr.md` draf 11t–11v FR-37, FR-38, FR-54); field Division dan Jabatan di Master User; Q-35 (c)

## Verbatim

> Di Master User, ganti field Position dan Division jadi satu field Role. Role dikelola lewat Master Role (input manual) dan jadi sumber pilihan di Master User maupun Role & Permission. Tambahkan juga Override Permission di Master User untuk memberi akses menu tambahan secara spesifik ke satu user di luar permission role-nya

## Catatan penafsiran

1. "Position" = field **Jabatan** di mockup (kolom `position` di modul employees aplikasi); "Division" = field **Division**. Keduanya dihapus dari form dan daftar Master User, diganti satu field **Role** (pilihan).
2. **Master Role** = menu baru di sub modul Pengguna. Role ditambah / diubah / dihapus manual; tidak lagi terbentuk otomatis dari Division + Jabatan. Memakai tabel existing `groups` (`nm_group`).
3. Role & Permission hanya mengatur hak role yang ada di Master Role; tombol Hapus role pindah ke Master Role.
4. **Override Permission** = hak menu tambahan untuk satu user, di luar hak role-nya. Hanya menambah, tidak mengurangi hak role (kata klien: "akses menu tambahan"). Memakai tabel existing `user_permissions`. Menjawab Q-35 (c): hak per user tetap ada, kini diatur dari Master User.
5. Belum disebut klien, diisi usulan analis:
   - Role wajib untuk setiap user (menggantikan Division dan Jabatan yang wajib) `[USULAN]`; disimpan di kolom baru `employees.id_group` agar user tanpa akun login tetap punya role; akun login memakai role itu di `user_groups` `[USULAN]`.
   - Master Role: isian hanya **Nama Role** (wajib, unik); role yang masih dipakai user tidak dapat dihapus; nama role dapat diubah `[USULAN]`.
   - Role **Superadmin** tetap bawaan sistem: tampil di Master Role terkunci, tidak dapat dipilih untuk user lain `[RNP2]`.
   - Override Permission hanya untuk user yang punya akun login, di bagian Akun Login form Master User; hak yang sudah dari role tampil tercentang dan terkunci `[USULAN]`.
   - **Manajemen** (Pengajuan Biaya, Activity Log) sementara = role **Direktur** (sebelumnya jabatan Direktur, Q-PB-14) `[USULAN]`.
   - Isi contoh Master Role di mockup: Direktur, Admin, Sales Public, Sales B2B, Trainer, Keuangan (contoh, menunggu data Labmania).
6. Pertanyaan baru **Q-37**: daftar role final; kolom Division / Position di tabel `employees` aplikasi tetap disimpan (modul HR lain) atau tidak dipakai lagi; perlu Override yang **mengurangi** hak role?

## Extracted

- FR-36 – FR-39, FR-54 – FR-58, FR-64 – FR-65, UC-32, UC-49, UC-55, Q-25, Q-35, Q-37 -> docs/fr-nfr.md (draf 11w)
- Manajemen = role Direktur -> docs/fr-nfr-pengajuan-biaya.md, docs/fr-nfr-activity-log.md
- ERD halaman *ERD Matrix Akses*: `employees.id_group` (baru), catatan Master Role & Override -> docs/diagrams/erd.drawio
- mockup -> ui-mockup/produk/app.js (Master Role, field Role, Override Permission), draf 14h

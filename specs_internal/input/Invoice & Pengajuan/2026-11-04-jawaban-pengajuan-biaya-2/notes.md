# Jawaban Q-PB-13 – Q-PB-16, Q-33, Q-34 (Pengajuan Biaya draf 2 / Matrix Akses Menu)

- **Source:** user / BA proyek Labmania, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-04
- **Channel:** chat (jawaban atas `docs/fr-nfr-pengajuan-biaya.md` draf 2 bagian 8.1 dan `docs/fr-nfr.md` draf 11r Q-33, Q-34)
- **Contents:** pernyataan verbatim, tanpa lampiran
- **Supersedes:** usulan draf 2 "budget mencukupi per item"; tim Sales dari semua quotation tidak Batal; group Admin = id 1 tunggal.

## Verbatim

> PB-13 = Setuju
> PB-14 = Di master user sih, sebenernya manajemen itu bukan department atau divisi, nanti lagi ya aku info posisi apa yang masuk ke manajemen
> PB-15 = Per kategoti, total fixed cost dan total variable cost.
> PB-16 = Hanya yang deal ya
> Q-33 = Di cek dulu ya
> Q-34 = 1 akun superadmin dan 1 admin. superadmin untuk developer dan admin untuk user labmania, masih bisa dibatasi juga permission akses modulnya

## Catatan penafsiran

1. **Q-PB-13**: field **Akun login** di Master Trainer (`ms_trainer.id_user`) disetujui.
2. **Q-PB-14**: "Manajemen" ditentukan dari **Master User** (posisi), bukan Department / Division. Daftar posisi yang termasuk Manajemen **menyusul** dari user. Sementara mockup tetap memakai Group Akses Manajemen.
3. **Q-PB-15**: batas budget **per kategori**: total pengajuan Fixed Cost ≤ sisa total Fixed Cost; total Variable Cost ≤ sisa total Variable Cost. Satu item boleh melebihi budget item-nya selama total kategori cukup.
4. **Q-PB-16**: Sales yang termasuk tim hanya dari quotation berstatus **Deal**.
5. **Q-33**: isi awal matrix masih dicek user — tetap terbuka.
6. **Q-34**: dua akun khusus: **Superadmin** (developer) = group id 1, lolos semua; **Admin** (user Labmania) = group biasa yang hak modulnya **masih dapat dibatasi** lewat matrix. Ditafsirkan: Admin tetap membuka semua **data** program (jawaban PB-07 "aku admin sehingga bisa buka semua"), tetapi menu/aksinya mengikuti matrix. Group **Admin Developer** di mockup = Superadmin.

## Extracted

- `docs/fr-nfr-pengajuan-biaya.md` draf 3
- `docs/fr-nfr.md` draf 11s (FR-56, FR-58, FR-59 akun login trainer; Q-34 terjawab)
- ERD, BPMN, mockup draf 14b

# Jawaban Q-35 (sebagian) dan Q-PB-14 sementara — Role & Permission

- **Source:** user / BA proyek Labmania, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-04
- **Channel:** chat (jawaban atas daftar "yang perlu diputuskan" setelah `docs/fr-nfr.md` draf 11t)
- **Contents:** pernyataan verbatim, tanpa lampiran
- **Supersedes:** usulan draf 11t "Superadmin = role IT – Developer (Division + Jabatan)"; status usulan hapus role tanpa user.

## Pertanyaan yang dijawab (dari draf 11t)

1. Kombinasi Division + Jabatan mana yang jadi Superadmin (developer) dan Admin (user Labmania)?
2. Role yang sudah tidak punya user boleh dihapus?
3. Manajemen sementara pakai jabatan Direktur sambil menunggu daftar posisi?

## Verbatim

> 1. superadmin udah dibuat dari awal aja jadi gabisa add" lagi untuk superadmin
> 2. boleh dihapus
> 3. iya pakai data sementara aja

## Catatan penafsiran

1. **Superadmin** = role **bawaan sistem**, dibuat dari awal bersama akun developer (group id 1); **tidak terbentuk dari Division + Jabatan** dan tidak dapat ditambah lagi. User baru dengan Division + Jabatan apa pun (mis. IT – Developer) menjadi role biasa. Bagian Admin pada pertanyaan 1 tidak dijawab — tetap terbuka.
2. Role tanpa user **boleh dihapus** (dulu usulan, kini keputusan).
3. Manajemen sementara = jabatan **Direktur** (data sementara) sampai daftar posisi dari user (Q-PB-14).

## Extracted

- `docs/fr-nfr.md` draf 11u (FR-38, FR-54 – FR-56, Q-35)
- `docs/fr-nfr-pengajuan-biaya.md` draf 3b (Q-PB-14)
- mockup `ui-mockup/produk/` draf 14d

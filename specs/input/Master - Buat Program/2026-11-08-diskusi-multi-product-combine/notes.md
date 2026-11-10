# Diskusi: satu program berisi lebih dari 1 Product Combine (BELUM DIPUTUSKAN)

- **Source:** tim Labmania (disampaikan ulang oleh BA proyek), lewat sesi Claude Code
- **Date:** 2026-11-08
- **Channel:** chat
- **Contents:** pesan verbatim + ringkasan usulan analis, tanpa lampiran
- **Supersedes:** tidak ada
- **Status:** **diskusi terbuka**. Belum ada keputusan; belum ada perubahan dokumen, ERD, atau mockup. User akan melanjutkan pembahasan nanti.

## Verbatim

> nah yang mau diskusiin, jadi kata client aku pihak labmania, akan ada banyak kemungkinan pada 1 program terdapat beberapa product layanan yang akan dijalani, ibaratnya 1 program terdiri dari lebih dari 1 product combined. nah aku kepikirannya pada satu penjualan program maka bisa select lebih dari 1 program dengan costing masing-masing, tapi agak ragu juga karena jadinya akan banyak sekali, gimana menurut kamu?

> inget diskusi kita ini ya, nanti aku tanya lagi, kalo bisa dicatat dulu

## Kondisi saat ini (to be)

1 Product Combine (`paket_produk`) → 1 Master Program (costing) → 1 Penjualan Program (salinan costing) → Quotation → SO → Surat Kerja Trainer / Invoice. Semua modul hilir memakai "1 Penjualan Program = 1 acara" (1 jadwal, 1 trainer `[K3M-1]`, 1 costing).

## Opsi yang dibahas (usulan analis, bukan keputusan)

| Opsi | Isi | Dampak |
|---|---|---|
| A. Ide user | Penjualan Program memilih lebih dari 1 Master Program, costing masing-masing | Berat: form/simulasi jadi beberapa blok costing; jadwal & trainer per produk (reschedule, Surat Kerja Trainer ikut pecah); baris per produk di Quotation/SO/invoice; Rencana vs Aktual per produk |
| **B. Usulan analis** | **Master Program boleh berisi lebih dari 1 Product Combine**, costing tetap **satu** per program | Kecil: Penjualan Program tetap 1 Master Program; alur Quotation, SO, Surat Kerja Trainer, reschedule, invoice tidak berubah; hanya tampilan daftar produk (sertifikat, lingkup) di Penjualan Program, quotation, formulir konfirmasi, SO. Gabungan yang sering dijual dibuat sekali sebagai Master Program |
| C. Bila sesi terpisah | Beberapa Penjualan Program dikelompokkan di bawah satu induk, masing-masing costing sendiri | Dipakai bila produk berjalan di sesi/tanggal/trainer berbeda atau peserta boleh memilih sebagian |

Arah: B cocok bila jawabannya satu acara, peserta ikut semua, harga paket, untung-rugi cukup per program. Bila sesi terpisah atau peserta memilih sebagian, condong ke C.

## Pertanyaan untuk Labmania (belum dijawab)

1. Contoh nyata program yang isinya lebih dari 1 produk?
2. Produk berjalan dalam **satu acara** (tanggal, lokasi, trainer sama) atau **sesi terpisah**?
3. Peserta selalu ikut **semua produk**, atau boleh memilih sebagian?
4. Harga **satu harga paket**, atau dirinci **per produk** di quotation dan invoice?
5. Perlu **untung-rugi per produk**, atau cukup per program?
6. Gabungan **standar** (sering dijual sama), atau berbeda tiap penjualan?

## Extracted

- Belum ada. Setelah keputusan: FR Master Program / Penjualan Program, ERD (kemungkinan tabel relasi Master Program ↔ Product Combine), mockup to be.

# Jalur Public vs B2B: program sama, beda setelah Final

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-25
- **Channel:** chat (diskusi ide flow ERP dan simulasi; analis mengusulkan flow, user menjawab)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:**
  - `input/Master - Buat Program/2026-10-23-requirements-3-modul/` *Penjualan Program* `[QT-4]`, `[QT-7]` — Company dan komponen harga B2B di Penjualan Program: pindah ke **Sales Order** (ditunda)
  - `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` butir 3 — jumlah peserta B2B default 10 / minimal 10 di Penjualan Program: default 10 kini untuk **semua program**; batas minimal B2B pindah ke Sales Order (lihat catatan)
  - `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/` butir 2 — skema harga B2B per peserta / all-in di Penjualan Program: dihapus dari Penjualan Program; harga paket = akumulasi per peserta di Sales Order
  - `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` butir 1 — Mandays: "lupakan" untuk sekarang

## Verbatim

Pesan 1 (pembuka):

> boleh ga kasih aku ide, gimana baiknya untuk flow di ERPnya. [...] dimana ada 2 jenis penjualan dan detailnya sudah aku kasih tau sebelumnya. nah untuk simulasi perhitungan juga gimana ya baiknya? lupakan yang mandays terpisah di b2b. coba samain dulu aja kedua jenis tersebut saat buat programnya. setelah buat program baru akan ada pembeda jalur. aku agak bingung sebenernya

Pesan 2 (setelah usulan analis: Penjualan Program sama untuk keduanya, beda jalur setelah Final):

> nah bedanya gini:
> 1. kalo untuk public = quotation bisa di share ke banyak peserta, karena ya public ga nginduk ke company
> 2. kalo untuk b2b = quotation hanya untuk 1 karena dia nginduk ke company, tapi da detail harga per peserta juga. tapi namanya jg bukan quotation, apa ya? aku mikirnya sih SO ya.
>
> lalu saat buat program target peserta udah dibuat default 10 peserta ya, tpi bisa di edit. nah gmn ya jadinya?

Pesan 3 (jawaban atas 3 pertanyaan analis: (1) B2B minimal 10 masih berlaku, dicek di SO? (2) jenis dipilih saat buat Penjualan Program atau saat Finalkan? (3) simulasi Master Program lengkap atau ringkas?):

> 1. di SO juga ada detailnya ya
> 2. iya saat buat penjualan program
> 3. simulasi tuh maunya ada lengkap tapi rinciannya simple dan mudah dipahami ya

## Catatan penafsiran

- **Penjualan Program sama untuk Public dan B2B**: satu form, satu simulasi (per peserta). Jenis Public/B2B tetap dipilih saat membuat Penjualan Program (pesan 3 butir 2), tetapi hanya menentukan jalur setelah Final, tidak mengubah form.
- **Setelah Final**: Public → **Quotation** — satu Penjualan Program dapat dibagikan ke banyak calon peserta (1 : banyak), tidak terikat company. B2B → **Sales Order** — tepat satu, terikat satu Company, memuat **detail harga per peserta** (1 : 1). Nama "Sales Order" untuk B2B diusulkan user sendiri ("aku mikirnya sih SO"). Kedua modul tetap ditunda.
- Usulan analis (belum dikonfirmasi): SO punya tahap Draft → Dikirim (surat penawaran) → Deal / Batal, karena di ERP umumnya SO adalah pesanan yang sudah disepakati.
- **Target peserta default 10** saat membuat program (Master Program), dapat diedit; disalin ke Penjualan Program dan masih dapat diedit.
- Pesan 3 butir 1 tidak menjawab langsung apakah **minimal 10 peserta B2B** masih berlaku. Ditafsirkan: aturan peserta B2B berada di detail SO (ditunda); Penjualan Program tidak lagi memberi batas minimal. Ditanyakan ulang sebagai pertanyaan terbuka SO.
- **Company, skema all-in, dan harga paket** keluar dari Penjualan Program. Harga paket B2B = harga per peserta × jumlah peserta, sesuai `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` butir 3 ("all in nanti langsung akumulasi dari per peserta").
- **Simulasi**: tetap lengkap (BEP, hasil rencana, skala peserta, rincian), tetapi **rincian disederhanakan**: langkah hitung dalam bahasa sehari-hari dan tabel profit yang pendek.

## Extracted

- MP-FR-02, MP-FR-10, MP-NFR-05 -> docs/fr-nfr-master-program.md (draf 9)
- PJ-FR-01 – PJ-FR-16, UC-22 (UC-23 digabung), Q-PJ baru -> docs/fr-nfr-penjualan-program.md (draf 8)
- FR-30, bagian 1 (Company dipakai Sales Order) -> docs/fr-nfr.md (draf 9)
- ERD: `penjualan_program.id_customer`, `skema_harga`, `harga_paket`, `jumlah_peserta` dihapus; `target_peserta` default 10 -> docs/diagrams/erd.drawio
- UC-22, UC-23 -> docs/workbook.xlsx
- mockup -> ui-mockup/produk/app.js

# Aksi Lihat/Unduh jadi menu gear; aksi cetak Manage Peserta pindah ke kolom Aksi; case uji reschedule (to be)

- **Source:** BA proyek, lewat sesi Claude Code
- **Date:** 2026-11-12
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Lintas paket:** Data Peserta quotation (Master - Quotation) dan Manage Peserta (Invoice - Pengajuan); keduanya diminta di pesan yang sama.

## Verbatim

> coba di mock up dibuatkan case terkait yang reschedule, peserta batal ikut, dan refund ya. aku mau test dulu.
>
> lalu di bagian data peserta kan ada aksi "Lihat" dan "Unduh" nah itu dibuat pakai icon gear dulu ya
> lalu di manage peserta lalu klik detail peserta, dibagian atas tabel ada banyak action untuk "cetak" itu pindahkan aja ke kolom "Aksi" disamping nomor quotation ya. dengan icon gear juga. lalu yang "Cetak Daftar Peserta" gausah ada.

## Catatan penafsiran

1. "Data peserta" = tab Data Peserta di detail quotation. Bagian peserta di View Sales Order memakai tombol Lihat/Unduh yang sama, jadi ikut dibuat gear agar seragam `[USULAN]`.
2. Menu gear Data Peserta juga memuat **Proses tidak ikut** bila peserta perlu diproses setelah reschedule `[USULAN]`.
3. Cetak yang dipindah: Cetak Name Table dan Cetak Daftar Hadir. Export Excel bukan cetak, tetap di atas tabel `[USULAN]`.
4. Daftar Hadir adalah satu dokumen per program; di menu gear baris peserta labelnya "(semua peserta)". Name Table dapat dicetak untuk peserta itu saja atau semua `[USULAN]`.
5. Case uji: data contoh mockup (bukan data nyata) untuk reschedule, konfirmasi ulang, peserta tidak ikut, dan refund.

## Extracted

- `docs-to-be/fr-nfr-manage-peserta.md` UC-51, MP-FR-04 – MP-FR-06, MP-FR-11 (draf 1j)
- `docs-to-be/fr-nfr-quotation.md` UC-57; `docs-to-be/fr-nfr-sales-order.md` SO-FR-05 (menu gear)
- Mockup `ui-mockup/produk-to-be/mod-quotation.js`, `mod-sales-order.js` (`rsDemo`), `mod-manage-peserta.js`, `main.js`

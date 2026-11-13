# Jawaban 15 pertanyaan terbuka Master Program & Penjualan Program

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-28
- **Channel:** chat (jawaban atas daftar 15 pertanyaan analis)
- **Contents:** pertanyaan (ringkas, oleh analis) + jawaban verbatim, tanpa file lampiran
- **Supersedes:** Q-MP-01, Q-MP-02 (sementara), Q-MP-04, Q-MP-06, Q-MP-08 (`docs/fr-nfr-master-program.md` draf 9c); Q-PJ-01, Q-PJ-02, Q-PJ-03, Q-PJ-08 (sementara), Q-PJ-12, Q-PJ-15, Q-PJ-16 (sementara), Q-PJ-17, Q-PJ-19 (`docs/fr-nfr-penjualan-program.md` draf 8d)

## Butir yang ditanyakan (ringkas, oleh analis)

1. Company B2B boleh diganti selama Draft (terkunci setelah Final), atau terkunci begitu disimpan?
2. Company belum ada di Master Company: tambah dari form (popup), atau tambah dulu di Master Company?
3. Dropdown Company hanya menampilkan Company aktif?
4. (Q-PJ-19) Penjualan Program Public Final juga dicetak sebagai surat penawaran?
5. (Q-PJ-12) Penjualan Program Final boleh dibatalkan, atau hanya Draft?
6. (Q-PJ-03) Tanggal mulai–selesai + lokasi teks bebas cukup? Durasi diisi sendiri atau dihitung dari tanggal?
7. (Q-PJ-08) Format Nomor Penjualan; dibedakan Public dan B2B?
8. (Q-MP-08) Master Program Final masih boleh dibatalkan?
9. (Q-MP-06) Master Program Final boleh disalin menjadi Draft baru?
10. (Q-MP-02) Format Nomor Master Program?
11. (Q-MP-01 / Q-PJ-02) Siapa pengguna yang menyusun, memfinalkan, membatalkan?
12. (Q-MP-04 / Q-PJ-01) Nama menu sudah sesuai?
13. (Q-PJ-15) Sales Order B2B: minimal 10 peserta wajib, atau hanya default?
14. (Q-PJ-16) Status Sales Order apa saja (mis. Draft → Final → Invoice)?
15. (Q-PJ-17) Quotation Public masih memuat Deal/Batal per peserta?

## Verbatim

> 1. A
> 2. buat master company, lalu di penjualan program tinggal select narik data dari master
> 3. A
> 4. sementara iya
> 5. hanya draft ya
> 6. otomatis dari tanggal ya
> 7. iya dibedain ya, buatin formatnya ya.. aku ngikut dulu
> 8. tidak bisa dibatalkan
> 9. tidal
> 10. format ikut dari kamu dulu ya, fix nya belakangan
> 11. manajemen, user belakangan
> 12. sudah
> 13. wajib
> 14. iya ikutin kamu dulu
> 15. iya masih ada
>
> coba buatin dulu tanpa ada tanya jawab lebih lanjut ya, aku mau testing dulu nih

## Catatan penafsiran

- 1 = A: Company dapat diganti selama Draft, terkunci setelah Final.
- 2: tidak ada tambah Company dari form Penjualan Program; Company dibuat di Master Company, Penjualan Program hanya memilih.
- 3 = A: hanya Company **aktif** yang muncul. Master Company karenanya perlu status Aktif/Nonaktif (tabel `customer` sudah punya status aktif). Delete Company tetap mengikuti Q-16 (masih terbuka).
- 4: Public juga dapat dicetak — "sementara", dapat berubah.
- 6: durasi Penjualan Program = tanggal selesai − tanggal mulai + 1 (inklusif, usulan analis) dan tidak diisi manual. Tanggal mulai & selesai karenanya wajib. Lokasi teks bebas tidak dibantah — tetap.
- 7, 10: format diserahkan ke analis, final belakangan. Usulan: Master Program `MP-YYMM-NNN`; Penjualan Program Public `PJP-YYMM-NNN`, B2B `PJB-YYMM-NNN`; nomor urut ulang tiap bulan per prefix.
- 9: "tidal" dibaca "tidak" (salah ketik) — Master Program Final tidak dapat disalin.
- 11: "manajemen, user belakangan" dibaca: aktor = **Manajemen**; pengguna lain (user) menyusul.
- 14: status SO diserahkan ke analis (sementara): Draft → Final → Invoice, atau Batal dari Draft.
- 15: Quotation Public masih memuat Deal/Batal per peserta.
- Penutup: tidak ada tanya jawab lanjutan sebelum testing; sisa ambiguitas diputuskan analis dan ditandai `[USULAN]`.

## Extracted

- MP-FR-03, MP-FR-14, MP-FR-15, UC-19 – UC-21, bagian 6, bagian 7 -> docs/fr-nfr-master-program.md (draf 10)
- PJ-FR-05, PJ-FR-06, PJ-FR-07, PJ-FR-17, PJ-FR-20, PJ-FR-22, UC-22 – UC-31, bagian 4.4, bagian 7 -> docs/fr-nfr-penjualan-program.md (draf 9)
- FR-30, FR-32 -> docs/fr-nfr.md
- nomor dokumen, durasi dari tanggal, Company aktif, MP Final tanpa Batalkan -> ui-mockup/produk/app.js (draf 11c)

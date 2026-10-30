# Fixed & Variable Cost: hanya item yang dipakai yang ditampilkan

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-28
- **Channel:** chat (masukan atas mockup draf 11j)
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** MP-FR-06 "Fixed Cost langsung menampilkan semua item Fixed Cost aktif" (`[MP-13]`)

## Verbatim

> di fixed cost kalo ga di pake gausah di munculin jadi penuh halamannya ya, sama halnya dengan variavle cost

## Catatan penafsiran

- Fixed Cost tidak lagi menampilkan semua item Master Rate otomatis. Sama dengan Variable Cost: item **dipilih satu per satu** dari Master Rate ("+ Tambah") dan dapat dihapus selama Draft.
- "Tidak dipakai" = nominal 0. Item bernilai 0 **dibuang saat disimpan** (usulan analis), sehingga halaman Lihat/Final hanya memuat item terpakai.
- Kelompok satuan yang belum punya item tidak menampilkan tabel kosong; saat terkunci (Final/Batal) kelompok kosong tidak ditampilkan sama sekali (usulan analis).
- Berlaku di Master Program dan Penjualan Program (layout costing sama, PJ-FR-08).

## Extracted

- MP-FR-06, MP-FR-07 -> docs/fr-nfr-master-program.md (draf 10b)
- PJ-FR-05 -> docs/fr-nfr-penjualan-program.md (draf 9b)
- form costing -> ui-mockup/produk/app.js (draf 11k)

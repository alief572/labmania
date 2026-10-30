# Sidebar tanpa label sub modul

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-28
- **Channel:** chat (masukan atas mockup draf 11e)
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** tampilan label sub modul di sidebar (`[RMD-1]`, `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`) — pengelompokan tetap, labelnya tidak ditampilkan

## Verbatim

> di semua menu gausah ada label pembeda kayak yang "atribut produk", "user", "master program", dsbnya. langsung aja soalnya jadi penuh bgt

## Catatan penafsiran

- Sidebar hanya menampilkan nama modul (Master Data, Penjualan Program) dan langsung daftar menu di bawahnya; label sub modul (Atribut Produk, Produk, Customer, Biaya, Pengguna, Master Program, Penjualan Program) tidak tampil.
- Urutan menu tetap mengikuti pengelompokan sub modul. Breadcrumb menjadi Modul / Menu (usulan analis, mengikuti sidebar).
- Search menu hanya mencocokkan nama menu (label sub modul tidak terlihat lagi).

## Extracted

- FR-27, FR-29 -> docs/fr-nfr.md (draf 11c)
- sidebar & breadcrumb -> ui-mockup/produk/app.js, style.css (draf 11f)

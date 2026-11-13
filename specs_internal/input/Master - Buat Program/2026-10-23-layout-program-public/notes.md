# Acuan layout form Program Public dari labmania.html

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** chat, menunjuk file HTML yang sudah ada
- **Contents:** file dibiarkan di lokasi asal agar tidak mengubah `input/`:
  - `input/labmania.html` (22-10-2026) — prototipe HTML buatan BA: master data, Product Costing (penawaran), Quotation, Approval. Salinan lebih lama ada di luar repo (`Downloads/HTML konsep/labmania.html`, 16-10-2026) dan tidak dipakai.
- **Supersedes:** —

## Verbatim

> di modul daftar program, saat buat program ada select antara program public dan b2b, nah yang program publuc aku udah pernah buat HTMLnya yang namanya labmania, ikutin layoutnya ya lalu yang simulasi hasil juga. hanya layout aja, kalo dari formula dan konsep ikuti yang kita diskusiin tadi

## Catatan penafsiran

Yang diikuti dari `labmania.html` (panel *Product Costing*, form "Buat Product Costing") hanya **layout**:
- bar "← Kembali" + judul; kartu *Header*; langkah 1–4 yang dapat dilipat dengan ringkasan saat terlipat;
- kotak kuning *Input simulasi* (durasi, target peserta, harga jual, target profit);
- Fixed Cost dipisah tabel *Event* dan *Day*, Variable Cost dipisah *Peserta/Event* dan *Peserta/Day*; kolom Item / Nominal / Qty / Total; catatan "default Rp X, diubah"; baris total dan rumus;
- Simulasi Hasil: kartu KPI, batang Pendapatan vs HPP, grafik profit menurut jumlah peserta, blok target vs realisasi.

Yang **tidak** diikuti karena konsep/rumus mengikuti `docs/fr-nfr-program-penjualan.md`:
- tanggal mulai/selesai, lokasi, diskon, PPN, catatan internal, bagian "Pengiriman & pendaftar" — tidak ada di Requirements Program (tanggal dicatat di Q-PP-05);
- tombol "+ Biaya lain" (biaya manual) di Public — Q-PP-10;
- realisasi "variable cost mengikuti jumlah peserta aktual" — spec saat ini: cost tetap, hanya profit berubah (PP-FR-28, Q-PP-07);
- kartu "Margin / peserta", "margin % dari pendapatan", dan dua kartu turunan target profit — diganti kartu HPP dan kartu Target profit (perbandingan profit vs target, PP-FR-12).

## Extracted

- Layout form Program Public -> ui-mockup/produk/ (app.js `viewPublicForm`, style.css)
- Baris *Mockup* di header -> docs/fr-nfr-program-penjualan.md

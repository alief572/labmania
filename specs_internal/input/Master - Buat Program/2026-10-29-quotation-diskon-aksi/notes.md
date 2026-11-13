# Quotation: tujuan Company/Personal, peserta baru, diskon Rupiah, banyak quotation Public, 4 aksi

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat
- **Contents:** pesan verbatim; acuan tampilan index `input/labmania.html` (panel Quotation)
- **Supersedes:**
  - `input/Master - Buat Program/2026-10-28-quotation-company-personal/` — usulan analis "Personal = satu peserta" (Q-QTN-02): Personal kini **boleh lebih dari satu** peserta.
  - `input/Master - Buat Program/2026-10-23-tanpa-diskon/` untuk modul Quotation — Quotation kini **punya diskon** (Rupiah saja). Penjualan Program tetap tanpa diskon.
  - Usulan analis `docs/fr-nfr-quotation.md` draf 1: status Draft → Terkirim → Selesai, aksi Hapus, daftar satu baris per quotation.

## Verbatim

> 1. untuk penjualan b2b atau public, setelah buat program akan masuk ke modul quotation
> 2. saat di quotation ada select quotation itu untuk company atau personal. kalau company maka select company, kalau personal maka select peserta (bisa lebih dari satu dan apabila peserta yang belum ada di master maka bisa input di situ dan otomatis tersimpan di master peserta jg. kalau personel dan select peserta maka company peserta juga ketarik datanya ya)
> 3. di quotation ada diskon, buat diskonnya dalam bentuk rupiah saja, gausah persen %
> 4. saat buat quotation untuk public maka bisa dibuat berkali-kali ya quotationnya (kamu lihat html yang namanya "labmania" itu udah bener bentuk index dan kurang lebih begitu.
> 5. saat sudah buat quotation ada 4  action, "Confirmation Letter", "Deal", "Batalkan", "Kirim dan Cetak Quotation". Confirmation letter outputnya berupa surat konfirmasi kehadiran, apabila tidak hadir maka akan kena denda dsbnya (format menyusul) artinya confirmation letter ini ada kalau quotation sudah deal. action batalkan ini teruntuk quotation yang tidak jadi deal. untuk action kirim dan cetak apabila quotation sudah jadi untuk menunggu apakah peserta sudah deal.
>
> buatin dulu ya

## Catatan penafsiran

- 1: menegaskan `[QCP-1]` — Penjualan Program Final, Public maupun B2B, masuk Quotation.
- 2: Company → pilih Company (peserta dari company itu, `[QCP-3]`). Personal → pilih **satu atau lebih** peserta; peserta yang belum ada dapat diisi langsung di form quotation dan otomatis tersimpan ke Master Peserta. Company/instansi milik peserta ikut tampil (ditarik dari Master Peserta).
- 3: diskon hanya nominal Rupiah, tanpa %. Rp **per peserta** mengikuti `labmania.html` (pilihan "Rp / peserta") `[USULAN]`.
- 4: Public dapat dibuatkan quotation berkali-kali. Index mengikuti `labmania.html`: dikelompokkan per Penjualan Program (peserta deal / target, progres dengan BEP, jumlah quotation, Lihat detail, + Quotation), kartu Draft › Terkirim › Deal | Batal, tab On going / Selesai. B2B (yang tidak disebut) dibaca satu quotation aktif `[USULAN]`.
- 5: empat aksi:
  - **Kirim dan Cetak Quotation** — quotation sudah jadi, dikirim, menunggu deal → status Terkirim.
  - **Deal** — quotation disetujui → status Deal.
  - **Batalkan** — quotation tidak jadi deal → status Batal.
  - **Confirmation Letter** — hanya untuk quotation Deal; surat konfirmasi kehadiran, ketidakhadiran dikenai denda. Format menyusul.
- Deal per quotation bertemu jawaban 28-10-2026 butir 15 (Deal/Batal per peserta masih ada). Usulan analis: aksi Deal membuka daftar peserta untuk dicentang yang deal (default semua); yang tidak dicentang Batal.

## Extracted

- QTN-FR-01 – QTN-FR-12, bagian 6, bagian 7 -> docs/fr-nfr-quotation.md (draf 2)
- FR-15, FR-16 (peserta baru dari Quotation) -> docs/fr-nfr.md
- mockup -> ui-mockup/produk/app.js (draf 11m)

# Multi Master Program, batal program, invoice B2B, TOP, harga katalog, modul Refund (13-11-2026)

- **Tanggal:** 13-11-2026
- **Sumber:** permintaan BA di chat, "coba kerjain dulu ya"
- **Penanda sitasi:** `[MMB-n]` = butir n

## Verbatim

> ada yang mau berubah:
> 1. saat ini di master penjualan program bisa select product lebih dari 1, tapi ternyata ada miss komunikasi. yang dimana ternyata yang benar adalah saat penjualan program bisa select lebih dari 1 master program dengan costingnya masing-masing. sehingga pada penjualan program, bisa terdiri dari 5 master beserta 5 costingnya. tolong dibuat seperti itu ya.
> 2. pada penjualan program apabila memang prosesnya sudah sampai quotation tetap bisa "batalkan" ya, efeknya semua proses yang sudah terjadi setelahnya maka menjadi "batal"
> 3. apabila b2b maka invoicenya terhadap company aja, jangan didetailkan ke perpeserta kayak sekarang
> 4. pada quotation tambah poin terkait cara pembayaran. ada full payment dan TOP. apabila TOP, per TOPnya buat keterangan ya. Per top akan menjadi per invoice juga
> 5. di katalog produk selain ada tambah lingkup ada juga inputan harga dengan keterangan "start from" jadi belum fix, hanya perkiraan harga aja
> 6. untuk refund buatkan 1 modul khusus yang dimana apabila ada peserta batal ikut dan refund akan masuk ke menu itu untuk di proses refundnya, nah tampilan proses refundnya yang saat ini sudah sesuai, tambahkan menu approval refund juga ya
>
> coba kerjain dulu ya

## Interpretasi analis

1. **Multi Master Program di Penjualan Program** `[MMB-1]`:
   - Master Program kembali memuat **satu Product**. Pilihan banyak Product (09-11-2026, `input/Master - Buat Program/2026-11-09-product-multi-reschedule-evidence/`) adalah salah paham.
   - Penjualan Program dapat memilih **satu atau lebih Master Program Final**, masing-masing membawa costing-nya sendiri (Fixed + Variable Cost) dan harga jual per peserta sendiri.
   - Harga jual per peserta Penjualan Program = jumlah harga per Master Program. HPP dan simulasi = jumlah seluruh costing.
   - Target peserta, tanggal, lokasi, trainer, dan asisten tetap satu per Penjualan Program `[USULAN]`.
2. **Batalkan Penjualan Program Final** `[MMB-2]`, termasuk yang sudah punya quotation; tidak berlaku untuk program yang sudah Closed. Efeknya:
   - semua quotation yang belum Batal menjadi Batal;
   - SO pindah ke SO Batal;
   - invoice yang belum dibayar dibatalkan;
   - invoice yang sudah dibayar tetap, dan untuk peserta/company terkait dibuat **Refund** `[USULAN]`;
   - Surat Kerja Trainer berstatus Batal;
   - pengajuan biaya yang masih Menunggu Approval dibatalkan `[USULAN]`.
3. **Invoice B2B** `[MMB-3]`: selalu satu invoice ke company, rincian tagihan satu baris paket (jumlah peserta disebut), tanpa pecahan per peserta.
4. **Cara pembayaran di quotation** `[MMB-4]`:
   - Pilihan **Full payment** atau **TOP**.
   - TOP berisi beberapa termin, masing-masing dengan **persentase** `[USULAN]` dan **keterangan**.
   - Satu termin = satu invoice. Baris Pembayaran di Penawaran dicetak lagi (menggantikan Q-QTN-14 "dihilangkan dulu").
5. **Harga "start from" di Katalog Produk** `[MMB-5]`: per Product ada isian perkiraan harga, tampil sebagai "Mulai dari Rp …" di katalog/PDF. Tidak dipakai di perhitungan mana pun.
6. **Modul Refund** `[MMB-6]`:
   - Modul sidebar sendiri dengan menu **Daftar Refund** dan **Approval Refund**.
   - Refund dari peserta yang tidak ikut dan sudah membayar masuk ke modul ini.
   - Alur: Menunggu Approval → (Setujui / Tolak) → Disetujui, menunggu transfer → Proses Refund (form sekarang dipertahankan) → Selesai.
   - Tab Refund di Sales Order dipindah ke modul ini.

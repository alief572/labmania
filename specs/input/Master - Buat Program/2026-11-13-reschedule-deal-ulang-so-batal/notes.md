# Reschedule: penanda, Deal ulang, SO Batal, penanda invoice (13-11-2026)

- **Tanggal:** 13-11-2026
- **Sumber:** diskusi BA dengan analis di chat (usulan BA, belum dikonfirmasi ke Labmania)
- **Status:** disetujui BA untuk dikerjakan; **belum diterapkan** ke spec/mockup (BA: "itu dulu aja, ada secara ui ux yang mau aku benahi lagi")

## Permintaan BA (verbatim)

> disaat ada reschedule maka ada status atau penanda di modul quotation bahwa program tersebut reschedule dan detail perquotationnya perlu ada penanda bahwa harus dikirimkan link konfirmasi baru agar data peserta terupdate. deal ulang juga, jadi kalo ada reschedule SO yang sebelumnya udah terbentuk karena sebelumnya udah deal juga jadi ga ada, karena apabila ada reschedule maka harus mengulang proses dari link konfirmasi baru..nah kalo udah ada yang terlanjur di invoice dan udah dibayar kasih penanda jg di invoice ya

## Keputusan BA atas poin diskusi (verbatim)

> 1. SO lama tetap ada tetapi beda tab aja dengan nama tab "SO batal"
> 2. setuju sama usul kamu. iya dokumen lama ikut terbawa dan tercatat pada sistem aja. yang utama dan penting adalah harus isi konfirmasi baru karena butuh persetujuan dan ttd
> 3. yang poin a ya
> 4. nah ini dia.. gimana caranya supaya ga perlu ubah faktur pajak tetapi nomor SO bisa terupdate. selama belum diibayarkan aku rasa no SO berubah ga menjadi masalah?
> 5. setuju sama usul kamu
> 6. iya h-1 dan iya dianggap batal aja
> 7. setuju
>
> nah ini note tambahan ya, SO lama dan SO baru dalam artian apabila baru hanya ada tambahan keterangan "rev" aja.

Lanjutan poin 4: "1. setuju" (usulan analis di bawah); opsi Nomor Faktur diisi saat pembayaran: "2. tidak usah dulu".

## Interpretasi yang disetujui

1. **Penanda:** Daftar Quotation menandai program "Reschedule"; setiap quotation program itu bertanda "Perlu kirim link konfirmasi baru".
2. **Deal ulang:** quotation yang sudah Deal kembali ke Terkirim (nomor -rev{n}); dokumen customer lama tetap tersimpan (tidak wajib unggah ulang); peserta wajib mengisi link konfirmasi baru (persetujuan + surat bertanda tangan); Sales melakukan Deal ulang dengan mencentang peserta yang Pindah jadwal.
3. **SO:** SO lama tetap ada sebagai riwayat di tab **SO Batal**; SO yang berlaku setelah Deal ulang memakai nomor yang sama + "-rev{n}" (mis. LMI/SO/11/26-004 → LMI/SO/11/26-004-rev1).
4. **Link konfirmasi ulang:** aturan H-14 tidak berlaku; link menerima jawaban sampai **H-1** (poin 3a).
5. **Invoice belum dibayar:** **tidak dibatalkan** saat reschedule, hanya diberi penanda. Setelah Deal ulang: bila semua pesertanya Pindah jadwal, invoice tetap (nomor, nilai, faktur sama) dan rujukan SO pindah ke SO -rev; bila ada peserta Batal ikut, invoice dibatalkan dan peserta yang ikut ditagih dengan invoice baru (faktur terdampak karena nilai berubah).
6. **Invoice sudah dibayar:** tetap, diberi penanda "Reschedule: menunggu konfirmasi ulang"; setelah Deal ulang peserta Pindah jadwal memakai invoice lama untuk SO -rev (tidak ditagih lagi), peserta Refund masuk proses Refund. Tab Uninvoiced hanya memuat peserta yang belum punya invoice.
7. **Batas:** jawaban konfirmasi ulang dan Deal ulang paling lambat H-1; tidak menjawab = batal.
8. Quotation yang belum Deal saat reschedule: nomor -rev, link baru, penanda yang sama, proses biasa.

## Belum ditanyakan ke Labmania

- Perubahan ini menggeser jawaban sebelumnya bahwa SO tidak dapat dibatalkan (Q-SO-05) dan bahwa quotation/SO cukup naik revisi.
- Perlakuan faktur pajak saat Refund / invoice dibatalkan (ditunda BA 13-11-2026, "nanti dulu").

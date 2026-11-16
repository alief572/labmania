# Jawaban pertanyaan kritis: multi Master Program, batal & refund, TOP, reschedule (13-11-2026)

- **Tanggal:** 13-11-2026
- **Sumber:** jawaban BA di chat atas 8 pertanyaan kritis analis (lanjutan `input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/`)
- **Penanda sitasi:** `[JMR-n]` = jawaban nomor n

## Pertanyaan analis (ringkas)

1. Dalam satu Penjualan Program, tanggal, durasi, target peserta, trainer, asisten berlaku untuk semua Master Program, atau tiap Master Program bisa punya tanggal dan trainer sendiri? (usulan: berlaku untuk seluruh program)
2. Peserta ikut semua Master Program atau boleh sebagian; sertifikat dan JP per Master Program? (usulan: ikut semua; sertifikat per Master Program/Product; JP per program, ditulis total)
3. Peserta sudah bayar saat program dibatalkan: refund penuh otomatis atau ada pilihan lain?
4. Siapa yang menyetujui refund?
5. Refund ditolak: apa yang terjadi?
6. Termin TOP diisi persen atau rupiah?
7. TOP, peserta tidak ikut setelah sebagian termin dibayar: refund dari termin yang sudah dibayar, termin belum dibayar dibatalkan? (usulan: ya)
8. Pendaftar setelah reschedule: link sampai H-1 jadwal baru atau tetap H-14? Tambah kuota boleh saat menunggu Deal ulang? (usulan: H-1, boleh)

## Verbatim jawaban

> Multi master program:
> 1. bisa punya trainer, tanggal, asisten trainer yang berbeda-beda. jadi 1 program bisa aja dalam 5 hari kerja dengan 5 produk berbeda gitu (misalnya aja) costing jg dipisah karena ya beda-beda
> 2. sesuai usulan kamu ya
>
> Batal program dan refund
> 3. refund aja ya
> 4. buat menjadi finance dulu
> 5. apabila reject balik ke menu refund dan sales/marketing perlu memperbaiki sesuai dengan alasan reject
>
> TOP
> 6. dalam rupiah ya, lalu sampingnya ada peresennya otomatis, atau apabila pengisian sebaliknya jg gapapa
> 7. sesuai usulan kamu
>
> Reschedule
> 8. sesuai dengan usulan kamu
>
> faktur belakangan, belum terlalu mendetail ke invoice soalnya. kerjain dan update ke mockp up hingga semua dokumentasi lainnya ya (erd, use case, FR & NFR, flow process)

## Interpretasi analis

1. `[JMR-1]` Tiap Master Program dalam Penjualan Program punya **tanggal mulai–selesai, trainer, dan asisten sendiri**; durasi dan costing per Master Program. Tanggal program = tanggal mulai paling awal s.d. selesai paling akhir. Target peserta tetap satu (peserta ikut semua, `[JMR-2]`).
2. `[JMR-2]` Peserta ikut semua Master Program; **sertifikat per Master Program (Product)**; Jam Pembelajaran per Master Program, Penawaran menulis total.
3. `[JMR-3]` Program dibatalkan → peserta yang sudah bayar **Refund** (tidak ada opsi lain).
4. `[JMR-4]` Yang menyetujui refund: **Finance** (sementara).
5. `[JMR-5]` Refund **ditolak** kembali ke menu Refund; **Sales/Marketing memperbaiki** sesuai alasan penolakan lalu mengajukan ulang ke approval.
6. `[JMR-6]` Termin TOP diisi **rupiah**, persen dihitung otomatis; boleh juga isi persen, rupiah dihitung otomatis.
7. `[JMR-7]` Usulan analis disetujui: refund TOP = bagian termin yang sudah dibayar; termin belum dibayar dibatalkan.
8. `[JMR-8]` Usulan analis disetujui: link untuk pendaftar baru setelah reschedule terbuka sampai **H-1** jadwal baru; **Tambah kuota** boleh saat menunggu Deal ulang.
9. Faktur pajak untuk refund: ditunda ("faktur belakangan").

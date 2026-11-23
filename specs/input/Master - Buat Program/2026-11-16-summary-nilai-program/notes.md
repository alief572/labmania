# Summary nilai program untuk Penjualan Program gabungan beberapa Master Program (16-11-2026)

- **Tanggal:** 16-11-2026
- **Sumber:** permintaan BA di chat, setelah penjelasan perhitungan profit multi Master Program (PJ-FR-32)
- **Penanda sitasi:** `[SNP]`

## Verbatim

> eh iya mau nanya, berarti pas penjualan program kan dia bisa select beberapa master jadi 1 penjualan program. itu perhitungan profitnya gimana ya? digabungin ya? jadi kayak 1 program nanti total simulasinya gabungan dari beberapa master?

> nah mungkin boleh tuh ada 1 poin lagi khusus untuk 1 penjualan program gabungan beberapa master yang menjadi summary nya ya? summary nilai perogram gitu

## Interpretasi analis

1. Form Penjualan Program yang berisi **lebih dari satu Master Program** mendapat bagian baru **5. Summary Nilai Program**, di bawah bagian 4 Simulasi Hasil. Program dengan satu Master Program tidak menampilkannya (nilainya sama dengan bagian 4).
2. Isi bagian ini memakai aturan nilai program yang sudah ada di PJ-FR-32 (tidak ada rumus baru):
   - kepala: jadwal program (mulai paling awal s.d. selesai paling akhir, total JP), target peserta, harga jual / peserta di quotation (jumlah harga semua Master Program);
   - tabel rincian per Master Program: jadwal, harga jual / peserta, Fixed Cost, Variable Cost, HPP, pendapatan, profit, margin, target profit, dan baris **Total program**;
   - **simulasi nilai program (gabungan)**: tampilan sama dengan Simulasi Hasil (BEP peserta, BEP harga, pendapatan, HPP, profit, margin, target profit) dihitung atas nilai gabungan `[USULAN]`.
3. Hanya tampilan; tidak disimpan sebagai data terpisah, ikut berubah saat costing Master Program diubah, dan tampil juga saat program Final (hanya baca).

## Catatan terbuka

- Fixed Cost dari tiap Master Program dijumlah apa adanya. Biaya yang sebenarnya dipakai bersama (mis. sewa tempat di hari yang sama) dapat terhitung dua kali bila tidak dikurangi user. Belum ada aturan otomatis; perlu konfirmasi Labmania.

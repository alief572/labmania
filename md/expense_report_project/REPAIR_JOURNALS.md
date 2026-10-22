# Pemulihan jurnal expense approved

Skrip `scripts/repair_expense_report_journals.php` berjalan hanya lewat CLI PHP 7.4+ dengan mysqli/mysqlnd. Skrip membaca konfigurasi `application/config/development/database.php`, lalu memakai satu koneksi `default` untuk mengakses tiga schema di server yang sama: `db_consultant_new`, `db_sendigs_ss`, dan `db_sendigs_gl_ss`. Pengaturan host koneksi `gl_sendigs` tidak digunakan; schema GL dibaca pada server consultant/finance supaya transaksi pemulihan memakai satu koneksi.

## Audit dan backup

Jalankan dari host melalui Docker. Pilih direktori baru untuk setiap audit; direktori wajib berada di luar `/var/www`, dengan izin 0700.

```powershell
docker exec consultant_web php /var/www/scripts/repair_expense_report_journals.php --output-dir=/tmp/expense-journal-repair/run-01
docker cp consultant_web:/tmp/expense-journal-repair/run-01 C:\private-backups\expense-run-01
```

Buat direktori backup host terlebih dahulu di lokasi privat di luar document root. Pastikan salinan host berhasil sebelum apply. Jangan menyimpan manifest/receipt di Git atau `uploads`: isinya mencakup jurnal asli, rincian expense, dan referensi perusahaan/bank. Kredensial koneksi tidak disertakan.

`manifest.json` memuat setiap nomor expense, klasifikasi `ok`/`repair`/`held`, alasan, jurnal yang diusulkan, fingerprint, dan seluruh data sumber yang dibaca. Pemeriksaan tidak hanya mengandalkan keseimbangan debit-kredit: jumlah serta isi setiap baris dibandingkan, termasuk baris dengan nominal sama yang berasal dari detail berbeda.

## Apply

Gunakan jendela singkat untuk pemulihan. Named lock mencegah dua skrip repair berjalan bersamaan. Skrip juga mengambil `READ` table locks pada tabel sumber/master dan `WRITE` table lock pada `tr_jurnal` sepanjang apply/restore, sehingga aktivitas approval/pembuatan jurnal lain yang menyentuh tabel tersebut menunggu. Kunci tetap bertahan melewati commit/rollback masing-masing expense dan dilepas saat selesai/gagal. Akun database membutuhkan hak `LOCK TABLES`; jika tidak tersedia, proses berhenti tanpa mengganti jurnal. Perubahan sumber sejak audit membatalkan pemulihan expense itu. Pembacaan jurnal dari aplikasi juga dapat tertunda selama jendela ini.

```powershell
docker exec consultant_web php /var/www/scripts/repair_expense_report_journals.php --apply=/tmp/expense-journal-repair/run-01/manifest.json --maintenance-window
docker cp consultant_web:/tmp/expense-journal-repair/run-01/. C:\private-backups\expense-run-01
docker exec consultant_web php /var/www/scripts/repair_expense_report_journals.php --output-dir=/tmp/expense-journal-repair/verify-01
```

Salin kembali hasil apply/receipt dan manifest verifikasi ke host. `/tmp` di container tidak cukup sebagai satu-satunya backup karena dapat hilang saat container dibuat ulang.

Apply hanya mengganti jurnal belum diposting (`sts=0` atau `NULL`) untuk pasangan nomor expense dan jenis `Expense Report Consultant`. Status lain ditahan. Backup asli tersedia sebelum penulisan; receipt berisi ID jurnal baru disimpan sebelum commit. Kegagalan insert atau penyimpanan receipt membatalkan seluruh perubahan expense tersebut. Transaksi sebelumnya yang telah commit tetap tercatat dalam receipt; jika proses berhenti di tengah, jalankan ulang manifest untuk melewati transaksi yang sudah benar. Jika data sumber berubah, buat audit baru.

Nomor AJV baru melanjutkan prefix angka terbesar pada bulan/tahun tanggal jurnal, bukan `MAX(id)`. Skrip tidak memperbaiki benturan nomor jurnal lama di luar expense sasaran. `created_by` jurnal baru menggunakan `repair:<run-id>`, dan `created_date` mencatat waktu pemulihan; metadata asli tersimpan di backup. Tanggal jurnal lama yang konsisten dipertahankan; untuk jurnal baru, urutannya tanggal approval lalu tanggal pembuatan expense.

## Mengembalikan jurnal asli

```powershell
docker exec consultant_web php /var/www/scripts/repair_expense_report_journals.php --restore=/tmp/expense-journal-repair/run-01/manifest.json --maintenance-window
```

Restore membutuhkan manifest beserta seluruh `receipt-*.json` pada direktori yang sama. Jika container dibuat ulang, salin backup host kembali ke direktori privat dalam container, lalu atur izin direktori 0700 dan berkas 0600. Restore memeriksa bahwa jurnal sekarang persis sama dengan hasil repair, kemudian memulihkan semua kolom asli termasuk ID dan status `NULL`. Restore ditolak jika jurnal sudah berubah/diposting. Restore mengembalikan kondisi sebelum perbaikan, termasuk kerusakannya; gunakan hanya untuk membatalkan run yang salah. Setelah restore, buat audit baru sebelum apply lagi.

## Aturan pemulihan yang disepakati

- Pertahankan pemetaan biaya yang ada jika COA valid pada master GL. Jika kosong/tidak valid: tipe kasbon 2 memakai `5101-01-04`, tipe 3 memakai `5101-01-05`. Tipe lain membutuhkan pemetaan valid.
- Kredit kasbon memakai `1103-01-04`; debit refund mengikuti COA bank header; kredit kurang bayar memakai `9999-99-99`.
- Perhitungan detail mengikuti modul: kuantitas dikali nominal, kecuali kuantitas di bawah satu memakai nominal langsung. Aritmetika memakai integer sen dan pembulatan setengah ke atas untuk hasil perkalian. Nilai di luar rentang aman ditolak.
- Expense `0023`, `0024`, dan `0025/EXP/H/2025` sah sebagai tiga transaksi terpisah untuk kasbon yang sama.
- Expense `0002`, `0004`, dan `0005/EXP/H/2025` ditahan sampai bank pengembalian diketahui. Skrip tidak mengisi bank atau tanggal approval pada header.

## Pemeriksaan dan TODO

```powershell
docker exec consultant_web php -l /var/www/scripts/repair_expense_report_journals.php
docker exec consultant_web php /var/www/tests/ExpenseReportJournalRepairTest.php --integration
```

Pengujian integrasi memakai tabel sementara serta satu tabel fixture yang dibuat dan dihapus kembali di database Docker lokal `mysql8`. Test tidak membaca konfigurasi remote dan tidak menulis ke server remote. Pengujian mencakup rollback setelah insert gagal, backup gagal ditulis, restore, penomoran, perubahan sumber, idempotensi, dan pemblokiran penulis lain selama maintenance.

TODO terpisah: perbaiki alur approval rutin yang memiliki ketidaksesuaian COA pada cabang refund, master biaya tanpa COA, serta penomoran jurnal dan pencegahan approval ganda. Skrip ini tidak mengubah alur approval, request payment, pembayaran, atau PPh21.

# Keputusan 2: Laravel di root, CI3 sebagai arsip

Tanggal: 16-11-2026. Sumber: persetujuan pengguna terhadap plan relokasi dan
instruksi eksekusi pada percakapan proyek. Menggantikan keputusan lokasi folder
pada keputusan 1; tidak mengesahkan cutover production atau mengubah aturan bisnis.

Laravel berada di root repository, CI3 di `ci3/` sebagai arsip tanpa runtime web
aktif. `specs/`, `.artifacts/`, metadata Git dan `docker/mysql/` tetap di root.
Pengecualian Docker diperlukan untuk bind mount database GL yang masih aktif.

Runtime lokal tetap localhost:8080; database/storage/APP_KEY, akses Navicat
127.0.0.1:3308 dan sumber GL dipertahankan. Path internal container tetap
`/var/www/laravel`; hanya path host/build/development yang berubah.

ERD lokal memiliki perbedaan aturan bisnis dengan versi origin/dev. Relokasi
mempertahankan isi ERD lokal tanpa menyelaraskan aturan tersebut. Versi remote
dan snapshot lokal tersimpan dalam artifact privat relokasi serta riwayat Git;
rekonsiliasi bisnis ERD harus menjadi pekerjaan BA terpisah sebelum mengubah skema.

# Peserta Deal dari link konfirmasi disimpan ke Master Peserta (to be)

- **Source:** BA proyek, keputusan dalam sesi Claude Code
- **Date:** 2026-11-11
- **Channel:** chat
- **Contents:** pertanyaan dan jawaban BA, verbatim, tanpa lampiran
- **Menjawab:** Q-TB-04 (`docs-to-be/fr-nfr-quotation.md`); menyentuh `docs-to-be/fr-nfr.md` FR-15 – FR-17.

## Verbatim

Pertanyaan BA:

> memungkinkan ga data peserta yang di link konfirmasi letter terintegrasi ke master peserta? jadi kalo ada peserta baru dari link konfirmasi maka otomatis tersimpan di master peserta?

Pilihan waktu simpan yang ditawarkan: saat isian link masuk atau saat Deal. Jawaban BA:

> saat deal aja deh. field yang perlu diisi saat isi konfirmasi letter maka akan ada perubahan ya?

Pilihan isian yang ditawarkan:
(a) link tidak diubah; field Master Peserta yang tidak ada di link (Gender, Bidang, Provinsi, Kota) ditandai "Belum lengkap" dan dilengkapi admin;
(b) link ditambah Gender, Bidang, Provinsi, Kota (wajib);
(c) Bidang otomatis dari program, Provinsi/Kota dari Master Company, sisanya "Belum lengkap".
Ditanyakan juga: apakah Bidang peserta = bidang program.

Jawaban BA:

> a. setuju gitu, sisanya dari admin yang mengisi
> b. diisiin sama internal labmania
> c. iya otomatis aja

## Catatan penafsiran

- Isian link **tidak berubah** (Public maupun B2B).
- Penyimpanan ke Master Peserta terjadi **saat Deal**, hanya untuk peserta yang dicentang Deal. Pencocokan peserta lama memakai NIK, email, atau No. WhatsApp; bila cocok, hanya field kosong yang diisi dan data lama tidak ditimpa (keputusan sesi 11-11 sebelum hold).
- Field yang tidak ada di link (Gender, dan Bidang/Provinsi/Kota bila tidak dapat diturunkan) diisi **internal Labmania (admin)** lewat Edit Master Peserta; peserta tersebut ditandai **Belum lengkap**.
- "c. iya otomatis aja" ditafsirkan: **Bidang** terisi otomatis dari Jenis Bidang Product program, dan **Provinsi/Kota** terisi otomatis dari Master Company bila quotation ditujukan ke Company. Bila program memakai lebih dari satu Product dengan Bidang berbeda, Bidang dibiarkan kosong (usulan BA-AI, belum dikonfirmasi).

## Extracted

1. Saat Deal, peserta Deal disimpan ke Master Peserta (baru atau dicocokkan dengan yang sudah ada).
2. Link konfirmasi tidak menambah isian.
3. Bidang otomatis dari program; Provinsi/Kota otomatis dari company bila ada.
4. Field lain yang kosong dilengkapi admin Labmania; daftar Master Peserta menandai "Belum lengkap".

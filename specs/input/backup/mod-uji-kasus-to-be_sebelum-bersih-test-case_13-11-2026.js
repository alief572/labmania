/* Labmania mockup to be — data uji jawaban pertanyaan terbuka 12-11-2026 (permintaan BA: "tambahin test case untuk mencoba
   si perubahan-perubahan yang ada"). Sumber aturan: input/Master - Buat Program/2026-11-12-jawaban-pertanyaan-terbuka/.
   Hanya data contoh: setiap kasus diberi lokasi atau judul berawalan "UJI-n" supaya mudah dicari. Dipanggil main.js setelah rsDemo().
   Tanggal dihitung dari hari ini, sehingga kasus tetap berlaku kapan pun mockup dibuka. */
'use strict';
  function ujiKasus() {
    const hari = n => addDays(todayIso, n);
    const psId = nama => (db.peserta.find(p => p.nama === nama) || {}).id;
    const us = db.user.filter(u => u.aktif);
    const co2 = db.company[1];
    const mkPj = (lokasi, mulai) => seedQT(Object.assign(fromMP(mp1, 'public'), { status: 'final', hargaJual: 650000, tglMulai: mulai, tglSelesai: addDays(mulai, 1),
      lokasi, waktu: '09.00–15.15 WIB', trainer: (db.trainer[0] || {}).id || '', asisten: (us[2 % us.length] || {}).id || '' }));
    const mkQ = (pj, nama, o = {}) => {
      const tgl = o.tanggal || addDays(pj.tglMulai, -30), tunggu = o.status === 'terkirim';
      const k = Object.assign({ id: uid(), nomor: qtnNo(tgl), pj: pj.id, tanggal: tgl, berlaku: addDays(pj.tglMulai, -1), sales: db.user[1].id, sumber: 'Sales', ppn: true, diskon: 0, lain: 0,
        up: '', tujuan: 'personal', company: '', alamat: 'Kota Bekasi, Jawa Barat', kuota: nama.length, status: 'deal',
        peserta: nama.map(n => ({ id: psId(n), st: tunggu ? 'menunggu' : 'deal' })) }, o);
      if (k.status === 'deal' && !k.dokumen) { k.dokumen = [{ nama: `Bukti-transfer-${k.nomor.slice(-3)}.jpg`, ukuran: 210000, oleh: 'Contoh User Dua', tgl: `${addDays(tgl, 2)} 10:00` }]; k.dokKet = 'Bukti transfer (contoh uji)'; }
      k.ket = ketDefault(pj.jenis); k.kf = kfNew(kfDefaultFor(pj)); db.qtn.push(k); return k;
    };
    const mkSo = k => { const s = soCreate(k, addDays(k.tanggal, 2)); return s; };
    const mkInv = (k, bayar) => {
      const tgl = addDays(k.tanggal, 3), pl = ivcPerlakuan(k), u = ivcNilaiUnit(k, 'satu'), hh = ivcHitung(u.penjualan, u.lain, pl), nomor = ivcNo(tgl), coa = coaSalesDefault(k);
      const i = Object.assign({ id: uid(), nomor, tanggal: tgl, qtn: k.id, prog: progNo(k), jenis: pjOfQtn(k).jenis, cara: 'satu', peserta: u.ps, perlakuan: pl,
        noFaktur: `0400260000${String(300 + db.inv.length)}`, coaPenjualan: coa, setor: null, status: 'invoiced', jurnal: ivcJurnal(hh, pl, coa, nomor, k, tgl) }, hh);
      if (bayar) i.bayar = { tanggal: addDays(tgl, 2), oleh: 'Admin Sistem', ket: 'Transfer BCA (contoh uji)' };
      db.inv.push(i); Object.assign(soOfQtn(k) || {}, { status: 'siap', siapBy: 'Admin Sistem', siapAt: `${tgl} 09:00` });
      return i;
    };

    // UJI-1 Program siap Close: acara sudah lewat, satu-satunya invoice sudah dibayar.
    const pj1 = mkPj('UJI-1 Siap Close · Offline Bekasi', hari(-12));
    const q1 = mkQ(pj1, ['Contoh Peserta K']);
    // UJI-2 Acara sudah lewat tetapi belum bisa Close: satu invoice belum dibayar, satu quotation Deal belum ditagih.
    const pj2 = mkPj('UJI-2 Belum bisa Close · Offline Bekasi', hari(-8));
    const q2a = mkQ(pj2, ['Contoh Peserta L']), q2b = mkQ(pj2, ['Contoh Peserta M']);
    // UJI-3 Program sudah Closed: quotation dan invoice terkunci, program di tab Selesai.
    const pj3 = mkPj('UJI-3 Sudah Closed · Online (Zoom)', hari(-20));
    const q3 = mkQ(pj3, ['Contoh Peserta N']);
    // UJI-4 Link tertutup karena H-14: acara 10 hari lagi, quotation masih Terkirim ke company.
    const pj4 = mkPj('UJI-4 Link tertutup H-14 · Offline Bekasi', hari(10));
    mkQ(pj4, ['Contoh Peserta O'], { status: 'terkirim', tujuan: 'company', company: co2.id, up: co2.picNama, alamat: `${co2.alamat}, ${co2.kota}`, kuota: 3, tanggal: hari(-6) });
    // UJI-5 Tambah kuota: link tertutup karena kuota penuh; setelah kuota dinaikkan, link terbuka lagi.
    const pj5 = mkPj('UJI-5 Tambah kuota · Offline Surabaya', hari(45));
    mkQ(pj5, ['Contoh Peserta F', 'Contoh Peserta G'], { status: 'terkirim', tujuan: 'company', company: co2.id, up: co2.picNama, alamat: `${co2.alamat}, ${co2.kota}`, kuota: 2, tanggal: hari(-3) });
    // UJI-6 Konfirmasi ulang lewat H-1: program di-reschedule ke besok, tiga peserta belum menjawab.
    //   Contoh Peserta D: invoice sudah dibayar (Proses: opsi awal Refund); E: invoice belum dibayar (Batal ikut, invoice dibatalkan);
    //   A: belum ditagih (Batal ikut).
    const pj6 = mkPj('UJI-6 Konfirmasi ulang lewat H-1 · Offline Bekasi', hari(30));
    const q6a = mkQ(pj6, ['Contoh Peserta D'], { tanggal: hari(-10) }), q6b = mkQ(pj6, ['Contoh Peserta E'], { tanggal: hari(-10) }), q6c = mkQ(pj6, ['Contoh Peserta A'], { tanggal: hari(-10) });

    qpsSeed(); // peserta quotation baru → isian Konfirmasi Kehadiran (surat contoh ikut dibuat)
    [q1, q2a, q2b, q3, q6a, q6b, q6c].forEach(mkSo);
    mkInv(q1, true); mkInv(q2a, false); mkInv(q3, true); mkInv(q6a, true); mkInv(q6b, false);
    pj3.closed = { by: 'Contoh User Dua', at: `${hari(-5)}T16:00` };
    reschedApply(pj6, { mulai: hari(1), trainer: pj6.trainer, asisten: pj6.asisten, alasan: 'Ruang pelatihan tidak tersedia (contoh uji)', evidence: ['Surat-pembatalan-ruang.jpg'], oleh: 'Contoh User Dua', waktuUbah: `${hari(-2)}T10:00` });

    // UJI-7 Product nonaktif dengan kombinasi sama dengan Product aktif: tidak menghalangi, tetapi tidak bisa diaktifkan kembali.
    const pk = db.paket.find(p => p.aktif);
    if (pk) db.paket.push(Object.assign({}, pk, { id: uid(), nomor: nextCode('PC'), judul: `UJI-7 ${pk.judul} (versi lama, nonaktif)`, aktif: false }));
    // UJI-8 Company belum dipakai transaksi: boleh dihapus (bandingkan dengan company contoh lain yang ditolak).
    db.company.push(Object.assign({ id: uid(), kode: nextCode('CO'), aktif: true }, { nama: 'UJI-8 PT Belum Dipakai', jenis: 'Swasta', alamat: 'Jl. Contoh Uji No. 8', provinsi: 'Jawa Barat',
      kota: 'Kota Bekasi', telp: '0218880008', email: 'info@uji8.co.id', picNama: 'Contoh PIC Uji', picJabatan: 'Admin', picTelp: '081300000008', picEmail: 'pic@uji8.co.id', npwp: '012345678908000', ppnPerlakuan: 'PKP' }));
  }

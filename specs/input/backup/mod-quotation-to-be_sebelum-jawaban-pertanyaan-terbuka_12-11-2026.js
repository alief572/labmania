/* Labmania mockup TO BE — Quotation: daftar, detail (tab Data Peserta), form, link Konfirmasi Kehadiran, PDF cetak.
   Perubahan dari as is: input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/.
   Semua file berbagi lingkup global; urutan muat ada di Labmania.html (core.js pertama, main.js terakhir). */
'use strict';
  // ---------- Quotation (to be: docs-to-be/fr-nfr-quotation.md QTN-FR-01 – QTN-FR-28) ----------
  // Varian paket ikut tampil di samping judul (docs-to-be/fr-nfr.md FR-01; sama dengan mod-program.js).
  const varianTxt = pj => findRec('varian', paketOf(mpOf(pj)).varian).nama;
  // Penjualan Program Final (Public & B2B) → Quotation. Public berkali-kali, B2B satu quotation aktif (usulan).
  // To be: form tidak memilih peserta; diisi Kuota peserta. Saat disimpan, sistem membuat link Konfirmasi Kehadiran
  // khusus quotation itu. Calon peserta mengisi data dirinya lewat link; hasilnya masuk tab Data Peserta.
  // Peserta hanya masuk lewat link, tanpa tambah manual oleh Sales (input/Master - Buat Program/2026-11-09-peserta-hanya-lewat-link/).
  // Peserta quotation tidak diambil dari Master Peserta.
  const QTN_STATUS = { draft: 'Draft', terkirim: 'Terkirim', deal: 'Deal', batal: 'Batal' };
  const PS_STATUS = { menunggu: 'Menunggu', deal: 'Deal', batal: 'Batal', keluar: 'Keluar' };
  const TUJUAN = { company: 'Company', personal: 'Personal' };
  const SUMBER = ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya'];
  const PS_SRC = { link: 'Link', manual: 'Manual', company: 'Daftar PIC' };
  const PPN_RATE = 11;
  const QQTN = q('Q-QTN-01', 'Format nomor LMI/PW/MM/yy-zzz (terjawab). Masih usulan: MM/yy dari Tanggal quotation dan nomor urut di-reset tiap bulan.');
  // Pertanyaan baru dari to be (catatan penafsiran di input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/notes.md).
  const QTB1 = q('Q-TB-01', 'Usulan: harga quotation dihitung dari Kuota peserta sampai Deal; setelah Deal dari jumlah peserta Deal.');
  const QTB2 = q('Q-TB-02', 'Link menerima isian selama quotation Draft atau Terkirim, kuota belum penuh, dan belum melewati Berlaku sampai (terjawab 09-11-2026). Masih terbuka: perlu batas tambahan H-14 sebelum acara?');
  const QTB3 = q('Q-TB-03', 'Terjawab 09-11-2026: B2B tetap punya Confirmation Letter, sama seperti Public (formulir konfirmasi + pernyataan).');
  const QTB4 = q('Q-TB-04', 'Usulan: data peserta milik quotation, tidak disimpan ke Master Peserta. Perlu disalin ke Master Peserta setelah Deal?');
  const QTB5 = q('Q-TB-05', 'Terjawab 10-11-2026: tanda tangan basah diperlukan. Calon peserta mencetak surat konfirmasi dari link, menandatangani, lalu mengunggahnya (wajib). Sales melihat surat yang diunggah.');
  const QTB9 = q('Q-TB-09', 'Usulan: surat konfirmasi diunggah satu berkas PDF, JPG, atau PNG, maksimal 5 MB. Bila surat yang diunggah salah atau tidak terbaca, peserta mengisi ulang lewat link, atau Sales dapat mengganti berkasnya?');
  const QTB8 = q('Q-TB-08', 'Peserta hanya lewat link. Bila link sudah tertutup (kuota penuh, lewat Berlaku sampai, atau Terkirim dengan kuota penuh) dan ada calon peserta tambahan: dibuatkan quotation baru (usulan), atau kuota quotation boleh dinaikkan setelah Terkirim?');
  // Nomor dokumen (input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/): LMI/{kode}/MM/yy-zzz,
  // kode PW = Penawaran, INV = Invoice. MM/yy dari tanggal dokumen; urutan per kode & bulan (usulan: reset tiap bulan).
  const lmiNo = (kode, iso, peek = false) => { const t = iso || todayIso, mm = t.slice(5, 7), yy = t.slice(2, 4), key = `${kode}${yy}${mm}`, n = (codeSeq[key] || 0) + 1; if (!peek) codeSeq[key] = n; return `LMI/${kode}/${mm}/${yy}-${String(n).padStart(3, '0')}`; };
  const qtnNo = (iso, peek = false) => lmiNo('PW', iso, peek);
  // B2B (input/Master - Buat Program/2026-11-10-b2b-paket-25-konfirmasi-company/): hanya all-in; harga paket mencakup s.d. 25 peserta,
  // peserta ke-26 dst + Rp300.000/orang. Nilai disimpan per quotation agar perubahan aturan tidak mengubah quotation lama (usulan).
  const B2B_PAKET = 25, B2B_TAMBAH = 300000;
  const QB2B = q('Q-QTN-21', 'Usulan: batas 25 peserta dan tambahan Rp300.000/orang disimpan per quotation (nilai saat dibuat). Perlu dapat diubah di Master Data? Tambahan ikut dikenai PPN (masuk DPP) dan tidak ikut didiskon (usulan)?');
  const QKCO = q('Q-QTN-22', 'Usulan: template surat konfirmasi B2B (atas nama company + lampiran daftar peserta) belum punya kode dokumen Labmania. Kode dan nomor revisinya? PIC mengirim daftar satu kali; perubahan daftar setelah dikirim lewat Sales (usulan)?');
  const QDISK = q('Q-QTN-06', 'Diskon Rupiah per peserta (usulan, mengikuti labmania.html) atau total per quotation?');
  const QALLIN = q('Q-QTN-15', 'All-in hanya untuk B2B dan boleh diskon (terjawab; mockup: Rupiah total). Masih terbuka: di Penawaran dicetak satu baris paket (usulan)? Invoice B2B mengikuti skema: all-in = 1 invoice, per peserta = 1 invoice per peserta.');
  // Default Berlaku sampai = 31 Desember tahun Tanggal quotation (QTN-FR-04).
  const akhirTahun = iso => `${String(iso).slice(0, 4)}-12-31`;
  const addDays = (iso, n) => { const d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
  const pjOfQtn = k => db.quot.find(x => x.id === k.pj);
  // Peserta quotation (hasil Konfirmasi Kehadiran lewat link) disimpan di db.qps, terpisah dari Master Peserta.
  // Modul hilir (Invoice, Manage Peserta) tetap memanggil pesertaById.
  db.qps = [];
  const pesertaById = id => db.qps.find(x => x.id === id) || db.peserta.find(x => x.id === id);
  const salesOf = k => db.user.find(x => x.id === k.sales);
  const pesertaCo = p => { const c = p && db.company.find(x => x.id === p.company); return c ? c.nama : (p && p.instansi) || '—'; };
  const tujuanTag = t => `<span class="tag tag-${t === 'company' ? 'b2b' : 'public'}">${TUJUAN[t]}</span>`;
  const psBadge = s => `<span class="badge st-ps-${s}">${PS_STATUS[s]}</span>`;
  const qtnBadge = s => `<span class="badge st-${s}">${QTN_STATUS[s]}</span>`;
  const srcTag = p => `<span class="src-tag ${p.src === 'manual' ? 'manual' : ''}">${PS_SRC[p.src] || 'Link'}</span>`;
  // Nilai: jumlah × harga jual Penjualan Program − diskon Rp / peserta, + PPN dari DPP bila dicentang (QTN-FR-08).
  // To be: tanpa rows = Kuota peserta (penawaran); dengan rows = peserta tertentu (mis. peserta Deal untuk invoice).
  // B2B all-in (QTN-FR-20): satu harga untuk seluruh peserta; diskon Rupiah total.
  function qtnNilai(k, rows) {
    const pj = pjOfQtn(k), harga = pj ? Number(pj.hargaJual) || 0 : 0, n = rows ? rows.length : Math.max(Number(k.kuota) || 0, 0);
    const lain = Math.max(Number(k.lain) || 0, 0);
    if (isAllin(k)) {
      // Paket s.d. maks peserta; kelebihan × tarif masuk Sub Total (diskon hanya dari harga paket).
      const paket = Math.max(Number(k.allin) || 0, 0), maks = Number(k.paketMaks) || B2B_PAKET, tarif = k.tambahPs == null ? B2B_TAMBAH : Number(k.tambahPs) || 0;
      const lebih = Math.max(n - maks, 0), tambah = lebih * tarif, sub = paket + tambah;
      const diskTot = Math.min(Math.max(Number(k.diskon) || 0, 0), paket), dpp = sub - diskTot;
      const ppn = k.ppn ? Math.round(dpp * PPN_RATE / 100) : 0;
      return { allin: true, harga, disk: 0, net: n ? Math.round(dpp / n) : 0, n, paket, maks, tarif, lebih, tambah, sub, diskTot, dpp, ppn, lain, total: dpp + ppn + lain };
    }
    const disk = Math.min(Math.max(Number(k.diskon) || 0, 0), harga), sub = n * harga, diskTot = n * disk, dpp = sub - diskTot;
    const ppn = k.ppn ? Math.round(dpp * PPN_RATE / 100) : 0;
    return { harga, disk, net: harga - disk, n, sub, diskTot, dpp, ppn, lain, total: dpp + ppn + lain };
  }
  const isB2bQtn = k => ((pjOfQtn(k) || {}).jenis) === 'b2b';
  const isAllin = k => k.skema === 'allin' && isB2bQtn(k);
  // Personal: nama yang dituju diisi di form (to be), karena peserta belum ada saat quotation dibuat.
  const kepadaOf = k => k.tujuan === 'company'
    ? (db.company.find(c => c.id === k.company) || {}).nama || '—'
    : k.kepada || (pesertaById(k.peserta[0] && k.peserta[0].id) || {}).nama || '—';
  const qtnOfPj = pj => db.qtn.filter(k => k.pj === pj.id);
  const dealRows = k => k.status === 'deal' ? k.peserta.filter(r => r.st === 'deal') : [];
  const dealOf = pj => qtnOfPj(pj).reduce((t, k) => t + dealRows(k).length, 0);
  // Menunggu = kuota quotation Terkirim (kursi yang sedang ditawarkan).
  const pendOf = pj => qtnOfPj(pj).filter(k => k.status === 'terkirim').reduce((t, k) => t + (Number(k.kuota) || 0), 0);
  // QTN-FR-03: Public selalu dapat ditambah quotation; B2B satu quotation — baru boleh lagi bila yang lama Batal (usulan, Q-QTN-08).
  const canAddQtn = pj => pj.status === 'final' && (pj.jenis === 'public' || !qtnOfPj(pj).some(k => k.status !== 'batal'));
  const pjSelesai = pj => !!pj.tglSelesai && pj.tglSelesai < todayIso;
  // Tab Data Peserta memuat semua batch, On going dan Selesai (input/Master - Buat Program/2026-11-09-tab-data-peserta-batch/).
  const qtnTabOk = pj => pj.status === 'final' && (state.qtnTab === 'peserta' || (state.qtnTab === 'done') === pjSelesai(pj));

  // ---------- Link Konfirmasi Kehadiran (to be) ----------
  // Satu link per quotation, dibuat saat quotation disimpan. Di mockup link mengarah ke konfirmasi.html di folder ini;
  // di aplikasi nanti memakai domain aplikasi (belum ditetapkan). Mockup tanpa server: konfigurasi link dan isian
  // calon peserta dioper lewat localStorage browser (linkPublish → konfirmasi.html → linkSync).
  const linkToken = () => Math.random().toString(36).slice(2, 8).toUpperCase();
  const linkUrl = k => k.link ? new URL(`konfirmasi.html?t=${encodeURIComponent(k.link.token)}`, location.href).href : '';
  const LINK_KEY = t => `labmania-tobe:link:${t}`, ISIAN_KEY = t => `labmania-tobe:isian:${t}`;
  const lsGet = key => { try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (e) { return null; } };
  const lsSet = (key, v) => { try { localStorage.setItem(key, JSON.stringify(v)); return true; } catch (e) { return false; } };
  const psAktif = k => k.peserta.filter(r => r.st !== 'batal' && r.st !== 'keluar');
  const kuotaPenuh = k => psAktif(k).length >= (Number(k.kuota) || 0);
  // Link berlaku sampai tanggal Berlaku sampai quotation; mulai hari berikutnya tertutup (input/Master - Buat Program/2026-11-09-link-berlaku-quotation/).
  const linkLewat = k => !!k.berlaku && todayIso > k.berlaku;
  // B2B: link diisi satu kali oleh PIC company; tertutup setelah daftar peserta dikirim (input/Master - Buat Program/2026-11-10-b2b-paket-25-konfirmasi-company/).
  const linkBuka = k => (k.status === 'draft' || k.status === 'terkirim') && !linkLewat(k) && (isB2bQtn(k) ? !k.konfCo : !kuotaPenuh(k));
  const linkStatus0 = k => k.status === 'deal' ? 'Ditutup: quotation sudah Deal' : k.status === 'batal' ? 'Ditutup: quotation Batal'
    : linkLewat(k) ? `Ditutup: masa berlaku quotation berakhir ${fmtTgl(k.berlaku)}`
    : isB2bQtn(k) ? (k.konfCo ? `Ditutup: daftar ${k.peserta.length} peserta sudah dikirim PIC company ${fmtStamp(k.konfCo.tgl)}` : 'Menunggu PIC company mengirim daftar peserta')
    : kuotaPenuh(k) ? `Ditutup: kuota ${k.kuota} peserta sudah terisi` : `Menerima isian · ${psAktif(k).length} dari ${k.kuota} kuota terisi`;

  // ---------- Revisi karena reschedule (QTN-FR-34 – QTN-FR-36) ----------
  // input/Master - Buat Program/2026-11-12-jawaban-reschedule-refund-asisten/: reschedule yang mengubah tanggal menaikkan revisi
  // semua quotation bukan Batal (nomor -rev{n}), membuat link baru, dan meminta peserta Menunggu/Deal konfirmasi ulang.
  const QQTN24 = q('Q-QTN-24', 'Terjawab 12-11-2026: sudah bayar = Refund atau Pindah jadwal; belum bayar = Batal ikut atau Pindah jadwal; invoice belum dibayar dibatalkan bila Batal ikut. Masih terbuka: B2B Refund hanya bila company batal ikut? Peserta yang tidak menjawab sampai H-1 dianggap ikut?');
  const perluUlang = (k, r) => !!k.rev && (r.st === 'menunggu' || r.st === 'deal') && !(r.konf && r.konf.rev === k.rev);
  // Link konfirmasi ulang terbuka sampai sehari sebelum tanggal mulai baru (usulan).
  const ulangBuka = k => !!k.rev && k.status !== 'batal' && !!pjOfQtn(k).tglMulai && todayIso < pjOfQtn(k).tglMulai && k.peserta.some(r => perluUlang(k, r));
  const ulangJawab = k => k.peserta.filter(r => (r.st === 'menunggu' || r.st === 'deal' || r.keluar) && r.konf && r.konf.rev === k.rev).length;
  const ulangTotal = k => k.peserta.filter(r => r.st === 'menunggu' || r.st === 'deal' || (r.keluar && r.keluar.rev === k.rev)).length;
  const linkStatus = k => ulangBuka(k) ? `Konfirmasi ulang jadwal baru: ${ulangJawab(k)} dari ${ulangTotal(k)} peserta sudah menjawab${linkBuka(k) ? ' · pendaftar baru tetap diterima' : ''}` : linkStatus0(k);
  function qtnRevisi(k, ref) {
    const dulu = k.nomor;
    k.nomorDasar = k.nomorDasar || k.nomor; k.rev = (k.rev || 0) + 1; k.nomor = `${k.nomorDasar}-rev${k.rev}`;
    if (k.link) { // link lama ditutup: halaman link menampilkan pesan jadwal berubah
      const lama = lsGet(LINK_KEY(k.link.token)); if (lama) lsSet(LINK_KEY(k.link.token), Object.assign(lama, { diganti: true, buka: false, ulang: null }));
      (k.linkLama = k.linkLama || []).push(k.link.token);
    }
    // Token baru turunan token lama + nomor revisi, agar link data contoh tetap sama setelah halaman dimuat ulang.
    k.link = { token: k.link ? `${k.link.token.replace(/R\d+$/, '')}R${k.rev}` : linkToken(), dibuat: stampNow() };
    k.revInfo = { rev: k.rev, lama: ref.lama, baru: ref.baru, waktu: ref.waktuUbah, alasan: ref.alasan };
    linkPublish(k);
    logAct('Quotation', 'Quotation', 'Revisi', k.nomor, k.id, `Revisi ${dulu} → ${k.nomor} karena reschedule program: ${ref.alasan}. Link Konfirmasi Kehadiran baru dibuat, link lama ditutup.`);
  }
  function revNotice(k) {
    const i = k.revInfo || {}, sisa = k.peserta.filter(r => perluUlang(k, r)).length;
    return `<div class="notice rv-note"><span><b>rev${k.rev}</b> karena reschedule program ${i.waktu ? fmtStamp(i.waktu.replace('T', ' ')) : ''}: ${i.lama ? fmtRange(i.lama.tglMulai, i.lama.tglSelesai) : ''} → <b>${i.baru ? fmtRange(i.baru.tglMulai, i.baru.tglSelesai) : ''}</b>.
      ${ulangJawab(k)} dari ${ulangTotal(k)} peserta sudah konfirmasi ulang${sisa ? `, <b>${sisa} belum</b>` : ''}. Kirim ulang PDF quotation revisi dan <b>link baru</b> ke customer; link lama sudah ditutup.</span><span class="ref">QTN-FR-34 – QTN-FR-36${QQTN24}</span></div>`;
  }
  // Opsi konfirmasi ulang menurut status bayar (input/Master - Buat Program/2026-11-12-pindah-jadwal-batal-invoice/):
  // sudah bayar = Refund atau Pindah jadwal; belum bayar = Batal ikut atau Pindah jadwal.
  // Pindah jadwal = ikut jadwal baru program yang sama (hasil reschedule), bukan pindah ke program lain. Kunci data 'ikut'.
  const KONF_KEP = { ikut: 'Pindah jadwal', batal: 'Batal ikut', refund: 'Refund', tidak: 'Dicoret PIC' };
  const KELUAR_LBL = { batal: 'Batal ikut', refund: 'Refund', lepas: 'Dicoret PIC' };
  // Pembayaran invoice dicatat lewat aksi Catat Pembayaran di Invoicing (lunas penuh). rid kosong = tagihan company (B2B).
  const invBayar = (k, rid) => invOfQtn(k).find(i => i.bayar && (rid == null || i.peserta.includes(rid)));
  const invTerbit = (k, rid) => invOfQtn(k).find(i => rid == null || i.peserta.includes(rid));
  const opsiUlang = bayar => bayar ? ['ikut', 'refund'] : ['ikut', 'batal'];
  const bisaProses = (k, r) => !!k.rev && !r.keluar && r.st !== 'batal' && (perluUlang(k, r) || (!!r.konf && r.konf.rev === k.rev && r.konf.keputusan !== 'ikut'));
  function ulangCell(k, r, p) {
    if (r.keluar) return `<span class="badge st-ps-keluar">${KELUAR_LBL[r.keluar.jenis] || 'Keluar'}</span>${r.keluar.alasan ? `<br><span class="muted">${esc(r.keluar.alasan)}</span>` : ''}${r.keluar.invBatal ? `<br><span class="muted">invoice ${esc(r.keluar.invBatal)} dibatalkan</span>` : ''}`;
    if (r.st === 'batal') return '<span class="muted">—</span>';
    if (!r.konf || r.konf.rev !== k.rev) return '<span class="badge st-ps-menunggu">Belum konfirmasi ulang</span>';
    if (r.konf.keputusan === 'ikut') return `<span class="badge st-ps-deal">Pindah jadwal</span><br><span class="muted">${r.konf.sumber === 'sales' ? 'dicatat Sales' : 'surat baru'} ${fmtStamp(r.konf.tgl)}</span>`;
    return `<span class="badge st-ps-menunggu">${KONF_KEP[r.konf.keputusan]}</span>${r.konf.catatan ? `<br><span class="muted">"${esc(r.konf.catatan)}"</span>` : ''}<br><span class="muted">perlu diproses (menu Aksi)</span>`;
  }
  // Konfigurasi konfirmasi ulang yang dibaca konfirmasi.html; bayar menentukan opsi (Refund atau Batal ikut).
  function ulangCfg(k) {
    if (!k.rev) return null;
    const i = k.revInfo || {}, pj = pjOfQtn(k);
    return { rev: k.rev, buka: ulangBuka(k), deal: k.status === 'deal', lama: i.lama ? rangePanjang(i.lama.tglMulai, i.lama.tglSelesai) : '', baru: rangePanjang(pj.tglMulai, pj.tglSelesai),
      bayarCo: isB2bQtn(k) && !!invBayar(k),
      peserta: k.peserta.filter(r => perluUlang(k, r)).map(r => { const p = pesertaById(r.id) || {}; return { id: r.id, nama: p.nama, data: p.data || {}, bayar: !!invBayar(k, r.id) }; }) };
  }
  // Simpan jawaban konfirmasi ulang dari link. Pindah jadwal: surat baru menggantikan surat aktif, surat lama disimpan sebagai riwayat.
  // Keputusan disesuaikan dengan status bayar saat diterima (refund hanya bila sudah bayar, batal hanya bila belum).
  const kepSah = (kep, bayar) => kep === 'ikut' || kep === 'tidak' ? kep : bayar ? 'refund' : 'batal';
  function konfUlangSimpan(k, x) {
    const simpan = (r, keputusan, catatan, surat) => {
      r.konf = { rev: k.rev, keputusan, catatan: catatan || '', tgl: x.tgl, sumber: 'link' };
      const p = pesertaById(r.id);
      if (surat && p && !isB2bQtn(k)) { if (p.surat) (p.suratLama = p.suratLama || []).push(Object.assign({ rev: k.rev - 1 }, p.surat)); p.surat = surat; }
    };
    if (x.tipe === 'ulang') {
      const r = k.peserta.find(y => y.id === x.ps); if (!r || !perluUlang(k, r)) return 0;
      const kep = kepSah(x.keputusan, !!invBayar(k, r.id));
      simpan(r, kep, x.catatan, kep === 'ikut' ? x.surat : null);
      logAct('Quotation', 'Data Peserta', 'Konfirmasi Ulang', k.nomor, k.id, `Konfirmasi ulang lewat link: ${(pesertaById(r.id) || {}).nama} · ${KONF_KEP[kep]}`);
      return 1;
    }
    const aktif = k.peserta.filter(r => perluUlang(k, r));
    const kepCo = kepSah(x.keputusan, !!invBayar(k));
    aktif.forEach(r => simpan(r, kepCo === 'ikut' ? (x.ikut.includes(r.id) ? 'ikut' : 'tidak') : kepCo, x.catatan, null));
    if (kepCo === 'ikut' && x.surat && k.konfCo) { (k.konfCo.suratLama = k.konfCo.suratLama || []).push(Object.assign({ rev: k.rev - 1 }, k.konfCo.surat)); Object.assign(k.konfCo, { surat: x.surat, tgl: x.tgl, rev: k.rev }); }
    logAct('Quotation', 'Data Peserta', 'Konfirmasi Ulang', k.nomor, k.id, `Konfirmasi ulang PIC company lewat link: ${KONF_KEP[kepCo]}${kepCo === 'ikut' ? ` (${x.ikut.length} dari ${aktif.length} peserta tetap ikut)` : ''}`);
    return aktif.length;
  }
  // Proses konfirmasi ulang (QTN-FR-36), dari menu Aksi di Data Peserta. Opsi sama dengan link (menurut status bayar).
  // Batal ikut dengan invoice terbit tetapi belum dibayar: invoice dibatalkan (jawaban BA 12-11-2026: "batal invoice aja").
  // B2B: peserta yang dicoret PIC keluar tanpa Refund (harga paket tetap); Batal ikut / Refund dari PIC berlaku untuk seluruh company.
  function qpsKeluar(p) {
    const k = db.qtn.find(x => x.id === p.qtn), r = k.peserta.find(x => x.id === p.id), pj = pjOfQtn(k), b2b = isB2bQtn(k);
    const kep = r.konf && r.konf.rev === k.rev ? r.konf.keputusan : '';
    const lepas = b2b && kep === 'tidak', company = b2b && !lepas && (kep === 'batal' || kep === 'refund');
    const target = company ? k.peserta.filter(x => x.st === 'deal' || x.st === 'menunggu') : [r];
    const bayar = k.status === 'deal' ? invBayar(k, company ? null : r.id) : null, terbit = k.status === 'deal' && !bayar ? invTerbit(k, company ? null : r.id) : null;
    const nominal = !bayar ? 0 : company ? bayar.total : (v => v.dpp + v.ppn)(qtnNilai(k, [r]));
    const opsi = opsiUlang(!!bayar), def = lepas ? 'batal' : opsi.includes(kep) ? kep : kep && kep !== 'ikut' ? opsi[1] : 'ikut';
    const HINT = { ikut: `tetap ikut program ini di jadwal baru ${fmtRange(pj.tglMulai, pj.tglSelesai)}`, batal: terbit ? `tidak ikut; invoice ${terbit.nomor} (belum dibayar) dibatalkan` : 'tidak ikut; belum ada tagihan',
      refund: `tidak ikut; dana dikembalikan sesuai S&K (invoice ${bayar ? bayar.nomor : ''} sudah dibayar)` };
    const lainDiInv = terbit ? terbit.peserta.filter(id => !target.some(x => x.id === id)).length : 0;
    const tagihInfo = bayar ? `Invoice <span class="code">${esc(bayar.nomor)}</span> sudah dibayar ${tglPanjang(bayar.bayar.tanggal)}: opsi <b>Refund</b> atau <b>Pindah jadwal</b>.`
      : terbit ? `Invoice <span class="code">${esc(terbit.nomor)}</span> sudah terbit tetapi belum dibayar: opsi <b>Batal ikut</b> atau <b>Pindah jadwal</b>. Bila Batal ikut, invoice itu <b>dibatalkan</b>${lainDiInv ? ` dan ${lainDiInv} peserta lain di invoice itu kembali belum ditagih (ditagih ulang lewat tab Uninvoiced)` : ''}.`
      : 'Belum ada invoice: opsi <b>Batal ikut</b> atau <b>Pindah jadwal</b>.';
    openModal({
      title: `Proses konfirmasi ulang: ${esc(company ? kepadaOf(k) : p.nama)}`, wide: true,
      body: `<div class="summary">${sumRow('Quotation', `<span class="code">${esc(k.nomor)}</span> · ${esc(kepadaOf(k))}`)}${sumRow('Jadwal baru', `<span class="code">${esc(pj.nomor)}</span> · ${fmtRange(pj.tglMulai, pj.tglSelesai)}`)}
        ${sumRow('Jawaban lewat link', kep ? esc(KONF_KEP[kep]) + (r.konf.catatan ? ` · "${esc(r.konf.catatan)}"` : '') : '<span class="muted">Belum menjawab</span>')}${company && target.length > 1 ? sumRow('Berlaku untuk', `${target.length} peserta company`) : ''}</div>
        <div class="notice gap-t"><span>${lepas ? 'Peserta dicoret PIC company: keluar tanpa Refund karena harga paket all-in tidak berubah.' : tagihInfo}</span><span class="ref">QTN-FR-36${QQTN24}</span></div>
        <div class="grid2 gap-t">
          ${lepas ? '' : field('Keputusan' + req, opsi.map(v => `<label class="chk"><input type="radio" name="kep" value="${v}" ${v === def ? 'checked' : ''}> <span><b>${KONF_KEP[v]}</b> <span class="muted">${esc(HINT[v])}</span></span></label>`).join(''), '', 'span2')}
          <div class="field span2" id="kp-alasan" ${def === 'ikut' && !lepas ? 'hidden' : ''}><label for="kp-al">Alasan${req}</label><textarea class="input" id="kp-al" name="alasan" rows="2" maxlength="500" placeholder="mis. Bentrok dengan jadwal audit">${esc(kep && kep !== 'ikut' ? r.konf.catatan : '')}</textarea></div>
        </div>`,
      saveLabel: 'Simpan',
      onMount: form => form.addEventListener('change', e => { if (e.target.name === 'kep') $('#kp-alasan', form).hidden = e.target.value === 'ikut'; }),
      onSave: f => {
        const jenis = lepas ? 'lepas' : (f.kep && f.kep.value) || 'ikut', alasan = f.alasan.value.trim();
        if (jenis === 'ikut') { // pindah jadwal = tetap ikut program yang sama di jadwal rev; tanpa alasan
          r.konf = { rev: k.rev, keputusan: 'ikut', catatan: '', tgl: stampNow(), sumber: 'sales' };
          logAct('Quotation', 'Data Peserta', 'Konfirmasi Ulang', k.nomor, k.id, `${p.nama}: pindah jadwal ke ${fmtRange(pj.tglMulai, pj.tglSelesai)} (dicatat Sales)`);
          toast(`${p.nama} pindah ke jadwal baru`); return;
        }
        if (!alasan) return invalid(f.alasan, 'Isi alasan');
        const info = { jenis, alasan, rev: k.rev, tgl: isoNow().slice(0, 16), oleh: meName() };
        target.forEach(x => { x.st = x.st === 'deal' ? 'keluar' : 'batal'; x.keluar = Object.assign({}, info); });
        let rf = null;
        if (jenis === 'refund' && bayar) {
          rf = rfCreate({ k, ps: company ? null : r.id, inv: bayar, alasan, nominal });
          target.forEach(x => { x.keluar.refund = rf.nomor; });
        }
        if (jenis === 'batal' && terbit) { // invoice belum dibayar dibatalkan, tidak dihapus
          ivcBatal(terbit, `Peserta batal ikut setelah reschedule ${pj.nomor}: ${alasan}`);
          target.forEach(x => { x.keluar.invBatal = terbit.nomor; });
        }
        logAct('Quotation', 'Data Peserta', 'Keluar', k.nomor, k.id, `${company ? `Company ${kepadaOf(k)} (${target.length} peserta)` : p.nama}: ${KELUAR_LBL[jenis]}. ${alasan}${rf ? `. Refund ${rf.nomor} dibuat` : ''}${jenis === 'batal' && terbit ? `. Invoice ${terbit.nomor} dibatalkan` : ''}`);
        toast(`${company ? kepadaOf(k) : p.nama}: ${KELUAR_LBL[jenis]}${rf ? `, Refund ${rf.nomor} menunggu Finance` : ''}${jenis === 'batal' && terbit ? `, invoice ${terbit.nomor} dibatalkan` : ''}`);
      }
    });
  }
  // B2B memakai template formulir dan Confirmation Letter seperti Public (input/Master - Buat Program/2026-11-09-b2b-tetap-confirmation-letter/).
  // Isian cadangan bila quotation lama belum punya template formulir.
  const B2B_ISIAN = ['nama', 'jabatan', 'wa', 'email'];
  // B2B: kolom daftar peserta yang diminta ke PIC; Nama Lengkap selalu ada, lainnya opsional per quotation.
  const B2B_KOLOM = [['jabatan', 'Jabatan'], ['email', 'Email'], ['wa', 'Nomor WhatsApp'], ['nik', 'NIK']];
  const kolomOf = k => B2B_KOLOM.filter(([x]) => (k.kolomB2b || []).includes(x));
  const isianOf = k => isB2bQtn(k) ? [['nama', 'Nama Lengkap*'], ...kolomOf(k)] : k.kf ? KF_FIELDS.filter(([x]) => k.kf.isian.includes(x) || x === 'nama').concat((k.kf.extra || []).map(l => [`x:${l}`, l]))
    : KF_FIELDS.filter(([x]) => B2B_ISIAN.includes(x));
  const ISIAN_TYPE = { email: 'email', wa: 'tel', hp: 'tel', habis: 'date' };
  const pad2q = n => String(n).padStart(2, '0');
  const stampNow = (d = new Date()) => `${d.getFullYear()}-${pad2q(d.getMonth() + 1)}-${pad2q(d.getDate())} ${pad2q(d.getHours())}:${pad2q(d.getMinutes())}`;
  const fmtStamp = s => { if (!s) return '—'; const [d, t] = s.split(' '); const [y, m, dd] = d.split('-'); return `${dd}-${m}-${y}${t ? ` ${t.slice(0, 5)}` : ''}`; };
  // Simpan satu isian link sebagai peserta quotation.
  function qpsAdd(k, data, src, tgl = stampNow(), surat = null) {
    const co = k.tujuan === 'company' ? db.company.find(c => c.id === k.company) : null;
    if (co && !data.instansi) data.instansi = co.nama;
    const p = { id: uid(), qtn: k.id, src, tgl, kode: '', company: '', nama: data.nama, instansi: data.instansi || '', telp: data.wa || data.hp || '', email: data.email || '', data, surat };
    db.qps.push(p);
    k.peserta.push({ id: p.id, st: 'menunggu' });
    return p;
  }
  // Data contoh: peserta yang dulu dipilih dari Master Peserta diubah menjadi isian Konfirmasi Kehadiran (dipanggil main.js
  // setelah semua data contoh dimuat, termasuk invoice contoh yang merujuk ID peserta).
  function qpsSeed() {
    db.qtn.forEach((k, ki) => {
      const map = {};
      k.peserta.forEach((r, i) => {
        if (db.qps.some(x => x.id === r.id)) return;
        const m = db.peserta.find(x => x.id === r.id) || {}, co = db.company.find(c => c.id === m.company);
        const data = { nama: m.nama || '', instansi: co ? co.nama : m.instansi || '', alamat: co ? `${co.alamat}, ${co.kota}` : [m.kota, m.provinsi].filter(Boolean).join(', '), wa: m.telp || '', email: m.email || '' };
        const p = { id: uid(), qtn: k.id, src: 'link', tgl: `${addDays(k.tanggal, 1 + (i % 3))} ${pad2q(9 + i % 8)}:${pad2q((i * 7) % 60)}`, kode: '', company: '', nama: data.nama, instansi: data.instansi, telp: data.wa, email: data.email, data };
        p.surat = { nama: `Surat_Konfirmasi_${(data.nama || 'peserta').replace(/[^\w]+/g, '_')}.${i % 2 ? 'jpg' : 'pdf'}`, ukuran: 180000 + i * 23000, tipe: i % 2 ? 'image/jpeg' : 'application/pdf', contoh: true };
        if (isB2bQtn(k)) { p.src = 'company'; p.tgl = `${addDays(k.tanggal, 2)} 10:20`; delete p.surat; }
        db.qps.push(p); map[r.id] = p.id; r.id = p.id;
      });
      db.inv.filter(i => i.qtn === k.id).forEach(i => { i.peserta = i.peserta.map(id => map[id] || id); });
      if (k.kuota === undefined) k.kuota = Math.max(k.peserta.length, isB2bQtn(k) ? B2B_MIN : 1) + (k.status === 'terkirim' || k.status === 'draft' ? 1 : 0);
      if (!k.link) k.link = { token: `Q${pad2q(ki + 1)}${k.nomor.slice(-3)}`, dibuat: k.tanggal };
      if (isB2bQtn(k) && k.peserta.length && !k.konfCo) {
        const co = db.company.find(c => c.id === k.company) || {};
        k.kolomB2b = k.kolomB2b || ['email'];
        k.konfCo = { nama: co.nama || '', alamat: k.alamat || '', pic: co.picNama || '', jab: co.picJabatan || '', hp: co.picTelp || '', email: co.picEmail || '', tgl: `${addDays(k.tanggal, 2)} 10:20`,
          surat: { nama: `Konfirmasi_${(co.nama || 'company').replace(/[^\w]+/g, '_')}.pdf`, ukuran: 412000, tipe: 'application/pdf', contoh: true } };
      }
      if (k.tujuan === 'personal' && !k.kepada) k.kepada = (db.qps.find(x => x.id === (k.peserta[0] || {}).id) || {}).nama || '';
    });
  }

  // Data contoh: peserta dihubungkan ke Company (dipakai data contoh quotation, lalu diubah qpsSeed).
  db.qtn = [];
  {
    const [co1, co2] = db.company;
    db.peserta[0].company = co1.id; db.peserta[0].instansi = co1.nama;
    [['Contoh Peserta D', 'Laki-laki', co1, 'ISO 17025', 'Jawa Barat', 'Kota Bekasi', '081355566677', 'peserta.d@contoh.id'],
      ['Contoh Peserta E', 'Perempuan', co1, 'Analisis Kimia', 'Jawa Barat', 'Kota Bekasi', '081377788899', 'peserta.e@contoh.id'],
      ['Contoh Peserta F', 'Laki-laki', co2, 'Pangan', 'Jawa Timur', 'Kota Surabaya', '082144455566', 'peserta.f@contoh.id'],
      ['Contoh Peserta G', 'Perempuan', co2, 'Pangan', 'Jawa Timur', 'Kota Surabaya', '082166677788', 'peserta.g@contoh.id']
    ].concat(['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'].map((x, i) => [`Contoh Peserta ${x}`, i % 2 ? 'Perempuan' : 'Laki-laki', co1, 'ISO 17025', 'Jawa Barat', 'Kota Bekasi', `08131000${String(i).padStart(4, '0')}`, `peserta.${x.toLowerCase()}@contoh.id`]))
    .forEach(([nama, gender, co, bidang, provinsi, kota, telp, email]) => db.peserta.push({
      id: uid(), kode: nextCode('PS'), nama, gender, company: co.id, instansi: co.nama, bidang: idOf('jenisBidang', bidang), provinsi, kota, telp, email
    }));
    const ps = nama => db.peserta.find(p => p.nama === nama).id;
    const pjB2b = db.quot.find(x => x.jenis === 'b2b' && x.status === 'final');
    const pjPub = db.quot.find(x => x.jenis === 'public' && x.status === 'final');
    const sales = db.user[1].id;
    const seed = o => { const k = Object.assign({ id: uid(), nomor: qtnNo(o.tanggal), sales, sumber: 'Sales', ppn: true, diskon: 0, lain: 0, up: '', alamat: '' }, o); k.ket = k.ket || ketDefault(pjOfQtn(k).jenis); k.kf = k.kf || kfNew(kfDefaultFor(pjOfQtn(k))); db.qtn.push(k); };
    seed({ pj: pjB2b.id, tanggal: '2026-10-26', berlaku: '2026-12-31', tujuan: 'company', company: co1.id, up: co1.picNama, alamat: `${co1.alamat}, ${co1.kota}`, status: 'deal', kuota: 12,
      skema: 'allin', allin: 24000000, diskon: 1000000,
      peserta: ['A', 'D', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'E'].map((x, i) => ({ id: ps(`Contoh Peserta ${x}`), st: i < 10 ? 'deal' : 'batal' })),
      dokumen: [{ nama: 'PO-118-PO-X-2026.pdf', ukuran: 245760, oleh: 'Contoh User Dua', tgl: '2026-11-02 10:15' }, { nama: 'Surat-Penunjukan-Peserta.docx', ukuran: 51200, oleh: 'Contoh User Dua', tgl: '2026-11-02 10:15' }], dokKet: 'PO No. 118/PO/X/2026' });
    seed({ pj: pjPub.id, tanggal: '2026-10-27', berlaku: '2026-12-31', tujuan: 'company', company: co2.id, up: co2.picNama, alamat: `${co2.alamat}, ${co2.kota}`, status: 'terkirim', sumber: 'Referensi / repeat order', diskon: 100000, kuota: 5,
      peserta: [{ id: ps('Contoh Peserta F'), st: 'menunggu' }, { id: ps('Contoh Peserta G'), st: 'menunggu' }] });
    seed({ pj: pjPub.id, tanggal: '2026-10-27', berlaku: '2026-12-31', tujuan: 'personal', company: '', alamat: 'Kota Bekasi, Jawa Barat', status: 'deal', sumber: 'Website', kuota: 1,
      peserta: [{ id: ps('Contoh Peserta B'), st: 'deal' }],
      dokumen: [{ nama: 'Bukti-transfer-Peserta-B.jpg', ukuran: 389120, oleh: 'Contoh User Dua', tgl: '2026-10-30 14:02' }], dokKet: 'Bukti transfer pembayaran' });
    seed({ pj: pjPub.id, tanggal: '2026-10-28', berlaku: '2026-12-31', tujuan: 'personal', company: '', kepada: 'Contoh Peserta C', alamat: 'Jakarta Timur, DKI Jakarta', status: 'draft', sumber: 'Media sosial', ppn: false, diskon: 50000, kuota: 3,
      peserta: [] });
    seed({ pj: pjPub.id, tanggal: '2026-10-28', berlaku: '2026-12-31', tujuan: 'personal', company: '', alamat: 'Kota Bekasi, Jawa Barat', status: 'batal', sumber: 'Sales', kuota: 1,
      peserta: [{ id: ps('Contoh Peserta E'), st: 'batal' }] });
  }

  // ---------- Quotation: daftar per Penjualan Program & detail (QTN-FR-01 – QTN-FR-03) ----------
  const bepTxt = pj => {
    const bep = calc(pj).bepPeserta, deal = dealOf(pj);
    return bep === null ? '<span class="neg">BEP tidak tercapai</span>' : deal >= bep ? `<span class="pos">Lewat BEP (${bep})</span>` : `BEP ${bep}, kurang ${bep - deal}`;
  };
  function qtnBar(pj, big) {
    const t = Math.max(nPeserta(pj), 1), deal = dealOf(pj), pend = pendOf(pj), bep = calc(pj).bepPeserta;
    const scale = big ? Math.max(t, deal + pend, bep || 0) : t;
    const pct = v => `${Math.min(100, v / scale * 100).toFixed(1)}%`;
    return `<div class="qbar ${big ? 'big' : ''}"><div class="qb-fill"><span class="qb-deal" style="width:${pct(deal)}"></span><span class="qb-pend" style="width:${pct(pend)}"></span></div>
      ${big ? `<i class="qb-t" style="left:${pct(t)}" title="Target ${t}"></i>` : ''}${bep !== null ? `<i class="qb-bep" style="left:${pct(bep)}" title="BEP ${bep}"></i>` : ''}</div>`;
  }

  function qtnPipe(scope) {
    const agg = s => { const l = scope.filter(k => k.status === s); return { n: l.length, p: l.reduce((t, k) => t + (Number(k.kuota) || 0), 0) }; };
    const A = { draft: agg('draft'), terkirim: agg('terkirim'), deal: agg('deal'), batal: agg('batal') };
    const dealP = scope.reduce((t, k) => t + dealRows(k).length, 0);
    const dealV = scope.filter(k => k.status === 'deal').reduce((t, k) => t + qtnNilai(k, dealRows(k)).total, 0);
    const conv = A.deal.n + A.batal.n ? Math.round(A.deal.n / (A.deal.n + A.batal.n) * 100) : null;
    const card = (s, cls, sub) => `<div class="kpi dash-link ${cls} ${state.status === s ? 'on' : ''}" data-act="qtn-filter" data-s="${s}" role="button" tabindex="0" title="Saring menurut status">
      <div class="kpi-l">${QTN_STATUS[s]}</div><div class="kpi-v">${A[s].n} <small>quotation</small></div><div class="kpi-s">${sub}</div></div>`;
    return `<div class="qtn-flow">
      ${card('draft', '', `kuota ${A.draft.p} peserta, belum dikirim`)}<span class="qf-arrow">›</span>
      ${card('terkirim', 'kwarn', `kuota ${A.terkirim.p} peserta, menunggu deal`)}<span class="qf-arrow">›</span>
      ${card('deal', 'good', `${dealP} peserta deal · ${rp(dealV)}${conv !== null ? `<br>tingkat deal ${conv}%` : ''}`)}<span class="qf-gap"></span>
      ${card('batal', 'lost', `kuota ${A.batal.p} peserta`)}</div>`;
  }

  function viewQtn() {
    linkPublishAll(); // status/kuota terbaru untuk konfirmasi.html (mis. setelah Deal atau Batal dari menu gear)
    const pj = state.qtnPj && db.quot.find(x => x.id === state.qtnPj);
    if (!pj) state.qtnPj = null;
    if (pj) {
      const add = canAddQtn(pj);
      const info = `${jenisTag(pj.jenis)} <b>${esc(paketOf(mpOf(pj)).judul)}</b> · ${esc(varianTxt(pj))} · ${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')} · ${rp(pj.hargaJual)} / peserta${pj.jenis === 'b2b' ? ` · ${esc((db.company.find(x => x.id === pj.company) || {}).nama || '—')}` : ''}
        <br>Peserta deal <b>${dealOf(pj)}</b> / ${nPeserta(pj)}${pendOf(pj) ? ` · kuota ${pendOf(pj)} menunggu` : ''} · ${bepTxt(pj)}`;
      const createBtn = add
        ? `<button class="btn btn-accent" data-act="qtn-from-pj" data-id="${pj.id}">+ Create Quotation</button>`
        : `<button class="btn btn-accent" disabled title="Penjualan B2B sudah punya quotation aktif">+ Create Quotation</button>${q('Q-QTN-08', 'B2B boleh lebih dari satu quotation seperti Public? Usulan: satu quotation aktif; quotation baru setelah yang lama Batal.')}`;
      return `${pageHead(`Quotation — ${esc(pj.nomor)}`, info, 'QTN-FR-02',
        `<button class="btn btn-ghost" data-act="qtn-back">← Semua Penjualan Program</button>${createBtn}`)}
        <div class="card">${toolbar('Cari nomor, kepada, peserta, atau sales…', false,
          selectFilter('fjenis', 'Filter tujuan', state.jenis, [['all', 'Semua tujuan'], ['company', 'Company'], ['personal', 'Personal']])
          + selectFilter('fstatus', 'Filter status', state.status, [['all', 'Semua status'], ...Object.keys(QTN_STATUS).map(s => [s, QTN_STATUS[s]])]))}
          <div id="list">${listQtn()}</div></div>`;
    }
    const bp = state.qtnTab === 'peserta' && state.bpPj && db.quot.find(x => x.id === state.bpPj);
    if (!bp) state.bpPj = null;
    if (bp) {
      const all = bpRows(bp), n = st => all.filter(x => x.st === st).length;
      const info = `${jenisTag(bp.jenis)} <b>${esc(paketOf(mpOf(bp)).judul)}</b> · ${esc(varianTxt(bp))} · ${fmtRange(bp.tglMulai, bp.tglSelesai)} · ${esc(bp.lokasi || '—')}${pjSelesai(bp) ? ' · Selesai' : ''}
        <br><b>${all.length}</b> peserta dari ${qtnOfPj(bp).length} quotation · ${n('deal')} deal · ${n('menunggu')} menunggu · ${n('batal')} batal`;
      return `${pageHead(`Data Peserta — ${esc(bp.nomor)}`, info, 'QTN-FR-29',
        `<button class="btn btn-ghost" data-act="bp-back">← Semua batch</button><button class="btn btn-accent" data-act="bp-pdf" data-id="${bp.id}" ${all.some(x => x.st !== 'batal') ? '' : 'disabled'}>Export PDF</button>`)}
        <div class="card">${toolbar('Cari peserta, instansi, kontak, atau nomor quotation…', false,
          selectFilter('fstatus', 'Filter status peserta', state.status, [['all', 'Semua status peserta'], ...['deal', 'menunggu', 'batal'].map(x => [x, PS_STATUS[x]])]))}
          <div id="list">${listQtn()}</div></div>`;
    }
    const inTab = db.quot.filter(qtnTabOk);
    const tabCnt = t => t === 'peserta' ? db.quot.filter(x => x.status === 'final').reduce((n, x) => n + bpRows(x).length, 0)
      : db.quot.filter(x => x.status === 'final' && (t === 'done') === pjSelesai(x)).length;
    const isPs = state.qtnTab === 'peserta';
    const tabs = [['ongoing', 'On going'], ['done', 'Selesai'], ['peserta', 'Data Peserta']].map(([t, l]) =>
      `<button class="tab ${state.qtnTab === t ? 'active' : ''}" data-act="qtn-tab" data-tab="${t}">${l}<span class="nav-count">${tabCnt(t)}</span></button>`).join('');
    return `${pageHead('Daftar Quotation', 'Penjualan Program Final, Public maupun B2B, dibuatkan quotation sebagai persiapan invoice. Satu baris per Penjualan Program; Public dapat dibuatkan quotation berkali-kali. Klik <b>Lihat detail</b> untuk quotation-nya.', 'QTN-FR-01 – QTN-FR-29',
      '<button class="btn btn-accent" data-act="qtn-new">+ Buat Quotation</button>')}
      <div class="tobe-box"><h3>Yang berubah di Quotation <span class="tobe">To be</span></h3><ol>
        <li>Form quotation tidak lagi memilih peserta dari Master Peserta; diganti <b>Kuota peserta</b>.${QTB1}</li>
        <li>Saat quotation disimpan, sistem membuat <b>link Konfirmasi Kehadiran</b> khusus quotation itu. Tiap calon peserta mengisi data dirinya sendiri.${QTB2}</li>
        <li>Hasil isian masuk ke tab <b>Data Peserta</b> di detail quotation, lengkap dengan Preview dan Download. Peserta <b>hanya</b> masuk lewat link; tidak ada tambah manual, supaya semua tercatat di sistem.${QTB4}${QTB8}</li>
        <li>Tab <b>Data Peserta</b> di halaman ini: satu baris per batch Penjualan Program; <b>Lihat detail</b> menampilkan peserta semua quotation batch itu, dengan <b>Confirmation Letter</b> per peserta dan <b>Export PDF</b> (daftar + Confirmation Letter).${QBP}</li>
        <li><b>Confirmation Letter</b> menjadi dokumen output quotation, dibentuk dari isian Konfirmasi Kehadiran.${QTB5}</li></ol></div>
      ${isPs ? '' : qtnPipe(db.qtn.filter(k => inTab.some(x => x.id === k.pj)))}
      <div class="card"><div class="tabs">${tabs}<span class="tabs-note">${q('Q-QTN-09', 'Kapan Penjualan Program dianggap Selesai? Usulan: tanggal selesai acara sudah lewat.')}</span></div>
        ${isPs ? toolbar('Cari program, nomor, atau nama peserta…', false, ymFilter(db.quot.filter(qtnTabOk).map(pj => pj.tglMulai)))
          : toolbar('Cari program, nomor quotation, kepada, atau peserta…', false, ymFilter(db.quot.filter(qtnTabOk).map(pj => pj.tglMulai)))}
        <div id="list">${listQtn()}</div></div>`;
  }

  // ---------- Detail Quotation: halaman penuh dengan tab Detail | Data Peserta (to be) ----------
  const qtnAksi = k => {
    const a = [];
    // Tanpa aksi Confirmation Letter dan Lihat Sales Order: Confirmation Letter lewat tab Data Peserta, SO lewat menu Sales Order
    // (input/Master - Buat Program/2026-11-09-aksi-quotation-tanpa-so-cl/).
    if (k.status === 'draft') a.push(['qtn-edit', 'Edit'], ['qtn-print', 'Kirim dan Cetak Quotation'], ['qtn-cancel', 'Batalkan', true]);
    if (k.status === 'terkirim') a.push(['qtn-deal', 'Deal'], ['qtn-print', 'Kirim dan Cetak Quotation'], ['qtn-cancel', 'Batalkan', true]);
    if (k.status === 'deal') a.push(['qtn-print', 'Cetak Quotation']);
    if (k.status === 'batal') a.push(['qtn-print', 'Lihat PDF']);
    return a;
  };
  const psStOf = (k, r) => k.status === 'deal' ? r.st : k.status === 'batal' || r.st === 'batal' ? 'batal' : 'menunggu';
  const psRingkas = k => {
    const n = psAktif(k).length, names = k.peserta.slice(0, 2).map(r => (pesertaById(r.id) || {}).nama).filter(Boolean);
    return `<b>${k.status === 'deal' ? dealRows(k).length : n}</b> <span class="muted">${k.status === 'deal' ? 'peserta deal' : `dari kuota ${k.kuota} terisi`}</span>
      ${names.length ? `<br><span class="muted">${esc(names.join(', '))}${k.peserta.length > 2 ? `, +${k.peserta.length - 2} lainnya` : ''}</span>` : '<br><span class="muted">Belum ada isian</span>'}
      <br><button class="btn btn-ghost sm" data-act="qtn-view-ps" data-id="${k.id}">Data Peserta</button>`;
  };
  function qtnView(k, tab = 'detail') { state.qtnView = k.id; state.view = 'qtnDetail'; state.qvF = 'all'; state.qvTab = tab; render(); window.scrollTo(0, 0); }

  // Bar kuota: terisi / kuota (dipakai panel status, kartu link, dan tab Data Peserta).
  function kuotaBar(k) {
    const kuota = Number(k.kuota) || 0, n = psAktif(k).length, pct = kuota ? Math.min(100, n / kuota * 100) : 0, sisa = Math.max(kuota - n, 0);
    return `<div class="kq"><div class="kq-top"><span><b>${n}</b> dari ${kuota} kuota terisi</span><span class="muted">${n > kuota ? `${n - kuota} melebihi kuota` : sisa ? `${sisa} kursi tersisa` : 'kuota penuh'}</span></div>
      <div class="kq-bar" role="progressbar" aria-label="Kuota terisi" aria-valuemin="0" aria-valuemax="${kuota}" aria-valuenow="${n}"><span style="width:${pct.toFixed(1)}%"></span></div></div>`;
  }
  const linkBadge = k => linkBuka(k) || ulangBuka(k) ? '<span class="badge st-terkirim">Link terbuka</span>' : '<span class="badge st-draft">Link tertutup</span>';
  function linkSection(k) {
    const b2b = isB2bQtn(k);
    linkPublish(k);
    return `<p class="lk-lead">Calon peserta mengisi data dirinya sendiri lewat link ini. Link hanya berlaku untuk quotation ${esc(k.nomor)}.</p>
      <div class="lk-copy"><label class="sr-only" for="link-url-${k.id}">Link Konfirmasi Kehadiran</label>
        <input class="input lk-url" id="link-url-${k.id}" value="${esc(linkUrl(k))}" readonly>
        <button type="button" class="btn btn-primary" data-act="qtn-link-copy" data-id="${k.id}">Salin link</button>
        <a class="btn btn-outline" href="${esc(linkUrl(k))}" target="_blank" rel="noopener">Buka<span class="sr-only"> link di tab baru</span></a></div>
      <p class="lk-state">${linkBadge(k)} <span class="muted">${esc(linkStatus(k))}</span></p>
      <p class="lk-h">Isian yang diminta</p>
      <div class="lk-chips">${isianOf(k).map(([key, l]) => `<span class="lk-chip ${key === 'nama' ? 'on' : ''}">${esc(l.replace('*', ''))}${key === 'nama' ? ' (wajib)' : ''}</span>`).join('')}</div>
      <p class="lk-foot muted">Dibuat ${fmtStamp(k.link.dibuat)} · Berlaku sampai ${k.berlaku ? fmtTgl(k.berlaku) : '—'} (mengikuti Berlaku sampai quotation) · Pertanyaan terbuka: ${QTB2}</p>
      <details class="lk-note"><summary>Catatan mockup</summary><p>Link membuka <b>konfirmasi.html</b> di folder mockup to be. Isian yang dikirim di tab itu masuk ke Data Peserta saat Anda kembali ke tab ini (browser yang sama). Di aplikasi, link memakai domain aplikasi.</p></details>`;
  }

  function dataPesertaTab(k) {
    const invOfPs = id => invOfQtn(k).find(i => i.peserta.includes(id));
    const cnt = st => k.peserta.filter(r => psStOf(k, r) === st).length;
    const F = state.qvF || 'all';
    const list = k.peserta.filter(r => F === 'all' || psStOf(k, r) === F).filter(r => { const p = pesertaById(r.id) || {}; return !state.qvQ || [p.nama, pesertaCo(p), p.email, p.telp].some(x => String(x || '').toLowerCase().includes(state.qvQ.toLowerCase())); });
    const chips = [['all', 'Semua', k.peserta.length], ...['deal', 'menunggu', 'batal', 'keluar'].map(st => [st, PS_STATUS[st], cnt(st)]).filter(x => x[2])]
      .map(([st, l, n]) => `<button type="button" class="tab ${F === st ? 'active' : ''}" data-act="qv-filter" data-s="${st}">${l}<span class="nav-count">${n}</span></button>`).join('');
    const doc = 'surat konfirmasi';
    const head = `<div class="dp-head">
        <p class="dp-sum"><b>${k.peserta.length}</b> peserta, ${isB2bQtn(k) ? 'dikirim PIC company lewat link' : 'semua mengisi lewat link Konfirmasi Kehadiran'}</p>
        <div class="dp-btns">
          <button type="button" class="btn btn-outline" data-act="qps-xls" data-id="${k.id}" ${k.peserta.length ? '' : 'disabled'}>Unduh Excel</button>
          <button type="button" class="btn btn-outline" data-act="qtn-cl" data-id="${k.id}" ${psAktif(k).length ? '' : 'disabled'}>Confirmation Letter (semua)</button>
        </div></div>`;
    const foot = `<p class="lk-foot muted">Pertanyaan terbuka: ${QTB4}${QTB8}${QTB9}${isB2bQtn(k) ? QKCO : ''}</p>`;
    // B2B: satu konfirmasi company (PIC + surat) untuk seluruh daftar peserta.
    const co = k.konfCo, coBox = isB2bQtn(k) ? `<div class="card pad dp-co"><p class="ivc-sec-h">Konfirmasi company <span class="tobe">To be</span></p>${co
      ? `<p><b>${esc(co.nama)}</b> · PIC ${esc(co.pic)}${co.jab ? ` (${esc(co.jab)})` : ''} · ${esc(co.hp || '—')} · ${esc(co.email || '')}<br><span class="muted">${suratMeta(k, pesertaById(k.peserta[0].id))}</span></p>
        <div class="dp-btns"><button type="button" class="btn btn-outline sm" data-act="qps-prev" data-id="${k.peserta[0].id}">Lihat surat konfirmasi</button><button type="button" class="btn btn-ghost sm" data-act="qps-pdf" data-id="${k.peserta[0].id}">Unduh</button></div>`
      : '<p class="muted">PIC company belum mengirim daftar peserta. Bagikan link ke PIC company.</p>'}</div>` : '';
    if (!k.peserta.length) {
      return `<div class="dp-empty card pad">
        <h3>${isB2bQtn(k) ? 'Belum ada daftar peserta dari company' : 'Belum ada calon peserta yang mengisi'}</h3>
        <p class="muted">${isB2bQtn(k) ? 'Bagikan link Konfirmasi Kehadiran ke PIC company. PIC mengisi daftar peserta sekaligus.' : 'Bagikan link Konfirmasi Kehadiran ke calon peserta. Peserta hanya dapat masuk lewat link.'}</p>
        <div class="dp-empty-act"><button type="button" class="btn btn-primary" data-act="qtn-link-copy" data-id="${k.id}">Salin link</button></div></div>${foot}`;
    }
    const cols = ['Peserta', 'Instansi', 'Kontak', 'Masuk lewat', 'Status', ...(k.rev ? [`Konfirmasi ulang rev${k.rev}`] : []), ...(k.status === 'deal' ? ['Invoice'] : []), 'Aksi'];
    return `${head}${coBox}
      <div class="card"><div class="dp-tools"><div class="tabs qv-tabs">${chips}</div>
        <div class="search">${SEARCH_ICON}<input type="search" class="input" id="qv-q" placeholder="Cari nama, instansi, email, WhatsApp…" value="${esc(state.qvQ || '')}" aria-label="Cari peserta"></div></div>
      <div class="table-wrap"><table class="table dp-table"><thead><tr><th class="num">No</th>${cols.map(c => `<th${c === 'Aksi' ? ' class="act"' : ''}>${c}</th>`).join('')}</tr></thead><tbody>
      ${list.length ? list.map((r, n) => { const p = pesertaById(r.id) || {}, st = psStOf(k, r), inv = st === 'deal' ? invOfPs(r.id) : null;
        return `<tr class="${st === 'batal' || st === 'keluar' ? 'is-off' : ''}"><td class="num">${n + 1}</td>
          <td data-l="Peserta"><b>${esc(p.nama || '—')}</b>${p.data && p.data.jabatan ? `<br><span class="muted">${esc(p.data.jabatan)}</span>` : ''}</td>
          <td data-l="Instansi">${esc(pesertaCo(p))}</td>
          <td data-l="Kontak">${esc(p.telp || '—')}<br><span class="muted">${esc(p.email || '')}</span></td>
          <td data-l="Masuk lewat">${srcTag(p)} <span class="muted">${fmtStamp(p.tgl)}</span></td>
          <td data-l="Status">${psBadge(st)}</td>
          ${k.rev ? `<td data-l="Konfirmasi ulang">${ulangCell(k, r, p)}</td>` : ''}
          ${k.status === 'deal' ? `<td data-l="Invoice">${inv ? `<button class="btn btn-ghost sm" data-act="ivc-view" data-id="${inv.id}">${esc(inv.nomor)}</button>` : st === 'deal' ? '<span class="badge st-ivc-uninvoiced">Uninvoiced</span>' : r.keluar && r.keluar.refund ? `Refund <span class="code">${esc(r.keluar.refund)}</span>` : '—'}</td>` : ''}
          <td class="act">${gearMenu({ id: p.id }, [['qps-prev', `Lihat ${doc}`], ['qps-pdf', `Unduh ${doc}`], ...(bisaProses(k, r) ? [['qps-keluar', 'Proses tidak ikut']] : [])])}</td></tr>`; }).join('')
        : `<tr><td colspan="${cols.length + 1}" class="empty">Tidak ada peserta yang cocok. Ubah filter status atau kata pencarian.</td></tr>`}</tbody></table></div></div>
      ${foot}`;
  }

  function viewQtnDetail() {
    const k = db.qtn.find(x => x.id === state.qtnView);
    if (!k) { state.view = 'qtn'; return viewQtn(); }
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), s = salesOf(k) || {};
    const v = qtnNilai(k), vd = k.status === 'deal' ? qtnNilai(k, dealRows(k)) : null;
    const sec = (n, t, hint, b) => `<section class="card pad ivc-sec"><p class="ivc-sec-h"><span class="ivc-no">${n}</span>${t}${hint ? ` <span class="muted">${hint}</span>` : ''}</p>${b}</section>`;
    const kv = rows => `<div class="qv-kv">${rows.map(([l, x]) => `<div><span>${l}</span><b>${x}</b></div>`).join('')}</div>`;
    // Panel status: posisi di alur, langkah berikutnya, satu tombol utama; aksi lain sekunder, Batalkan terpisah.
    const aksi = qtnAksi(k);
    const utamaKey = { draft: 'qtn-print', terkirim: 'qtn-deal', deal: 'qtn-print', batal: 'qtn-print' }[k.status];
    const utama = aksi.find(([a]) => a === utamaKey) || aksi[0];
    const lain = aksi.filter(x => x !== utama && !x[2]), bahaya = aksi.filter(x => x[2]);
    const LANGKAH = {
      draft: 'Periksa isi quotation, lalu kirim ke customer. Link Konfirmasi Kehadiran sudah bisa dibagikan.',
      terkirim: 'Tunggu calon peserta mengisi link. Setelah peserta pasti ikut, tandai Deal.',
      deal: ivcStatus(k) !== 'invoiced' ? `Peserta sudah Deal dan Sales Order ${esc(soOfQtn(k) ? soNomor(soOfQtn(k)) : '')} sudah dibuat. Lanjutkan di menu Sales Order: cetak SO, lalu tandai siap dibuatkan invoice. Confirmation Letter ada di tab Data Peserta.` : 'Semua peserta Deal sudah ditagih.',
      batal: 'Quotation dibatalkan dan link Konfirmasi Kehadiran ditutup.'
    };
    const alur = ['draft', 'terkirim', 'deal'].map((s, i, arr) => { const pos = arr.indexOf(k.status), cls = k.status === 'batal' ? '' : i < pos ? 'done' : i === pos ? 'on' : '';
      return `<li class="${cls}"${i === pos ? ' aria-current="step"' : ''}>${QTN_STATUS[s]}</li>`; }).join('');
    const btn = ([a, l], cls) => `<button type="button" class="btn ${cls}" data-act="${a}" data-id="${k.id}">${l}</button>`;
    const panel = `<section class="card pad qv-panel">
      <div class="qv-panel-top"><ol class="qv-alur" aria-label="Alur quotation">${alur}</ol>${k.status === 'batal' ? qtnBadge('batal') : ''}${linkBadge(k)}</div>
      <p class="qv-next"><span class="muted">Langkah berikutnya:</span> ${LANGKAH[k.status]}</p>
      <div class="qv-panel-row"><div class="qv-btns">${utama ? btn(utama, 'btn-primary') : ''}${lain.map(x => btn(x, 'btn-outline')).join('')}${bahaya.map(x => btn(x, 'btn-ghost danger-t')).join('')}</div>
        ${k.status === 'batal' ? '' : `<div class="qv-kq">${kuotaBar(k)}</div>`}</div></section>`;
    const tab = state.qvTab === 'peserta' ? 'peserta' : 'detail';
    const tabs = `<div class="qv-tabbar" role="tablist" aria-label="Isi quotation">
      <button type="button" class="tab ${tab === 'detail' ? 'active' : ''}" role="tab" aria-selected="${tab === 'detail'}" data-act="qv-tab" data-tab="detail">Detail Quotation</button>
      <button type="button" class="tab ${tab === 'peserta' ? 'active' : ''}" role="tab" aria-selected="${tab === 'peserta'}" data-act="qv-tab" data-tab="peserta">Data Peserta<span class="nav-count">${k.peserta.length}</span><span class="tobe">To be</span></button></div>`;
    const harga = `<div class="totals">${v.allin
      ? `${b2bHargaRows(v)}<div class="tr"><span>Diskon <span class="muted">Rupiah total, dari harga paket</span></span><span>− ${rp(v.diskTot)}</span></div>`
      : `<div class="tr"><span>Subtotal <span class="muted">kuota ${v.n} peserta × ${rp(v.harga)}</span></span><span>${rp(v.sub)}</span></div><div class="tr"><span>Diskon <span class="muted">${v.n} × ${rp(v.disk)}</span></span><span>− ${rp(v.diskTot)}</span></div>`}
      <div class="tr"><span>Setelah diskon</span><span>${rp(v.dpp)}</span></div><div class="tr"><span>PPN ${k.ppn ? `${PPN_RATE}%` : '<span class="muted">(tidak dikenakan)</span>'}</span><span>${rp(v.ppn)}</span></div>
      <div class="tr"><span>Biaya lain-lain</span><span>${rp(v.lain)}</span></div><div class="tr grand"><span><b>Grand total quotation</b></span><span><b>${rp(v.total)}</b></span></div>
      ${vd ? `<div class="tr"><span><b>Nilai Deal</b> <span class="muted">${vd.n} peserta deal</span></span><span><b>${rp(vd.total)}</b></span></div>` : ''}</div>`;
    const invSec = k.status === 'deal' ? sec(5, 'Invoice', `${ivcCaraOf(k) === 'peserta' ? 'per peserta' : 'satu invoice untuk quotation ini'}`, invOfQtn(k).length
      ? `<div class="table-wrap"><table class="table"><thead><tr><th>No. Invoice</th><th>Tanggal</th><th>Peserta</th><th>Perlakuan PPN</th><th class="money">Total Tagihan</th><th class="act">Aksi</th></tr></thead><tbody>
        ${invOfQtn(k).map(i => `<tr><td><span class="code">${esc(i.nomor)}</span></td><td>${tglPanjang(i.tanggal)}</td><td>${i.cara === 'peserta' ? esc((pesertaById(i.peserta[0]) || {}).nama || '—') : `${i.peserta.length} peserta`}</td><td>${PERLAKUAN[i.perlakuan]}</td><td class="money">${rp(i.total)}</td>
          <td class="act">${gearMenu(i, [['ivc-view', 'Lihat Invoice'], ['ivc-print', 'Print Invoice']])}</td></tr>`).join('')}</tbody></table></div>
        <p class="hint">${ivcStatus(k) === 'invoiced' ? 'Semua peserta Deal sudah ditagih.' : `${ivcSisaPs(k).length} peserta Deal belum ditagih.`}</p>`
      : `<p class="hint">Belum ada invoice. Invoice dibuat dari Sales Order <b>${esc(soOfQtn(k) ? soNomor(soOfQtn(k)) : '—')}</b> setelah ditandai siap dibuatkan invoice (menu Sales Order).</p>`) : '';
    const body = tab === 'peserta' ? dataPesertaTab(k)
      : `<div class="qtn-grid">${sec(1, 'Data Quotation', '', kv([['Nomor', `<span class="code">${esc(k.nomor)}</span>`], ['Tanggal', tglPanjang(k.tanggal)], ['Berlaku sampai', tglPanjang(k.berlaku)], ['Sales', esc(s.nama || '—')], ['Sumber informasi', esc(k.sumber || '—')], ['Kuota peserta', `${k.kuota} peserta`], ...(isB2bQtn(k) ? [['Skema harga', isAllin(k) ? 'All-in 1 company' : 'Per peserta']] : [])]))}
        ${sec(2, 'Ditujukan Kepada', '', kv([['Tujuan', tujuanTag(k.tujuan)], ['Kepada', esc(kepadaOf(k))], ['u.p.', esc(k.tujuan === 'company' ? k.up || '—' : kepadaOf(k))], ['Alamat', esc(k.alamat || '—')], ...(k.tujuan === 'company' ? [['Perlakuan PPN', PERLAKUAN[ivcPerlakuan(k)]]] : [])]))}</div>
        ${sec(3, 'Link Konfirmasi Kehadiran <span class="tobe">To be</span>', `<button type="button" class="btn btn-ghost sm" data-act="qv-tab" data-tab="peserta">Lihat Data Peserta (${k.peserta.length})</button>`, linkSection(k))}
        ${sec(4, 'Harga', `dihitung dari kuota${k.status === 'deal' ? '; Nilai Deal dari peserta Deal' : ''}`, harga)}
        ${invSec}
        ${k.status === 'deal' ? sec(6, 'Dokumen Deal', 'diunggah saat Deal; juga tampil di Sales Order', dokList(k)) : ''}`;
    return `<div class="pf-formbar"><button type="button" class="btn btn-ghost sm" data-act="qv-back">← Kembali</button><h2>Quotation <span class="code">${esc(k.nomor)}</span></h2>${qtnBadge(k.status)}<span class="ref">QTN-FR-22</span></div>
      <div class="notice ivc-ctx"><span>No. Program <span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)} · ${esc(pk.judul)} · ${rangePanjang(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')}</span></div>
      ${panel}${k.rev ? revNotice(k) : ''}${tabs}${body}`;
  }

  function qtnGear(k) {
    return gearMenu(k, [['qtn-view', 'Lihat Detail'], ['qtn-view-ps', 'Data Peserta'], ...qtnAksi(k)]);
  }

  // ---------- Tab Data Peserta: peserta semua quotation, dikelompokkan per batch Penjualan Program ----------
  const QBP = q('Q-QTN-19', 'Tab Data Peserta per batch: peserta quotation Batal ikut tampil dengan status Batal dan dapat disaring, tetapi tidak ikut Export PDF (usulan)? Tab Data Peserta di detail quotation tetap ada untuk tambah manual (usulan)?');
  const bpRows = pj => qtnOfPj(pj).flatMap(k => k.peserta.map(r => ({ k, r, p: pesertaById(r.id) || { data: {} }, st: psStOf(k, r) })));
  const bpMatch = (pj, x) => (state.status === 'all' || x.st === state.status)
    && matches([x.p.nama, pesertaCo(x.p), x.p.email, x.p.telp, x.p.data && x.p.data.jabatan, x.k.nomor, kepadaOf(x.k), pj.nomor, paketOf(mpOf(pj)).judul]);
  // Daftar batch: satu baris per Penjualan Program, seperti tab On going.
  function listBatchPeserta() {
    const rows = db.quot.filter(qtnTabOk).slice().reverse().filter(pj => ymOk(pj.tglMulai))
      .filter(pj => matches([pj.nomor, paketOf(mpOf(pj)).judul, pj.lokasi, ...bpRows(pj).flatMap(x => [x.p.nama, x.k.nomor])]));
    const cnt = (pj, st) => bpRows(pj).filter(x => x.st === st).length;
    return listBlock(rows, [
      NO_COL,
      { label: 'Penjualan Program', cls: 'title', render: pj => `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}<br>${esc(paketOf(mpOf(pj)).judul)}<br><span class="muted">${esc(varianTxt(pj))} · ${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')}</span>` },
      { label: 'Peserta', cls: 'money', render: pj => { const n = bpRows(pj).length; return `<b>${n}</b> peserta${n ? `<br><span class="muted">${cnt(pj, 'deal')} deal · ${cnt(pj, 'menunggu')} menunggu · ${cnt(pj, 'batal')} batal</span>` : '<br><span class="muted">belum ada</span>'}`; } },
      { label: 'Quotation', render: pj => `<b>${qtnOfPj(pj).length}</b> quotation` },
      { label: 'Batch', render: pj => pjSelesai(pj) ? '<span class="badge st-draft">Selesai</span>' : '<span class="badge st-terkirim">On going</span>' },
      { label: 'Action', cls: 'act', render: pj => gearMenu(pj, [['bp-open', 'Lihat detail']]) }
    ]);
  }
  // Detail batch: peserta dari semua quotation Penjualan Program itu.
  function listBatchDetail(pj) {
    const rows = bpRows(pj).filter(x => bpMatch(pj, x));
    if (!bpRows(pj).length) return '<p class="muted pad">Belum ada peserta. Peserta masuk lewat link Konfirmasi Kehadiran quotation.</p>';
    return `<div class="table-wrap"><table class="table dp-table"><thead><tr><th class="num">No</th><th>Peserta</th><th>Instansi</th><th>Kontak</th><th>Quotation</th><th>Masuk lewat</th><th>Status</th><th class="act">Action</th></tr></thead><tbody>
      ${rows.length ? rows.map(({ k, p, st }, i) => `<tr class="${st === 'batal' ? 'is-off' : ''}"><td class="num">${i + 1}</td>
        <td data-l="Peserta"><b>${esc(p.nama || '—')}</b>${p.data && p.data.jabatan ? `<br><span class="muted">${esc(p.data.jabatan)}</span>` : ''}</td>
        <td data-l="Instansi">${esc(pesertaCo(p))}</td>
        <td data-l="Kontak">${esc(p.telp || '—')}<br><span class="muted">${esc(p.email || '')}</span></td>
        <td data-l="Quotation"><button class="btn btn-ghost sm code" data-act="qtn-view-ps" data-id="${k.id}">${esc(k.nomor)}</button><br><span class="muted">${esc(kepadaOf(k))}</span></td>
        <td data-l="Masuk lewat">${srcTag(p)} <span class="muted">${fmtStamp(p.tgl)}</span></td>
        <td data-l="Status">${psBadge(st)}</td>
        <td class="act">${gearMenu({ id: p.id }, [['qps-prev', 'Lihat Confirmation Letter'], ['qps-pdf', 'Unduh surat konfirmasi']])}</td></tr>`).join('')
        : '<tr><td colspan="8" class="empty">Tidak ada peserta yang cocok. Ubah filter status atau kata pencarian.</td></tr>'}</tbody></table></div>`;
  }
  // Export PDF satu batch (input/Master - Buat Program/2026-11-09-batch-cl-export-pdf/): halaman 1 daftar peserta,
  // lalu Confirmation Letter tiap peserta (Public dan B2B) = surat bertanda tangan yang diunggah peserta (input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/).
  // Peserta Batal tidak ikut (usulan, Q-QTN-19).
  function bpPdf(pj) {
    const ps = bpRows(pj).filter(x => x.st !== 'batal'), pk = paketOf(mpOf(pj)), tot = ps.length + 1;
    const rekap = pdfPage(`<h3 class="pdf-title">Daftar Peserta</h3>
      <p class="pdf-mut">${esc(pj.nomor)} · ${esc(pk.judul)} · ${esc(varianTxt(pj))} · ${esc(rangePanjang(pj.tglMulai, pj.tglSelesai))} · ${esc(pj.lokasi || '')}</p>
      <table class="pdf-tbl bp-pdf-tbl"><thead><tr><th>No</th><th>Nama Lengkap</th><th>Instansi</th><th>Kontak</th><th>Quotation</th><th>Status</th></tr></thead><tbody>
      ${ps.map(({ k, p, st }, i) => `<tr><td>${i + 1}</td><td>${esc(p.nama || '')}</td><td>${esc(pesertaCo(p))}</td><td>${esc(p.telp || '')}<br>${esc(p.email || '')}</td><td>${esc(k.nomor)}</td><td>${PS_STATUS[st]}</td></tr>`).join('')}</tbody></table>
      <p class="pdf-mut">${ps.length} peserta. Halaman berikutnya: surat konfirmasi bertanda tangan yang diunggah tiap peserta, urut sesuai daftar.</p>`, pdfFoot('[kode dokumen menyusul]', `1 dari ${tot}`, '—'));
    openModal({
      title: `Data Peserta: ${pj.nomor}`, wide: 'x',
      body: pdfWrap(rekap + (seen => ps.map(({ k, p }) => { if (isB2bQtn(k)) { if (seen.has(k.id)) return ''; seen.add(k.id); } return suratPage(k, p); }).join(''))(new Set()), `Daftar peserta + surat konfirmasi yang diunggah ${ps.length} peserta (tanpa peserta Batal)${QBP}`),
      onMount: pdfMount,
      saveLabel: 'Export PDF',
      onSave: () => { window.print(); logAct('Quotation', 'Data Peserta', 'Export', pj.nomor, pj.id, `Export PDF Data Peserta batch ${pj.nomor} (${ps.length} peserta)`); }
    });
  }

  function listQtn() {
    if (!state.qtnPj && state.qtnTab === 'peserta') { const bp = state.bpPj && db.quot.find(x => x.id === state.bpPj); return bp ? listBatchDetail(bp) : listBatchPeserta(); }
    if (!state.qtnPj) {
      const rows = db.quot.filter(qtnTabOk).slice().reverse()
        .filter(pj => state.status === 'all' || qtnOfPj(pj).some(k => k.status === state.status))
        .filter(pj => ymOk(pj.tglMulai))
        .filter(pj => matches([pj.nomor, paketOf(mpOf(pj)).judul, pj.lokasi, ...qtnOfPj(pj).flatMap(k => [k.nomor, kepadaOf(k), k.up, ...k.peserta.map(r => (pesertaById(r.id) || {}).nama)])]));
      return listBlock(rows, [
        NO_COL,
        { label: 'Penjualan Program', cls: 'title', render: pj => `<span class="code">${esc(pj.nomor)}</span> ${jenisTag(pj.jenis)}<br>${esc(paketOf(mpOf(pj)).judul)}<br><span class="muted">${esc(varianTxt(pj))} · ${fmtRange(pj.tglMulai, pj.tglSelesai)} · ${esc(pj.lokasi || '—')}</span>` },
        { label: 'Peserta deal', cls: 'money', render: pj => `<b>${dealOf(pj)}</b> / ${nPeserta(pj)}${pendOf(pj) ? `<br><span class="muted">kuota ${pendOf(pj)} menunggu</span>` : ''}` },
        { label: 'Progres', render: pj => `${qtnBar(pj)}<span class="muted">${bepTxt(pj)}</span>` },
        { label: 'Quotation', render: pj => { const l = qtnOfPj(pj);
          return `<b>${l.length}</b> quotation<br><span class="muted">${Object.keys(QTN_STATUS).map(s => { const n = l.filter(k => k.status === s).length; return n ? `${n} ${QTN_STATUS[s].toLowerCase()}` : ''; }).filter(Boolean).join(', ') || 'belum ada'}</span>`; } },
        { label: 'Action', cls: 'act', render: pj => gearMenu(pj, [['qtn-open', 'Lihat detail'], ...(canAddQtn(pj) ? [['qtn-from-pj', '+ Buat Quotation']] : [])]) }
      ]);
    }
    const rows = db.qtn.filter(k => k.pj === state.qtnPj).slice().reverse()
      .filter(k => (state.jenis === 'all' || k.tujuan === state.jenis) && (state.status === 'all' || k.status === state.status) &&
        matches([k.nomor, kepadaOf(k), k.up, (salesOf(k) || {}).nama, ...k.peserta.map(r => (pesertaById(r.id) || {}).nama)]));
    return listBlock(rows, [
      NO_COL,
      { label: 'Quotation' + QQTN, cls: 'nowrap', render: k => `<button class="btn btn-ghost sm code" data-act="qtn-view" data-id="${k.id}">${esc(k.nomor)}</button><br><span class="muted">${fmtTgl(k.tanggal)}${k.berlaku ? ` s.d. ${fmtTgl(k.berlaku)}` : ''}<br>Sales: ${esc((salesOf(k) || {}).nama || '—')}${k.sumber ? `, ${esc(k.sumber)}` : ''}</span>` },
      { label: 'Kepada', render: k => `${tujuanTag(k.tujuan)} ${esc(kepadaOf(k))}${k.up ? `<br><span class="muted">u.p. ${esc(k.up)}</span>` : ''}` },
      { label: 'Peserta <span class="tobe">To be</span>', render: psRingkas },
      { label: 'Nilai', cls: 'money', render: k => { const v = qtnNilai(k); return `${rp(v.total)}<br><span class="muted">kuota ${v.n} · ${v.allin ? `All-in ${rp(v.paket)}${v.tambah ? ` + ${v.lebih} × ${rp(v.tarif)}` : ''}${v.diskTot ? ` − diskon ${rp(v.diskTot)}` : ''}` : `${rp(v.net)} / peserta${v.disk ? ` (diskon ${rp(v.disk)})` : ''}`}${k.ppn ? ` + PPN ${PPN_RATE}%` : ''}</span>`; } },
      { label: 'Status', cls: 'nowrap', render: k => qtnBadge(k.status) + (k.status === 'deal' ? `<br><span class="muted">${dealRows(k).length} dari ${k.peserta.length} peserta deal</span>` : `<br><span class="muted">Link ${linkBuka(k) ? 'terbuka' : 'tertutup'}</span>`) },
      { label: 'Action', cls: 'act', render: qtnGear }
    ]);
  }

  // ---------- Quotation: form (QTN-FR-04 – QTN-FR-08; to be: kuota menggantikan pilihan peserta) ----------
  // Baris harga paket B2B + tambahan peserta di atas batas paket.
  const b2bHargaRows = v => `<div class="tr"><span>Harga all-in <span class="muted">paket s.d. ${v.maks} peserta</span></span><span>${rp(v.paket)}</span></div>
    <div class="tr"><span>Tambahan peserta <span class="muted">${v.lebih ? `${v.lebih} peserta di atas ${v.maks} × ${rp(v.tarif)}` : `tidak ada (${v.n} peserta, s.d. ${v.maks})`}</span></span><span>${rp(v.tambah)}</span></div>`;
  function qtnTotals(d) {
    const pj = db.quot.find(x => x.id === d.pj), v = qtnNilai({ pj: d.pj, ppn: d.ppn, diskon: d.diskon, lain: d.lain, skema: d.skema, allin: d.allin, kuota: d.kuota, paketMaks: d.paketMaks, tambahPs: d.tambahPs });
    const bepH = pj ? calc(pj).bepHarga : null;
    if (v.allin) {
      const low = bepH !== null && v.n && v.net < bepH;
      return `${b2bHargaRows(v)}
      <div class="tr"><span>Diskon <span class="muted">Rupiah total, dari harga paket</span></span><span>− ${rp(v.diskTot)}</span></div>
      <div class="tr"><span>Setelah diskon</span><span>${rp(v.dpp)}</span></div>
      <div class="tr"><label class="chk"><input type="checkbox" name="ppn" ${d.ppn ? 'checked' : ''}> PPN ${PPN_RATE}%</label><span>${rp(v.ppn)}</span></div>
      <div class="tr"><span>Biaya lain-lain</span><span>${rp(v.lain)}</span></div>
      <div class="tr grand"><span><b>Grand total</b></span><span><b>${rp(v.total)}</b></span></div>
      <div class="tr"><span class="muted">Setara harga / peserta <span class="muted">(harga Penjualan Program ${pj ? rp(v.harga) : '—'})</span></span><span class="${low ? 'neg' : 'muted'}">${v.n ? rp(v.net) : '—'}${low ? ` (di bawah BEP harga ${rp(bepH)})` : ''}</span></div>`;
    }
    return `<div class="tr"><span>Harga / peserta <span class="muted">dari Penjualan Program, tidak dapat diubah</span></span><span>${pj ? rp(v.harga) : '—'}</span></div>
      <div class="tr"><span>Subtotal <span class="muted">kuota ${v.n} peserta × ${rp(v.harga)}</span></span><span>${rp(v.sub)}</span></div>
      <div class="tr"><span>Diskon <span class="muted">${v.n} × ${rp(v.disk)}</span></span><span>− ${rp(v.diskTot)}</span></div>
      <div class="tr"><span>Setelah diskon</span><span>${rp(v.dpp)}</span></div>
      <div class="tr"><label class="chk"><input type="checkbox" name="ppn" ${d.ppn ? 'checked' : ''}> PPN ${PPN_RATE}%</label><span>${rp(v.ppn)}</span></div>
      <div class="tr"><span>Biaya lain-lain</span><span>${rp(v.lain)}</span></div>
      <div class="tr grand"><span><b>Grand total</b></span><span><b>${rp(v.total)}</b></span></div>
      <div class="tr"><span class="muted">Harga / peserta setelah diskon</span><span class="${bepH !== null && v.disk && v.net < bepH ? 'neg' : 'muted'}">${rp(v.net)}${bepH !== null && v.disk && v.net < bepH ? ` (di bawah BEP harga ${rp(bepH)})` : ''}</span></div>`;
  }

  // Kuota yang sudah ditawarkan di quotation lain (selain Batal) untuk Penjualan Program yang sama.
  const kuotaLain = (pj, kecuali) => db.qtn.filter(k => k.pj === pj && k.id !== kecuali && k.status !== 'batal').reduce((t, k) => t + (Number(k.kuota) || 0), 0);

  function qtnStep1(d) {
    const E = d.err || {};
    const pj = db.quot.find(x => x.id === d.pj), b2b = pj && pj.jenis === 'b2b';
    const pjOpts = db.quot.filter(x => x.status === 'final' || x.id === d.pj).map(x => { const ok = canAddQtn(x) || x.id === d.pj;
      return `<option value="${x.id}" ${x.id === d.pj ? 'selected' : ''} ${ok ? '' : 'disabled'}>${esc(x.nomor)} · ${esc(paketOf(mpOf(x)).judul)} · ${esc(varianTxt(x))} · ${JENIS[x.jenis]} · ${fmtRange(x.tglMulai, x.tglSelesai)}${ok ? '' : ' (sudah ada quotation)'}</option>`; }).join('');
    const salesOpts = db.user.filter(u => u.aktif || u.id === d.sales).map(u => `<option value="${u.id}" ${u.id === d.sales ? 'selected' : ''}>${esc(u.nama)}</option>`).join('');
    const coOpts = db.company.filter(c => c.aktif || c.id === d.company).map(c => `<option value="${c.id}" ${c.id === d.company ? 'selected' : ''}>${esc(c.nama)}</option>`).join('');
    const pjInfo = pj ? `${jenisTag(pj.jenis)} ${esc(pj.lokasi || '—')} · harga ${rp(pj.hargaJual)} / peserta · target ${nPeserta(pj)} peserta` : 'Hanya Penjualan Program berstatus Final.';

    const top = `<p class="qtn-h">Data Quotation</p>
      <div class="grid4 qtn-top">
        ${field('Penjualan Program' + req, `<select class="input" name="pj"><option value="">— Pilih Penjualan Program Final —</option>${pjOpts}</select><span class="hint">${pjInfo}</span>`, E.pj, 'span3')}
        ${field('Nomor Quotation' + QQTN, `<input class="input" value="${esc(d.nomor || qtnNo(d.tanggal, true))}" readonly><span class="hint">Otomatis oleh sistem saat Simpan</span>`)}
        ${field('Tanggal quotation' + req, `<input class="input" type="date" name="tanggal" value="${esc(d.tanggal)}">`, E.tanggal)}
        ${field('Berlaku sampai', `<input class="input" type="date" name="berlaku" value="${esc(d.berlaku)}"><span class="hint">Default 31 Desember tahun quotation</span>`, E.berlaku)}
        ${field('Sales / marketing' + req, `<select class="input" name="sales"><option value="">— Pilih —</option>${salesOpts}</select><span class="hint">Dari Master User aktif</span>`, E.sales)}
        ${field('Sumber informasi', `<select class="input" name="sumber"><option value="">—</option>${opts(SUMBER, d.sumber)}</select>`)}
      </div>`;

    const left = `<p class="qtn-h">Ditujukan kepada</p>
      ${field('Quotation ke' + req, `<div class="static seg">
          <label><input type="radio" name="tujuan" value="company" ${d.tujuan === 'company' ? 'checked' : ''}> Company</label>
          <label class="${b2b ? 'is-dis' : ''}"><input type="radio" name="tujuan" value="personal" ${d.tujuan === 'personal' ? 'checked' : ''} ${b2b ? 'disabled' : ''}> Personal</label></div>
        <span class="hint">${b2b ? 'Penjualan B2B: quotation ke Company penjualan tersebut.' : 'Company = ditujukan ke perusahaan/instansi · Personal = ditujukan ke perorangan.'}</span>`, E.tujuan)}
      ${d.tujuan === 'company' ? `<div class="grid2">
        ${field('Company' + req, `<select class="input" name="company" ${b2b ? 'disabled' : ''}><option value="">— Pilih Company —</option>${coOpts}</select>${b2b ? '<span class="hint">Mengikuti Company Penjualan Program B2B</span>' : '<span class="hint">Dari Master Company aktif</span>'}`, E.company, 'span2')}
        ${field('u.p.', `<input class="input" name="up" value="${esc(d.up)}" maxlength="100" placeholder="Nama yang dituju">`, '', 'span2')}
      </div>` : ''}
      ${d.tujuan === 'personal' ? field('Kepada' + req + ' <span class="tobe">To be</span>', `<input class="input" name="kepada" value="${esc(d.kepada || '')}" maxlength="100" placeholder="Nama orang yang dituju"><span class="hint">Peserta belum ada saat quotation dibuat, jadi nama yang dituju diisi di sini.</span>`, E.kepada) : ''}
      ${d.tujuan ? field('Alamat', `<textarea class="input" name="alamat" rows="2">${esc(d.alamat)}</textarea><span class="hint">${d.tujuan === 'company' ? 'Terisi otomatis dari Company; dapat diubah untuk surat ini.' : 'Isi alamat yang dituju.'}</span>`) : ''}`;

    const target = pj ? nPeserta(pj) : 0, lain = pj ? kuotaLain(d.pj, d.id) : 0, sudah = d.isEdit ? d.terisi || 0 : 0;
    const right = `<p class="qtn-h">Kuota peserta <span class="tobe">To be</span></p>
      ${field('Kuota peserta' + req + QTB1, `<div class="kq-input"><input class="input" type="number" name="kuota" min="${b2b ? B2B_MIN : 1}" step="1" inputmode="numeric" value="${esc(d.kuota === '' || d.kuota === undefined ? '' : d.kuota)}" placeholder="${b2b ? B2B_MIN : 1}" aria-describedby="kq-help"><span>peserta</span></div>
        <span class="hint" id="kq-help">${b2b ? `Penjualan B2B minimal ${B2B_MIN} peserta. Harga paket mencakup s.d. ${B2B_PAKET} peserta; peserta ke-${B2B_PAKET + 1} dan seterusnya + ${rp(B2B_TAMBAH)}/orang.${QB2B} ` : ''}${sudah ? `Sudah terisi ${sudah}; kuota tidak boleh kurang dari itu.` : 'Jumlah kursi yang ditawarkan di quotation ini.'}</span>`, E.kuota)}
      ${pj ? `<dl class="kq-stat"><div><dt>Target program</dt><dd>${target}</dd></div><div><dt>Di quotation lain</dt><dd>${lain}</dd></div><div><dt>Sisa target</dt><dd>${Math.max(target - lain, 0)}</dd></div></dl>` : ''}
      <p class="kq-info">Peserta tidak dipilih di sini. Setelah disimpan, sistem membuat <b>link Konfirmasi Kehadiran</b>. Calon peserta mengisi data dirinya lewat link; hasilnya masuk tab <b>Data Peserta</b>. Tidak ada tambah peserta manual.</p>`;
    // B2B hanya all-in (input/Master - Buat Program/2026-11-10-b2b-paket-25-konfirmasi-company/); pilihan skema per peserta dihapus.
    const allin = b2b;
    const skemaHtml = b2b ? field('Skema harga' + QALLIN, `<div class="static">All-in 1 company</div>
        <span class="hint">Satu harga paket untuk s.d. ${B2B_PAKET} peserta; diskon Rupiah total.</span>`) : '';
    const harga = allin ? `<div>${skemaHtml}
        ${field('Harga all-in (Rp)' + req, `${moneyIn('name="allin" placeholder="0"', d.allin || '')}<span class="hint">Harga paket s.d. ${B2B_PAKET} peserta. Terisi awal harga / peserta × kuota (maks. ${B2B_PAKET}); ubah sesuai kesepakatan.</span>`, E.allin)}
        ${field('Diskon (Rp total)', `${moneyIn('name="diskon" placeholder="0"', d.diskon || '')}<span class="hint">Rupiah saja, tanpa %. Satu nilai untuk seluruh quotation, maksimal harga all-in.</span>`, E.diskon)}
        ${field('Biaya lain-lain (Rp)', `${moneyIn('name="lain" placeholder="0"', d.lain || '')}<span class="hint">Baris "Biaya Lain-lain" di Penawaran</span>`)}
      </div><div class="totals" id="qtn-totals">${qtnTotals(d)}</div>` : `<div>${skemaHtml}
        ${field('Diskon (Rp / peserta)' + QDISK, `${moneyIn('name="diskon" placeholder="0"', d.diskon || '')}<span class="hint">Rupiah saja, tanpa %. Maksimal harga / peserta.</span>`, E.diskon)}
        ${field('Biaya lain-lain (Rp)', `${moneyIn('name="lain" placeholder="0"', d.lain || '')}<span class="hint">Baris "Biaya Lain-lain" di Penawaran</span>`)}
      </div><div class="totals" id="qtn-totals">${qtnTotals(d)}</div>`;
    return `<div class="qtn-form">${top}
      <div class="qtn-grid"><div>${left}</div><div>${right}</div></div>
      <p class="qtn-h">Harga</p><div class="qtn-grid">${harga}</div></div>`;
  }

  const b2bAllinDefault = (d, pj) => { d.skema = 'allin'; if (!d.allin) d.allin = (Number(pj.hargaJual) || 0) * Math.min(Math.max(Number(d.kuota) || 0, B2B_MIN), B2B_PAKET); };
  function qtnChange(d, form, t) {
    const n = t.name, val = t.value;
    if (n === 'pj') {
      d.pj = Number(val) || ''; d.err = {}; d.skema = 'peserta'; d.allin = ''; d.kolomB2b = d.kolomB2b || [];
      const pj = db.quot.find(x => x.id === d.pj);
      if (!d.ketTouched) d.ketItems = pj ? ketItems(pj.jenis, null) : null;
      if (!d.kfTouched) d.kf = pj ? kfNew(kfDefaultFor(pj)) : null;
      if (pj && pj.jenis === 'b2b') { qtnSetCompany(d, pj.company, 'company'); if ((Number(d.kuota) || 0) < B2B_MIN) d.kuota = B2B_MIN; b2bAllinDefault(d, pj); }
      else { d.tujuan = d.tujuan || ''; if (d.tujuan === 'company' && d.company) qtnSetCompany(d, d.company, 'company'); }
    } else if (n === 'tujuan') { d.tujuan = val; d.company = ''; d.up = ''; d.alamat = ''; d.kepada = ''; }
    else if (n === 'company') qtnSetCompany(d, Number(val) || '', 'company');
    else if (n === 'kuota') { d.kuota = val === '' ? '' : Math.max(Math.floor(Number(val) || 0), 0); if (d.err) delete d.err.kuota; form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); return; }
    else if (n === 'ppn') { d.ppn = t.checked; form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); return; }
    else if (n === 'skema') {
      d.skema = val; d.diskon = '';
      const pj = db.quot.find(x => x.id === d.pj);
      if (val === 'allin' && !d.allin && pj) d.allin = (Number(pj.hargaJual) || 0) * Math.max(Number(d.kuota) || 0, B2B_MIN);
    }
    else if (n === 'diskon' || n === 'lain' || n === 'allin') return;
    else if (n === 'sales') { d.sales = Number(val) || ''; return; }
    else {
      // Berlaku sampai ikut pindah tahun selama masih kosong atau masih default 31 Desember tahun lama.
      const ikut = n === 'tanggal' && val && (!d.berlaku || d.berlaku === akhirTahun(d.tanggal));
      d[n] = val;
      if (ikut) { d.berlaku = akhirTahun(val); form.elements.berlaku.value = d.berlaku; }
      return;
    }
    if (d.err) delete d.err[n];
    qtnRedraw(d, form);
  }
  function qtnSetCompany(d, id, tujuan) {
    const c = db.company.find(x => x.id === id);
    Object.assign(d, { tujuan, company: id, up: c ? c.picNama || '' : '', alamat: c ? `${c.alamat}, ${c.kota}` : '' });
  }

  // Form quotation: Data Quotation → Keterangan → Formulir Konfirmasi (isian link, Public) → Preview.
  const QTN_STEPS = [['data', 'Data Quotation'], ['ket', 'Keterangan'], ['kf', 'Formulir Konfirmasi (link)'], ['prev', 'Preview']];
  const isB2bD = d => ((db.quot.find(x => x.id === d.pj) || {}).jenis) === 'b2b';
  const qtnSteps = d => QTN_STEPS.map(([k, l]) => [k, k === 'kf' && d && isB2bD(d) ? 'Konfirmasi Company (link)' : l]);
  // Langkah 3 B2B (input/Master - Buat Program/2026-11-10-b2b-paket-25-konfirmasi-company/): link diisi satu kali oleh PIC company, berisi data
  // company/PIC + daftar peserta (Nama wajib, kolom lain opsional), lalu surat konfirmasi company ditandatangani dan diunggah.
  function kfB2bHtml(d) {
    const pj = db.quot.find(x => x.id === d.pj), kol = d.kolomB2b || [];
    const chips = `<span class="kf-chip on">Nama Lengkap <span class="muted">(wajib)</span></span>` + B2B_KOLOM.map(([k, l]) => `<label class="kf-chip ${kol.includes(k) ? 'on' : ''}"><input type="checkbox" data-kbk="${k}" ${kol.includes(k) ? 'checked' : ''}> ${esc(l)}</label>`).join('');
    const contoh = pj ? pageKonfirmasiB2b(Object.assign({}, d, { kolomB2b: kol }), null, [], '') : '';
    return `<div class="notice"><span><span class="tobe">To be</span> Penjualan B2B dikonfirmasi <b>atas nama company</b>. Link diisi <b>satu kali oleh PIC company</b>: data company dan PIC, lalu daftar peserta. PIC mencetak surat konfirmasi company beserta lampiran daftar peserta, menandatangani dan memberi cap, lalu wajib mengunggahnya.</span><span class="ref">QTN-FR-31${QKCO}</span></div>
      <p class="qtn-h">Diisi PIC company</p>
      <p class="kf-fixed">Nama Perusahaan · Alamat Perusahaan · Nama PIC · Jabatan PIC · Nomor HP / WhatsApp PIC · Email PIC <span class="muted">(terisi awal dari Master Company, dapat diubah PIC)</span></p>
      <p class="qtn-h">Kolom daftar peserta</p>
      <p class="hint top">Company biasanya hanya mengirim nama. Centang kolom lain bila memang dibutuhkan.</p>
      <div class="kf-chips">${chips}</div>
      <p class="qtn-h">Surat konfirmasi company <span class="muted">(kosong, contoh)</span></p>
      <div class="pdf-doc kf-b2b-prev">${contoh}</div>`;
  }
  function qtnBody(d) {
    const steps = qtnSteps(d);
    if (d.step > steps.length) d.step = steps.length;
    const nav = `<ol class="stepper">${steps.map(([, l], i) => `<li class="${d.step === i + 1 ? 'on' : d.step > i + 1 ? 'done' : ''}"><button type="button" data-q="step" data-n="${i + 1}" ${d.step === i + 1 ? 'aria-current="step"' : ''}><span>${i + 1}</span>${l}</button></li>`).join('')}</ol>`;
    const s = steps[d.step - 1][0];
    return nav + (s === 'data' ? qtnStep1(d) : s === 'ket' ? ketHtml(d) : s === 'kf' ? (isB2bD(d) ? kfB2bHtml(d) : kfHtml(d)) : qtnPreview(d));
  }
  // To be: PDF quotation = Penawaran + S&K. Formulir Konfirmasi tidak dicetak kosong; diisi lewat link.
  const qtnPages = k => pagePenawaran(k) + pagesSnk();
  const qtnPagesNote = k => `Penawaran (1 hlm) + Syarat &amp; Ketentuan (2 hlm). Formulir Konfirmasi tidak ikut dicetak; calon peserta mengisinya lewat link${k.link ? '' : ' yang dibuat saat Simpan'}.`;
  function qtnPreview(d) {
    const k = Object.assign({ nomor: d.nomor || qtnNo(d.tanggal, true), status: 'draft', link: d.link || null, peserta: [] }, qtnData(d));
    return `<div class="pdf-tools"><span><span class="badge st-draft">Preview: belum disimpan</span> <span class="muted">${qtnPagesNote(k)} Periksa, lalu Simpan atau ← Kembali.</span></span></div>
      <div class="pdf-doc">${qtnPages(k)}</div>`;
  }
  const qtnRedraw = (d, form) => {
    const body = form.querySelector('.modal-body');
    body.innerHTML = qtnBody(d);
    const sub = form.querySelector('footer [type=submit]'), back = form.querySelector('footer [data-q="back"]');
    if (sub) sub.textContent = d.step < qtnSteps(d).length ? 'Lanjut →' : d.isEdit ? 'Simpan Perubahan' : 'Simpan Draft & Buat Link';
    if (back) back.hidden = d.step === 1;
    if (d.lastStep !== d.step) { body.scrollTop = 0; d.lastStep = d.step; }
  };
  function qtnErrors(d, rec) {
    const E = {}, pj = db.quot.find(x => x.id === d.pj);
    if (!pj) E.pj = 'Penjualan Program wajib dipilih';
    else if (pj.status !== 'final') E.pj = 'Hanya Penjualan Program Final yang dapat dibuatkan quotation';
    else if (!rec && !canAddQtn(pj)) E.pj = 'Penjualan B2B ini sudah punya quotation aktif';
    if (!d.tanggal) E.tanggal = 'Tanggal quotation wajib diisi';
    if (d.berlaku && d.tanggal && d.berlaku < d.tanggal) E.berlaku = 'Tidak boleh sebelum tanggal quotation';
    if (!d.sales) E.sales = 'Sales / marketing wajib dipilih';
    if (!d.tujuan) E.tujuan = 'Pilih Company atau Personal';
    else if (d.tujuan === 'company' && !d.company) E.company = 'Company wajib dipilih';
    else if (d.tujuan === 'personal' && !String(d.kepada || '').trim()) E.kepada = 'Nama yang dituju wajib diisi';
    const b2b = pj && pj.jenis === 'b2b', allin = b2b, kuota = Number(d.kuota) || 0;
    if (!kuota) E.kuota = 'Kuota peserta wajib diisi, minimal 1';
    else if (b2b && kuota < B2B_MIN) E.kuota = `Penjualan B2B minimal ${B2B_MIN} peserta`;
    else if (d.isEdit && kuota < (d.terisi || 0)) E.kuota = `Sudah ${d.terisi} peserta mengisi; kuota tidak boleh kurang dari itu`;
    if (allin && !(Number(d.allin) > 0)) E.allin = 'Harga all-in wajib diisi';
    if (pj && !allin && (Number(d.diskon) || 0) > (Number(pj.hargaJual) || 0)) E.diskon = 'Diskon tidak boleh melebihi harga / peserta';
    if (allin && (Number(d.diskon) || 0) > (Number(d.allin) || 0)) E.diskon = 'Diskon tidak boleh melebihi harga all-in';
    return E;
  }
  // Data header quotation (tanpa peserta: peserta datang dari link).
  function qtnData(d) {
    const jenis = (db.quot.find(x => x.id === d.pj) || {}).jenis || 'public';
    return { pj: d.pj, tanggal: d.tanggal, berlaku: d.berlaku, sales: d.sales, sumber: d.sumber, tujuan: d.tujuan,
      company: d.tujuan === 'company' ? d.company : '', up: d.tujuan === 'company' ? (d.up || '').trim() : '', kepada: d.tujuan === 'personal' ? (d.kepada || '').trim() : '',
      alamat: (d.alamat || '').trim(), ppn: d.ppn, kuota: Number(d.kuota) || 0,
      skema: jenis === 'b2b' ? 'allin' : 'peserta', allin: jenis === 'b2b' ? Number(d.allin) || 0 : 0,
      ...(jenis === 'b2b' ? { paketMaks: Number(d.paketMaks) || B2B_PAKET, tambahPs: d.tambahPs == null ? B2B_TAMBAH : Number(d.tambahPs), kolomB2b: (d.kolomB2b || []).slice() } : {}),
      diskon: Number(d.diskon) || 0, lain: Number(d.lain) || 0, ket: ketFromItems(d.ketItems, jenis),
      kf: d.kf ? clone(d.kf) : null };
  }
  function qtnGo(d, form, rec, n) {
    if (n > 1) {
      const E = qtnErrors(d, rec);
      if (Object.keys(E).length) { d.err = E; d.step = 1; qtnRedraw(d, form); toast('Lengkapi Data Quotation dahulu', 'warn'); return false; }
    }
    d.err = {}; d.step = n; qtnRedraw(d, form);
    return true;
  }
  function qtnKetAct(d, form, a, b) {
    const k = b.dataset.k, i = +b.dataset.i, jenis = (db.quot.find(x => x.id === d.pj) || {}).jenis || 'public';
    if (a === 'ket-edit') d.ketEdit = `${k}:${i}`;
    if (a === 'ket-edit-cancel') d.ketEdit = null;
    if (a === 'ket-edit-ok') {
      const v = form.querySelector('[data-ket-edit]').value.trim(), x = d.ketItems[k][i];
      if (v && v !== x.teks) { x.teks = v; x.id = null; }
      x.on = true; d.ketEdit = null; d.ketTouched = true;
    }
    if (a === 'ket-new') {
      const inp = form.querySelector(`[data-ket-new="${k}"]`), v = inp.value.trim();
      if (!v) return inp.focus();
      d.ketItems[k].push({ id: null, teks: v, on: true }); d.ketTouched = true;
    }
    if (a === 'ket-reset') { d.ketItems = ketItems(jenis, null); d.ketTouched = false; d.ketEdit = null; }
    qtnRedraw(d, form);
    if (a === 'ket-new') form.querySelector(`[data-ket-new="${k}"]`).focus();
    if (a === 'ket-edit') form.querySelector('[data-ket-edit]').focus();
  }

  function qtnForm(rec, preset) {
    const pjRec = rec && db.quot.find(x => x.id === rec.pj);
    const d = rec ? Object.assign(clone(rec), { err: {}, isEdit: true, terisi: psAktif(rec).length, ketItems: ketItems(pjRec.jenis, rec.ket), ketTouched: true, kf: rec.kf ? clone(rec.kf) : kfNew(kfDefaultFor(pjRec)), kfTouched: true })
      : { pj: '', tanggal: todayIso, berlaku: akhirTahun(todayIso), sales: (db.user.find(u => u.aktif) || {}).id || '', sumber: '', tujuan: '', company: '', up: '', kepada: '', alamat: '', kuota: '', ppn: true, diskon: '', lain: '', ketItems: null, ketTouched: false, err: {} };
    Object.assign(d, { step: 1, lastStep: 1, ketOpen: {}, ketEdit: null });
    if (rec && pjRec.jenis === 'b2b' && rec.skema !== 'allin') b2bAllinDefault(d, pjRec); // quotation B2B lama skema per peserta
    if (preset) { d.pj = preset; const pj = db.quot.find(x => x.id === preset); d.ketItems = ketItems(pj.jenis, null); d.kf = kfNew(kfDefaultFor(pj)); if (pj.jenis === 'b2b') { qtnSetCompany(d, pj.company, 'company'); d.kuota = B2B_MIN; d.kolomB2b = []; b2bAllinDefault(d, pj); } }
    openModal({
      title: rec ? `Edit Quotation ${rec.nomor}` : 'Buat Quotation', wide: 'x',
      body: qtnBody(d), saveLabel: 'Lanjut →',
      onMount: form => {
        form.querySelector('footer [type=submit]').insertAdjacentHTML('beforebegin', '<button type="button" class="btn btn-ghost" data-q="back" hidden>← Kembali</button>');
        form.addEventListener('change', e => {
          const t = e.target;
          if (t.name === 'kfTpl') { d.kf = kfNew(kfTplById(+t.value)); d.kfTouched = true; qtnRedraw(d, form); return; }
          if (t.dataset.kbk) { const k = t.dataset.kbk; d.kolomB2b = B2B_KOLOM.map(([x]) => x).filter(x => x === k ? t.checked : (d.kolomB2b || []).includes(x)); qtnRedraw(d, form); return; }
          if (t.dataset.kfi) { const k = t.dataset.kfi; d.kf.isian = KF_FIELDS.map(([x]) => x).filter(x => x === k ? t.checked : d.kf.isian.includes(x)); d.kfTouched = true; qtnRedraw(d, form); return; }
          if (t.name === 'kfPernyataan' || t.dataset.kfNew !== undefined) return;
          if (t.dataset.kc) { d.ketItems[t.dataset.kc][+t.dataset.i].on = t.checked; d.ketTouched = true; qtnRedraw(d, form); return; }
          if (t.dataset.ketNew !== undefined || t.dataset.ketEdit !== undefined) return;
          qtnChange(d, form, t);
        });
        form.addEventListener('toggle', e => { if (e.target.classList && e.target.classList.contains('ket-acc')) d.ketOpen[e.target.dataset.k] = e.target.open; }, true);
        form.addEventListener('input', e => {
          const t = e.target;
          if (t.name === 'up' || t.name === 'alamat' || t.name === 'kepada') d[t.name] = t.value;
          if (t.name === 'kuota') { d.kuota = t.value === '' ? '' : Math.max(Math.floor(Number(t.value) || 0), 0); form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); }
          if (t.name === 'kfPernyataan') { d.kf.pernyataan = t.value; d.kfTouched = true; const pj = db.quot.find(x => x.id === d.pj), pv = form.querySelector('#kf-prev'); if (pj && pv) pv.innerHTML = kfText(t.value, pj); }
          if (t.name === 'lain') { d.lain = num(t.value.replace(/\D/g, '')); form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); }
          if (t.name === 'diskon') { d.diskon = num(t.value.replace(/\D/g, '')); form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); }
          if (t.name === 'allin') { d.allin = num(t.value.replace(/\D/g, '')); form.querySelector('#qtn-totals').innerHTML = qtnTotals(d); }
        });
        form.addEventListener('keydown', e => {
          if (e.key !== 'Enter') return;
          const t = e.target;
          if (t.dataset.kfNew !== undefined) { e.preventDefault(); kfAct(d, form, 'kf-new', t); }
          else if (t.dataset.ketNew) { e.preventDefault(); qtnKetAct(d, form, 'ket-new', { dataset: { k: t.dataset.ketNew } }); }
          else if (t.dataset.ketEdit) { e.preventDefault(); const [k, i] = t.dataset.ketEdit.split(':'); qtnKetAct(d, form, 'ket-edit-ok', { dataset: { k, i } }); }
        });
        form.addEventListener('click', e => {
          const b = e.target.closest('[data-q]'); if (!b) return;
          const a = b.dataset.q;
          if (a === 'step') return qtnGo(d, form, rec, +b.dataset.n);
          if (a === 'back') return qtnGo(d, form, rec, d.step - 1);
          if (a.startsWith('ket-')) return qtnKetAct(d, form, a, b);
          if (a.startsWith('kf-')) return kfAct(d, form, a, b);
        });
      },
      onSave: () => {
        const form = $('#modal-root form');
        const last = qtnSteps(d).length;
        if (d.step < last) { qtnGo(d, form, rec, d.step + 1); return false; }
        if (!qtnGo(d, form, rec, last)) return false;
        const data = qtnData(d);
        let k = rec;
        if (rec) { Object.assign(rec, data); toast(`Quotation ${rec.nomor} diperbarui`); }
        else {
          k = Object.assign({ id: uid(), nomor: qtnNo(data.tanggal), status: 'draft', peserta: [], link: { token: linkToken(), dibuat: stampNow() } }, data);
          db.qtn.push(k); state.page = 1;
          logAct('Quotation', 'Quotation', 'Tambah', k.nomor, k.id, `Buat Quotation ${k.nomor} (kuota ${k.kuota}) dan link Konfirmasi Kehadiran`);
          toast(`Quotation ${k.nomor} tersimpan sebagai Draft. Link Konfirmasi Kehadiran sudah dibuat.`);
        }
        state.qtnPj = d.pj; state.status = 'all';
        // Langsung ke detail quotation agar link bisa disalin.
        state.qtnView = k.id; state.view = 'qtnDetail'; state.qvTab = 'detail'; state.qvF = 'all';
      }
    });
  }

  // ---------- Quotation: PDF cetak (QTN-FR-10, QTN-FR-14 – QTN-FR-16) ----------
  const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const tglPanjang = iso => { if (!iso) return '—'; const [y, m, d] = iso.split('-'); return `${+d} ${BULAN[+m - 1]} ${y}`; };
  const rangePanjang = (a, b) => {
    if (!a) return '—';
    if (!b || a === b) return tglPanjang(a);
    const [y1, m1, d1] = a.split('-'), [y2, m2] = b.split('-');
    if (y1 === y2 && m1 === m2) return `${+d1}–${tglPanjang(b)}`;
    return y1 === y2 ? `${+d1} ${BULAN[+m1 - 1]} – ${tglPanjang(b)}` : `${tglPanjang(a)} – ${tglPanjang(b)}`;
  };
  const KOP = `<div class="pdf-kop"><div class="pdf-logo">L<i>A</i>B<small>M A N I A</small></div><div class="pdf-kop-t"><b>PT LabMania Indonesia Jaya</b>
    <span>Ruby Commercial No. TC25 Summarecon Bekasi, Jalan Bulevar Selatan, Marga Mulya, Bekasi Utara, Kota Bekasi, Jawa Barat 17143</span>
    <span>www.labmaniaindonesia.id · Customer Care: 0821-2429-3839</span></div></div>`;
  const pdfFoot = (kode, hal, berlaku) => `<div class="pdf-foot"><span>${kode}</span><span><i>${hal}</i></span><span>Berlaku ${berlaku}</span></div>`;
  const pdfPage = (inner, foot) => `<section class="pdf-page">${KOP}<div class="pdf-body">${inner}</div>${foot}</section>`;

  // 1. Penawaran — mengikuti Template Penawaran Labmania (FR/LMI-TR-01/01/Ed.Rev-1.1).
  // Judul kategori Keterangan di PDF (template: "Biaya sudah termasuk mendapatkan:").
  const KET_PDF = { syarat: 'Syarat keikutsertaan', termasuk: 'Biaya sudah termasuk mendapatkan', tidak: 'Biaya tidak termasuk', dokumen: 'Dokumen persyaratan' };

  // Tanggal di print out = tanggal dicetak (hari ini); Confirmation Letter = tanggal peserta mengisi link
  // (input/Master - Buat Program/2026-11-09-tanggal-print-out/).
  function pagePenawaran(k) {
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), deal = k.status === 'deal', v = deal ? qtnNilai(k, dealRows(k)) : qtnNilai(k), s = salesOf(k) || {};
    const up = k.tujuan === 'company' ? k.up : kepadaOf(k);
    const ket = k.ket || ketKosong();
    const nama = deal ? dealRows(k).map(r => (pesertaById(r.id) || {}).nama).filter(Boolean) : [];
    return pdfPage(`
      <div class="pdf-2col">
        <div class="pdf-box"><div class="pdf-lbl">Kepada</div><b>${esc(kepadaOf(k))}</b><br>${esc(k.alamat || '—')}</div>
        <div class="pdf-box"><div class="pdf-h">Penawaran</div>
          <table class="pdf-kv"><tr><td>Nomor</td><td>: ${esc(k.nomor)}</td></tr><tr><td>Tanggal</td><td>: ${tglPanjang(todayIso)}</td></tr></table></div>
      </div>
      <table class="pdf-tbl"><thead><tr><th>No Catalog</th><th>Item</th><th>Deskripsi</th><th class="r">Kts.</th><th class="r">Harga</th><th class="r">Diskon</th><th class="r">Total Harga</th></tr></thead>
        <tbody><tr><td>${esc(pk.nomor)}</td><td>${esc(pk.judul)}</td>
          <td>${pj.durasi} hari${pj.jamPembelajaran ? ` · ${pj.jamPembelajaran} JP` : ''}<br>${rangePanjang(pj.tglMulai, pj.tglSelesai)}<br>${esc(pj.waktu || '—')}<br>${esc(pj.lokasi || '—')}${nama.length ? `<br><span class="pdf-mut">Peserta: ${esc(nama.join(', '))}</span>` : ''}</td>
          ${v.allin ? `<td class="r">1 paket<br><span class="pdf-mut">s.d. ${v.maks} peserta</span></td><td class="r">${rp(v.paket)}<br><span class="pdf-mut">all-in</span></td><td class="r">${v.diskTot ? rp(v.diskTot) : '0'}</td><td class="r">${rp(v.paket)}</td></tr>
          ${v.tambah ? `<tr><td></td><td>Tambahan peserta</td><td>Peserta di atas ${v.maks} (${v.n} peserta)</td><td class="r">${v.lebih} peserta</td><td class="r">${rp(v.tarif)}</td><td class="r">0</td><td class="r">${rp(v.tambah)}</td></tr>` : ''}`
            : `<td class="r">${v.n} peserta</td><td class="r">${rp(v.harga)}</td><td class="r">${v.disk ? rp(v.disk) : '0'}</td><td class="r">${rp(v.sub)}</td></tr>`}</tbody></table>
      <div class="pdf-2col">
        <div><div class="pdf-lbl">Keterangan</div><div class="pdf-line">Up. Ibu/Bapak ${esc(up || '—')}</div>
          <div class="pdf-small">${KET_KAT.filter(([c]) => c !== 'klausul' && ket[c].length).map(([c]) => `${KET_PDF[c]}:<ul class="pdf-dash">${ket[c].map(t => `<li>${esc(t)}</li>`).join('')}</ul>`).join('')}
            ${ket.klausul.map(t => `<p>${esc(t)}</p>`).join('')}
            <p><b>Konfirmasi kehadiran peserta:</b> ${k.link ? `isi lewat https://[domain-aplikasi]/konfirmasi?t=${esc(k.link.token)}` : 'link dibuat saat quotation disimpan'}</p></div></div>
        <table class="pdf-sum"><tr><td>Sub Total</td><td>${rp(v.sub)}</td></tr><tr><td>Diskon</td><td>${v.diskTot ? `− ${rp(v.diskTot)}` : '0'}</td></tr>
          <tr><td>PPN (${PPN_RATE}%)</td><td>${rp(v.ppn)}</td></tr><tr><td>Biaya Lain-lain</td><td>${rp(v.lain)}</td></tr><tr class="tot"><td>Total</td><td>${rp(v.total)}</td></tr></table>
      </div>
      <div class="pdf-2col pdf-sign">
        <div>${tglPanjang(todayIso)}<br>${esc(roleOf(s) || 'Sales')}<div class="pdf-ttd"></div><b>${esc(s.nama || '—')}</b></div>
        <div>............., .................. 20.....<br>Disetujui oleh Pelanggan,<div class="pdf-ttd"></div>Nama :</div>
      </div>`, pdfFoot('FR/LMI-TR-01/01/Ed.Rev-1.1', 'Halaman 1 dari 1', '01/01/2025'));
  }

  // 2. Syarat & Ketentuan Kebijakan Layanan Pembelajaran (DP/LMI-TR-01/01/Ed.Rev-1.1) — teks tetap sesuai dokumen.
  const SNK = [
    ['Pembatalan Kepesertaan, Penarikan dan Pengembalian Uang', [
      'Pembatalan kepesertaan tidak bisa dilakukan jika pelanggan telah mengirimkan penawaran harga yang telah ditandatangani dan formulir konfirmasi keikutsertaan kegiatan yang telah diisi (khusus in house training tidak perlu formulir konfirmasi keikutsertaan, melainkan hanya dari penawaran harga yang sudah ditandatangan) dan/atau telah melakukan pembayaran.',
      'Jika pelanggan membatalkan pesanan layanan pembelajaran dan telah membayar, maka biaya layanan pembelajaran yang telah dibayarkan tidak bisa ditarik kembali.',
      'Jika pelanggan telah mengonfirmasi pesanan melalui penawaran harga yang telah ditandatangani dan formulir konfirmasi keikutsertaan kegiatan yang telah diisi (khusus in house training tidak perlu formulir konfirmasi keikutsertaan, melainkan hanya dari penawaran harga yang sudah ditandatangan) dan belum melakukan pembayaran, namun dibatalkan sepihak tanpa ada konfirmasi kepada penyelenggara setelah H-7 kegiatan layanan pembelajaran, maka akan dikenakan biaya pembatalan sebesar 20% dari total biaya layanan pembelajaran.',
      'Pengembalian uang bisa dilakukan jika LabMania membatalkan layanan pembelajaran atau mengubah jadwal layanan pembelajaran dikarenakan kuota peserta belum memenuhi atau ada hal teknis lainnya.',
      'Jika peserta tidak hadir pada pembelajaran atau membatalkan pesanan setelah batas waktu yang telah ditentukan, maka tidak ada pengembalian uang.']],
    ['Pembayaran', [
      'Apabila pembayaran pelanggan menggunakan pajak, maka pelanggan <b>wajib</b> membayarkan pajak (PPN &amp; PPh) sesuai dengan ketentuan perpajakan yang berlaku di Indonesia dan menyerahkan bukti potong/setor pajak tersebut ke LabMania dalam bentuk soft copy/hard copy.',
      'Pelanggan yang melakukan pembayaran menggunakan sistem <i>guarantee letter</i> (<i>pay later</i>) maka, pelanggan <b>wajib</b> melakukan pelunasan dengan batas waktu sesuai dengan yang tercantum di guarantee letter. Apabila pembayaran dilakukan melebihi batas waktu yang telah ditetapkan sebelumnya maka, akan dikenakan <b>denda</b> dengan ketentuan sebagai berikut:<br>- Terlambat 1-7 hari : 10% dari nilai project<br>- Terlambat &gt;7 hari : 30% dari nilai project',
      'Pelanggan yang telah melakukan pembayaran <b>wajib</b> memberikan bukti pembayaran kepada pihak LabMania dalam bentuk soft copy/hard copy.',
      'Pelanggan yang belum menyelesaikan administrasi pembayaran maka, pelanggan tersebut belum dapat mengikuti kegiatan LabMania selanjutnya dan belum dapat menerima sertifikat pelatihan tersebut.']],
    ['Penggantian Barang Rusak/Hilang', [
      'Selama kegiatan pembelajaran peserta <b>wajib</b> menjaga barang milik LabMania yang digunakan selama pembelajaran.',
      'Jika peserta selama pembelajaran merusak atau menghilangkan barang milik LabMania yang dapat menimbulkan kerugian, sehingga mengganggu/ menghambat kegiatan pembelajaran, maka akan dikenakan biaya ganti rugi terhadap barang yang rusak/ hilang.',
      'Setiap terjadi kerusakan atau kehilangan barang, peserta <b>wajib</b> mengisi berita acara dan menyetujui penggantian biaya ganti rugi sesuai dengan nilai dari barang tersebut. Penggantian dilakukan pada hari kegiatan tersebut berlangsung.',
      'Jika peserta tidak melakukan penggantian biaya ganti rugi sesuai dengan waktu yang telah ditentukan, maka sertifikat pelatihan tidak bisa diserahkan.']],
    ['Kerahasiaan', [
      'Peserta <b>wajib</b> menjaga kerahasiaan informasi milik LabMania termasuk modul pembelajaran.',
      'Peserta tidak diperkenankan menyalin atau menduplikasi, mengubah isi dan menyebarluaskan modul pembelajaran tanpa seizin dari pihak LabMania.']],
    ['Sertifikat Pelatihan', [
      'Sertifikat hanya diterbitkan jika peserta telah mengikuti kegiatan pembelajaran dan mengisi <i>post test</i> dengan syarat memenuhi kriteria penilaian minimal.',
      'Sertifikat diserahkan setelah peserta telah melakukan pembayaran layanan pembelajaran.',
      'Sertifikat dikirimkan maksimal 10 hari kerja (senin-jumat) setelah kegiatan, dengan catatan sudah dilakukan pembayaran (lunas).',
      'Jika sertifikat yang telah dicetak, nama yang tertera tidak sesuai karena kesalahan dari peserta dalam memberikan/ menuliskan nama peserta, maka revisi sertifikat diberikan dalam bentuk <i>softcopy</i>. Jika dibutuhkan <i>hardcopy</i>, maka dikenakan biaya sebesar Rp. 30.000,- (belum termasuk biaya pengiriman melalui ekspedisi). Ketentuan ini berlaku jika kegiatan di luar kantor LabMania.',
      'Jika peserta menghilangkan sertifikat pelatihan lebih dari 6 bulan, maka sertifikat dan materi pelatihan tidak bisa diberikan kembali. Jika masih dalam jangka waktu kurang dari 6 bulan, maka peserta masih bisa diberikan <i>softcopy</i> sertifikat dan materi pelatihan.']],
    ['Lisensi', [
      'Kegiatan yang dilakukan di tempat pelanggan, tanggung jawab lisensi terkait perangkat lunak jika digunakan, maka sepenuhnya tanggung jawab dari pelanggan.',
      'LabMania menjamin bahwa setiap perangkat lunak milik LabMania yang digunakan dalam pembelajaran telah terlisensi.']],
    ['Umpan Balik, Saran dan Keluhan', [
      'Setelah pelatihan, setiap peserta harus mengisi umpan balik kuisioner sebagai <i>feedback</i> kepada LabMania dalam peningkatan layanan pembelajaran.',
      'Saran dan keluhan dapat disampaikan melalui kuisioner atau melalui personil LabMania untuk ditindak lanjuti sesuai prosedur di LabMania.']]
  ];
  function pagesSnk() {
    const sec = (from, to) => SNK.slice(from, to).map(([h, list], i) => `<li><b>${h}</b><ul>${list.map(x => `<li>${x}</li>`).join('')}</ul></li>`).join('');
    const head = '<h3 class="pdf-title">SYARAT &amp; KETENTUAN<br>KEBIJAKAN LAYANAN PEMBELAJARAN</h3>';
    return pdfPage(`${head}<p>LabMania telah menetapkan syarat dan ketentuan yang berlaku yang harus dipatuhi oleh seluruh pelanggan (pembelajar/peserta maupun sponsor/instansi terkait yang mendaftarkan peserta). Berikut adalah beberapa syarat dan ketentuan terkait layanan pembelajaran (termasuk layanan pelatihan):</p>
        <ol class="pdf-snk">${sec(0, 3)}</ol>`, pdfFoot('DP/LMI-TR-01/01/Ed.Rev-1.1', '1 dari 2', '21/10/2024'))
      + pdfPage(`<ol class="pdf-snk" start="4">${sec(3, 7)}</ol>
        <p>Sebelum menyetujui layanan pembelajaran, pastikan Anda membaca kebijakan tersebut dengan cermat untuk memastikan bahwa Anda memahami syarat dan ketentuan yang berlaku.</p>
        <p class="pdf-pengesahan">Bekasi, 21 Oktober 2024<br>Disahkan oleh,<br>Operational Director<span class="pdf-ttd"></span>(Ivan Sarifudin)</p>`, pdfFoot('DP/LMI-TR-01/01/Ed.Rev-1.1', '2 dari 2', '21/10/2024'));
  }

  // 3. Confirmation Letter (FR/LMI-TR-01/02/Ed.Rev-1.0). To be: satu halaman per peserta, terisi dari isian
  // Konfirmasi Kehadiran. Tanpa isian (contoh template di Master Data) tetap tercetak kosong.
  // Template ini dicetak calon peserta dari link, ditandatangani basah, lalu diunggah (input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/):
  // tempat dan tanda tangan diisi tangan; tanggal = tanggal cetak (tgl dapat diganti token untuk konfirmasi.html).
  function pageKonfirmasi(k, p, tgl = tglPanjang(todayIso)) {
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), s = salesOf(k) || {};
    const kf = k.kf || kfNew(kfDefaultFor(pj)), t = kfTplById(kf.tpl) || kfDefaultFor(pj);
    const terisi = !!(p && p.data);
    const isi = KF_FIELDS.filter(([key]) => kf.isian.includes(key) || key === 'nama').map(([key, l]) => [l, kfValue(key, p)])
      .concat((kf.extra || []).map(l => [l, terisi ? p.data[`x:${l}`] || '' : '']));
    const cat = { email: s.email || '—', telp: s.telp || '—', kegiatan: t.kegiatan, judul: pk.judul };
    const sign = terisi
      ? `<div class="pdf-sign1">..........................., ${tgl}<div class="pdf-ttd"></div>(${esc(p.nama)})<br><b>Jabatan:</b> ${esc((p.data && p.data.jabatan) || '')}</div>`
      : `<div class="pdf-sign1">..........., ...................... 20.....<div class="pdf-ttd"></div>(..........................................................)<br><b>Jabatan:</b></div>`;
    return pdfPage(`<h3 class="pdf-title">Konfirmasi Keikutsertaan Peserta</h3>
      ${terisi ? `<p class="pdf-mut">Quotation ${esc(k.nomor)} · ${esc(pk.judul)}</p>` : ''}
      <table class="pdf-form">${isi.map(([l, v]) => `<tr><td>${esc(l)}</td><td>:</td><td>${esc(v || '')}</td></tr>`).join('')}</table>
      <p>Dengan ini menyatakan:</p>
      <ol class="pdf-small-ol"><li>${kfText(kf.pernyataan, pj)}</li>
        <li>Saya <b><u>telah membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan yang berlaku di LabMania</li></ol>
      ${sign}
      <div class="pdf-small"><b><u>Catatan :</u></b><ul>${(t.catatan || []).map(c => `<li>${kfMark(kfFill(c, cat))}</li>`).join('')}</ul></div>`,
      pdfFoot('FR/LMI-TR-01/02/Ed.Rev-1.0', '1 dari 1', '01/08/2023')).replace('class="pdf-page"', 'class="pdf-page kf-page"');
  }
  // Surat konfirmasi B2B atas nama company + lampiran daftar peserta (input/Master - Buat Program/2026-11-10-b2b-paket-25-konfirmasi-company/, format usulan).
  // c = data company/PIC (null = kosong); list = peserta [{nama, ...kolom}]; rowsRaw/nRaw = token untuk konfirmasi.html.
  function pageKonfirmasiB2b(k, c, list, tgl, rowsRaw, nRaw) {
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), sl = salesOf(k) || {}, v = kfVars(pj), kol = kolomOf(k);
    const x = key => c ? esc(c[key] || '') : '';
    const n = nRaw || (list.length ? String(list.length) : '......');
    const isi = [['Nama Perusahaan', x('nama')], ['Alamat Perusahaan', x('alamat')], ['Nama PIC', x('pic')], ['Jabatan', x('jab')], ['Nomor HP / WhatsApp', x('hp')], ['Email', x('email')]];
    const rows = rowsRaw || (list.length ? list : Array.from({ length: 10 }, () => ({}))).map((p, i) => `<tr><td>${i + 1}</td><td>${esc(p.nama || '')}</td>${kol.map(([key]) => `<td>${esc(p[key] || '')}</td>`).join('')}</tr>`).join('');
    const foot = h => pdfFoot('[kode dokumen menyusul]', h, '—');
    return pdfPage(`<h3 class="pdf-title">Konfirmasi Keikutsertaan Perusahaan (In-House)</h3>
      ${k.nomor ? `<p class="pdf-mut">Quotation ${esc(k.nomor)} · ${esc(pk.judul)}</p>` : ''}
      <table class="pdf-form">${isi.map(([l, val]) => `<tr><td>${l}</td><td>:</td><td>${val}</td></tr>`).join('')}</table>
      <p>Dengan ini perusahaan kami menyatakan:</p>
      <ol class="pdf-small-ol"><li>Mengikutsertakan <b>${n} peserta</b> (daftar terlampir) pada kegiatan "<b>${esc(v.judul)}</b>" yang akan diselenggarakan pada tanggal <b>${esc(v.tanggal)}</b> pukul ${esc(v.waktu)} (${esc(v.durasi)} hari, ${esc(v.lokasi)}).</li>
        <li>Telah <b><u>membaca</u></b> dan <b><u>menyetujui</u></b> syarat &amp; ketentuan yang berlaku di LabMania, termasuk ketentuan biaya peserta di atas ${B2B_PAKET} orang sesuai quotation.</li></ol>
      <div class="pdf-sign1">..........................., ${tgl || '...................... 20.....'}<br>Hormat kami,<div class="pdf-ttd"></div>(${c ? x('pic') : '..........................................................'})<br><b>Jabatan:</b> ${x('jab')}<br><span class="pdf-mut">tanda tangan dan cap perusahaan</span></div>
      <div class="pdf-small"><b><u>Catatan :</u></b><ul>
        <li>Nama lengkap peserta pada lampiran menjadi acuan penulisan di sertifikat.</li>
        <li>Harga paket mencakup s.d. ${B2B_PAKET} peserta; peserta ke-${B2B_PAKET + 1} dan seterusnya dikenakan tambahan ${rp(B2B_TAMBAH)}/orang.</li>
        <li>Perubahan daftar peserta setelah konfirmasi dikirim, hubungi ${esc(sl.nama || 'Sales LabMania')}${sl.email ? ` (${esc(sl.email)})` : ''}${sl.telp ? ` di ${esc(sl.telp)}` : ''}.</li></ul></div>`,
      foot('1 dari 2')).replace('class="pdf-page"', 'class="pdf-page kf-page"')
      + pdfPage(`<h3 class="pdf-title">Lampiran: Daftar Peserta</h3>
      <p class="pdf-mut">${c ? `${x('nama')} · ` : ''}${k.nomor ? `Quotation ${esc(k.nomor)} · ` : ''}${esc(pk.judul)} · ${esc(v.tanggal)}</p>
      <table class="kf-lamp-tbl"><thead><tr><th>No</th><th>Nama Lengkap</th>${kol.map(([, l]) => `<th>${esc(l)}</th>`).join('')}</tr></thead><tbody>${rows}</tbody></table>`,
      foot('2 dari 2')).replace('class="pdf-page"', 'class="pdf-page kf-page kf-lamp"');
  }
  const b2bList = k => k.peserta.map(r => pesertaById(r.id)).filter(Boolean).map(p => p.data || { nama: p.nama });
  function suratTemplateB2b(k) {
    const T = { nama: '[[co]]', alamat: '[[alamat]]', pic: '[[pic]]', jab: '[[jab]]', hp: '[[hp]]', email: '[[email]]' };
    return pageKonfirmasiB2b(k, T, [], '[[tgl]]', '[[rows]]', '[[n]]');
  }

  // Template untuk konfirmasi.html: isian diganti token [[i]] (urutan isianOf) dan [[tgl]], diisi di halaman link.
  function suratTemplate(k) {
    const data = {}; isianOf(k).forEach(([key], i) => { data[key] = `[[${i}]]`; });
    return pageKonfirmasi(k, { data, nama: data.nama }, '[[tgl]]');
  }

  // Surat konfirmasi bertanda tangan yang diunggah peserta (input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/). Inilah yang dilihat
  // Sales/marketing sebagai Confirmation Letter. Data contoh tidak punya berkas; ditampilkan surat terisi sebagai gambaran.
  const kbOf = n => `${Math.max(1, Math.round((n || 0) / 1024))} KB`;
  const suratUrl = f => { if (!f || !f.url) return ''; if (!f.blob) { const [h, b64] = f.url.split(','), bin = atob(b64), u = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) u[i] = bin.charCodeAt(i); f.blob = URL.createObjectURL(new Blob([u], { type: h.slice(5).split(';')[0] })); } return f.blob; };
  const suratFile = (k, p) => isB2bQtn(k) ? (k.konfCo || {}).surat : p.surat;
  const suratTgl = (k, p) => isB2bQtn(k) ? (k.konfCo || {}).tgl || p.tgl : p.tgl;
  const suratContoh = (k, p) => { const tgl = tglPanjang(String(suratTgl(k, p)).slice(0, 10));
    return isB2bQtn(k) ? pageKonfirmasiB2b(k, k.konfCo, b2bList(k), tgl) : pageKonfirmasi(k, p, tgl); };
  function suratIsi(k, p) {
    const f = suratFile(k, p), url = suratUrl(f);
    if (url && /^image\//.test(f.tipe)) return `<img class="sk-img" src="${url}" alt="Surat konfirmasi bertanda tangan dari ${esc(p.nama)}">`;
    if (url) return `<iframe class="sk-pdf" src="${url}" title="Surat konfirmasi ${esc(p.nama)}"></iframe>`;
    if (f && f.contoh) return `<p class="sk-note">Data contoh: berkas asli tidak ada di mockup. Di bawah ini surat terisi sebagai gambaran isi berkas.</p><div class="sk-contoh">${suratContoh(k, p)}</div>`;
    if (f) return '<p class="sk-note">Berkas terlalu besar untuk disimpan di browser (mockup). Di aplikasi berkas tersimpan di server dan tampil di sini.</p>';
    return `<p class="sk-note">${isB2bQtn(k) ? 'PIC company belum mengirim daftar peserta dan surat konfirmasi.' : 'Peserta ini belum mengunggah surat konfirmasi.'}</p>`;
  }
  const suratMeta = (k, p) => { const f = suratFile(k, p); return f ? `<span class="code">${esc(f.nama)}</span> · ${kbOf(f.ukuran)} · diunggah ${fmtStamp(suratTgl(k, p))} lewat link${isB2bQtn(k) && k.konfCo ? ` oleh PIC ${esc(k.konfCo.pic)}` : ''}` : 'Belum ada berkas'; };
  const suratBlok = (k, p) => `<section class="sk-blok"><p class="sk-head"><b>${esc(isB2bQtn(k) ? kepadaOf(k) : p.nama)}</b> <span class="muted">${isB2bQtn(k) ? `${k.peserta.length} peserta` : esc(pesertaCo(p))} · ${suratMeta(k, p)}</span></p>${suratIsi(k, p)}</section>`;
  // Halaman Export PDF batch: gambar dicetak satu halaman; PDF unggahan digabung server di aplikasi.
  function suratPage(k, p) {
    const f = suratFile(k, p), url = suratUrl(f), siapa = isB2bQtn(k) ? kepadaOf(k) : p.nama;
    if (f && f.contoh) return suratContoh(k, p);
    const isi = url && /^image\//.test(f.tipe) ? `<img class="sk-img" src="${url}" alt="Surat konfirmasi ${esc(siapa)}">`
      : `<p class="pdf-mut">Berkas ${f ? `PDF <b>${esc(f.nama)}</b> digabungkan di sini saat Export PDF di aplikasi.` : 'belum diunggah.'}</p>`;
    return `<section class="pdf-page sk-page"><p class="pdf-mut">${esc(siapa)} · Quotation ${esc(k.nomor)} · ${f ? esc(f.nama) : '—'}</p>${isi}</section>`;
  }

  // Pratinjau PDF: halaman A4 berurutan + tombol cetak/simpan PDF (window.print, CSS @media print).
  const pdfWrap = (pages, info) => `<div class="pdf-tools"><span class="muted">${info}</span>
      <button type="button" class="btn btn-ghost sm" data-q="pdf-print">Cetak / Simpan PDF</button></div><div class="pdf-doc">${pages}</div>`;
  const pdfMount = form => form.addEventListener('click', e => { if (e.target.closest('[data-q="pdf-print"]')) window.print(); });

  // Kirim dan Cetak Quotation: Draft → Terkirim (menunggu deal); Terkirim/Deal cetak ulang; Batal hanya lihat.
  function qtnPrint(k) {
    const kirim = k.status === 'draft';
    openModal({
      title: k.status === 'batal' ? `Quotation ${k.nomor} (Batal)` : k.status === 'deal' ? 'Cetak Quotation' : 'Kirim dan Cetak Quotation', wide: 'x', info: k.status === 'batal',
      body: pdfWrap(qtnPages(k), `${kirim ? '<span class="badge st-draft">Preview: belum dikirim</span> ' : ''}PDF ${esc(k.nomor)}: ${qtnPagesNote(k)}`)
        + (kirim ? '<p class="hint">Setelah dikirim, quotation tidak dapat diedit dan berstatus <b>Terkirim</b>. Link Konfirmasi Kehadiran tetap menerima isian sampai kuota penuh. Lalu tandai <b>Deal</b>, atau <b>Batalkan</b> bila tidak jadi.</p>' : ''),
      onMount: pdfMount, cancelLabel: 'Tutup',
      saveLabel: kirim || k.status === 'terkirim' ? 'Kirim & Cetak' : 'Cetak',
      onSave: () => {
        if (kirim) { k.status = 'terkirim'; toast(`Quotation ${k.nomor} terkirim, menunggu deal`); }
        else { logAct('Quotation', 'Quotation', 'Cetak', k.nomor, k.id, `Cetak ulang Quotation ${k.nomor}`); toast(`Quotation ${k.nomor} ${k.status === 'terkirim' ? 'dikirim ulang & ' : ''}dicetak (mockup)`); }
      }
    });
  }

  // Deal: centang peserta dari Data Peserta (default semua); yang tidak dicentang Batal (QTN-FR-11).
  // Dokumen dari customer (PO, SPK, surat penunjukan, bukti bayar, dll.) wajib diunggah saat Deal; tampil di detail
  // quotation dan di Sales Order (input/Master - Buat Program/2026-11-09-dokumen-deal/). Mockup hanya menyimpan nama file.
  const DOK_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'], DOK_MAX = 5 * 1024 * 1024;
  const QDOK = q('Q-QTN-20', 'Usulan: minimal satu dokumen wajib saat Deal; tipe PDF, JPG, PNG, DOC/DOCX, XLS/XLSX; maks. 5 MB per file. Dokumen boleh ditambah setelah Deal (mis. PO menyusul)?');
  function dokList(k) {
    const d = k.dokumen || [];
    if (!d.length) return '<p class="dash-empty">Belum ada dokumen.</p>';
    return `${k.dokKet ? `<p class="dok-ket"><span class="muted">Keterangan:</span> ${esc(k.dokKet)}</p>` : ''}<div class="table-wrap"><table class="table"><thead><tr><th class="num">No</th><th>Nama file</th><th>Ukuran</th><th>Diunggah</th></tr></thead><tbody>
      ${d.map((x, i) => `<tr><td class="num">${i + 1}</td><td><span class="code">${esc(x.nama)}</span></td><td class="nowrap">${x.ukuran ? `${Math.max(1, Math.round(x.ukuran / 1024))} KB` : '—'}</td><td class="nowrap">${esc(x.oleh || '—')}<br><span class="muted">${fmtStamp(x.tgl)}</span></td></tr>`).join('')}</tbody></table></div>`;
  }
  // Peserta Deal ke Master Peserta (QTN-FR-33, input/Master - Buat Program/2026-11-11-peserta-deal-ke-master-peserta/).
  // Cocok NIK, lalu email, lalu No. WA: hanya field kosong yang diisi. Tidak cocok: peserta baru (Belum lengkap bila ada yang kosong).
  // Bidang dari Product program (kosong bila Bidang Product berbeda-beda); Provinsi/Kota dari Master Company bila quotation ke Company.
  function mpSyncDeal(k) {
    const pj = pjOfQtn(k), co = k.tujuan === 'company' ? db.company.find(c => c.id === k.company) : null;
    const bidangs = [...new Set(paketList(mpOf(pj) || {}).map(x => x.bidang).filter(Boolean))];
    const hasil = { baru: 0, cocok: 0 };
    dealRows(k).forEach(r => {
      const p = pesertaById(r.id); if (!p || p.idPeserta) return;
      const d = p.data || {}, nik = digits(d.nik), email = String(p.email || d.email || '').trim().toLowerCase(), wa = digits(p.telp || d.wa || d.hp);
      const isi = { nama: p.nama, nik: d.nik || '', telp: p.telp || d.wa || d.hp || '', email: p.email || d.email || '', instansi: co ? co.nama : p.instansi || d.instansi || '',
        company: co ? co.id : '', npwp: co ? co.npwp || '' : '', bidang: bidangs.length === 1 ? bidangs[0] : '', provinsi: co ? co.provinsi || '' : '', kota: co ? co.kota || '' : '', gender: '' };
      const m = (nik && db.peserta.find(x => digits(x.nik) === nik)) || (email && db.peserta.find(x => String(x.email || '').toLowerCase() === email))
        || (wa && db.peserta.find(x => digits(x.telp) === wa));
      if (m) { Object.keys(isi).forEach(f => { if (!m[f] && isi[f]) m[f] = isi[f]; }); p.idPeserta = m.id; hasil.cocok++; }
      else { const n = Object.assign({ id: uid(), kode: nextCode('PS'), asal: k.nomor }, isi); db.peserta.push(n); p.idPeserta = n.id; hasil.baru++; }
    });
    return hasil;
  }
  let dealFiles = [];
  function qtnDeal(k) {
    const pj = pjOfQtn(k), list = psAktif(k);
    openModal({
      title: `Deal: ${k.nomor}`, wide: true,
      body: `<p class="hint top">${esc(kepadaOf(k))} · ${esc(paketOf(mpOf(pj)).judul)} (${esc(pj.nomor)}). Peserta diambil dari <b>Data Peserta</b> quotation ini (isian link). Centang yang <b>deal</b>; yang tidak dicentang menjadi <b>Batal</b>. Setelah Deal, link Konfirmasi Kehadiran ditutup, <b>Sales Order</b> dibuat otomatis, dan peserta Deal disimpan ke <b>Master Peserta</b>.${isB2bQtn(k) ? ` Penjualan B2B: minimal ${B2B_MIN} peserta deal.` : ''}${q('Q-QTN-07', 'Deal untuk seluruh quotation, atau tetap memilih peserta yang deal? Usulan: centang, default semua.')}</p>
        ${list.length ? `<div class="ps-pick">${list.map(r => { const p = pesertaById(r.id) || {};
          return `<label class="ps-row"><input type="checkbox" name="deal" value="${r.id}" ${perluUlang(k, r) ? 'disabled' : 'checked'}><span><b>${esc(p.nama)}</b> ${srcTag(p)}<br><span class="muted">${esc(pesertaCo(p))} · ${esc(p.telp || '—')}</span>${perluUlang(k, r) ? `<br><span class="neg">Belum konfirmasi ulang untuk jadwal rev${k.rev}</span>` : ''}</span></label>`; }).join('')}</div>`
          : `<p class="dash-empty">Belum ada peserta di Data Peserta. Bagikan link Konfirmasi Kehadiran dahulu.</p>`}
        <div class="field" id="deal-err"></div>
        <div class="grid2 gap-t">
          ${field('Dokumen dari customer' + req + QDOK, `<label class="pb-drop"><input type="file" name="dokumen" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" hidden><b>Pilih file dokumen</b><span class="hint">PO, SPK, surat penunjukan, bukti bayar, atau dokumen lain · PDF, JPG, PNG, DOC/DOCX, XLS/XLSX · maks. 5 MB per file · boleh lebih dari satu</span></label>
            <ul class="dok-pick" id="deal-files" aria-live="polite"><li class="hint">Belum ada file</li></ul>`, '', 'span2')}
          ${field('Keterangan dokumen', `<input class="input" name="dokKet" maxlength="200" placeholder="mis. PO No. 118/PO/X/2026">`, '', 'span2')}
        </div>`,
      saveLabel: 'Tandai Deal',
      // File terkumpul tiap kali memilih (tidak mengganti pilihan sebelumnya); tiap file dapat dihapus.
      onMount: form => {
        const ev = form.elements.dokumen, out = $('#deal-files', form); dealFiles = [];
        const draw = () => { out.innerHTML = dealFiles.length ? dealFiles.map((x, i) => `<li><span class="code">${esc(x.name)}</span> <span class="muted">${Math.max(1, Math.round(x.size / 1024))} KB</span>
          <button type="button" class="btn btn-ghost sm danger-t" data-dok-del="${i}" aria-label="Hapus ${esc(x.name)}">Hapus</button></li>`).join('') : '<li class="hint">Belum ada file</li>'; };
        ev.addEventListener('change', () => { [...ev.files].forEach(x => { if (!dealFiles.some(y => y.name === x.name && y.size === x.size)) dealFiles.push(x); }); ev.value = ''; draw(); });
        out.addEventListener('click', e => { const b = e.target.closest('[data-dok-del]'); if (b) { dealFiles.splice(+b.dataset.dokDel, 1); draw(); } });
      },
      onSave: () => {
        const on = [...$('#modal-root form').querySelectorAll('[name=deal]:checked')].map(x => +x.value);
        if (!on.length) { $('#deal-err').innerHTML = `<div class="err"><i>!</i>${list.length ? 'Centang minimal 1 peserta. Bila tidak ada yang deal, gunakan Batalkan.' : 'Belum ada peserta yang dapat ditandai Deal.'}</div>`; return false; }
        if (isB2bQtn(k) && on.length < B2B_MIN) { $('#deal-err').innerHTML = `<div class="err"><i>!</i>Penjualan B2B minimal ${B2B_MIN} peserta deal (baru ${on.length}).</div>`; return false; }
        const f = $('#modal-root form'), files = dealFiles.slice();
        const salah = files.find(x => !DOK_EXT.includes(x.name.split('.').pop().toLowerCase())), besar = files.find(x => x.size > DOK_MAX);
        if (!files.length) return invalid(f.elements.dokumen, 'Unggah minimal satu dokumen dari customer (mis. PO)');
        if (salah) return invalid(f.elements.dokumen, `${salah.name}: hanya PDF, JPG, PNG, DOC/DOCX, atau XLS/XLSX`);
        if (besar) return invalid(f.elements.dokumen, `${besar.name} lebih dari 5 MB`);
        k.dokumen = files.map(x => ({ nama: x.name, ukuran: x.size, oleh: meName(), tgl: isoNow().slice(0, 16).replace('T', ' ') }));
        k.dokKet = f.elements.dokKet.value.trim();
        k.peserta.forEach(r => { r.st = on.includes(r.id) ? 'deal' : 'batal'; });
        k.status = 'deal';
        const so = soCreate(k); // to be: 1 quotation Deal = 1 Sales Order
        const mp = mpSyncDeal(k);
        logAct('Quotation', 'Quotation', 'Deal', k.nomor, k.id, `Deal ${k.nomor}: ${on.length} peserta deal, Sales Order ${so.nomor}. Master Peserta: ${mp.baru} baru, ${mp.cocok} dicocokkan`);
        toast(`${k.nomor} Deal: ${on.length} peserta deal${k.peserta.length - on.length ? `, ${k.peserta.length - on.length} batal` : ''}. Sales Order ${so.nomor} dibuat. Master Peserta: ${mp.baru} baru, ${mp.cocok} sudah terdaftar.`);
      }
    });
  }

  function qtnCancel(k) {
    openModal({
      title: 'Batalkan Quotation',
      body: `<p style="margin:0 0 14px">Batalkan <b>${esc(k.nomor)}</b> (${esc(kepadaOf(k))}) karena tidak jadi deal? Quotation Batal tidak dapat diubah dan link Konfirmasi Kehadiran-nya ditutup.</p>`,
      saveLabel: 'Batalkan', danger: true,
      onSave: () => { k.status = 'batal'; k.peserta.forEach(r => { r.st = 'batal'; }); toast(`Quotation ${k.nomor} dibatalkan`); }
    });
  }

  // Confirmation Letter (to be): surat konfirmasi bertanda tangan yang diunggah tiap peserta lewat link.
  // Deal: peserta Deal saja; sebelum Deal: semua peserta yang sudah mengisi.
  function qtnConfirm(k) {
    const rows = k.status === 'deal' ? dealRows(k) : psAktif(k), all = rows.map(r => pesertaById(r.id)).filter(Boolean), ps = isB2bQtn(k) ? all.slice(0, 1) : all;
    openModal({
      title: `Confirmation Letter: ${k.nomor}`, wide: 'x',
      body: `<p class="hint top">${isB2bQtn(k) ? `Surat konfirmasi company yang diunggah PIC lewat link, dengan lampiran daftar ${k.peserta.length} peserta.` : `Surat konfirmasi bertanda tangan yang diunggah ${ps.length} peserta ${k.status === 'deal' ? 'Deal' : 'yang sudah mengisi'} lewat link.`}${QTB5}</p>${ps.map(p => suratBlok(k, p)).join('')}`,
      info: true
    });
    logAct('Quotation', 'Data Peserta', 'Lihat', k.nomor, k.id, `Lihat Confirmation Letter ${k.nomor} (${ps.length} peserta)`);
  }

  // ---------- Link Konfirmasi Kehadiran & Data Peserta (to be) ----------
  function linkCopy(k) {
    const url = linkUrl(k);
    const done = () => toast('Link Konfirmasi Kehadiran disalin');
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(url).then(done, () => toast(`Salin manual: ${url}`, 'warn'));
    else toast(`Salin manual: ${url}`, 'warn');
  }
  // Konfigurasi yang dibaca konfirmasi.html (halaman calon peserta): program, isian, pernyataan, sisa kuota, status.
  function linkPublish(k) {
    if (!k.link) return;
    const pj = pjOfQtn(k), pk = paketOf(mpOf(pj)), b2b = isB2bQtn(k), co = k.tujuan === 'company' ? db.company.find(c => c.id === k.company) : null;
    lsSet(LINK_KEY(k.link.token), {
      token: k.link.token, nomor: k.nomor, kepada: kepadaOf(k), judul: pk.judul, jadwal: rangePanjang(pj.tglMulai, pj.tglSelesai),
      waktu: pj.waktu || '', lokasi: pj.lokasi || '', kuota: Number(k.kuota) || 0, terisi: psAktif(k).length,
      buka: k.status === 'draft' || k.status === 'terkirim', berlaku: k.berlaku || '', status: QTN_STATUS[k.status], b2b, sales: (salesOf(k) || {}).nama || '',
      instansi: co ? co.nama : '',
      isian: isianOf(k).map(([key, label]) => ({ key, label: label.replace('*', ''), type: ISIAN_TYPE[key] || 'text', wajib: key === 'nama' })),
      pernyataan: kfText((k.kf || kfNew(kfDefaultFor(pj))).pernyataan, pj),
      surat: b2b ? suratTemplateB2b(k) : suratTemplate(k),
      ulang: ulangCfg(k), diganti: false,
      ...(b2b ? { kolom: kolomOf(k).map(([key, label]) => ({ key, label, type: ISIAN_TYPE[key] || 'text' })), sudahKirim: !!k.konfCo, paketMaks: Number(k.paketMaks) || B2B_PAKET, tambahPs: k.tambahPs == null ? B2B_TAMBAH : Number(k.tambahPs),
        company: co ? { nama: co.nama, alamat: k.alamat || `${co.alamat}, ${co.kota}`, pic: co.picNama || '', jab: co.picJabatan || '', hp: co.picTelp || '', email: co.picEmail || '' } : {} } : {})
    });
  }
  const linkPublishAll = () => db.qtn.forEach(linkPublish);
  // Ambil isian dari konfirmasi.html ke Data Peserta. Dipanggil saat mockup dimuat dan saat tab aktif kembali.
  // Data mockup di memori hilang saat dimuat ulang, jadi isian ditarik ulang tiap sesi (linkDiambil per sesi);
  // tanda imp di localStorage hanya dipakai konfirmasi.html untuk menghitung sisa kuota.
  const linkDiambil = new Set();
  function linkSync() {
    let n = 0;
    db.qtn.forEach(k => {
      if (!k.link) return;
      const list = lsGet(ISIAN_KEY(k.link.token)) || [];
      if (!list.some(x => !linkDiambil.has(x.id))) return;
      list.forEach(x => {
        if (linkDiambil.has(x.id)) return;
        linkDiambil.add(x.id); x.imp = true;
        if (x.tipe === 'ulang' || x.tipe === 'ulang-b2b') { // konfirmasi ulang setelah reschedule (QTN-FR-35)
          if (!ulangBuka(k) || x.rev !== k.rev) { x.ditolak = 'Konfirmasi ulang sudah ditutup'; return; }
          n += konfUlangSimpan(k, x); return;
        }
        if (!linkBuka(k)) { x.ditolak = linkStatus(k); return; }
        if (x.tipe === 'b2b') { // satu kiriman PIC = semua peserta company + surat company
          x.peserta.forEach(ps => qpsAdd(k, Object.assign({ instansi: x.co.nama }, ps), 'company', x.tgl, null));
          k.konfCo = Object.assign({}, x.co, { tgl: x.tgl, surat: x.surat || null });
          logAct('Quotation', 'Data Peserta', 'Tambah', k.nomor, k.id, `Daftar ${x.peserta.length} peserta dari PIC ${x.co.pic} (${x.co.nama}) lewat link`);
          n += x.peserta.length; return;
        }
        const p = qpsAdd(k, x.data, 'link', x.tgl, x.surat || null);
        logAct('Quotation', 'Data Peserta', 'Tambah', k.nomor, k.id, `Konfirmasi Kehadiran lewat link: ${p.nama}`);
        n++;
      });
      lsSet(ISIAN_KEY(k.link.token), list);
      linkPublish(k);
    });
    if (n) { toast(`${n} isian Konfirmasi Kehadiran baru masuk ke Data Peserta`); render(); }
    return n;
  }
  // Lihat / Unduh per peserta: surat konfirmasi bertanda tangan yang diunggah peserta itu.
  function qpsPreview(p, unduh) {
    const k = db.qtn.find(x => x.id === p.qtn), f = suratFile(k, p), url = suratUrl(f), siapa = isB2bQtn(k) ? kepadaOf(k) : p.nama;
    if (unduh) {
      if (!f) { toast('Surat konfirmasi belum diunggah', 'warn'); return; }
      if (url) {
        const a = document.createElement('a'); a.href = url; a.download = f.nama; document.body.appendChild(a); a.click(); a.remove();
        logAct('Quotation', 'Data Peserta', 'Unduh', k.nomor, k.id, `Unduh surat konfirmasi ${siapa}`);
        toast(`${f.nama} diunduh`);
      } else if (f.contoh) {
        // Data contoh tidak punya berkas asli: tampilkan surat terisi yang dapat disimpan sebagai PDF.
        openModal({ title: `Unduh surat konfirmasi: ${siapa}`, wide: 'x', info: true, onMount: pdfMount, cancelLabel: 'Tutup',
          body: pdfWrap(suratContoh(k, p), `Data contoh: berkas asli <span class="code">${esc(f.nama)}</span> tidak ada di mockup. Simpan surat terisi ini lewat <b>Cetak / Simpan PDF</b>. Di aplikasi, tombol Unduh langsung mengunduh berkas yang diunggah${isB2bQtn(k) ? ' PIC' : ' peserta'}.`) });
        logAct('Quotation', 'Data Peserta', 'Unduh', k.nomor, k.id, `Unduh surat konfirmasi ${siapa} (data contoh)`);
      } else {
        toast(`${f.nama} lebih besar dari batas penyimpanan browser, jadi tidak dapat diunduh di mockup. Di aplikasi berkas tersimpan di server dan dapat diunduh.`, 'warn');
      }
      return;
    }
    openModal({ title: `Confirmation Letter: ${siapa}`, wide: 'x', info: true,
      body: `<div class="pdf-tools"><span class="muted">${suratMeta(k, p)} · Quotation ${esc(k.nomor)}${isB2bQtn(k) ? ' · surat company untuk semua peserta quotation ini' : ''}</span>
        ${f ? `<button type="button" class="btn btn-ghost sm" data-act="qps-pdf" data-id="${p.id}">Unduh berkas</button>` : ''}</div>${suratIsi(k, p)}` });
    logAct('Quotation', 'Data Peserta', 'Lihat', k.nomor, k.id, `Lihat surat konfirmasi ${siapa}`);
  }
  // Download semua isian sebagai Excel (pola sama dengan Export Excel Manage Peserta).
  function qpsXls(k) {
    const cols = isianOf(k), cell = v => `<td>${esc(v || '')}</td>`;
    const html = `<table><tr><td colspan="${cols.length + 5}"><b>Data Peserta ${esc(k.nomor)}</b> · ${esc(paketOf(mpOf(pjOfQtn(k))).judul)} · kuota ${k.kuota}</td></tr>
      <tr><th>No</th>${cols.map(([, l]) => `<th>${esc(l.replace('*', ''))}</th>`).join('')}<th>Surat konfirmasi</th><th>Masuk lewat</th><th>Waktu</th><th>Status</th></tr>
      ${k.peserta.map((r, i) => { const p = pesertaById(r.id) || { data: {} };
        return `<tr>${cell(i + 1)}${cols.map(([key]) => (key === 'wa' || key === 'hp') ? `<td style="mso-number-format:'\\@'">${esc(p.data[key] || '')}</td>` : cell(p.data[key] || (key === 'instansi' ? p.instansi : ''))).join('')}${cell((suratFile(k, p) || {}).nama || '')}${cell(PS_SRC[p.src])}${cell(fmtStamp(p.tgl))}${cell(PS_STATUS[psStOf(k, r)])}</tr>`; }).join('')}</table>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + html], { type: 'application/vnd.ms-excel' }));
    a.download = `Data_Peserta_${k.nomor.replace(/[^\w-]+/g, '_')}.xls`;
    document.body.appendChild(a); a.click(); a.remove();
    logAct('Quotation', 'Data Peserta', 'Export', k.nomor, k.id, `Download Data Peserta ${k.nomor} (${k.peserta.length} peserta)`);
    toast(`Data_Peserta_${k.nomor}.xls diunduh`);
  }
  // ---------- Tutorial highlight driver.js (permintaan BA 12-11-2026; menggantikan callout Panduan) ----------
  // Dimulai dari tombol Tutorial di atas kartu tabel. Helper tS/tRows/tKol/tGear/tAdd ada di core.js; langkah yang elemennya tidak ada dilewati.

  TOUR.qtn = () => {
    const pj = state.qtnPj && db.quot.find(x => x.id === state.qtnPj);
    if (pj) return [
      tS(null, `Quotation program ${pj.nomor}`, 'Semua quotation untuk satu Penjualan Program, beserta peserta Deal dan kuota yang masih menunggu.'),
      tS('.toolbar', 'Cari dan saring', 'Saring tujuan (Company / Personal) dan status.'),
      tS(tKol(tRows()[0], 'Peserta'), 'Peserta', 'Jumlah isian link dibanding kuota; setelah Deal: jumlah peserta Deal.'),
      tS(tKol(tRows()[0], 'Status'), 'Status', '<b>Draft</b> → <b>Terkirim</b> → <b>Deal</b>, atau <b>Batal</b>. Link Konfirmasi Kehadiran terbuka selama Draft/Terkirim dan kuota belum penuh.'),
      tS(tGear(), 'Aksi', '<b>Lihat Detail</b>, <b>Data Peserta</b>, <b>Edit</b> (Draft), <b>Kirim dan Cetak Quotation</b>, <b>Deal</b> (Terkirim), <b>Batalkan</b>.'),
      canAddQtn(pj)
        ? tS('[data-act="qtn-from-pj"]', 'Mulai: + Create Quotation', 'Membuat quotation untuk program ini. Tutorial berlanjut di form.', { lanjut: { label: 'Buka form', run: () => qtnForm(null, pj.id), ke: 'qtnModal' } })
        // B2B yang sudah punya quotation aktif tidak bisa ditambah quotation: lanjut ke detail quotation yang ada.
        : qtnOfPj(pj)[0] && { popover: { title: 'Buka detail quotation', description: 'Program B2B hanya boleh satu quotation aktif. Lanjut ke detail quotation yang sudah ada.' }, lanjut: { label: 'Lihat detail', run: () => qtnView(qtnOfPj(pj)[0]), ke: 'qtnDetail' } }
    ];
    if (state.qtnTab === 'peserta' && state.bpPj) return [
      tS(null, 'Data Peserta per batch', 'Semua peserta dari semua quotation program ini, lengkap dengan status dan surat konfirmasinya.'),
      tS('.toolbar', 'Saring', 'Saring status peserta Deal / Menunggu / Batal.'),
      tS(tGear(), 'Aksi', '<b>Lihat</b> / <b>Unduh</b> surat konfirmasi peserta.'),
      tS('[data-act="bp-pdf"]', 'Export PDF', 'Daftar peserta beserta Confirmation Letter dalam satu PDF.')
    ];
    const first = db.quot.find(x => qtnTabOk(x) && qtnOfPj(x).length && canAddQtn(x)) || db.quot.find(x => qtnTabOk(x) && qtnOfPj(x).length);
    return [
      tS(null, 'Daftar Quotation', 'Quotation dikelompokkan per Penjualan Program Final. Biasanya dikelola <b>Sales / marketing</b>.'),
      tS('#view .card .tabs', 'On going / Selesai / Data Peserta', '<b>On going</b>: acara belum lewat. <b>Selesai</b>: acara sudah lewat. <b>Data Peserta</b>: peserta per batch.'),
      tS('#view .qtn-flow', 'Alur quotation', 'Jumlah quotation per status. Klik kartu untuk menyaring.'),
      tS('.toolbar', 'Cari dan saring', 'Cari program atau nomor quotation; saring jenis dan tanggal acara.'),
      tS(tGear(), 'Aksi program', '<b>Lihat detail</b>: daftar quotation program itu. <b>+ Buat Quotation</b>: penawaran baru.'),
      first && { popover: { title: 'Buka satu program', description: 'Lanjut ke daftar quotation satu program untuk melihat aksi tiap quotation.' }, lanjut: { label: 'Buka program', run: () => { state.qtnPj = first.id; state.q = ''; state.status = 'all'; state.jenis = 'all'; render(); }, ke: 'qtn' } }
    ];
  };
  // Form quotation (modal 4 langkah)
  TOUR.qtnModal = () => [
    tS('#modal-root .modal header', 'Form quotation', 'Empat langkah: <b>1 Data Quotation</b>, <b>2 Keterangan</b>, <b>3 Formulir Konfirmasi (link)</b>, <b>4 Preview</b>. Penanda langkah dapat diklik.'),
    tS('#modal-root .modal-body', '1. Data Quotation', 'Penjualan Program, tanggal, berlaku sampai, sales, tujuan <b>Company / Personal</b>, <b>Kuota peserta</b>, diskon, PPN, biaya lain. B2B: harga all-in.'),
    tS('#modal-root footer [type=submit]', 'Lanjut sampai Preview', 'Isi tiap langkah lalu <b>Lanjut</b>. Di langkah 4 klik <b>Simpan Draft & Buat Link</b>: link Konfirmasi Kehadiran dibuat otomatis.'),
    (() => { const k = db.qtn.find(x => x.rev && x.status === 'deal') || db.qtn.find(x => x.status === 'deal'); return k && { popover: { title: 'Setelah disimpan', description: 'Detail quotation terbuka: bagikan link, kirim PDF, lalu tandai Deal. Lihat contoh quotation yang sudah Deal.' },
      lanjut: { label: 'Lihat contoh detail', run: () => { closeModal(); qtnView(k); }, ke: 'qtnDetail' } }; })()
  ];
  TOUR.qtnDetail = () => {
    const k = db.qtn.find(x => x.id === state.qtnView); if (!k) return [];
    const ps = state.qvTab === 'peserta';
    return [
      tS('.qv-panel', 'Panel status', 'Posisi quotation di alur Draft › Terkirim › Deal, <b>langkah berikutnya</b>, tombol utama, dan bar kuota.'),
      tS('.rv-note', 'Revisi karena reschedule', 'Nomor menjadi <b>-rev</b>, link baru dibuat, dan jumlah peserta yang sudah / belum konfirmasi ulang tampil di sini.'),
      tS('.qv-tabbar', 'Detail Quotation / Data Peserta', 'Pindah antara isi quotation dan daftar peserta yang mengisi link.'),
      !ps && tS('.lk-copy', 'Link Konfirmasi Kehadiran', '<b>Salin link</b> lalu bagikan ke calon peserta (Public) atau PIC company (B2B). Peserta mengisi data dan mengunggah surat konfirmasi bertanda tangan.'),
      ps && tS('.dp-btns', 'Unduh & Confirmation Letter', '<b>Unduh Excel</b> daftar peserta, atau lihat <b>Confirmation Letter</b> semua peserta.'),
      ps && tS(tKol(tRows()[0], 'Konfirmasi ulang'), 'Konfirmasi ulang', 'Jawaban peserta setelah reschedule: <b>Pindah jadwal</b>, <b>Refund</b> (sudah bayar), atau <b>Batal ikut</b> (belum bayar).'),
      ps && tS(tGear(), 'Aksi peserta', '<b>Lihat / Unduh</b> surat konfirmasi. <b>Proses konfirmasi ulang</b>: catat Pindah jadwal, Refund, atau Batal ikut.'),
      !ps && { popover: { title: 'Lihat Data Peserta', description: 'Lanjut ke tab Data Peserta.' }, lanjut: { label: 'Buka Data Peserta', run: () => { state.qvTab = 'peserta'; render(); }, ke: 'qtnDetail' } },
      ps && k.status === 'deal' && { popover: { title: 'Langkah berikutnya', description: 'Quotation Deal otomatis membuat <b>Sales Order</b>.' }, lanjut: { label: 'Ke Sales Order', run: () => { state.soTab = 'so'; go('so'); }, ke: 'so' } }
    ];
  };

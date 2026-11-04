<div class="page-head">
  <div>
    <h1>Product Combine <span class="ref">FR-07 – FR-13</span></h1>
    <p>Daftar seluruh paket produk hasil kombinasi 3 atribut master. Satu paket produk dibentuk dari kombinasi unik.</p>
  </div>
  <div class="page-actions">
    <?php if (lm_can('master_data.paket_produk', 'tambah')): ?><a href="<?= site_url('master_data/combine'); ?>" class="btn btn-accent">+ Tambah Product (Combine)</a><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" class="input" id="table-search" placeholder="Cari nomor atau judul paket…" aria-label="Cari">
    </div>
  </div>

  <div class="table-wrap">
    <table class="table" id="data-table" data-dt>
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th style="width: 130px;">Kode</th>
          <th>Judul Paket</th>
          <th>Produk</th>
          <th>Bidang</th>
          <th>Sertifikat</th>
          <th style="width: 110px;">Status</th>
          <th style="width: 160px;" class="hist">Dibuat / Diubah</th>
          <th style="width: 80px;" class="act">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($pakets)): $no = 1; foreach ($pakets as $p): ?>
          <tr data-id="<?= $p['id']; ?>">
            <td><?= $no++; ?></td>
            <td><span class="code"><?= htmlspecialchars($p['nomor']); ?></span></td>
            <td><b><?= htmlspecialchars($p['judul']); ?></b></td>
            <td><span class="tag tag-public"><?= htmlspecialchars($p['nama_produk'] ?? '—'); ?></span></td>
            <td><?= htmlspecialchars($p['nama_bidang'] ?? '—'); ?></td>
            <td><span class="tag tag-b2b"><?= htmlspecialchars($p['nama_sertifikasi'] ?? '—'); ?></span></td>
            <td>
              <span class="badge <?= $p['aktif'] ? 'on' : 'off'; ?>">
                <?= $p['aktif'] ? 'Aktif' : 'Nonaktif'; ?>
              </span>
            </td>
            <td class="hist"><?= hist_cell($p); ?></td>
            <td class="act">
              <button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button>
              <span class="gear-src" hidden>
              <?php if (lm_can('master_data.paket_produk', 'ubah')): ?><button class="icon-btn" onclick="openEditPaketModal(<?= $p['id']; ?>, '<?= htmlspecialchars(addslashes($p['judul'])); ?>', '<?= htmlspecialchars($p['nomor']); ?>')" title="Edit Judul">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              </button><?php endif; ?>
              <?php if (lm_can('master_data.paket_produk', 'aktif')): ?><button class="icon-btn" onclick="togglePaket(<?= $p['id']; ?>)" title="<?= $p['aktif'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
              </button><?php endif; ?>
            <?= riwayat_btn('paket_produk', $p['nomor']); ?></span>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="9" class="empty">Belum ada data paket produk. Silakan susun melalui menu Tambah Product.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function openEditPaketModal(id, judul, nomor) {
  var html = '<div class="modal-scrim" onclick="closeModal()"></div>' +
    '<div class="modal">' +
      '<div class="modal-head">' +
        '<h3>Edit Judul Paket (' + nomor + ')</h3>' +
        '<button class="close-btn" onclick="closeModal()">&times;</button>' +
      '</div>' +
      '<div class="modal-body">' +
        '<div class="field">' +
          '<label>Judul Paket Produk <span class="req">*</span></label>' +
          '<input type="text" class="input" id="paket-judul" value="' + judul + '" autofocus>' +
          '<div class="err" id="paket-err" style="display:none;"></div>' +
        '</div>' +
      '</div>' +
      '<div class="modal-foot">' +
        '<button class="btn btn-muted" onclick="closeModal()">Batal</button>' +
        '<button class="btn btn-accent" id="btn-save-paket" onclick="savePaketTitle(' + id + ')">Simpan</button>' +
      '</div>' +
    '</div>';
  openModal(html);
  setTimeout(function() { document.getElementById('paket-judul').focus(); }, 100);
}

function savePaketTitle(id) {
  var judul = document.getElementById('paket-judul').value.trim();
  var errEl = document.getElementById('paket-err');
  if (!lmRequired([['paket-judul', 'Judul Paket Produk']])) return;
  document.getElementById('btn-save-paket').disabled = true;

  $.ajax({
    url: '<?= site_url("master_data/update_paket"); ?>',
    type: 'POST',
    data: { id: id, judul: judul },
    dataType: 'json',
    success: function(res) {
      if (res.status) {
        toast(res.msg, 'ok');
        closeModal();
        setTimeout(function() { location.reload(); }, 600);
      } else {
        errEl.textContent = res.msg;
        errEl.style.display = 'block';
        document.getElementById('btn-save-paket').disabled = false;
      }
    }
  });
}

function togglePaket(id) {
  $.ajax({
    url: '<?= site_url("master_data/toggle_paket"); ?>/' + id,
    type: 'POST',
    dataType: 'json',
    success: function(res) {
      if (res.status) {
        toast(res.msg, 'ok');
        setTimeout(function() { location.reload(); }, 600);
      } else {
        toast(res.msg, 'warn');
      }
    }
  });
}

</script>

<?php
// Dokumen PDF CV Trainer (mPDF): HTML + CSS inline sederhana; isi sama dengan halaman cv.
$e  = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$or = function ($v) use ($e) { return trim((string)$v) === '' ? '—' : $e($v); };
?>
<style>
  body { font-family: dejavusans, sans-serif; font-size: 10pt; color: #030637; }
  .muted { color: #6E7390; }
  h1 { font-size: 13pt; margin: 0 0 1mm 0; color: #14304F; }
  .kode { font-size: 9pt; color: #6E7390; margin: 0 0 4mm 0; }
  table { border-collapse: collapse; width: 100%; }
  .sum td { padding: 2mm 3mm; border: 0.2mm solid #D3DCE8; font-size: 9.5pt; vertical-align: top; }
  .sum td.lbl { width: 45%; color: #030637; }
  .sum td.val { text-align: right; font-weight: bold; }
  .sum tr.odd td { background-color: #E4EAF3; }
  h3 { font-size: 11pt; color: #14304F; margin: 7mm 0 2mm 0; }
  .lst th { background-color: #14304F; color: #ffffff; font-size: 8.5pt; text-align: left; padding: 2mm 2.5mm; border: 0.2mm solid #14304F; }
  .lst td { font-size: 9pt; padding: 2mm 2.5mm; border: 0.2mm solid #D3DCE8; vertical-align: top; }
  .lst tr.odd td { background-color: #F6F8FB; }
  .none { font-size: 9pt; color: #6E7390; }
</style>

<h1>MASTER TRAINER — IDENTITAS FASILITATOR</h1>
<p class="kode"><?= $e($t['kode']); ?></p>

<table class="sum">
  <?php foreach ($summary as $i => $row): ?>
    <tr class="<?= $i % 2 === 0 ? 'odd' : ''; ?>"><td class="lbl"><?= $e($row[0]); ?></td><td class="val"><?= $or($row[1]); ?></td></tr>
  <?php endforeach; ?>
</table>

<?php foreach ($lists as $l): list($key, $title, $cols) = $l; $rows = $t[$key] ?? []; ?>
  <h3><?= $e($title); ?></h3>
  <?php if ($rows): ?>
    <table class="lst" repeat_header="1">
      <thead>
        <tr><th style="width: 9mm;">No.</th><?php foreach ($cols as $c): ?><th><?= $e($c[1]); ?></th><?php endforeach; ?></tr>
      </thead>
      <tbody>
        <?php foreach (array_values($rows) as $i => $r): ?>
          <tr class="<?= $i % 2 ? 'odd' : ''; ?>" nobr="true">
            <td><?= $i + 1; ?></td>
            <?php foreach ($cols as $c): ?>
              <td>
                <?php if ($c[0] === 'file_path'): ?>
                  <?php if (!empty($r['file_path'])): ?><a href="<?= $e(base_url('uploads/trainers/' . rawurlencode(basename($r['file_path'])))); ?>" style="color:#14304F;">Lihat berkas</a><?php else: ?>—<?php endif; ?>
                <?php else: echo $or($r[$c[0]] ?? ''); endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="none">Belum ada data.</p>
  <?php endif; ?>
<?php endforeach; ?>

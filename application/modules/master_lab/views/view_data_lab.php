<style>
    .modal-detail-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .modal-detail-card .pkg-label {
        font-size: 11px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .modal-detail-card .pkg-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
    }
    .rate-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 16px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        transition: transform 0.2s ease;
    }
    .rate-card:hover {
        transform: translateY(-2px);
    }
    .rate-card-ssc {
        border-left: 4px solid #0f766e;
    }
    .rate-card-lab {
        border-left: 4px solid #1e40af;
    }
    .rate-title {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .rate-value {
        font-size: 20px;
        font-weight: 700;
    }
    .rate-value-ssc {
        color: #0f766e;
    }
    .rate-value-lab {
        color: #1e40af;
    }
    .info-section-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .info-section-title {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .info-section-content {
        font-size: 13px;
        color: #334155;
        line-height: 1.6;
    }
</style>

<div class="modal-detail-card">
    <div>
        <div class="pkg-label"><i class="fa fa-flask"></i> Isu Lingkungan</div>
        <div class="pkg-title"><?= htmlspecialchars($data_lab->isu_lingkungan) ?></div>
    </div>
    <div>
        <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 700; padding: 8px 14px; border-radius: 8px; font-size: 13px;">
            <i class="fa fa-clock-o"></i> <?= htmlspecialchars($data_lab->waktu) ?> Jam
        </span>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="rate-card rate-card-ssc">
            <div class="rate-title"><i class="fa fa-tag"></i> Tarif SSC / Titik</div>
            <div class="rate-value rate-value-ssc">
                Rp <?= number_format($data_lab->harga_ssc, 0, ',', '.') ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="rate-card rate-card-lab">
            <div class="rate-title"><i class="fa fa-flask"></i> Tarif Lab / Titik</div>
            <div class="rate-value rate-value-lab">
                Rp <?= number_format($data_lab->harga_lab, 0, ',', '.') ?>
            </div>
        </div>
    </div>
</div>

<div class="info-section-card">
    <div class="info-section-title">
        <i class="fa fa-book text-primary"></i> Pengaturan Perundang-undangan
    </div>
    <div class="info-section-content">
        <?= (!empty($data_lab->peraturan)) ? nl2br(htmlspecialchars($data_lab->peraturan)) : '<span class="text-muted">Tidak ada keterangan peraturan perundang-undangan.</span>' ?>
    </div>
</div>

<div class="info-section-card">
    <div class="info-section-title">
        <i class="fa fa-credit-card text-success"></i> Pemetaan Akun (COA)
    </div>
    <div class="info-section-content">
        <?php if (!empty($data_lab->no_coa)) : ?>
            <span class="label label-info" style="font-size: 13px; padding: 6px 12px; border-radius: 6px; display: inline-block; background-color: #0284c7;">
                <i class="fa fa-folder-open"></i> (<?= htmlspecialchars($data_lab->no_coa) ?>) - <?= htmlspecialchars($data_lab->nm_coa) ?>
            </span>
        <?php else : ?>
            <span class="text-muted">Belum ada akun COA terpilih</span>
        <?php endif; ?>
    </div>
</div>
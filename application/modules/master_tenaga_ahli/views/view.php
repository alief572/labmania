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
        <div class="pkg-label"><i class="fa fa-users"></i> Nama Tenaga Ahli</div>
        <div class="pkg-title"><?= htmlspecialchars($data_biaya->nm_biaya) ?></div>
    </div>
    <div>
        <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 700; padding: 8px 14px; border-radius: 8px; font-size: 13px;">
            <i class="fa fa-user"></i> Tenaga Ahli
        </span>
    </div>
</div>

<div class="info-section-card">
    <div class="info-section-title">
        <i class="fa fa-credit-card text-success"></i> Pemetaan Akun (COA)
    </div>
    <div class="info-section-content">
        <?php if (!empty($data_biaya->no_coa)) : ?>
            <span class="label label-info" style="font-size: 13px; padding: 6px 12px; border-radius: 6px; display: inline-block; background-color: #0284c7;">
                <i class="fa fa-folder-open"></i> (<?= htmlspecialchars($data_biaya->no_coa) ?>) - <?= htmlspecialchars($data_biaya->nm_coa) ?>
            </span>
        <?php else : ?>
            <span class="text-muted">Belum ada akun COA terpilih</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($data_biaya->input_date)) : ?>
    <div class="info-section-card">
        <div class="info-section-title">
            <i class="fa fa-calendar text-primary"></i> Riwayat Input
        </div>
        <div class="info-section-content">
            <span class="text-muted">Diinput pada:</span> <strong><?= date('d-m-Y H:i', strtotime($data_biaya->input_date)) ?></strong>
        </div>
    </div>
<?php endif; ?>

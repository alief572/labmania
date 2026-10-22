<html>
<head>
    <title>Laporan Kunjungan - <?= htmlspecialchars(!empty($spk_info->nm_paket) ? $spk_info->nm_paket : $spk_info->nm_project) ?></title>
    <style>
        @media print { .no-print { display: none; } }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h1 { font-size: 14pt; margin: 0; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 3px 5px; }
        .info-label { font-weight: bold; width: 100px; }
        .section-title { font-size: 11pt; font-weight: bold; margin: 15px 0 5px 0; padding: 4px 6px; background: #f0f0f0; border-left: 3px solid #333; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #999; padding: 4px 6px; font-size: 9pt; vertical-align: top; }
        table.data th { background: #444; color: #fff; text-align: center; font-size: 9pt; }
        .status-done { color: #27ae60; font-weight: bold; }
        .status-progress { color: #e67e22; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 8pt; color: #888; text-align: center; border-top: 1px solid #ccc; padding-top: 5px; }
        .btn-print { padding: 8px 20px; background: #3c8dbc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 12pt; margin: 10px 5px; }
        .btn-print:hover { background: #2d6a8f; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KUNJUNGAN</h1>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Perusahaan</td>
            <td>: <?= htmlspecialchars($spk_info->nm_customer) ?></td>
            <td class="info-label">Project</td>
            <td>: <?= htmlspecialchars(!empty($spk_info->nm_paket) ? $spk_info->nm_paket : $spk_info->nm_project) ?></td>
        </tr>
        <tr>
            <td class="info-label">Project Leader</td>
            <td>: <?= htmlspecialchars(ucfirst($spk_info->nm_project_leader ?? '')) ?></td>
            <td class="info-label">Target Selesai</td>
            <td>: <?= !empty($spk_info->waktu_to) ? date('d-m-Y', strtotime($spk_info->waktu_to)) : '-' ?></td>
        </tr>
        <tr>
            <td class="info-label">Konsultan</td>
            <td colspan="3">: <?php
                $konsultan_names = [];
                if (!empty($spk_info->nm_konsultan_1)) $konsultan_names[] = ucfirst($spk_info->nm_konsultan_1);
                if (!empty($spk_info->nm_konsultan_2)) $konsultan_names[] = ucfirst($spk_info->nm_konsultan_2);
                echo htmlspecialchars(implode(', ', $konsultan_names) ?: '-');
            ?></td>
        </tr>
    </table>

    <div class="section-title">Kegiatan & Action Plan</div>
    <table class="data">
        <thead>
            <tr>
                <th width="9%">Date</th>
                <th width="10%">Konsultan</th>
                <th width="20%">Kegiatan</th>
                <th width="25%">Action Plan</th>
                <th width="10%">PIC</th>
                <th width="9%">Due Date</th>
                <th width="7%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($action_plans)): ?>
                <?php foreach ($action_plans as $plan): ?>
                <tr>
                    <td><?= !empty($plan->visit_date) ? date('d-m-Y', strtotime($plan->visit_date)) : '-' ?></td>
                    <td><?= htmlspecialchars($plan->consultant_name ?? '') ?></td>
                    <td><?= htmlspecialchars($plan->activity_name ?? '') ?></td>
                    <td><?= htmlspecialchars($plan->description ?? '') ?></td>
                    <td><?= htmlspecialchars($plan->pic ?? '') ?></td>
                    <td><?= !empty($plan->due_date) ? date('d-m-Y', strtotime($plan->due_date)) : '-' ?></td>
                    <td class="<?= $plan->status === 'done' ? 'status-done' : 'status-progress' ?>"><?= ucfirst($plan->status) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;"><em>Tidak ada data.</em></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini digenerate secara otomatis pada <?= date('d-m-Y H:i') ?>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print / Save as PDF</button>
        <button class="btn-print" onclick="window.close()" style="background: #666;">Tutup</button>
    </div>
</body>
</html>

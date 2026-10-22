<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
            padding: 0;
            color: #222222;
            letter-spacing: 1px;
        }
        .header .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #444444;
        }
        .info-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-section td {
            padding: 3px 5px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .info-separator {
            width: 10px;
            text-align: center;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            padding: 5px 8px;
            background-color: #f0f0f0;
            border-left: 4px solid #333333;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #444444;
            color: #ffffff;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            font-size: 10pt;
            border: 1px solid #333333;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #cccccc;
            font-size: 10pt;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        .status-progress {
            color: #e67e22;
            font-weight: bold;
        }
        .status-done {
            color: #27ae60;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            color: #999999;
            font-style: italic;
            padding: 10px;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 9pt;
            color: #888888;
            text-align: center;
            border-top: 1px solid #cccccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name"><?php echo htmlspecialchars($report['header']['company_name']); ?></div>
        <h1>LAPORAN KUNJUNGAN</h1>
    </div>

    <!-- Info Section -->
    <table class="info-section">
        <tr>
            <td class="info-label">Perusahaan</td>
            <td class="info-separator">:</td>
            <td><?php echo htmlspecialchars($report['header']['company_name']); ?></td>
        </tr>
        <tr>
            <td class="info-label">Project</td>
            <td class="info-separator">:</td>
            <td><?php echo htmlspecialchars($report['header']['project_name']); ?></td>
        </tr>
        <tr>
            <td class="info-label">Tanggal</td>
            <td class="info-separator">:</td>
            <td><?php echo date('d-m-Y', strtotime($report['header']['visit_date'])); ?></td>
        </tr>
        <tr>
            <td class="info-label">Konsultan</td>
            <td class="info-separator">:</td>
            <td><?php echo htmlspecialchars($report['header']['consultant_name']); ?></td>
        </tr>
        <tr>
            <td class="info-label">Waktu Mulai</td>
            <td class="info-separator">:</td>
            <td><?php echo !empty($report['header']['start_time']) ? date('H:i', strtotime($report['header']['start_time'])) : '-'; ?></td>
        </tr>
        <tr>
            <td class="info-label">Waktu Selesai</td>
            <td class="info-separator">:</td>
            <td><?php echo !empty($report['header']['finish_time']) ? date('H:i', strtotime($report['header']['finish_time'])) : '-'; ?></td>
        </tr>
    </table>

    <!-- Activities Section -->
    <div class="section-title">Kegiatan</div>
    <?php if (!empty($report['activities'])): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No</th>
                <th>Kegiatan</th>
                <th style="width: 80px; text-align: center;">Source</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($report['activities'] as $activity): ?>
            <tr>
                <td style="text-align: center;"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($activity['activity_name']); ?></td>
                <td style="text-align: center;"><?php echo strtoupper($activity['activity_source']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p class="no-data">Tidak ada kegiatan tercatat.</p>
    <?php endif; ?>

    <!-- Action Plans Section (Current Report) -->
    <div class="section-title">Action Plan</div>
    <?php
    $has_action_plans = false;
    if (!empty($report['activities'])) {
        foreach ($report['activities'] as $activity) {
            if (!empty($activity['action_plans'])) {
                $has_action_plans = true;
                break;
            }
        }
    }
    ?>
    <?php if ($has_action_plans): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;">No</th>
                <th>Kegiatan</th>
                <th>Action Plan</th>
                <th style="width: 80px;">PIC</th>
                <th style="width: 80px; text-align: center;">Due Date</th>
                <th style="width: 60px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($report['activities'] as $activity): ?>
                <?php if (!empty($activity['action_plans'])): ?>
                    <?php foreach ($activity['action_plans'] as $plan): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($activity['activity_name']); ?></td>
                        <td><?php echo htmlspecialchars($plan['description']); ?></td>
                        <td><?php echo htmlspecialchars($plan['pic']); ?></td>
                        <td style="text-align: center;"><?php echo date('d-m-Y', strtotime($plan['due_date'])); ?></td>
                        <td style="text-align: center;">
                            <span class="<?php echo $plan['status'] === 'done' ? 'status-done' : 'status-progress'; ?>">
                                <?php echo ucfirst($plan['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p class="no-data">Tidak ada action plan tercatat.</p>
    <?php endif; ?>

    <!-- Previous Action Plans Section -->
    <?php if (!empty($previous_action_plans)): ?>
    <div class="section-title">Action Plan Sebelumnya</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;">No</th>
                <th>Kegiatan</th>
                <th>Action Plan</th>
                <th style="width: 80px;">PIC</th>
                <th style="width: 80px; text-align: center;">Due Date</th>
                <th style="width: 60px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($previous_action_plans as $plan): ?>
            <tr>
                <td style="text-align: center;"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars(isset($plan->activity_name) ? $plan->activity_name : '-'); ?></td>
                <td><?php echo htmlspecialchars($plan->description); ?></td>
                <td><?php echo htmlspecialchars($plan->pic); ?></td>
                <td style="text-align: center;"><?php echo date('d-m-Y', strtotime($plan->due_date)); ?></td>
                <td style="text-align: center;">
                    <span class="<?php echo $plan->status === 'done' ? 'status-done' : 'status-progress'; ?>">
                        <?php echo ucfirst($plan->status); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer-note">
        Dokumen ini digenerate secara otomatis oleh sistem pada <?php echo date('d-m-Y H:i'); ?>
    </div>
</body>
</html>

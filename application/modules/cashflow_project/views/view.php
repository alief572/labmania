<?php
// Calculate grand totals across all tipes
$grand_budget    = 0;
$grand_aktual    = 0;
$grand_pengajuan = 0;
$grand_sisa      = 0;
foreach ($summaries as $s) {
    $grand_budget    += $s['budget'];
    $grand_aktual    += $s['total_aktual'];
    $grand_pengajuan += $s['pengajuan_terpakai'];
    $grand_sisa      += $s['sisa_budget'];
}
?>

<link rel="stylesheet" href="<?= base_url('assets/adminlte/plugins/datatables/dataTables.bootstrap.css') ?>">

<style>
    .btn {
        border-radius: 10px;
    }

    .pd-5 {
        padding: 5px;
    }

    .valign-top {
        vertical-align: top;
    }

    .form-inline .form-control {
        width: auto;
        max-width: 100%;
    }

    .form-inline {
        display: flex;
        justify-content: flex-start;
        flex-wrap: nowrap;
    }

    /* Grand Total Cards */
    .grand-total-row {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .grand-card {
        flex: 1;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px 20px;
        background: #fff;
    }

    .grand-card .label-text {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #666;
        margin-bottom: 5px;
    }

    .grand-card .value-text {
        font-size: 20px;
        font-weight: 700;
        color: #333;
    }

    .grand-card .value-text.text-success {
        color: #28a745;
    }

    .grand-card .value-text.text-danger {
        color: #dc3545;
    }

    /* Section Cards */
    .section-box {
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        margin-bottom: 20px;
        background: #fff;
        overflow: hidden;
    }

    .section-header {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
    }

    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #333;
        margin: 0 0 10px 0;
    }

    .section-title .dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #17a2b8;
        margin-right: 8px;
    }

    .section-summary {
        display: flex;
        gap: 30px;
        align-items: center;
    }

    .section-summary .sum-item {
        text-align: right;
    }

    .section-summary .sum-item .sum-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        color: #888;
    }

    .section-summary .sum-item .sum-value {
        font-size: 14px;
        font-weight: 700;
        color: #333;
    }

    /* Progress bar */
    .budget-progress {
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        margin: 10px 0 5px 0;
        overflow: hidden;
    }

    .budget-progress .bar {
        height: 100%;
        border-radius: 3px;
        background: #28a745;
        transition: width 0.3s;
    }

    .budget-progress .bar.warning {
        background: #ffc107;
    }

    .budget-progress .bar.danger {
        background: #dc3545;
    }

    .progress-label {
        font-size: 11px;
        color: #888;
    }

    /* Table styling */
    .section-body {
        padding: 0;
    }

    .section-body table thead th {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #666;
        border-bottom: 2px solid #e8e8e8;
        padding: 10px 15px;
    }

    .section-body table tbody td {
        padding: 10px 15px;
        font-size: 13px;
        vertical-align: middle;
    }

    /* Badge */
    .badge-er {
        background: #28a745;
        color: #fff;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-dp {
        background: #17a2b8;
        color: #fff;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
</style>

<!-- Back Button + SPK Header -->
<div class="box">
    <div class="box-header">
        <a href="<?= base_url('cashflow_project') ?>" class="btn btn-sm btn-danger">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="box-body">
        <table border="0" style="width: 100%;">
            <tr>
                <th class="pd-5 valign-top" width="150">No. SPK</th>
                <td class="pd-5 valign-top" width="400"><?= htmlspecialchars($header->id_spk_penawaran, ENT_QUOTES, 'UTF-8') ?></td>
                <th class="pd-5 valign-top" width="150">Project Leader</th>
                <td class="pd-5 valign-top" width="400"><?= htmlspecialchars(ucfirst($header->nm_project_leader), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <th class="pd-5 valign-top" width="150">Customer</th>
                <td class="pd-5 valign-top" width="400"><?= htmlspecialchars($header->nm_customer, ENT_QUOTES, 'UTF-8') ?></td>
                <th class="pd-5 valign-top" width="150">Sales</th>
                <td class="pd-5 valign-top" width="400"><?= htmlspecialchars(ucfirst($header->nm_sales), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <th class="pd-5 valign-top" width="150">Address</th>
                <td class="pd-5 valign-top" width="400"><?= htmlspecialchars($header->alamat, ENT_QUOTES, 'UTF-8') ?></td>
                <th class="pd-5 valign-top" width="150">Waktu</th>
                <td class="pd-5 valign-top" width="400">
                    <div class="form-inline">
                        <div class="form-group">
                            <input type="date" class="form-control form-control-sm" value="<?= htmlspecialchars($header->waktu_from, ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: 50px; padding-top: 8px;">
                            <span>-</span>
                        </div>
                        <div class="form-group">
                            <input type="date" class="form-control form-control-sm" value="<?= htmlspecialchars($header->waktu_to, ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th class="pd-5 valign-top" width="150">Project</th>
                <td class="pd-5 valign-top" width="400"><?= htmlspecialchars($header->nama_project, ENT_QUOTES, 'UTF-8') ?></td>
                <th class="pd-5 valign-top" width="150"></th>
                <td class="pd-5 valign-top" width="400"></td>
            </tr>
        </table>
    </div>
</div>

<!-- Grand Total Cards -->
<div class="grand-total-row">
    <div class="grand-card">
        <div class="label-text">Total Budget</div>
        <div class="value-text">Rp <?= number_format($grand_budget, 0, ',', '.') ?></div>
    </div>
    <div class="grand-card">
        <div class="label-text">Total Aktual</div>
        <div class="value-text">Rp <?= number_format($grand_aktual, 0, ',', '.') ?></div>
    </div>
    <div class="grand-card">
        <div class="label-text">Total Pengajuan / Terpakai</div>
        <div class="value-text">Rp <?= number_format($grand_pengajuan, 0, ',', '.') ?></div>
    </div>
    <div class="grand-card">
        <div class="label-text">Total Sisa Budget</div>
        <div class="value-text <?= ($grand_sisa < 0) ? 'text-danger' : 'text-success' ?>">Rp <?= number_format($grand_sisa, 0, ',', '.') ?></div>
    </div>
</div>

<!-- Per-Section Expense Types -->
<?php
$tipe_codes = [2, 3, 4, 5, 6];
foreach ($tipe_codes as $tipe_code):
    $summary = $summaries[$tipe_code];
    $sisa_class = ($summary['sisa_budget'] < 0) ? 'text-danger' : '';
    $pct = ($summary['budget'] > 0) ? round(($summary['pengajuan_terpakai'] / $summary['budget']) * 100, 1) : 0;
    $bar_class = '';
    if ($pct > 90) $bar_class = 'danger';
    elseif ($pct > 70) $bar_class = 'warning';
?>
    <div class="section-box">
        <div class="section-header">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div style="flex:1;">
                    <p class="section-title"><span class="dot"></span><?= htmlspecialchars($summary['name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="section-summary">
                    <div class="sum-item">
                        <div class="sum-label">Budget</div>
                        <div class="sum-value">Rp <?= number_format($summary['budget'], 0, ',', '.') ?></div>
                    </div>
                    <div class="sum-item">
                        <div class="sum-label">Aktual</div>
                        <div class="sum-value">Rp <?= number_format($summary['total_aktual'], 0, ',', '.') ?></div>
                    </div>
                    <div class="sum-item">
                        <div class="sum-label">Pengajuan/Terpakai</div>
                        <div class="sum-value">Rp <?= number_format($summary['pengajuan_terpakai'], 0, ',', '.') ?></div>
                    </div>
                    <div class="sum-item">
                        <div class="sum-label">Sisa Budget</div>
                        <div class="sum-value <?= htmlspecialchars($sisa_class, ENT_QUOTES, 'UTF-8') ?>">Rp <?= number_format($summary['sisa_budget'], 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <div class="budget-progress">
                <div class="bar <?= htmlspecialchars($bar_class, ENT_QUOTES, 'UTF-8') ?>" style="width: <?= htmlspecialchars((string) min($pct, 100), ENT_QUOTES, 'UTF-8') ?>%;"></div>
            </div>
            <div class="progress-label"><?= htmlspecialchars((string) $pct, ENT_QUOTES, 'UTF-8') ?>% budget terpakai</div>
        </div>

        <div class="section-body">
            <table id="table_tipe_<?= htmlspecialchars((string) $tipe_code, ENT_QUOTES, 'UTF-8') ?>" class="table" style="width:100%; margin-bottom:0;">
                <thead>
                    <tr>
                        <th>Tanggal Transaksi</th>
                        <th>No Transaksi</th>
                        <th>COA</th>
                        <th>Jenis Pengeluaran</th>
                        <th>Item</th>
                        <th>Jenis Transaksi</th>
                        <th class="text-right">Actual</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<script src="<?= base_url('assets/adminlte/plugins/datatables/dataTables.bootstrap.js') ?>"></script>
<script type="text/javascript">
    var spkId = '<?= htmlspecialchars(str_replace("/", "|", $id_spk_budgeting), ENT_QUOTES, 'UTF-8') ?>';

    $(document).ready(function() {
        [2, 3, 4, 5, 6].forEach(function(tipe) {
            initTipeTable(tipe);
        });
    });

    function formatRupiah(num) {
        if (num === null || num === undefined || num === '' || num == 0) return 'Rp 0';
        return 'Rp ' + parseFloat(num).toLocaleString('id-ID');
    }

    function formatTanggal(dateStr) {
        if (!dateStr) return '-';
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return parseInt(parts[2]) + '/' + parts[1] + '/' + parts[0];
    }

    function initTipeTable(tipe) {
        $('#table_tipe_' + tipe).DataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_view_tipe',
                type: "POST",
                dataType: "JSON",
                data: function(d) {
                    d.id_spk_budgeting = spkId;
                    d.tipe = tipe;
                }
            },
            columns: [{
                    data: 'tanggal_transaksi',
                    render: function(data) {
                        return formatTanggal(data);
                    }
                },
                {
                    data: 'no_transaksi'
                },
                {
                    data: 'coa',
                    render: function(data) {
                        if (!data || data === '' || data === '-') return '-';
                        return '<span style="background:#e8f5f0; color:#1a8f6e; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; display:inline-block;">' + data + '</span>';
                    }
                },
                {
                    data: 'jenis_pengeluaran'
                },
                {
                    data: 'item'
                },
                {
                    data: 'jenis_transaksi',
                    render: function(data) {
                        if (data === 'Direct Payment') {
                            return '<span class="badge-dp">Direct Payment</span>';
                        }
                        return '<span class="badge-er">Expense Report</span>';
                    }
                },
                {
                    data: 'actual',
                    className: 'text-right',
                    render: function(data) {
                        return formatRupiah(data);
                    }
                }
            ],
            processing: true,
            serverSide: true,
            pageLength: 25,
            ordering: false,
            language: {
                emptyTable: "Tidak ada data transaksi",
                zeroRecords: "Tidak ada data yang ditemukan"
            }
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
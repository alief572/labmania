<?php
/**
 * View SPK - Overview with Visit List
 *
 * Variables from controller:
 * - $spk_info: SPK project details
 * - $visit_list: array of visit report objects (id, report_id, visit_date, consultant_name, start_time, finish_time, status)
 * - $id_spk_penawaran: decoded SPK ID
 */
?>

<style>
    .view-spk-container { padding: 15px; }
    .view-spk-header { margin-bottom: 20px; }
    .view-spk-header table { width: 100%; }
    .view-spk-header td { padding: 4px 8px; vertical-align: top; }
    .view-spk-header .label-col { font-weight: bold; width: 120px; }
    .table-visit { width: 100%; margin-bottom: 20px; }
    .table-visit th, .table-visit td { border: 1px solid #333 !important; padding: 6px 8px; vertical-align: top; font-size: 13px; }
    .table-visit th { background-color: #f5f5f5; text-align: center; font-weight: bold; }
    .section-title { font-weight: bold; font-size: 14px; margin: 20px 0 8px 0; }
</style>

<div class="view-spk-container">

    <!-- Top Right: Kembali Button -->
    <div class="clearfix" style="margin-bottom: 15px;">
        <a href="<?= base_url('laporan_kunjungan') ?>" class="btn btn-default btn-sm pull-right">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Header: SPK Info -->
    <div class="view-spk-header">
        <table>
            <tr>
                <td class="label-col">Perusahaan</td>
                <td>: <?= htmlspecialchars($spk_info->nm_customer) ?></td>
                <td class="label-col" style="text-align: right; width: 100px;">Project</td>
                <td>: <?= htmlspecialchars(!empty($spk_info->nm_paket) ? $spk_info->nm_paket : $spk_info->nm_project) ?></td>
            </tr>
            <tr>
                <td class="label-col">Project Leader</td>
                <td>: <?= htmlspecialchars(ucfirst($spk_info->nm_project_leader ?? '')) ?></td>
                <td class="label-col" style="text-align: right; width: 100px;">Target Selesai</td>
                <td>: <?= !empty($spk_info->waktu_to) ? date('d-m-Y', strtotime($spk_info->waktu_to)) : '-' ?></td>
            </tr>
            <tr>
                <td class="label-col">Konsultan</td>
                <td colspan="3">: <?php
                    $konsultan_names = [];
                    if (!empty($spk_info->nm_konsultan_1)) $konsultan_names[] = ucfirst($spk_info->nm_konsultan_1);
                    if (!empty($spk_info->nm_konsultan_2)) $konsultan_names[] = ucfirst($spk_info->nm_konsultan_2);
                    echo htmlspecialchars(implode(', ', $konsultan_names) ?: '-');
                ?></td>
            </tr>
        </table>
    </div>

    <!-- Daftar Visit -->
    <div class="section-title">Daftar Visit</div>
    <table class="table table-bordered table-visit">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="20%">Konsultan</th>
                <th width="20%">Waktu Mulai</th>
                <th width="20%">Waktu Selesai</th>
                <th width="10%">Status</th>
                <th width="10%">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($visit_list)): ?>
                <?php foreach ($visit_list as $idx => $visit): ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><?= !empty($visit->visit_date) ? date('d-m-Y', strtotime($visit->visit_date)) : '-' ?></td>
                    <td><?= htmlspecialchars($visit->consultant_name ?? '') ?></td>
                    <td><?= !empty($visit->start_time) ? (strlen($visit->start_time) > 10 ? date('d-m-Y H:i', strtotime($visit->start_time)) : date('H:i', strtotime($visit->start_time))) : '-' ?></td>
                    <td><?= !empty($visit->finish_time) ? (strlen($visit->finish_time) > 10 ? date('d-m-Y H:i', strtotime($visit->finish_time)) : date('H:i', strtotime($visit->finish_time))) : '-' ?></td>
                    <td class="text-center">
                        <?php if ($visit->status === 'final'): ?>
                            <span class="label label-success">Final</span>
                        <?php else: ?>
                            <span class="label label-warning">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-info btn-detail-visit" data-id="<?= $visit->id ?>" title="Detail">
                            <i class="fa fa-eye"></i> Detail
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted"><em>Belum ada visit.</em></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Modal Detail Visit -->
<div class="modal fade" id="modal_detail_visit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Detail Visit</h4>
            </div>
            <div class="modal-body" id="modal_detail_visit_body">
                <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Detail Visit button click
    $(document).on('click', '.btn-detail-visit', function() {
        var visitId = $(this).data('id');
        var modalBody = $('#modal_detail_visit_body');

        modalBody.html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</p>');
        $('#modal_detail_visit').modal('show');

        $.ajax({
            url: siteurl + 'laporan_kunjungan/get_visit_detail/' + visitId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status == 1) {
                    var data = response.data;
                    var html = '';

                    // Info header
                    html += '<table style="width:100%; margin-bottom:15px;">';
                    html += '<tr><td style="width:120px; font-weight:bold; padding:3px 5px;">Tanggal</td><td>: ' + data.visit_date + '</td></tr>';
                    html += '<tr><td style="font-weight:bold; padding:3px 5px;">Konsultan</td><td>: ' + data.consultant_name + '</td></tr>';
                    html += '<tr><td style="font-weight:bold; padding:3px 5px;">Waktu Mulai</td><td>: ' + data.start_time + '</td></tr>';
                    html += '<tr><td style="font-weight:bold; padding:3px 5px;">Waktu Selesai</td><td>: ' + data.finish_time + '</td></tr>';
                    html += '<tr><td style="font-weight:bold; padding:3px 5px;">Status</td><td>: ' + data.status + '</td></tr>';
                    html += '</table>';

                    // Kegiatan & Action Plan
                    html += '<h5 style="font-weight:bold; margin-top:15px;">Kegiatan & Action Plan</h5>';
                    html += '<table class="table table-bordered" style="font-size:12px;">';
                    html += '<thead><tr><th>Date</th><th>Konsultan</th><th>Kegiatan</th><th>Action Plan</th><th>PIC</th><th>Due Date</th><th>Status</th></tr></thead>';
                    html += '<tbody>';
                    if (data.action_plans && data.action_plans.length > 0) {
                        for (var i = 0; i < data.action_plans.length; i++) {
                            var plan = data.action_plans[i];
                            var statusClass = plan.status === 'done' ? 'label-success' : 'label-warning';
                            html += '<tr>';
                            html += '<td>' + plan.visit_date + '</td>';
                            html += '<td>' + plan.consultant_name + '</td>';
                            html += '<td>' + plan.activity_name + '</td>';
                            html += '<td>' + plan.description + '</td>';
                            html += '<td>' + plan.pic + '</td>';
                            html += '<td>' + plan.due_date + '</td>';
                            html += '<td class="text-center"><span class="label ' + statusClass + '">' + plan.status.charAt(0).toUpperCase() + plan.status.slice(1) + '</span></td>';
                            html += '</tr>';
                        }
                    } else {
                        html += '<tr><td colspan="7" class="text-center text-muted"><em>Tidak ada data.</em></td></tr>';
                    }
                    html += '</tbody></table>';

                    // Potensi Improvement
                    html += '<h5 style="font-weight:bold; margin-top:15px;">Potensi Improvement</h5>';
                    html += '<table class="table table-bordered" style="font-size:12px;">';
                    html += '<thead><tr><th width="5%">No</th><th>Potensi Improvement</th><th>Hasil Improvement</th><th width="10%">Status</th></tr></thead>';
                    html += '<tbody>';
                    if (data.improvements && data.improvements.length > 0) {
                        for (var j = 0; j < data.improvements.length; j++) {
                            var imp = data.improvements[j];
                            var impStatusClass = imp.status === 'done' ? 'label-success' : 'label-warning';
                            html += '<tr>';
                            html += '<td class="text-center">' + (j + 1) + '</td>';
                            html += '<td>' + imp.potensi_improvement + '</td>';
                            html += '<td>' + imp.hasil_improvement + '</td>';
                            html += '<td class="text-center"><span class="label ' + impStatusClass + '">' + imp.status.charAt(0).toUpperCase() + imp.status.slice(1) + '</span></td>';
                            html += '</tr>';
                        }
                    } else {
                        html += '<tr><td colspan="4" class="text-center text-muted"><em>Tidak ada data.</em></td></tr>';
                    }
                    html += '</tbody></table>';

                    modalBody.html(html);
                } else {
                    modalBody.html('<p class="text-center text-danger">' + (response.pesan || 'Gagal memuat data.') + '</p>');
                }
            },
            error: function() {
                modalBody.html('<p class="text-center text-danger">Terjadi kesalahan server.</p>');
            }
        });
    });
});
</script>

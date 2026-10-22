<?php
/**
 * Visit Report - Report View per SPK
 *
 * Variables from controller:
 * - $spk_info: SPK project details
 * - $action_plans: all action plans (objects with ->id, ->visit_date, ->consultant_name, ->activity_name, ->description, ->pic, ->due_date, ->status)
 * - $improvements: all improvements (objects with ->id, ->potensi_improvement, ->hasil_improvement, ->status)
 * - $id_spk_penawaran: decoded SPK ID
 */
?>

<style>
    .report-container { padding: 15px; }
    .report-header { margin-bottom: 20px; }
    .report-header table { width: 100%; }
    .report-header td { padding: 4px 8px; vertical-align: top; }
    .report-header .label-col { font-weight: bold; width: 120px; }
    .table-report { width: 100%; margin-bottom: 20px; }
    .table-report th, .table-report td { border: 1px solid #333 !important; padding: 6px 8px; vertical-align: top; font-size: 13px; }
    .table-report th { background-color: #f5f5f5; text-align: center; font-weight: bold; }
    .section-title { font-weight: bold; font-size: 14px; margin: 20px 0 8px 0; }
    .btn-status { cursor: pointer; border: none; padding: 3px 10px; border-radius: 3px; font-weight: bold; font-size: 12px; }
    .btn-status-progress { background-color: #f39c12; color: #fff; }
    .btn-status-done { background-color: #00a65a; color: #fff; }
    .btn-status:hover { opacity: 0.8; }
</style>

<div class="report-container">

    <!-- Top Right: Kembali Button -->
    <div class="clearfix" style="margin-bottom: 15px;">
        <a href="<?= base_url('laporan_kunjungan') ?>" class="btn btn-default btn-sm pull-right">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Header: Perusahaan | Project -->
    <div class="report-header">
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

    <!-- Main Table: Kegiatan & Action Plan -->
    <div class="section-title">Kegiatan & Action Plan</div>
    <table class="table table-bordered table-report">
        <thead>
            <tr>
                <th width="10%">Date</th>
                <th width="10%">Konsultan</th>
                <th width="20%">Kegiatan</th>
                <th width="25%">Action Plan</th>
                <th width="10%">PIC</th>
                <th width="10%">Due Date</th>
                <th width="8%">Status</th>
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
                    <td class="text-center">
                        <button type="button" class="btn-status btn-status-<?= $plan->status ?> btn-toggle-status"
                                data-id="<?= $plan->id ?>" data-current="<?= $plan->status ?>">
                            <?= ucfirst($plan->status) ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted"><em>Tidak ada data kegiatan.</em></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Potensi Improvement Table -->
    <div class="section-title">Potensi Improvement</div>
    <table class="table table-bordered table-report">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="35%">Potensi Improvement</th>
                <th width="35%">Hasil Improvement</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($improvements)): ?>
                <?php foreach ($improvements as $index => $imp): ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($imp->potensi_improvement ?? '') ?></td>
                    <td><input type="text" class="form-control input-sm imp-hasil-editable" data-id="<?= $imp->id ?>" value="<?= htmlspecialchars($imp->hasil_improvement ?? '') ?>" placeholder="Hasil improvement..." style="width:100%;"></td>
                    <td class="text-center">
                        <button type="button" class="btn-status btn-status-<?= $imp->status ?> btn-toggle-imp-status"
                                data-id="<?= $imp->id ?>" data-current="<?= $imp->status ?>">
                            <?= ucfirst($imp->status) ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center text-muted"><em>Tidak ada data improvement.</em></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Action Buttons: Email & Download PDF -->
    <div style="margin-top: 20px;">
        <button type="button" class="btn btn-success" id="btn_send_email">
            <i class="fa fa-envelope"></i> Email
        </button>
        <a href="<?= base_url('laporan_kunjungan/generate_pdf_spk/' . str_replace('/', '_SLASH_', $id_spk_penawaran)) ?>" class="btn btn-primary" target="_blank">
            <i class="fa fa-file-pdf-o"></i> Download PDF
        </a>
    </div>

</div>

<!-- Email Modal -->
<div class="modal fade" id="modal_send_email" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-envelope"></i> Kirim Laporan via Email</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="email_address">Alamat Email</label>
                    <input type="email" class="form-control" id="email_address" placeholder="Masukkan alamat email tujuan...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btn_confirm_send_email">
                    <i class="fa fa-paper-plane"></i> Kirim
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {

    // Toggle action plan status (Progress <-> Done)
    $(document).on('click', '.btn-toggle-status', function() {
        var btn = $(this);
        var planId = btn.data('id');
        var currentStatus = btn.data('current');
        var newStatus = (currentStatus === 'progress') ? 'done' : 'progress';

        btn.prop('disabled', true);

        $.ajax({
            url: siteurl + 'laporan_kunjungan/toggle_action_plan_status',
            type: 'POST',
            dataType: 'json',
            data: { id: planId, status: newStatus },
            success: function(response) {
                if (response.status == 1) {
                    btn.data('current', newStatus);
                    btn.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    btn.removeClass('btn-status-progress btn-status-done')
                       .addClass('btn-status-' + newStatus);
                } else {
                    alert(response.pesan || 'Gagal mengupdate status.');
                }
                btn.prop('disabled', false);
            },
            error: function() {
                alert('Gagal mengupdate status.');
                btn.prop('disabled', false);
            }
        });
    });

    // Toggle improvement status (Progress <-> Done)
    $(document).on('click', '.btn-toggle-imp-status', function() {
        var btn = $(this);
        var impId = btn.data('id');
        var currentStatus = btn.data('current');
        var newStatus = (currentStatus === 'progress') ? 'done' : 'progress';

        btn.prop('disabled', true);

        $.ajax({
            url: siteurl + 'laporan_kunjungan/toggle_improvement_status',
            type: 'POST',
            dataType: 'json',
            data: { id: impId, status: newStatus },
            success: function(response) {
                if (response.status == 1) {
                    btn.data('current', newStatus);
                    btn.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    btn.removeClass('btn-status-progress btn-status-done')
                       .addClass('btn-status-' + newStatus);
                } else {
                    alert(response.pesan || 'Gagal mengupdate status.');
                }
                btn.prop('disabled', false);
            },
            error: function() {
                alert('Gagal mengupdate status.');
                btn.prop('disabled', false);
            }
        });
    });

    // Auto-save Hasil Improvement on blur
    $(document).on('blur', '.imp-hasil-editable', function() {
        var input = $(this);
        var impId = input.data('id');
        var newValue = $.trim(input.val());
        $.ajax({
            url: siteurl + 'laporan_kunjungan/update_improvement_hasil',
            type: 'POST', dataType: 'json',
            data: { id: impId, hasil_improvement: newValue },
            success: function(response) {
                if (response.status != 1) { alert(response.pesan || 'Gagal menyimpan.'); }
            }
        });
    });

    // Open email modal
    $('#btn_send_email').on('click', function() {
        $('#email_address').val('');
        $('#modal_send_email').modal('show');
    });

    // Send email
    $('#btn_confirm_send_email').on('click', function() {
        var email = $.trim($('#email_address').val());
        if (!email) { alert('Alamat email harus diisi.'); return; }
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) { alert('Format email tidak valid.'); return; }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');

        $.ajax({
            url: siteurl + 'laporan_kunjungan/send_email_spk/<?= str_replace('/', '_SLASH_', $id_spk_penawaran) ?>',
            type: 'POST',
            dataType: 'json',
            data: { email: email },
            success: function(response) {
                if (response.status == 1) {
                    alert(response.pesan || 'Email berhasil dikirim.');
                    $('#modal_send_email').modal('hide');
                } else {
                    alert(response.pesan || 'Gagal mengirim email.');
                }
                btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Kirim');
            },
            error: function() {
                alert('Terjadi kesalahan server.');
                btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Kirim');
            }
        });
    });
});
</script>

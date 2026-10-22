<?php
/**
 * Visit Report Edit Form
 *
 * Variables passed from controller:
 * - $report: full report data ['header' => [...], 'activities' => [...each with 'action_plans' => [...]], 'improvements' => [...]]
 * - $spk_info: SPK project details (nm_customer, nm_project, id_spk_penawaran)
 * - $current_date: today's date (Y-m-d)
 * - $consultant_name: logged-in consultant name
 * - $consultant_id: logged-in consultant ID
 */

// Encode id_spk_penawaran for URL usage (replace / with _SLASH_)
$id_spk_encoded = str_replace('/', '_SLASH_', $report['header']['id_spk_penawaran']);

// Prepare existing activities for JavaScript
$existing_spk_activity_ids = [];
$existing_custom_activities = [];
foreach ($report['activities'] as $activity) {
    if ($activity['activity_source'] === 'spk') {
        $existing_spk_activity_ids[] = (int) $activity['spk_activity_id'];
    } else {
        $existing_custom_activities[] = $activity['activity_name'];
    }
}

// Prepare existing action plans grouped by activity index
$existing_action_plans = [];
foreach ($report['activities'] as $index => $activity) {
    if (!empty($activity['action_plans'])) {
        $existing_action_plans[$index] = $activity['action_plans'];
    }
}

// Prepare existing improvements
$existing_improvements = $report['improvements'];
?>

<style>
    .btn { border-radius: 10px; }
    .time-display {
        font-size: 18px;
        font-weight: bold;
        color: #3c8dbc;
        display: inline-block;
        margin-left: 10px;
        vertical-align: middle;
    }
    .mandays-info {
        background: #f7f7f7;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px 15px;
        margin-top: 10px;
    }
    .mandays-info .info-item {
        display: inline-block;
        margin-right: 30px;
        font-size: 14px;
    }
    .mandays-info .info-item strong {
        color: #333;
    }
    .activity-checkbox-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
    }
    .activity-checkbox-list .checkbox {
        margin: 5px 0;
    }
    .custom-activity-row {
        margin-bottom: 8px;
    }
    .custom-activity-row .form-control {
        display: inline-block;
        width: calc(100% - 40px);
    }
    .custom-activity-row .btn-remove-custom {
        margin-left: 5px;
    }
    #custom_activities_container {
        margin-top: 10px;
    }
    /* Action Plan Styles */
    .action-plan-activity-group {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
        background: #fafafa;
    }
    .action-plan-activity-group h5 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #333;
        font-weight: bold;
    }
    .action-plan-row {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 8px;
    }
    .action-plan-row .form-group {
        margin-bottom: 5px;
    }
    .action-plan-row .error-message {
        color: #dd4b39;
        font-size: 12px;
        display: none;
    }
    .improvement-row {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 8px;
    }
    .improvement-row .form-group {
        margin-bottom: 5px;
    }
    .improvement-row .error-message {
        color: #dd4b39;
        font-size: 12px;
        display: none;
    }
    .previous-action-plans-table {
        max-height: 400px;
        overflow-y: auto;
    }
    .previous-action-plans-table table {
        font-size: 13px;
    }
    .no-data-message {
        padding: 15px;
        text-align: center;
        color: #999;
    }
    .btn-status { cursor: pointer; border: none; padding: 3px 10px; border-radius: 3px; font-weight: bold; font-size: 11px; }
    .btn-status-progress { background-color: #f39c12; color: #fff; }
    .btn-status-done { background-color: #00a65a; color: #fff; }
    .btn-status:hover { opacity: 0.8; }
</style>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-edit"></i> Edit Laporan Kunjungan</h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('laporan_kunjungan') ?>" class="btn btn-default btn-sm">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <form id="form_visit_report" method="post">
            <!-- Hidden fields -->
            <input type="hidden" name="id" id="report_id" value="<?= htmlspecialchars($report['header']['id']) ?>">
            <input type="hidden" name="id_spk_penawaran" id="id_spk_penawaran" value="<?= htmlspecialchars($report['header']['id_spk_penawaran']) ?>">
            <input type="hidden" name="company_name" id="company_name" value="<?= htmlspecialchars($report['header']['company_name']) ?>">
            <input type="hidden" name="project_name" id="project_name" value="<?= htmlspecialchars(!empty($spk_info->nm_paket) ? $spk_info->nm_paket : ($report['header']['project_name'] ?? $spk_info->nm_project)) ?>">
            <input type="hidden" name="consultant_id" id="consultant_id" value="<?= htmlspecialchars($report['header']['consultant_id']) ?>">
            <input type="hidden" name="consultant_name" id="consultant_name_hidden" value="<?= htmlspecialchars($report['header']['consultant_name']) ?>">
            <input type="hidden" name="visit_date" id="visit_date" value="<?= htmlspecialchars($report['header']['visit_date']) ?>">
            <input type="hidden" name="start_time" id="start_time" value="<?= htmlspecialchars($report['header']['start_time'] ?? '') ?>">
            <input type="hidden" name="finish_time" id="finish_time" value="<?= htmlspecialchars($report['header']['finish_time'] ?? '') ?>">

            <!-- Header Section -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-info-circle"></i> Informasi Kunjungan</h4>
                </div>
                <div class="panel-body">
                    <!-- SPK Info -->
                    <div class="well well-sm" style="margin-bottom: 15px; background: #eaf4fc;">
                        <div class="row">
                            <div class="col-md-4">
                                <strong><i class="fa fa-file-text-o"></i> No SPK:</strong> <?= htmlspecialchars($report['header']['id_spk_penawaran']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="fa fa-user"></i> Project Leader:</strong> <?= htmlspecialchars(ucfirst($spk_info->nm_project_leader ?? '-')) ?>
                            </div>
                            <div class="col-md-4">
                                <strong><i class="fa fa-calendar"></i> Target Selesai:</strong> <?= !empty($spk_info->waktu_to) ? date('d-m-Y', strtotime($spk_info->waktu_to)) : '-' ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Perusahaan</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($report['header']['company_name']) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Project</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars(!empty($spk_info->nm_paket) ? $spk_info->nm_paket : ($report['header']['project_name'] ?? $spk_info->nm_project)) ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="text" class="form-control" value="<?= date('d-m-Y', strtotime($report['header']['visit_date'])) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Konsultan</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($report['header']['consultant_name']) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Start/Finish Time Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Waktu Mulai</label>
                                <div>
                                    <button type="button" id="btn_start_time" class="btn btn-success btn-sm" <?= !empty($report['header']['start_time']) ? 'disabled' : '' ?>>
                                        <i class="fa fa-play"></i> Start
                                    </button>
                                    <span id="display_start_time" class="time-display"><?= !empty($report['header']['start_time']) ? htmlspecialchars(date('d-m-Y H:i', strtotime($report['header']['start_time']))) : '--:--' ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Waktu Selesai</label>
                                <div>
                                    <button type="button" id="btn_finish_time" class="btn btn-danger btn-sm" <?= !empty($report['header']['finish_time']) ? 'disabled' : '' ?>>
                                        <i class="fa fa-stop"></i> Finish
                                    </button>
                                    <span id="display_finish_time" class="time-display"><?= !empty($report['header']['finish_time']) ? htmlspecialchars(date('d-m-Y H:i', strtotime($report['header']['finish_time']))) : '--:--' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mandays Info Section -->
                    <div class="mandays-info" id="mandays_info_section">
                        <span class="info-item">
                            <i class="fa fa-calendar"></i> Mandays Project: <strong id="mandays_total">-</strong>
                        </span>
                        <span class="info-item">
                            <i class="fa fa-check-circle"></i> Mandays Terpakai: <strong id="mandays_used">-</strong>
                        </span>
                        <span class="info-item">
                            <i class="fa fa-file-text"></i> Kumulatif Laporan: <strong id="report_count">-</strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Activity Section -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-tasks"></i> Kegiatan / Aktivitas</h4>
                </div>
                <div class="panel-body">
                    <!-- SPK Activities (loaded via AJAX) -->
                    <div id="spk_activities_section">
                        <label>Aktivitas SPK:</label>
                        <div id="spk_activities_list" class="activity-checkbox-list">
                            <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat aktivitas...</p>
                        </div>
                    </div>

                    <hr>

                    <!-- Custom Activities -->
                    <div id="custom_activities_section">
                        <label>Aktivitas Tambahan (Custom):</label>
                        <div id="custom_activities_container">
                            <!-- Pre-populated custom activities -->
                        </div>
                        <button type="button" id="btn_add_custom_activity" class="btn btn-info btn-sm" style="margin-top: 5px;">
                            <i class="fa fa-plus"></i> Tambah Aktivitas Custom
                        </button>
                        <small class="text-muted" style="margin-left: 10px;">Maksimal 20 aktivitas custom, masing-masing maks 500 karakter</small>
                    </div>
                </div>
            </div>

            <!-- Action Plan Section -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-list-ol"></i> Action Plan</h4>
                </div>
                <div class="panel-body">
                    <div id="action_plans_container">
                        <p class="text-muted" id="no_action_plans_msg">
                            <i class="fa fa-info-circle"></i> Pilih atau tambahkan kegiatan terlebih dahulu untuk menambahkan action plan.
                        </p>
                        <!-- Action plan groups per activity will be dynamically inserted here -->
                    </div>
                </div>
            </div>

            <!-- Previous Action Plans Section -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-history"></i> Action Plan Sebelumnya</h4>
                </div>
                <div class="panel-body">
                    <div id="previous_action_plans_container" class="previous-action-plans-table">
                        <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat action plan sebelumnya...</p>
                    </div>
                </div>
            </div>

            <!-- Previous Improvements Section -->
            <!-- Potential Improvement Section -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-lightbulb-o"></i> Potensi Improvement</h4>
                </div>
                <div class="panel-body">
                    <div id="improvements_container">
                        <!-- Improvement rows will be dynamically inserted here -->
                    </div>
                    <button type="button" id="btn_add_improvement" class="btn btn-info btn-sm" style="margin-top: 10px;">
                        <i class="fa fa-plus"></i> Tambah Potensi Improvement
                    </button>
                    <small class="text-muted" style="margin-left: 10px;">Maksimal 50 entri</small>
                </div>
            </div>

            <!-- Previous Improvements Section -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-history"></i> Potensi Improvement Sebelumnya</h4>
                </div>
                <div class="panel-body">
                    <div id="previous_improvements_container" class="previous-action-plans-table">
                        <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat improvement sebelumnya...</p>
                    </div>
                </div>
            </div>

            <!-- Save Buttons -->
            <div class="panel panel-default">
                <div class="panel-body text-center">
                    <button type="button" id="btn_save_draft" class="btn btn-warning btn-lg" style="margin-right: 15px;">
                        <i class="fa fa-save"></i> Save Draft
                    </button>
                    <button type="button" id="btn_save_final" class="btn btn-primary btn-lg">
                        <i class="fa fa-check-circle"></i> Save (Finalize)
                    </button>
                </div>
            </div>
        </form>
    </div>
    <!-- /.box-body -->
</div>

<script type="text/javascript">
$(document).ready(function() {
    var idSpkPenawaran = '<?= addslashes($id_spk_encoded) ?>';
    var customActivityCount = 0;
    var maxCustomActivities = 20;

    // Pre-existing SPK activity IDs that were previously selected
    var existingSpkActivityIds = <?= json_encode($existing_spk_activity_ids) ?>;

    // Pre-existing custom activities
    var existingCustomActivities = <?= json_encode($existing_custom_activities) ?>;

    // Pre-existing action plans grouped by activity index
    var existingActionPlans = <?= json_encode($existing_action_plans) ?>;

    // Pre-existing improvements
    var existingImprovements = <?= json_encode($existing_improvements) ?>;

    // ========================================
    // On page load: fetch activities and mandays info, pre-populate custom activities
    // ========================================
    loadActivities();
    loadMandaysInfo();
    prePopulateCustomActivities();
    prePopulateImprovements();

    // Load previous action plans
    loadPreviousActionPlans();

    // ========================================
    // Start Time Button
    // ========================================
    $('#btn_start_time').on('click', function() {
        var btn = $(this);
        var existingStartTime = $('#start_time').val();

        btn.prop('disabled', true);

        $.ajax({
            url: siteurl + 'laporan_kunjungan/record_time',
            type: 'POST',
            dataType: 'json',
            data: {
                type: 'start',
                start_time: existingStartTime
            },
            success: function(response) {
                if (response.status == 1) {
                    $('#start_time').val(response.time);
                    $('#display_start_time').text(response.display);
                    btn.prop('disabled', true);
                } else {
                    alert(response.pesan);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Gagal mencatat waktu mulai. Silakan coba lagi.');
                btn.prop('disabled', false);
            }
        });
    });

    // ========================================
    // Finish Time Button
    // ========================================
    $('#btn_finish_time').on('click', function() {
        var btn = $(this);
        var existingStartTime = $('#start_time').val();
        var existingFinishTime = $('#finish_time').val();

        // Client-side check: start time must exist
        if (!existingStartTime) {
            alert('Waktu mulai harus dicatat terlebih dahulu.');
            return;
        }

        btn.prop('disabled', true);

        $.ajax({
            url: siteurl + 'laporan_kunjungan/record_time',
            type: 'POST',
            dataType: 'json',
            data: {
                type: 'finish',
                start_time: existingStartTime,
                finish_time: existingFinishTime
            },
            success: function(response) {
                if (response.status == 1) {
                    $('#finish_time').val(response.time);
                    $('#display_finish_time').text(response.display);
                    btn.prop('disabled', true);
                } else {
                    alert(response.pesan);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Gagal mencatat waktu selesai. Silakan coba lagi.');
                btn.prop('disabled', false);
            }
        });
    });

    // ========================================
    // Load SPK Activities via AJAX (with pre-check)
    // ========================================
    function loadActivities() {
        $.ajax({
            url: siteurl + 'laporan_kunjungan/get_activities/' + idSpkPenawaran,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status == 1 && response.data.length > 0) {
                    var html = '';
                    $.each(response.data, function(index, activity) {
                        var isChecked = (existingSpkActivityIds.indexOf(parseInt(activity.id)) !== -1) ? 'checked' : '';
                        html += '<div class="checkbox">';
                        html += '<label>';
                        html += '<input type="checkbox" class="spk-activity-checkbox" ';
                        html += 'data-activity-id="' + activity.id + '" ';
                        html += 'data-activity-name="' + escapeHtml(activity.nm_aktifitas) + '" ';
                        html += 'value="' + activity.id + '" ' + isChecked + '> ';
                        html += escapeHtml(activity.nm_aktifitas);
                        html += '</label>';
                        html += '</div>';
                    });
                    $('#spk_activities_list').html(html);

                    // After loading, create action plan groups for pre-checked SPK activities
                    $('.spk-activity-checkbox:checked').each(function() {
                        var activityId = $(this).data('activity-id');
                        var activityName = $(this).data('activity-name');
                        var groupKey = 'spk_' + activityId;
                        addActionPlanGroup(groupKey, activityName);
                    });

                    // Create action plan groups for custom activities
                    $('.custom-activity-input').each(function() {
                        var rowId = $(this).data('row-id');
                        var activityText = $.trim($(this).val());
                        if (activityText !== '') {
                            var groupKey = 'custom_' + rowId;
                            addActionPlanGroup(groupKey, activityText);
                        }
                    });

                    // Now pre-populate action plans from existing data
                    prePopulateActionPlansFromExisting();
                    toggleNoActionPlansMsg();
                } else if (response.status == 1 && response.data.length === 0) {
                    $('#spk_activities_list').html('<p class="text-info"><i class="fa fa-info-circle"></i> Tidak ada aktivitas SPK yang tersedia. Silakan gunakan aktivitas custom.</p>');

                    // Still create groups for custom activities
                    $('.custom-activity-input').each(function() {
                        var rowId = $(this).data('row-id');
                        var activityText = $.trim($(this).val());
                        if (activityText !== '') {
                            var groupKey = 'custom_' + rowId;
                            addActionPlanGroup(groupKey, activityText);
                        }
                    });

                    prePopulateActionPlansFromExisting();
                    toggleNoActionPlansMsg();
                } else {
                    $('#spk_activities_list').html('<p class="text-danger"><i class="fa fa-exclamation-circle"></i> Gagal memuat aktivitas.</p>');
                }
            },
            error: function() {
                $('#spk_activities_list').html('<p class="text-danger"><i class="fa fa-exclamation-circle"></i> Gagal memuat aktivitas. Silakan refresh halaman.</p>');
            }
        });
    }

    // ========================================
    // Load Mandays Info via AJAX
    // ========================================
    function loadMandaysInfo() {
        $.ajax({
            url: siteurl + 'laporan_kunjungan/get_mandays_info/' + idSpkPenawaran,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status == 1) {
                    $('#mandays_total').text(formatNumber(response.mandays_total));
                    $('#mandays_used').text(formatNumber(response.mandays_used));
                    $('#report_count').text(formatNumber(response.report_count));
                } else {
                    $('#mandays_total').text('0');
                    $('#mandays_used').text('0');
                    $('#report_count').text('0');
                }
            },
            error: function() {
                $('#mandays_total').text('-');
                $('#mandays_used').text('-');
                $('#report_count').text('-');
            }
        });
    }

    // ========================================
    // Pre-populate Custom Activities
    // ========================================
    function prePopulateCustomActivities() {
        if (existingCustomActivities.length > 0) {
            $.each(existingCustomActivities, function(index, activityName) {
                customActivityCount++;
                var rowHtml = '<div class="custom-activity-row" id="custom_activity_row_' + customActivityCount + '">';
                rowHtml += '<input type="text" class="form-control custom-activity-input" ';
                rowHtml += 'placeholder="Masukkan aktivitas custom..." maxlength="500" ';
                rowHtml += 'data-row-id="' + customActivityCount + '" ';
                rowHtml += 'value="' + escapeHtml(activityName) + '">';
                rowHtml += '<button type="button" class="btn btn-danger btn-xs btn-remove-custom" ';
                rowHtml += 'data-row-id="' + customActivityCount + '" title="Hapus">';
                rowHtml += '<i class="fa fa-times"></i>';
                rowHtml += '</button>';
                rowHtml += '</div>';
                $('#custom_activities_container').append(rowHtml);
            });
        }
    }

    // ========================================
    // Add Custom Activity
    // ========================================
    $('#btn_add_custom_activity').on('click', function() {
        if (customActivityCount >= maxCustomActivities) {
            alert('Maksimal ' + maxCustomActivities + ' aktivitas custom.');
            return;
        }

        customActivityCount++;
        var rowHtml = '<div class="custom-activity-row" id="custom_activity_row_' + customActivityCount + '">';
        rowHtml += '<input type="text" class="form-control custom-activity-input" ';
        rowHtml += 'placeholder="Masukkan aktivitas custom..." maxlength="500" ';
        rowHtml += 'data-row-id="' + customActivityCount + '">';
        rowHtml += '<button type="button" class="btn btn-danger btn-xs btn-remove-custom" ';
        rowHtml += 'data-row-id="' + customActivityCount + '" title="Hapus">';
        rowHtml += '<i class="fa fa-times"></i>';
        rowHtml += '</button>';
        rowHtml += '</div>';

        $('#custom_activities_container').append(rowHtml);
    });

    // Remove Custom Activity
    $(document).on('click', '.btn-remove-custom', function() {
        var rowId = $(this).data('row-id');
        $('#custom_activity_row_' + rowId).remove();
        customActivityCount--;
        // Remove associated action plan group
        removeActionPlanGroup('custom_' + rowId);
    });

    // ========================================
    // Save Draft
    // ========================================
    $('#btn_save_draft').on('click', function() {
        saveReport('draft');
    });

    // ========================================
    // Save (Finalize)
    // ========================================
    $('#btn_save_final').on('click', function() {
        saveReport('final');
    });

    // ========================================
    // Save Report Function (submits to update endpoint)
    // ========================================
    function saveReport(saveType) {
        // Collect form data
        var formData = {
            id: $('#report_id').val(),
            id_spk_penawaran: $('#id_spk_penawaran').val(),
            company_name: $('#company_name').val(),
            project_name: $('#project_name').val(),
            visit_date: $('#visit_date').val(),
            start_time: $('#start_time').val(),
            finish_time: $('#finish_time').val(),
            consultant_id: $('#consultant_id').val(),
            consultant_name: $('#consultant_name_hidden').val(),
            save_type: saveType,
            activities: [],
            action_plans: [],
            improvements: []
        };

        // Collect selected SPK activities
        $('.spk-activity-checkbox:checked').each(function() {
            formData.activities.push({
                activity_source: 'spk',
                spk_activity_id: $(this).data('activity-id'),
                activity_name: $(this).data('activity-name'),
                group_key: 'spk_' + $(this).data('activity-id')
            });
        });

        // Collect custom activities
        $('.custom-activity-input').each(function() {
            var activityText = $.trim($(this).val());
            if (activityText !== '') {
                formData.activities.push({
                    activity_source: 'custom',
                    spk_activity_id: null,
                    activity_name: activityText,
                    group_key: 'custom_' + $(this).data('row-id')
                });
            }
        });

        // Client-side validation for finalize
        if (saveType === 'final') {
            var errors = [];

            if (!formData.start_time) {
                errors.push('Waktu mulai harus dicatat.');
            }
            if (!formData.finish_time) {
                errors.push('Waktu selesai harus dicatat.');
            }
            if (formData.activities.length === 0) {
                errors.push('Minimal satu kegiatan harus dipilih atau ditambahkan.');
            }

            if (errors.length > 0) {
                alert('Validasi gagal:\n\n' + errors.join('\n'));
                return;
            }
        }

        // Collect action plans
        if (typeof collectActionPlans === 'function') {
            formData.action_plans = collectActionPlans();
        }

        // Collect improvements
        if (typeof collectImprovements === 'function') {
            formData.improvements = collectImprovements();
        }

        // Additional validation for finalize: check action plan and improvement errors
        if (saveType === 'final') {
            var additionalErrors = [];

            if (formData.action_plans && formData.action_plans._hasErrors) {
                additionalErrors.push('Terdapat action plan yang belum lengkap. Periksa field yang ditandai merah.');
            }

            if (formData.improvements && formData.improvements._hasErrors) {
                additionalErrors.push('Terdapat improvement yang belum lengkap. Periksa field yang ditandai merah.');
            }

            // Check that each selected activity has at least one action plan
            var activitiesWithoutPlans = [];
            $('.action-plan-activity-group').each(function() {
                var groupKey = $(this).data('group-key');
                var planCount = $(this).find('.action-plan-row').length;
                if (planCount === 0) {
                    var activityTitle = $(this).find('.activity-title').text();
                    activitiesWithoutPlans.push(activityTitle);
                }
            });

            if (activitiesWithoutPlans.length > 0) {
                additionalErrors.push('Kegiatan berikut belum memiliki action plan: ' + activitiesWithoutPlans.join(', '));
            }

            if (additionalErrors.length > 0) {
                alert('Validasi gagal:\n\n' + additionalErrors.join('\n'));
                $('#btn_save_draft, #btn_save_final').prop('disabled', false);
                return;
            }
        }

        // Clean up internal flags before sending
        if (formData.action_plans) {
            delete formData.action_plans._hasErrors;
            for (var i = 0; i < formData.action_plans.length; i++) {
                delete formData.action_plans[i].has_error;
            }
        }
        if (formData.improvements) {
            delete formData.improvements._hasErrors;
            for (var j = 0; j < formData.improvements.length; j++) {
                delete formData.improvements[j].has_error;
            }
        }

        // Disable buttons during save
        $('#btn_save_draft, #btn_save_final').prop('disabled', true);

        $.ajax({
            url: siteurl + 'laporan_kunjungan/update',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.status == 1) {
                    alert(response.pesan);
                    if (saveType === 'final') {
                        // Finish → go to Report view
                        var idSpkForUrl = '<?= str_replace("/", "_SLASH_", $report["header"]["id_spk_penawaran"]) ?>';
                        window.location.href = siteurl + 'laporan_kunjungan/view/' + idSpkForUrl;
                    } else {
                        // Save Draft → stay on this edit page
                        window.location.href = siteurl + 'laporan_kunjungan/edit/<?= htmlspecialchars($report["header"]["id"]) ?>';
                    }
                } else {
                    // Show validation errors
                    if (response.errors) {
                        var errorMessages = [];
                        $.each(response.errors, function(field, message) {
                            errorMessages.push(message);
                        });
                        alert('Error:\n\n' + errorMessages.join('\n'));
                    } else {
                        alert(response.pesan || 'Terjadi kesalahan saat menyimpan.');
                    }
                    $('#btn_save_draft, #btn_save_final').prop('disabled', false);
                }
            },
            error: function() {
                alert('Terjadi kesalahan server. Silakan coba lagi.');
                $('#btn_save_draft, #btn_save_final').prop('disabled', false);
            }
        });
    }

    // ========================================
    // Utility Functions
    // ========================================
    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // ========================================
    // Action Plan Section Logic
    // ========================================
    var maxActionPlansPerActivity = 20;
    var actionPlanCounters = {}; // Track action plan count per activity group

    // When SPK activity checkbox is checked/unchecked, show/hide action plan group
    $(document).on('change', '.spk-activity-checkbox', function() {
        var activityId = $(this).data('activity-id');
        var activityName = $(this).data('activity-name');
        var groupKey = 'spk_' + activityId;

        if ($(this).is(':checked')) {
            addActionPlanGroup(groupKey, activityName);
        } else {
            removeActionPlanGroup(groupKey);
        }
        toggleNoActionPlansMsg();
    });

    // When custom activity input changes, manage action plan group
    $(document).on('blur', '.custom-activity-input', function() {
        var rowId = $(this).data('row-id');
        var activityText = $.trim($(this).val());
        var groupKey = 'custom_' + rowId;

        if (activityText !== '') {
            if ($('#action_plan_group_' + groupKey).length === 0) {
                addActionPlanGroup(groupKey, activityText);
            } else {
                // Update the title if text changed
                $('#action_plan_group_' + groupKey + ' h5 span.activity-title').text(activityText);
            }
        } else {
            removeActionPlanGroup(groupKey);
        }
        toggleNoActionPlansMsg();
    });

    function toggleNoActionPlansMsg() {
        if ($('.action-plan-activity-group').length > 0) {
            $('#no_action_plans_msg').hide();
        } else {
            $('#no_action_plans_msg').show();
        }
    }

    function addActionPlanGroup(groupKey, activityName) {
        if ($('#action_plan_group_' + groupKey).length > 0) return; // Already exists

        actionPlanCounters[groupKey] = 0;

        var groupHtml = '<div class="action-plan-activity-group" id="action_plan_group_' + groupKey + '" data-group-key="' + groupKey + '">';
        groupHtml += '<h5><i class="fa fa-caret-right"></i> <span class="activity-title">' + escapeHtml(activityName) + '</span></h5>';
        groupHtml += '<div class="action-plan-rows" id="action_plan_rows_' + groupKey + '">';
        groupHtml += '</div>';
        groupHtml += '<button type="button" class="btn btn-success btn-xs btn-add-action-plan" data-group-key="' + groupKey + '" style="margin-top: 5px;">';
        groupHtml += '<i class="fa fa-plus"></i> Tambah Action Plan';
        groupHtml += '</button>';
        groupHtml += '<small class="text-muted" style="margin-left: 10px;">Maks 20 per kegiatan</small>';
        groupHtml += '</div>';

        $('#action_plans_container').append(groupHtml);
        toggleNoActionPlansMsg();
    }

    function removeActionPlanGroup(groupKey) {
        $('#action_plan_group_' + groupKey).remove();
        delete actionPlanCounters[groupKey];
        toggleNoActionPlansMsg();
    }

    // Add action plan row (with optional pre-populated values)
    function addActionPlanRow(groupKey, description, pic, dueDate, status) {
        var currentCount = $('#action_plan_rows_' + groupKey + ' .action-plan-row').length;

        if (currentCount >= maxActionPlansPerActivity) {
            return;
        }

        actionPlanCounters[groupKey] = (actionPlanCounters[groupKey] || 0) + 1;
        var rowIndex = actionPlanCounters[groupKey];
        var todayDate = '<?= date('Y-m-d') ?>';

        description = description || '';
        pic = pic || '';
        dueDate = dueDate || '';
        status = status || 'progress';

        var rowHtml = '<div class="action-plan-row" id="ap_row_' + groupKey + '_' + rowIndex + '" data-group-key="' + groupKey + '" data-row-index="' + rowIndex + '">';
        rowHtml += '<div class="row">';
        rowHtml += '<div class="col-md-4">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>Deskripsi <span class="text-danger">*</span></label>';
        rowHtml += '<textarea class="form-control ap-description" maxlength="500" rows="2" placeholder="Deskripsi action plan (maks 500 karakter)">' + escapeHtml(description) + '</textarea>';
        rowHtml += '<span class="error-message ap-description-error"></span>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-2">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>PIC <span class="text-danger">*</span></label>';
        rowHtml += '<input type="text" class="form-control ap-pic" maxlength="100" placeholder="Person in charge" value="' + escapeHtml(pic) + '">';
        rowHtml += '<span class="error-message ap-pic-error"></span>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-2">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>Due Date <span class="text-danger">*</span></label>';
        rowHtml += '<input type="date" class="form-control ap-due-date" value="' + escapeHtml(dueDate) + '">';
        rowHtml += '<span class="error-message ap-due-date-error"></span>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-2">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>Status</label>';
        rowHtml += '<select class="form-control ap-status" data-previous-value="' + escapeHtml(status) + '">';
        rowHtml += '<option value="progress"' + (status === 'progress' ? ' selected' : '') + '>Progress</option>';
        rowHtml += '<option value="done"' + (status === 'done' ? ' selected' : '') + '>Done</option>';
        rowHtml += '</select>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-2">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>&nbsp;</label><br>';
        rowHtml += '<button type="button" class="btn btn-danger btn-xs btn-remove-action-plan" data-group-key="' + groupKey + '" data-row-index="' + rowIndex + '">';
        rowHtml += '<i class="fa fa-trash"></i> Hapus';
        rowHtml += '</button>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '</div>';

        $('#action_plan_rows_' + groupKey).append(rowHtml);
    }

    // Click handler for add action plan button
    $(document).on('click', '.btn-add-action-plan', function() {
        var groupKey = $(this).data('group-key');
        var currentCount = $('#action_plan_rows_' + groupKey + ' .action-plan-row').length;

        if (currentCount >= maxActionPlansPerActivity) {
            alert('Maksimal ' + maxActionPlansPerActivity + ' action plan per kegiatan.');
            return;
        }

        addActionPlanRow(groupKey, '', '', '', 'progress');
    });

    // Remove action plan row
    $(document).on('click', '.btn-remove-action-plan', function() {
        var groupKey = $(this).data('group-key');
        var rowIndex = $(this).data('row-index');
        $('#ap_row_' + groupKey + '_' + rowIndex).remove();
    });

    // Prevent status revert from done to progress
    $(document).on('change', '.ap-status', function() {
        var $select = $(this);
        var previousVal = $select.data('previous-value') || 'progress';
        if (previousVal === 'done' && $select.val() === 'progress') {
            alert('Status tidak dapat dikembalikan dari "Done" ke "Progress".');
            $select.val('done');
            return;
        }
        $select.data('previous-value', $select.val());
    });

    // ========================================
    // Previous Action Plans (AJAX Load)
    // ========================================
    function loadPreviousActionPlans() {
        $.ajax({
            url: siteurl + 'laporan_kunjungan/get_previous_action_plans/' + idSpkPenawaran,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status == 1) {
                    renderPreviousActionPlans(response.action_plans || []);
                    renderPreviousImprovements(response.improvements || []);
                } else {
                    $('#previous_action_plans_container').html('<p class="no-data-message"><i class="fa fa-info-circle"></i> Tidak ada action plan sebelumnya untuk project ini.</p>');
                    $('#previous_improvements_container').html('<p class="no-data-message"><i class="fa fa-info-circle"></i> Tidak ada improvement sebelumnya untuk project ini.</p>');
                }
            },
            error: function() {
                $('#previous_action_plans_container').html('<p class="text-danger"><i class="fa fa-exclamation-circle"></i> Gagal memuat data action plan sebelumnya.</p>');
                $('#previous_improvements_container').html('<p class="text-danger"><i class="fa fa-exclamation-circle"></i> Gagal memuat data improvement sebelumnya.</p>');
            }
        });
    }

    function renderPreviousActionPlans(plans) {
        if (plans.length === 0) {
            $('#previous_action_plans_container').html('<p class="no-data-message"><i class="fa fa-info-circle"></i> Tidak ada action plan sebelumnya untuk project ini.</p>');
            return;
        }

        var html = '<table class="table table-bordered table-striped table-condensed">';
        html += '<thead><tr>';
        html += '<th>Date</th><th>Konsultan</th><th>Kegiatan</th><th>Action Plan</th><th>PIC</th><th>Due Date</th><th>Status</th>';
        html += '</tr></thead><tbody>';

        $.each(plans, function(i, plan) {
            html += '<tr id="prev_ap_row_' + plan.id + '">';
            html += '<td>' + escapeHtml(plan.visit_date || '') + '</td>';
            html += '<td>' + escapeHtml(plan.consultant_name || '') + '</td>';
            html += '<td>' + escapeHtml(plan.activity_name || '') + '</td>';
            html += '<td>' + escapeHtml(plan.description || '') + '</td>';
            html += '<td>' + escapeHtml(plan.pic || '') + '</td>';
            html += '<td>' + escapeHtml(plan.due_date || '') + '</td>';
            html += '<td><button type="button" class="btn-status btn-status-' + plan.status + ' btn-toggle-prev-ap" data-id="' + plan.id + '" data-current="' + plan.status + '">' + (plan.status.charAt(0).toUpperCase() + plan.status.slice(1)) + '</button></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $('#previous_action_plans_container').html(html);
    }

    function renderPreviousImprovements(improvements) {
        if (improvements.length === 0) {
            $('#previous_improvements_container').html('<p class="no-data-message"><i class="fa fa-info-circle"></i> Tidak ada improvement sebelumnya untuk project ini.</p>');
            return;
        }

        var html = '<table class="table table-bordered table-striped table-condensed">';
        html += '<thead><tr>';
        html += '<th>No</th><th>Date</th><th>Konsultan</th><th>Potensi Improvement</th><th>Hasil Improvement</th><th>Status</th>';
        html += '</tr></thead><tbody>';

        $.each(improvements, function(i, imp) {
            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + escapeHtml(imp.visit_date || '') + '</td>';
            html += '<td>' + escapeHtml(imp.consultant_name || '') + '</td>';
            html += '<td>' + escapeHtml(imp.potensi_improvement || '') + '</td>';
            html += '<td><input type="text" class="form-control input-sm imp-hasil-editable" data-id="' + imp.id + '" value="' + escapeHtml(imp.hasil_improvement || '') + '" placeholder="Hasil improvement..."></td>';
            html += '<td><button type="button" class="btn-status btn-status-' + imp.status + ' btn-toggle-prev-imp" data-id="' + imp.id + '" data-current="' + imp.status + '">' + (imp.status.charAt(0).toUpperCase() + imp.status.slice(1)) + '</button></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $('#previous_improvements_container').html(html);
    }

    // Toggle previous action plan status
    $(document).on('click', '.btn-toggle-prev-ap', function() {
        var btn = $(this);
        var planId = btn.data('id');
        var currentStatus = btn.data('current');
        var newStatus = (currentStatus === 'progress') ? 'done' : 'progress';
        btn.prop('disabled', true);
        $.ajax({
            url: siteurl + 'laporan_kunjungan/toggle_action_plan_status',
            type: 'POST', dataType: 'json',
            data: { id: planId, status: newStatus },
            success: function(response) {
                if (response.status == 1) {
                    btn.data('current', newStatus);
                    btn.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    btn.removeClass('btn-status-progress btn-status-done').addClass('btn-status-' + newStatus);
                } else { alert(response.pesan || 'Gagal.'); }
                btn.prop('disabled', false);
            },
            error: function() { alert('Gagal.'); btn.prop('disabled', false); }
        });
    });

    // Toggle previous improvement status
    $(document).on('click', '.btn-toggle-prev-imp', function() {
        var btn = $(this);
        var impId = btn.data('id');
        var currentStatus = btn.data('current');
        var newStatus = (currentStatus === 'progress') ? 'done' : 'progress';
        btn.prop('disabled', true);
        $.ajax({
            url: siteurl + 'laporan_kunjungan/toggle_improvement_status',
            type: 'POST', dataType: 'json',
            data: { id: impId, status: newStatus },
            success: function(response) {
                if (response.status == 1) {
                    btn.data('current', newStatus);
                    btn.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    btn.removeClass('btn-status-progress btn-status-done').addClass('btn-status-' + newStatus);
                } else { alert(response.pesan || 'Gagal.'); }
                btn.prop('disabled', false);
            },
            error: function() { alert('Gagal.'); btn.prop('disabled', false); }
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

    // ========================================
    // Potential Improvement Section Logic
    // ========================================
    var maxImprovements = 50;
    var improvementCounter = 0;

    // Add improvement row (with optional pre-populated values)
    function addImprovementRow(potensi, hasil, status) {
        var currentCount = $('#improvements_container .improvement-row').length;

        if (currentCount >= maxImprovements) {
            return;
        }

        improvementCounter++;
        var seqNo = currentCount + 1;

        potensi = potensi || '';
        hasil = hasil || '';
        status = status || 'progress';

        var rowHtml = '<div class="improvement-row" id="improvement_row_' + improvementCounter + '" data-row-id="' + improvementCounter + '">';
        rowHtml += '<div class="row">';
        rowHtml += '<div class="col-md-1">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>No</label>';
        rowHtml += '<input type="text" class="form-control improvement-seq-no" value="' + seqNo + '" readonly>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-4">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>Potensi Improvement <span class="text-danger">*</span></label>';
        rowHtml += '<textarea class="form-control imp-potensi" rows="2" placeholder="Potensi improvement...">' + escapeHtml(potensi) + '</textarea>';
        rowHtml += '<span class="error-message imp-potensi-error"></span>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-4">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>Hasil Improvement</label>';
        rowHtml += '<textarea class="form-control imp-hasil" rows="2" placeholder="Hasil improvement...">' + escapeHtml(hasil) + '</textarea>';
        rowHtml += '<span class="error-message imp-hasil-error"></span>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-2">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>Status</label>';
        rowHtml += '<select class="form-control imp-status" data-previous-value="' + escapeHtml(status) + '">';
        rowHtml += '<option value="progress"' + (status === 'progress' ? ' selected' : '') + '>Progress</option>';
        rowHtml += '<option value="done"' + (status === 'done' ? ' selected' : '') + '>Done</option>';
        rowHtml += '</select>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '<div class="col-md-1">';
        rowHtml += '<div class="form-group">';
        rowHtml += '<label>&nbsp;</label><br>';
        rowHtml += '<button type="button" class="btn btn-danger btn-xs btn-remove-improvement" data-row-id="' + improvementCounter + '">';
        rowHtml += '<i class="fa fa-trash"></i>';
        rowHtml += '</button>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '</div>';
        rowHtml += '</div>';

        $('#improvements_container').append(rowHtml);
    }

    // Click handler for add improvement button
    $('#btn_add_improvement').on('click', function() {
        var currentCount = $('#improvements_container .improvement-row').length;

        if (currentCount >= maxImprovements) {
            alert('Maksimal ' + maxImprovements + ' entri potensi improvement.');
            return;
        }

        addImprovementRow('', '', 'progress');
    });

    // Remove improvement row
    $(document).on('click', '.btn-remove-improvement', function() {
        var rowId = $(this).data('row-id');
        $('#improvement_row_' + rowId).remove();
        // Re-number remaining rows
        renumberImprovements();
    });

    // Prevent improvement status revert from done to progress
    $(document).on('change', '.imp-status', function() {
        var $select = $(this);
        var previousVal = $select.data('previous-value') || 'progress';
        if (previousVal === 'done' && $select.val() === 'progress') {
            alert('Status tidak dapat dikembalikan dari "Done" ke "Progress".');
            $select.val('done');
            return;
        }
        $select.data('previous-value', $select.val());
    });

    function renumberImprovements() {
        $('#improvements_container .improvement-row').each(function(index) {
            $(this).find('.improvement-seq-no').val(index + 1);
        });
    }

    // ========================================
    // Pre-populate Improvements from existing data
    // ========================================
    function prePopulateImprovements() {
        if (existingImprovements && existingImprovements.length > 0) {
            $.each(existingImprovements, function(index, imp) {
                addImprovementRow(
                    imp.potensi_improvement || '',
                    imp.hasil_improvement || '',
                    imp.status || 'progress'
                );
            });
        }
    }

    // ========================================
    // Pre-populate Action Plans from existing data
    // Called after activities are loaded and checkboxes are pre-checked
    // ========================================
    function prePopulateActionPlansFromExisting() {
        // Build a mapping from activity index to group_key
        // The existingActionPlans is keyed by activity index (from PHP)
        // We need to figure out which group_key corresponds to each activity index

        // The activities in the report are ordered: first SPK activities (in order of existingSpkActivityIds),
        // then custom activities (in order of existingCustomActivities)
        var activityIndexToGroupKey = {};
        var activityIndex = 0;

        // Map SPK activities
        $.each(existingSpkActivityIds, function(i, spkId) {
            activityIndexToGroupKey[activityIndex] = 'spk_' + spkId;
            activityIndex++;
        });

        // Map custom activities
        // Custom activities were pre-populated with customActivityCount starting from 1
        for (var c = 1; c <= existingCustomActivities.length; c++) {
            activityIndexToGroupKey[activityIndex] = 'custom_' + c;
            activityIndex++;
        }

        // Now iterate existingActionPlans and populate each group
        $.each(existingActionPlans, function(index, plans) {
            var groupKey = activityIndexToGroupKey[index];
            if (groupKey && plans && plans.length > 0) {
                $.each(plans, function(j, plan) {
                    addActionPlanRow(
                        groupKey,
                        plan.description || '',
                        plan.pic || '',
                        plan.due_date || '',
                        plan.status || 'progress'
                    );
                });
            }
        });
    }

    // ========================================
    // Collect Action Plans (called by saveReport)
    // ========================================
    window.collectActionPlans = function() {
        var actionPlans = [];
        var hasErrors = false;

        // Clear previous errors
        $('.action-plan-row .error-message').hide().text('');
        $('.action-plan-row .form-control').css('border-color', '');

        $('.action-plan-activity-group').each(function() {
            var groupKey = $(this).data('group-key');

            $(this).find('.action-plan-row').each(function() {
                var $row = $(this);
                var description = $.trim($row.find('.ap-description').val());
                var pic = $.trim($row.find('.ap-pic').val());
                var dueDate = $row.find('.ap-due-date').val();
                var status = $row.find('.ap-status').val();

                // Validate
                var rowHasError = false;

                if (!description) {
                    $row.find('.ap-description').css('border-color', '#dd4b39');
                    $row.find('.ap-description-error').text('Deskripsi wajib diisi.').show();
                    rowHasError = true;
                } else if (description.length > 500) {
                    $row.find('.ap-description').css('border-color', '#dd4b39');
                    $row.find('.ap-description-error').text('Maksimal 500 karakter.').show();
                    rowHasError = true;
                }

                if (!pic) {
                    $row.find('.ap-pic').css('border-color', '#dd4b39');
                    $row.find('.ap-pic-error').text('PIC wajib diisi.').show();
                    rowHasError = true;
                } else if (pic.length > 100) {
                    $row.find('.ap-pic').css('border-color', '#dd4b39');
                    $row.find('.ap-pic-error').text('Maksimal 100 karakter.').show();
                    rowHasError = true;
                }

                if (!dueDate) {
                    $row.find('.ap-due-date').css('border-color', '#dd4b39');
                    $row.find('.ap-due-date-error').text('Due date wajib diisi.').show();
                    rowHasError = true;
                }

                if (rowHasError) {
                    hasErrors = true;
                }

                actionPlans.push({
                    group_key: groupKey,
                    description: description,
                    pic: pic,
                    due_date: dueDate,
                    status: status,
                    has_error: rowHasError
                });
            });
        });

        // Attach error flag for the saveReport function to check
        actionPlans._hasErrors = hasErrors;
        return actionPlans;
    };

    // ========================================
    // Collect Improvements (called by saveReport)
    // ========================================
    window.collectImprovements = function() {
        var improvements = [];
        var hasErrors = false;

        // Clear previous errors
        $('.improvement-row .error-message').hide().text('');
        $('.improvement-row .form-control').css('border-color', '');

        $('#improvements_container .improvement-row').each(function(index) {
            var $row = $(this);
            var potensi = $.trim($row.find('.imp-potensi').val());
            var hasil = $.trim($row.find('.imp-hasil').val());
            var status = $row.find('.imp-status').val();

            var rowHasError = false;

            if (!potensi) {
                $row.find('.imp-potensi').css('border-color', '#dd4b39');
                $row.find('.imp-potensi-error').text('Potensi improvement wajib diisi.').show();
                rowHasError = true;
            }

            if (rowHasError) {
                hasErrors = true;
            }

            improvements.push({
                sort_order: index + 1,
                potensi_improvement: potensi,
                hasil_improvement: hasil,
                status: status,
                has_error: rowHasError
            });
        });

        // Attach error flag for the saveReport function to check
        improvements._hasErrors = hasErrors;
        return improvements;
    };
});
</script>

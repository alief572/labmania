<?php
$ENABLE_VIEW    = has_permission('Laporan_Kunjungan.View');
$ENABLE_ADD     = has_permission('Laporan_Kunjungan.Add');
$ENABLE_MANAGE  = has_permission('Laporan_Kunjungan.Manage');
$ENABLE_DELETE  = has_permission('Laporan_Kunjungan.Delete');
?>
<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box">
    <div class="box-header">
        <h3 class="box-title">Daftar Laporan Kunjungan</h3>
        <div class="pull-right">
            <a href="<?= base_url('laporan_kunjungan/index') ?>" class="btn btn-default btn-sm">
                <i class="fa fa-arrow-left"></i> Back to SPK List
            </a>
        </div>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <?php if (!empty($spk_info)) : ?>
        <div class="well well-sm">
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fa fa-building"></i> Perusahaan:</strong> <?= htmlspecialchars($spk_info->nm_customer) ?>
                </div>
                <div class="col-md-6">
                    <strong><i class="fa fa-briefcase"></i> Project:</strong> <?= htmlspecialchars($spk_info->nm_project) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Filter -->
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-3">
                <label for="status_filter">Filter Status:</label>
                <select id="status_filter" class="form-control input-sm">
                    <option value="all">All</option>
                    <option value="draft">Draft</option>
                    <option value="final">Final</option>
                </select>
            </div>
        </div>

        <!-- DataTable -->
        <div class="table-responsive">
            <table id="table_visit_reports" class="table table-bordered table-striped" width="100%">
                <thead class="bg-primary">
                    <tr>
                        <th class="text-center" width="5%">No</th>
                        <th class="text-center">Date</th>
                        <th class="text-center">Perusahaan</th>
                        <th class="text-center">Project</th>
                        <th class="text-center">Start Time</th>
                        <th class="text-center">Finish Time</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" width="15%">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <!-- /.box-body -->
</div>

<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>

<script type="text/javascript">
    var tableVisitReports;

    $(document).ready(function() {
        loadDataTable();

        // Reload table when status filter changes
        $('#status_filter').on('change', function() {
            tableVisitReports.ajax.reload();
        });
    });

    function loadDataTable() {
        tableVisitReports = $('#table_visit_reports').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            stateSave: true,
            destroy: true,
            searching: true,
            paging: true,
            pageLength: 10,
            lengthMenu: [[10, 20, 50, 100, 150], [10, 20, 50, 100, 150]],
            order: [[1, 'desc']],
            ajax: {
                url: siteurl + 'laporan_kunjungan/get_data_visit_reports',
                type: 'POST',
                data: function(d) {
                    d.status_filter = $('#status_filter').val();
                    <?php if (!empty($id_spk_penawaran)) : ?>
                    d.id_spk_penawaran = '<?= addslashes($id_spk_penawaran) ?>';
                    <?php endif; ?>
                }
            },
            columns: [
                { data: 'no', className: 'text-center', orderable: false },
                { data: 'visit_date', className: 'text-center' },
                { data: 'perusahaan' },
                { data: 'project' },
                { data: 'start_time', className: 'text-center' },
                { data: 'finish_time', className: 'text-center' },
                { data: 'status', className: 'text-center' },
                { data: 'action', className: 'text-center', orderable: false }
            ],
            columnDefs: [{
                targets: 'no-sort',
                orderable: false
            }]
        });
    }

    // Delete report handler
    $(document).on('click', '.btn-delete-report', function() {
        var btn = $(this);
        var reportId = btn.data('id');

        if (!confirm('Apakah Anda yakin ingin menghapus laporan ini?')) {
            return;
        }

        btn.prop('disabled', true);

        $.ajax({
            url: siteurl + 'laporan_kunjungan/delete/' + reportId,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status == 1) {
                    alert(response.pesan);
                    tableVisitReports.ajax.reload();
                } else {
                    alert(response.pesan || 'Gagal menghapus laporan.');
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Terjadi kesalahan server.');
                btn.prop('disabled', false);
            }
        });
    });
</script>

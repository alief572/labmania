<?php
$ENABLE_ADD     = has_permission('Monitoring_Expense_Report.Add');
$ENABLE_MANAGE  = has_permission('Monitoring_Expense_Report.Manage');
$ENABLE_VIEW    = has_permission('Monitoring_Expense_Report.View');
$ENABLE_DELETE  = has_permission('Monitoring_Expense_Report.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

<style>
    .btn {
        border-radius: 10px;
    }

    .dropdown-menu {
        top: 100%;
        position: absolute;
        overflow: auto;
    }
</style>
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<div class="box">
    <div class="box-header">

    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <table id="table_penawaran" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th align="center">No</th>
                    <th align="center">Tanggal Payment</th>
                    <th align="center">No. Payment</th>
                    <th align="center">Keperluan</th>
                    <th align="center">Nilai Pengajuan</th>
                    <th align="center">Nilai Expense</th>
                    <th align="center">PIC</th>
                    <th align="center">Status</th>
                    <th align="center">Action</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    <!-- /.box-body -->
</div>
<div id="form-data"></div>
<!-- DataTables -->
<!-- <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script> -->

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<!-- page script -->
<script type="text/javascript">
    $(document).ready(function() {
        DataTables();
    });



    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_expense',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                }
            },
            columns: [
                {
                    data: 'no'
                },
                {
                    data: 'tanggal_payment'
                },
                {
                    data: 'no_payment'
                },
                {
                    data: 'keperluan'
                },
                {
                    data: 'nilai_pengajuan'
                },
                {
                    data: 'nilai_expense'
                },
                {
                    data: 'pic'
                },
                {
                    data: 'status'
                },
                {
                    data: 'action'
                }
            ],
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: true,
            destroy: true,
            paging: true
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
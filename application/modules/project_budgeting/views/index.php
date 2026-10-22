<?php
$ENABLE_ADD     = has_permission('Project_Budgeting.Add');
$ENABLE_MANAGE  = has_permission('Project_Budgeting.Manage');
$ENABLE_VIEW    = has_permission('Project_Budgeting.View');
$ENABLE_DELETE  = has_permission('Project_Budgeting.Delete');
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
        <div class="col-md-4">
            <div class="form-group">
                <label for="">Status</label>
                <select class="form-control form-control-sm status" onchange="DataTables()">
                    <option value=""> Status </option>
                    <option value="1"> Draft </option>
                    <option value="2"> Approved </option>
                    <option value="3"> Rejected </option>
                    <option value="4"> Waiting Approval </option>
                </select>
            </div>
        </div>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <div class="table-responsive">
            <table id="table_penawaran" class="table table-bordered table-striped nowrap">
                <thead class="bg-primary">
                    <tr>
                        <th align="center">No</th>
                        <th align="center">Nomor SPK</th>
                        <th align="center">Customer</th>
                        <th align="center">Sales</th>
                        <th align="center">Project Leader</th>
                        <th align="center">Package</th>
                        <th align="center">Status</th>
                        <th align="center">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!-- /.box-body -->
</div>
<div id="form-data"></div>

<div class="modal" id="dialog-history" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">History Approval</h4>
            </div>
            <div class="modal-body" id="historyModalBody">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <span class="glyphicon glyphicon-remove"></span> Close</button>
            </div>
        </div>
    </div>
</div>
<!-- DataTables -->
<!-- <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script> -->

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<!-- page script -->
<script type="text/javascript">
    $(document).ready(function() {
        DataTables();
    });

    $(document).on('click', '.del_spk_budget', function() {
        var id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be deleted !',
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_spk_budgeting',
                    data: {
                        'id': id
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.pesan
                            }).then((lanjut) => {
                                DataTables();
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.pesan
                            });
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later!'
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn_history', function() {
        var id_spk_budgeting = $(this).data('id_spk_budgeting');

        $.ajax({
            type: 'get',
            url: siteurl + active_controller + 'history_approval',
            data: {
                'id_spk_budgeting': id_spk_budgeting
            },
            cache: false,
            dataType: 'JSON',
            success: function(result) {
                if (result.status == 1) {
                    $('#dialog-history').modal();
                    $('#historyModalBody').html(result.result);
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Failed !',
                        text: result.pesan
                    });
                }
            },
            error: function(result) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error !',
                    text: 'Please try again later!'
                });
            }
        });
    });

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var status = $('.status').val();

        var dataTables = $('#table_penawaran').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_spk',
                type: "POST",
                dataType: "JSON",
                data: function(d) {
                    d.status = status;
                }
            },
            columns: [{
                    data: 'no',
                }, {
                    data: 'id_spk_penawaran'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'nm_sales'
                },
                {
                    data: 'nm_project_leader'
                },
                {
                    data: 'nm_project',
                    render: function(data, type, row) {
                        if (type === 'display' && data && data.length > 40) {
                            return '<span title="' + data + '" style="cursor:help;">' + data.substring(0, 40) + '…</span>';
                        }
                        return data;
                    }
                },
                {
                    data: 'status'
                },
                {
                    data: 'option'
                }
            ],
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: true,
            destroy: true,
            paging: true,
            scrollX: true
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
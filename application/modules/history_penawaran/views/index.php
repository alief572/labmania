<?php
$ENABLE_ADD     = has_permission('Penawaran.Add');
$ENABLE_MANAGE  = has_permission('Penawaran.Manage');
$ENABLE_VIEW    = has_permission('Penawaran.View');
$ENABLE_DELETE  = has_permission('Penawaran.Delete');
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
        <div class="table-responsive">
            <table id="table_penawaran" class="table table-bordered table-striped nowrap">
                <thead class="bg-primary">
                    <tr>
                        <th align="center">No</th>
                        <th align="center">ID History</th>
                        <th align="center">ID Quotation</th>
                        <th align="center">Date</th>
                        <th align="center">Marketing</th>
                        <th align="center">Package</th>
                        <th align="center">Customer</th>
                        <th align="center">Grand Total</th>
                        <th align="center">Revisi</th>
                        <th align="center">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
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

    $(document).on('click', '.del_penawaran', function() {
        var id_penawaran = $(this).data('id_penawaran');

        swal({
            type: 'warning',
            title: 'Are you sure?',
            text: 'This data will be deleted !',
            showCancelButton: true
        }, function(next) {
            if (next) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_penawaran',
                    data: {
                        'id_penawaran': id_penawaran
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            swal({
                                type: 'success',
                                title: 'Success !',
                                text: result.msg
                            }, function(after) {
                                DataTables();
                            });
                        } else {
                            swal({
                                type: 'warning',
                                title: 'Failed !',
                                text: result.msg
                            });
                        }
                    },
                    error: function(result) {
                        swal({
                            type: 'error',
                            title: 'Error !',
                            text: 'Please try again later !'
                        });
                    }
                })
            }
        });
    });

    $(document).on('click', '.deal_penawaran', function() {
        var id_penawaran = $(this).data('id_penawaran');

        swal({
            type: 'warning',
            title: 'Warning !',
            text: 'Are you sure to deal this Quotation ?',
            showCancelButton: true
        }, function(next) {
            if (next) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'deal_penawaran',
                    data: {
                        'id_penawaran': id_penawaran
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            swal({
                                type: 'success',
                                title: 'Success !',
                                text: result.msg
                            }, function(after) {
                                DataTables();
                            });
                        } else {
                            swal({
                                type: 'warning',
                                title: 'Failed !',
                                text: result.msg
                            });
                        }
                    },
                    error: function(result) {
                        swal({
                            type: 'error',
                            title: 'Error !',
                            text: 'Please try again later !'
                        });
                    }
                });
            }
        });
    });

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran').dataTable({
            ajax: {
                url: siteurl + active_controller + 'get_data_penawaran',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                }
            },
            language: {
                loadingRecords: 'Please wait - Loading ...'
            },
            stateSave: false,
            autoWidth: true,
            columns: [{
                    data: 'no',
                },
                {
                    data: 'id_history'
                },
                {
                    data: 'id_quotation'
                },
                {
                    data: 'tgl_quotation'
                },
                {
                    data: 'nm_marketing'
                },
                {
                    data: 'nm_paket',
                    render: function(data, type, row) {
                        if (type === 'display' && data && data.length > 40) {
                            return '<span title="' + data + '" style="cursor:help;">' + data.substring(0, 40) + '…</span>';
                        }
                        return data;
                    }
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'grand_total'
                },
                {
                    data: 'revisi'
                },
                {
                    data: 'option'
                }
            ],
            responsive: true,
            processing: false,
            serverSide: true,
            destroy: true,
            paging: true,
            scrollX: true
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
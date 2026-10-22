<?php
$ENABLE_ADD     = has_permission('Penagihan.Add');
$ENABLE_MANAGE  = has_permission('Penagihan.Manage');
$ENABLE_VIEW    = has_permission('Penagihan.View');
$ENABLE_DELETE  = has_permission('Penagihan.Delete');
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

                </tr>
            </thead>

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

    $(document).on('click', '.del_spk', function() {
        var id_spk_penawaran = $(this).data('id_spk_penawaran');

        swal({
            type: 'warning',
            title: 'Are you sure?',
            text: 'This data will be deleted !',
            cancelShowButton: true
        }, function(next) {
            if (next) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_spk',
                    data: {
                        'id_spk_penawaran': id_spk_penawaran
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

    $(document).on('click', '#one_time', function() {
        swal({
            type: 'warning',
            title: 'Warning !',
            text: 'Are you sure ?',
            showCancelButton: true
        }, function(next) {
            if (next) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'one_time',
                    cache: false,
                    dataType: 'json',
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
                url: siteurl + active_controller + 'get_data_spk',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                }
            },
            columns: [{
                    data: 'no',
                }, {
                    data: 'id_spk_penawaran'
                },
                {
                    data: 'nm_marketing'
                },
                {
                    data: 'nm_paket'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'grand_total'
                },
                {
                    data: 'status'
                },
                {
                    data: 'status_spk'
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
            paging: true
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
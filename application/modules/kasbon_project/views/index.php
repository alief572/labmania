<?php
$ENABLE_ADD     = has_permission('Kasbon_Project.Add');
$ENABLE_MANAGE  = has_permission('Kasbon_Project.Manage');
$ENABLE_VIEW    = has_permission('Kasbon_Project.View');
$ENABLE_DELETE  = has_permission('Kasbon_Project.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css">

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
            <table id="table_penawaran" class="table table-bordered table-striped table-hover" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-center" width="5%">No</th>
                        <th class="text-center" width="28%">Nomor SPK & Paket</th>
                        <th class="text-center" width="22%">Customer</th>
                        <th class="text-center" width="20%">Team / PIC</th>
                        <th class="text-center" width="13%">Status</th>
                        <th class="text-center" width="12%">Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <!-- /.box-body -->
</div>
<div id="form-data"></div>
<!-- DataTables -->
<!-- <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script> -->

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((res) => {
            if (res.isConfirmed) {
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
        }).then(() => {
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

    $(document).on('click', '.req_approval', function(e) {
        e.preventDefault();

        var id_spk_budgeting = $(this).data('id_spk_budgeting');

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure to Request Approval this data?',
            text: 'This action cannot be undo !',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, request approval!',
            cancelButtonText: 'Cancel'
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'req_approval_kasbon',
                    data: {
                        'id_spk_budgeting': id_spk_budgeting
                    },
                    cache: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
            icon: 'success',
            title: 'Success !',
            text: result.pesan
        }).then(() => {
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

    function DataTables() {
        // var dataTables = $('#table_penawaran').dataTable();
        // dataTables.destroy();

        var dataTables = $('#table_penawaran').dataTable({
            processing: true,
            serverSide: true,
            stateSave: false,
            destroy: true,
            paging: true,
            ajax: {
                url: siteurl + active_controller + 'get_data_spk',
                type: "POST",
                dataType: "JSON",
                data: function(d) {

                }
            },
            columns: [{
                    data: 'no',
                    className: 'text-center'
                }, {
                    data: 'spk_paket'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'pic_team'
                },
                {
                    data: 'status',
                    className: 'text-center'
                },
                {
                    data: 'option',
                    className: 'text-center'
                }
            ],
            autoWidth: false
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>
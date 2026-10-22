<?php
$ENABLE_ADD     = has_permission('Approval_Kasbon_Project.Add');
$ENABLE_MANAGE  = has_permission('Approval_Kasbon_Project.Manage');
$ENABLE_VIEW    = has_permission('Approval_Kasbon_Project.View');
$ENABLE_DELETE  = has_permission('Approval_Kasbon_Project.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

<style>
    .btn {
        border-radius: 10px;
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
                        <th class="text-center" width="4%">No</th>
                        <th class="text-center" width="18%">Nomor SPK &amp; Paket</th>
                        <th class="text-center" width="13%">Customer</th>
                        <th class="text-center" width="13%">Team / PIC</th>
                        <th class="text-center" width="18%">Kasbon Info</th>
                        <th class="text-center" width="13%">Request By</th>
                        <th class="text-center" width="9%">Date</th>
                        <th class="text-center" width="6%">Status</th>
                        <th class="text-center" width="6%">Action</th>
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

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
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
            cancelButtonColor: '#d33'
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
                    className: 'text-center'
                },
                {
                    data: 'spk_paket'
                },
                {
                    data: 'nm_customer'
                },
                {
                    data: 'pic_team'
                },
                {
                    data: 'kasbon_info'
                },
                {
                    data: 'request_by'
                },
                {
                    data: 'date',
                    className: 'text-center'
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
            processing: true,
            serverSide: true,
            stateSave: true,
            destroy: true,
            paging: true,
            autoWidth: false
        });
    }
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>